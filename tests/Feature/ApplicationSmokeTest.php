<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApplicationSmokeTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_admin_can_open_every_main_module(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'is_active' => true]);

        foreach ([
            '/', '/clients', '/produits', '/categories', '/documents/proforma', '/documents/invoice',
            '/documents/proforma/nouveau', '/documents/invoice/nouveau',
            '/paiements', '/statistiques', '/parametres',
        ] as $uri) {
            $this->actingAs($admin)->get($uri)->assertOk();
        }
    }

    public function test_manager_cannot_open_administration_settings(): void
    {
        $manager = User::factory()->create(['role' => 'manager', 'is_active' => true]);

        $this->actingAs($manager)->get('/parametres')->assertForbidden();
    }
}
