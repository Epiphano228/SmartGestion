<?php

namespace Tests\Feature;

use App\Livewire\Products\Index;
use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ProductManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_manager_can_create_a_product_and_render_catalog_metrics(): void
    {
        $user = User::factory()->create(['role' => 'manager', 'is_active' => true]);
        $category = Category::create(['name' => 'Services']);

        Livewire::actingAs($user)
            ->test(Index::class)
            ->call('create')
            ->set('form.name', 'Conseil professionnel')
            ->set('form.sku', 'SRV-001')
            ->set('form.category_id', (string) $category->id)
            ->set('form.unit_price', 25000)
            ->set('form.tax_rate', 18)
            ->set('form.unit', 'heure')
            ->call('save')
            ->assertHasNoErrors()
            ->assertSet('showForm', false);

        $this->assertDatabaseHas('products', [
            'name' => 'Conseil professionnel',
            'category_id' => $category->id,
        ]);
        $this->assertTrue(Product::firstOrFail()->is_active);
    }
}
