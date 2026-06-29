<?php

namespace Tests\Feature;

use App\Enums\DocumentStatus;
use App\Enums\DocumentType;
use App\Models\Client;
use App\Models\Document;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PdfDownloadTest extends TestCase
{
    use RefreshDatabase;

    public function test_pdf_can_be_downloaded_even_when_the_configured_logo_is_missing(): void
    {
        $user = User::factory()->create();
        $client = Client::create(['company_name' => 'Client Démo']);
        $document = Document::create([
            'type' => DocumentType::Invoice,
            'number' => 'FAC-TEST-001',
            'client_id' => $client->id,
            'status' => DocumentStatus::Sent,
            'issued_at' => now(),
            'currency' => 'XOF',
            'subtotal' => 1000,
            'tax_total' => 180,
            'total' => 1180,
        ]);
        $document->items()->create([
            'name' => 'Conseil',
            'description' => 'Accompagnement mensuel',
            'quantity' => 1,
            'unit_price' => 1000,
            'tax_rate' => 18,
            'discount_rate' => 0,
            'line_subtotal' => 1000,
            'line_tax' => 180,
            'line_total' => 1180,
        ]);
        Setting::setValue('logo_path', 'branding/logo-introuvable.webp');

        $response = $this->actingAs($user)->get(route('documents.pdf', $document).'?download=1');

        $response->assertOk();
        $response->assertHeader('content-type', 'application/pdf');
        $this->assertStringContainsString('attachment;', $response->headers->get('content-disposition'));
        $this->assertStringStartsWith('%PDF', $response->getContent());
    }
}
