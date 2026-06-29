<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Database\Seeder;

class SmartGestionSeeder extends Seeder
{
    public function run(): void
    {
        User::updateOrCreate(
            ['email' => env('ADMIN_EMAIL', 'admin@smartgestion.local')],
            ['name' => env('ADMIN_NAME', 'Administrateur'), 'password' => env('ADMIN_PASSWORD', 'ChangeMe123!'), 'role' => 'admin', 'is_active' => true]
        );

        foreach (['Services', 'Produits', 'Abonnements'] as $name) {
            Category::firstOrCreate(['name' => $name]);
        }

        $settings = [
            'company_name' => 'SmartGestion',
            'currency' => 'XOF',
            'tax_enabled' => '1',
            'tax_rate' => '18',
            'invoice_prefix' => 'FAC',
            'proforma_prefix' => 'PRO',
            'quotation_prefix' => 'DEV',
            'number_digits' => '5',
            'payment_terms_days' => '30',
            'document_terms' => 'Paiement à effectuer avant la date d’échéance indiquée.',
        ];
        foreach ($settings as $key => $value) Setting::setValue($key, $value);
    }
}
