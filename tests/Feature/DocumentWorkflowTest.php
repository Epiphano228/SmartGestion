<?php

namespace Tests\Feature;

use App\Enums\DocumentStatus;
use App\Enums\DocumentType;
use App\Models\Client;
use App\Models\Document;
use App\Models\User;
use App\Services\DocumentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DocumentWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_proforma_can_be_converted_only_once(): void
    {
        $user = User::factory()->create();
        $client = Client::create(['company_name' => 'Acme']);
        $proforma = Document::create([
            'type' => DocumentType::Proforma, 'number' => 'PRO-2026-00001',
            'client_id' => $client->id, 'created_by' => $user->id,
            'status' => DocumentStatus::Sent, 'issued_at' => now(), 'currency' => 'XOF',
            'subtotal' => 1000, 'tax_total' => 180, 'total' => 1180,
        ]);
        $proforma->items()->create([
            'description' => 'Conseil', 'quantity' => 1, 'unit_price' => 1000,
            'tax_rate' => 18, 'discount_rate' => 0, 'line_subtotal' => 1000,
            'line_tax' => 180, 'line_total' => 1180,
        ]);

        $this->actingAs($user);
        $service = app(DocumentService::class);
        $first = $service->convertToInvoice($proforma);
        $second = $service->convertToInvoice($proforma->refresh());

        $this->assertTrue($first->is($second));
        $this->assertDatabaseCount('documents', 2);
        $this->assertEquals(DocumentType::Invoice, $first->type);
        $this->assertCount(1, $first->items);
    }

    public function test_payment_status_tracks_partial_and_full_payment(): void
    {
        $client = Client::create(['company_name' => 'Acme']);
        $invoice = Document::create([
            'type' => DocumentType::Invoice, 'number' => 'FAC-2026-00001',
            'client_id' => $client->id, 'status' => DocumentStatus::Sent,
            'issued_at' => now(), 'currency' => 'XOF', 'total' => 1000,
        ]);
        $invoice->payments()->create(['paid_at' => now(), 'amount' => 400, 'method' => 'cash']);
        app(DocumentService::class)->syncPaymentStatus($invoice);
        $this->assertEquals(DocumentStatus::Partial, $invoice->refresh()->status);

        $invoice->payments()->create(['paid_at' => now(), 'amount' => 600, 'method' => 'cash']);
        app(DocumentService::class)->syncPaymentStatus($invoice);
        $this->assertEquals(DocumentStatus::Paid, $invoice->refresh()->status);
    }
}
