<?php

namespace App\Livewire\Documents;

use App\Enums\DocumentStatus;
use App\Enums\DocumentType;
use App\Mail\DocumentMail;
use App\Models\ActivityLog;
use App\Models\Document;
use App\Models\Setting;
use App\Services\DocumentService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('components.layouts.app')]
class Index extends Component
{
    use WithPagination;

    public string $type = 'invoice';
    #[Url(as: 'q', except: '')]
    public string $search = '';
    #[Url(except: '')]
    public string $status = '';
    #[Url(as: 'du', except: '')]
    public string $dateFrom = '';
    #[Url(as: 'au', except: '')]
    public string $dateTo = '';

    public function mount(string $type): void
    {
        abort_unless(DocumentType::tryFrom($type), 404);
        $this->type = $type;
    }

    public function updated($property): void
    {
        if (in_array($property, ['search', 'status', 'dateFrom', 'dateTo'])) $this->resetPage();
    }

    public function resetFilters(): void
    {
        $this->reset(['search', 'status', 'dateFrom', 'dateTo']);
        $this->resetPage();
    }

    public function convert(Document $document, DocumentService $service)
    {
        $invoice = $service->convertToInvoice($document);
        session()->flash('success', "Facture {$invoice->number} créée depuis {$document->number}.");
        return $this->redirectRoute('documents.edit', ['type' => 'invoice', 'document' => $invoice], navigate: true);
    }

    public function duplicate(Document $document, DocumentService $service)
    {
        $copy = DB::transaction(function () use ($document, $service) {
            $copy = $document->replicate(['number', 'status', 'sent_at', 'converted_at']);
            $copy->fill([
                'number' => $service->nextNumber($document->type),
                'status' => DocumentStatus::Draft,
                'issued_at' => now(),
                'due_at' => now()->addDays((int) Setting::getValue('payment_terms_days', 30)),
                'created_by' => auth()->id(),
                'source_document_id' => null,
                'paid_total' => 0,
            ])->save();
            foreach ($document->items as $item) {
                $copy->items()->create($item->only(['product_id', 'name', 'description', 'quantity', 'unit_price', 'tax_rate', 'line_subtotal', 'line_tax', 'line_total', 'position']));
            }
            return $copy;
        });
        ActivityLog::record('duplicated', "{$document->number} dupliqué en {$copy->number}", $copy);
        return $this->redirectRoute('documents.edit', ['type' => $this->type, 'document' => $copy], navigate: true);
    }

    public function sendEmail(Document $document): void
    {
        if (! $document->client->email) {
            $this->addError('action', 'Ajoutez une adresse email au client avant l’envoi.');
            return;
        }

        try {
            Mail::to($document->client->email)->send(new DocumentMail($document));
            $document->update(['sent_at' => now(), 'status' => $document->status === DocumentStatus::Draft ? DocumentStatus::Sent : $document->status]);
            ActivityLog::record('emailed', "{$document->number} envoyé à {$document->client->email}", $document);
            $this->dispatch('notify', message: 'Document envoyé par email.');
        } catch (\Throwable $exception) {
            report($exception);
            $this->addError('action', 'L’envoi a échoué. Vérifiez la configuration email dans votre environnement.');
        }
    }

    public function delete(Document $document): void
    {
        if ($document->payments()->exists()) {
            $this->addError('action', 'Cette facture possède des paiements et ne peut pas être supprimée.');
            return;
        }
        ActivityLog::record('deleted', "Document supprimé : {$document->number}", $document);
        $document->delete();
        $this->dispatch('notify', message: 'Document supprimé.');
    }

    public function render()
    {
        if ($this->type === 'invoice') {
            Document::invoices()
                ->whereIn('status', [DocumentStatus::Sent->value, DocumentStatus::Partial->value])
                ->whereDate('due_at', '<', today())
                ->whereColumn('paid_total', '<', 'total')
                ->update(['status' => DocumentStatus::Overdue->value]);
        }

        $base = Document::query()->where('type', $this->type);
        $documents = (clone $base)->with(['client', 'source'])
            ->when($this->search, fn ($query) => $query->where(fn ($query) => $query
                ->where('number', 'like', "%{$this->search}%")
                ->orWhereHas('client', fn ($client) => $client->where('company_name', 'like', "%{$this->search}%"))))
            ->when($this->status, fn ($query) => $query->where('status', $this->status))
            ->when($this->dateFrom, fn ($query) => $query->whereDate('issued_at', '>=', $this->dateFrom))
            ->when($this->dateTo, fn ($query) => $query->whereDate('issued_at', '<=', $this->dateTo))
            ->latest('issued_at')->latest('id')->paginate(15);

        if ($this->type === 'invoice') {
            $metrics = (clone $base)->selectRaw(
                'COALESCE(SUM(CASE WHEN status NOT IN (?, ?) THEN total ELSE 0 END), 0) AS total,
                 COALESCE(SUM(CASE WHEN status NOT IN (?, ?) AND paid_total < total THEN total - paid_total ELSE 0 END), 0) AS outstanding,
                 SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) AS attention',
                [
                    DocumentStatus::Draft->value,
                    DocumentStatus::Cancelled->value,
                    DocumentStatus::Draft->value,
                    DocumentStatus::Cancelled->value,
                    DocumentStatus::Overdue->value,
                ],
            )->first();
        } else {
            $metrics = (clone $base)->selectRaw(
                'COALESCE(SUM(CASE WHEN status NOT IN (?, ?) THEN total ELSE 0 END), 0) AS total,
                 SUM(CASE WHEN status IN (?, ?) THEN 1 ELSE 0 END) AS outstanding,
                 SUM(CASE WHEN converted_at IS NOT NULL THEN 1 ELSE 0 END) AS attention',
                [
                    DocumentStatus::Draft->value,
                    DocumentStatus::Cancelled->value,
                    DocumentStatus::Draft->value,
                    DocumentStatus::Sent->value,
                ],
            )->first();
        }

        $summary = [
            'total' => (float) $metrics->total,
            'outstanding' => $this->type === 'invoice' ? (float) $metrics->outstanding : (int) $metrics->outstanding,
            'attention' => (int) $metrics->attention,
        ];

        $statuses = $this->type === 'invoice'
            ? [DocumentStatus::Draft, DocumentStatus::Sent, DocumentStatus::Partial, DocumentStatus::Paid, DocumentStatus::Overdue, DocumentStatus::Cancelled]
            : [DocumentStatus::Draft, DocumentStatus::Sent, DocumentStatus::Accepted, DocumentStatus::Rejected, DocumentStatus::Cancelled];

        return view('livewire.documents.index', [
            'documents' => $documents,
            'statuses' => $statuses,
            'summary' => $summary,
            'currency' => Setting::getValue('currency', 'XOF'),
            'hasFilters' => $this->search || $this->status || $this->dateFrom || $this->dateTo,
            'typeEnum' => DocumentType::from($this->type),
        ])->title(DocumentType::from($this->type)->pluralLabel());
    }
}