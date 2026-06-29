<?php

namespace Tests\Feature;

use App\Enums\DocumentStatus;
use App\Enums\DocumentType;
use App\Models\Client;
use App\Models\Document;
use App\Services\DocumentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CommercialConsistencyTest extends TestCase
{
    use RefreshDatabase;

    public function test_drafts_and_cancelled_invoices_are_excluded_from_issued_turnover(): void
    {
        $client = Client::create(['company_name' => 'Acme']);

        foreach ([
            ['FAC-DRAFT', DocumentStatus::Draft, 500],
            ['FAC-SENT', DocumentStatus::Sent, 1000],
            ['FAC-CANCELLED', DocumentStatus::Cancelled, 2500],
        ] as [$number, $status, $total]) {
            Document::create([
                'type' => DocumentType::Invoice,
                'number' => $number,
                'client_id' => $client->id,
                'status' => $status,
                'issued_at' => now(),
                'currency' => 'XOF',
                'total' => $total,
            ]);
        }

        $this->assertSame(1000.0, (float) Document::invoices()->issued()->sum('total'));
        $this->assertSame(1, Document::invoices()->receivable()->count());
    }

    public function test_an_overdue_partial_payment_keeps_the_invoice_overdue(): void
    {
        $client = Client::create(['company_name' => 'Acme']);
        $invoice = Document::create([
            'type' => DocumentType::Invoice,
            'number' => 'FAC-OVERDUE',
            'client_id' => $client->id,
            'status' => DocumentStatus::Sent,
            'issued_at' => now()->subDays(10),
            'due_at' => now()->subDay(),
            'currency' => 'XOF',
            'total' => 1000,
        ]);
        $invoice->payments()->create(['paid_at' => now(), 'amount' => 400, 'method' => 'cash']);

        app(DocumentService::class)->syncPaymentStatus($invoice);

        $this->assertSame(DocumentStatus::Overdue, $invoice->refresh()->status);
        $this->assertSame(600.0, $invoice->balance);
    }
}
