<?php

namespace Tests\Feature;

use App\Livewire\Clients\Index;
use App\Models\Client;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ClientManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_manager_can_create_and_update_a_client(): void
    {
        $user = User::factory()->create(['role' => 'manager', 'is_active' => true]);

        Livewire::actingAs($user)
            ->test(Index::class)
            ->call('create')
            ->set('form.company_name', 'Entreprise Démo')
            ->set('form.contact_name', 'Client Test')
            ->set('form.email', 'client@example.com')
            ->set('form.phone', '+229 01 02 03 04')
            ->set('form.tax_number', 'NIF-123')
            ->call('save')
            ->assertHasNoErrors()
            ->assertSet('showForm', false);

        $client = Client::where('email', 'client@example.com')->firstOrFail();

        Livewire::actingAs($user)
            ->test(Index::class)
            ->call('edit', $client)
            ->set('form.company_name', 'Entreprise Démo SARL')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertSame('Entreprise Démo SARL', $client->fresh()->company_name);
    }
}
