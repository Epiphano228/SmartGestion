<?php

namespace App\Livewire\Documents;

use App\Enums\DocumentStatus;
use App\Enums\DocumentType;
use App\Models\ActivityLog;
use App\Models\Client;
use App\Models\Document;
use App\Models\Product;
use App\Models\Setting;
use App\Services\DocumentService;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.app')]
class Form extends Component
{
    public string $type;
    public ?Document $document = null;
    public string $client_id = '';
    public string $issued_at = '';
    public string $due_at = '';
    public string $status = 'draft';
    public string $currency = 'XOF';
    public string $notes = '';
    public string $terms = '';
    public array $items = [];

    public function mount(string $type, ?Document $document = null): void
    {
        abort_unless(DocumentType::tryFrom($type), 404);
        if ($document && $document->type->value !== $type) abort(404);

        $this->type = $type;
        $this->document = $document;
        $this->currency = Setting::getValue('currency', 'XOF');
        $this->issued_at = now()->format('Y-m-d');
        $this->due_at = now()->addDays((int) Setting::getValue('payment_terms_days', 30))->format('Y-m-d');
        $this->terms = Setting::getValue('document_terms', '');

        if ($document) {
            $document->loadMissing(['items.product', 'payments']);
            $this->client_id = (string) $document->client_id;
            $this->issued_at = $document->issued_at->format('Y-m-d');
            $this->due_at = $document->due_at?->format('Y-m-d') ?? '';
            $this->status = $document->status->value;
            $this->currency = $document->currency;
            $this->notes = $document->notes ?? '';
            $this->terms = $document->terms ?? '';
            $this->items = $document->items->map(fn ($item) => [
                'product_id' => $item->product_id ? (string) $item->product_id : '',
                'name' => $item->name ?: $item->product?->name ?: $item->description,
                'description' => ($item->name && str_starts_with((string) $item->description, $item->name.' — ')) ? substr($item->description, strlen($item->name) + 3) : ($item->description === $item->name ? '' : $item->description),
                'quantity' => (float) $item->quantity,
                'unit_price' => (float) $item->unit_price,
                'tax_rate' => (float) $item->tax_rate,
            ])->all();
        } else {
            $requestedClient = request()->integer('client');
            if ($requestedClient && Client::whereKey($requestedClient)->where('is_active', true)->exists()) $this->client_id = (string) $requestedClient;
            $this->addItem();
        }
    }

    public function addItem(): void
    {
        $tax = Setting::getValue('tax_enabled', '1') === '1' ? (float) Setting::getValue('tax_rate', 18) : 0;
        $this->items[] = ['product_id' => '', 'name' => '', 'description' => '', 'quantity' => 1, 'unit_price' => 0, 'tax_rate' => $tax];
    }

    public function removeItem(int $index): void
    {
        unset($this->items[$index]);
        $this->items = array_values($this->items);
        if ($this->items === []) $this->addItem();
    }

    public function selectProduct(int $index): void
    {
        $product = Product::find($this->items[$index]['product_id'] ?? null);
        if (! $product) return;
        $this->items[$index] = array_merge($this->items[$index], [
            'name' => $product->name,
            'description' => $product->description ?: '',
            'unit_price' => (float) $product->unit_price,
            'tax_rate' => (float) $product->tax_rate,
        ]);
    }

