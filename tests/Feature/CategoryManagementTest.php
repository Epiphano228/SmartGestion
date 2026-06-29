<?php

namespace Tests\Feature;

use App\Livewire\Categories\Index;
use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class CategoryManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_categories_are_manageable_and_used_categories_are_protected(): void
    {
        $user = User::factory()->create(['role' => 'manager', 'is_active' => true]);
        $this->actingAs($user);

        $this->get('/categories')->assertOk();

        Livewire::test(Index::class)
            ->call('create')
            ->set('form.name', '  Services   premium  ')
            ->set('form.color', '#0891b2')
            ->call('save')
            ->assertHasNoErrors();

        $category = Category::where('name', 'Services premium')->firstOrFail();

        Livewire::test(Index::class)
            ->call('edit', $category)
            ->set('form.name', 'Services professionnels')
            ->call('save')
            ->assertHasNoErrors();

        $category->refresh();
        $this->assertSame('Services professionnels', $category->name);

        Product::create([
            'category_id' => $category->id,
            'name' => 'Conseil',
            'unit_price' => 1000,
            'tax_rate' => 18,
            'unit' => 'heure',
        ]);

        Livewire::test(Index::class)
            ->call('delete', $category)
            ->assertHasErrors('action');

        $this->assertDatabaseHas('categories', ['id' => $category->id]);
    }
}
