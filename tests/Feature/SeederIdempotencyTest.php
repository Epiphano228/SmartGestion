<?php

namespace Tests\Feature;

use App\Models\Setting;
use App\Models\User;
use Database\Seeders\SmartGestionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SeederIdempotencyTest extends TestCase
{
    use RefreshDatabase;

    public function test_startup_seeding_preserves_existing_account_and_company_settings(): void
    {
        $this->seed(SmartGestionSeeder::class);

        $user = User::firstOrFail();
        $password = $user->password;
        Setting::setValue('company_name', 'Entreprise personnalisée');

        $this->seed(SmartGestionSeeder::class);

        $this->assertSame($password, $user->fresh()->password);
        $this->assertSame('Entreprise personnalisée', Setting::where('key', 'company_name')->value('value'));
    }
}
