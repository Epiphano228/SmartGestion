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

class QuotationWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_quotation_pages_are_available_and_a_quotation_converts_to_one_invoice(): void
    {
        $user = User::factory()->create();
        $client = Client::create(['company_name' => 'Client Devis']);
        $quotation = Document::create([
            'type' => DocumentType::Quotation,
            'number' => 'DEV-2026-00001',
            'client_id' => $client->id,
            'created_by' => $user->id,
            'status' => DocumentStatus::Sent,
            'issued_at' => now(),
            'due_at' => now()->addDays(15),
            'currency' => 'XOF',
            'subtotal' => 1000,
            'tax_total' => 180,
            'total' => 1180,
        ]);
        $quotation->items()->create([
            'name' => 'Transport',
            'description' => 'Livraison interurbaine',
            'quantity' => 1,
            'unit_price' => 1000,
            'tax_rate' => 18,
            'discount_rate' => 0,
            'line_subtotal' => 1000,
            'line_tax' => 180,
            'line_total' => 1180,
        ]);

        $this->actingAs($user)
            ->get('/documents/quotation')
            ->assertOk();
        $this->get('/documents/quotation/nouveau')->assertOk();

        $service = app(DocumentService::class);
        $invoice = $service->convertToInvoice($quotation);
        $sameInvoice = $service->convertToInvoice($quotation->refresh());

        $this->assertTrue($invoice->is($sameInvoice));
        $this->assertSame(DocumentType::Invoice, $invoice->type);
        $this->assertSame($quotation->id, $invoice->source_document_id);
        $this->assertSame('Transport', $invoice->items->first()->name);
    }
}