    public function save(DocumentService $service)
    {
        $allowedStatuses = array_map(fn ($status) => $status->value, $this->availableStatuses());
        $data = $this->validate([
            'client_id' => ['required', 'exists:clients,id'],
            'issued_at' => ['required', 'date'],
            'due_at' => ['nullable', 'date', 'after_or_equal:issued_at'],
            'status' => ['required', 'in:'.implode(',', $allowedStatuses)],
            'currency' => ['required', 'max:10'],
            'notes' => ['nullable', 'max:5000'],
            'terms' => ['nullable', 'max:5000'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['nullable', 'exists:products,id'],
            'items.*.name' => ['required', 'max:255'],
            'items.*.description' => ['nullable', 'max:500'],
            'items.*.quantity' => ['required', 'numeric', 'gt:0'],
            'items.*.unit_price' => ['required', 'numeric', 'min:0'],
            'items.*.tax_rate' => ['required', 'numeric', 'min:0', 'max:100'],
        ]);

        if ($this->type === 'invoice' && (! $this->document || (float) $this->document->paid_total <= 0)) {
            $dueIsPast = $data['due_at'] && \Carbon\Carbon::parse($data['due_at'])->isPast();
            if ($data['status'] === DocumentStatus::Overdue->value && ! $dueIsPast) $data['status'] = DocumentStatus::Sent->value;
            if ($data['status'] === DocumentStatus::Sent->value && $dueIsPast) $data['status'] = DocumentStatus::Overdue->value;
        }
        if ($this->document && (float) $this->document->paid_total > $this->totals['total']) {
            $this->addError('items', 'Le nouveau total ne peut pas être inférieur au montant déjà encaissé.');
            return;
        }

        $document = DB::transaction(function () use ($data, $service) {
            $type = DocumentType::from($this->type);
            $document = $this->document ?: new Document([
                'type' => $type,
                'number' => $service->nextNumber($type),
                'created_by' => auth()->id(),
            ]);
            $document->fill(collect($data)->except('items')->all())->save();
            $document->items()->delete();
            foreach ($data['items'] as $position => $item) {
                $subtotal = round((float) $item['quantity'] * (float) $item['unit_price'], 2);
                $tax = round($subtotal * ((float) $item['tax_rate'] / 100), 2);
                $document->items()->create(array_merge($item, [
                    'product_id' => $item['product_id'] ?: null,
                    'description' => filled($item['description']) ? $item['description'] : $item['name'],
                    'discount_rate' => 0,
                    'line_subtotal' => $subtotal,
                    'line_tax' => $tax,
                    'line_total' => $subtotal + $tax,
                    'position' => $position,
                ]));
            }
            $document = $service->recalculate($document->fresh());
            if ($document->type === DocumentType::Invoice && $document->payments()->exists()) $service->syncPaymentStatus($document);
            return $document->refresh();
        });

        ActivityLog::record($this->document ? 'updated' : 'created', ($this->document ? 'Document modifié : ' : 'Document créé : ').$document->number, $document);
        session()->flash('success', "{$document->number} enregistré.");
        return $this->redirectRoute('documents.index', ['type' => $this->type], navigate: true);
    }

    public function availableStatuses(): array
    {
        if (DocumentType::from($this->type)->isOffer()) return [DocumentStatus::Draft, DocumentStatus::Sent, DocumentStatus::Accepted, DocumentStatus::Rejected, DocumentStatus::Cancelled];
        if ($this->document?->payments()->exists()) return [$this->document->status];
        return [DocumentStatus::Draft, DocumentStatus::Sent, DocumentStatus::Overdue, DocumentStatus::Cancelled];
    }

    public function getTotalsProperty(): array
    {
        $subtotal = $tax = 0;
        foreach ($this->items as $item) {
            $line = (float) ($item['quantity'] ?? 0) * (float) ($item['unit_price'] ?? 0);
            $subtotal += $line;
            $tax += $line * ((float) ($item['tax_rate'] ?? 0) / 100);
        }
        return compact('subtotal', 'tax') + ['total' => $subtotal + $tax];
    }

    public function render()
    {
        return view('livewire.documents.form', [
            'clients' => Client::where('is_active', true)->orderBy('company_name')->get(['id', 'company_name', 'contact_name']),
            'products' => Product::where('is_active', true)->orderBy('name')->get(['id', 'name', 'description', 'unit_price', 'tax_rate']),
            'statuses' => $this->availableStatuses(),
            'statusLocked' => $this->type === 'invoice' && $this->document?->payments()->exists(),
            'documentType' => DocumentType::from($this->type),
        ])->title($this->document ? 'Modifier '.$this->document->number : DocumentType::from($this->type)->newLabel());
    }
}