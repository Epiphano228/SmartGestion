<?php

namespace App\Livewire\Payments;

use App\Enums\DocumentStatus;
use App\Models\ActivityLog;
use App\Models\Document;
use App\Models\Payment;
use App\Models\Setting;
use App\Services\DocumentService;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('components.layouts.app')]
class Index extends Component
{
    use WithPagination;

    public bool $showForm = false;
    public string $invoice_id = '';
    public string $paid_at = '';
    public string $amount = '';
    public string $method = 'bank_transfer';
    public string $reference = '';
    public string $notes = '';
    #[Url(as: 'q', except: '')]
    public string $search = '';
    #[Url(except: '')]
    public string $methodFilter = '';

    public function mount(): void
    {
        $this->paid_at = now()->format('Y-m-d');
        $requestedInvoice = request()->integer('invoice');
        if ($requestedInvoice && Document::invoices()->whereKey($requestedInvoice)->exists()) {
            $this->invoice_id = (string) $requestedInvoice;
            $this->selectInvoice();
            $this->showForm = true;
        }
    }

    public function updated($property): void
    {
        if (in_array($property, ['search', 'methodFilter'])) $this->resetPage();
    }

    public function openForm(): void
    {
        $this->resetValidation();
        $this->showForm = true;
    }

    public function selectInvoice(): void
    {
        $invoice = Document::invoices()->find($this->invoice_id);
        $this->amount = $invoice ? (string) $invoice->balance : '';
    }

    public function save(DocumentService $service): void
    {
        $data = $this->validate([
            'invoice_id' => ['required', 'exists:documents,id'],
            'paid_at' => ['required', 'date'],
            'amount' => ['required', 'numeric', 'gt:0'],
            'method' => ['required', 'in:cash,bank_transfer,card,check,mobile_money,other'],
            'reference' => ['nullable', 'max:255'],
            'notes' => ['nullable', 'max:2000'],
        ]);
        $invoice = Document::invoices()->issued()->findOrFail($data['invoice_id']);
        if ((float) $data['amount'] > $invoice->balance + 0.001) {
            $this->addError('amount', 'Le montant dépasse le solde restant de '.number_format($invoice->balance, 2, ',', ' ').' '.$invoice->currency.'.');
            return;
        }

        DB::transaction(function () use ($data, $invoice, $service) {
            $payment = $invoice->payments()->create(collect($data)->except('invoice_id')->all() + ['recorded_by' => auth()->id()]);
            $service->syncPaymentStatus($invoice);
            ActivityLog::record('payment', "Paiement de {$payment->amount} enregistré sur {$invoice->number}", $payment);
        });

        $this->reset(['showForm', 'invoice_id', 'amount', 'reference', 'notes']);
        $this->paid_at = now()->format('Y-m-d');
        $this->method = 'bank_transfer';
        $this->dispatch('notify', message: 'Paiement enregistré et solde actualisé.');
    }

    public function delete(Payment $payment, DocumentService $service): void
    {
        DB::transaction(function () use ($payment, $service) {
            $invoice = $payment->document;
            ActivityLog::record('deleted', "Paiement supprimé sur {$invoice->number}", $payment);
            $payment->delete();
            $service->syncPaymentStatus($invoice);
        });
        $this->dispatch('notify', message: 'Paiement supprimé et solde recalculé.');
    }

    public function resetFilters(): void
    {
        $this->reset(['search', 'methodFilter']);
        $this->resetPage();
    }

    public function render()
    {
        $currency = Setting::getValue('currency', 'XOF');
        $activeInvoices = Document::invoices()->issued();
        $paymentMetrics = Payment::selectRaw(
            'COALESCE(SUM(amount), 0) AS total,
             COALESCE(SUM(CASE WHEN paid_at BETWEEN ? AND ? THEN amount ELSE 0 END), 0) AS month',
            [now()->startOfMonth(), now()->endOfMonth()],
        )->first();

        return view('livewire.payments.index', [
            'payments' => Payment::with(['document.client', 'recorder'])
                ->when($this->search, fn ($query) => $query->where(fn ($query) => $query->where('reference', 'like', "%{$this->search}%")->orWhereHas('document', fn ($document) => $document->where('number', 'like', "%{$this->search}%")->orWhereHas('client', fn ($client) => $client->where('company_name', 'like', "%{$this->search}%")))))
                ->when($this->methodFilter, fn ($query) => $query->where('method', $this->methodFilter))
                ->latest('paid_at')->latest('id')->paginate(15),
            'invoices' => Document::with('client')->invoices()->receivable()->orderByDesc('issued_at')->get(),
            'summary' => [
                'total' => (float) $paymentMetrics->total,
                'month' => (float) $paymentMetrics->month,
                'outstanding' => (float) (clone $activeInvoices)->whereColumn('total', '>', 'paid_total')->selectRaw('COALESCE(SUM(total - paid_total), 0) as amount')->value('amount'),
            ],
            'currency' => $currency,
            'hasFilters' => $this->search || $this->methodFilter,
        ])->title('Paiements');
    }
}