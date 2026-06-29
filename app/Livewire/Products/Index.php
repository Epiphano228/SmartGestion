<?php

namespace App\Livewire\Products;

use App\Models\ActivityLog;
use App\Models\Category;
use App\Models\Product;
use App\Models\Setting;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('components.layouts.app')]
class Index extends Component
{
    use WithPagination;

    #[Url(as: 'q', except: '')]
    public string $search = '';
    #[Url(as: 'categorie', except: '')]
    public string $categoryFilter = '';
    public bool $showForm = false;
    public ?int $editingId = null;
    public array $form = [];

    public function updated($property): void
    {
        if (in_array($property, ['search', 'categoryFilter'])) $this->resetPage();
    }

    public function create(): void
    {
        $this->resetValidation();
        $this->editingId = null;
        $this->form = ['name' => '', 'sku' => '', 'description' => '', 'category_id' => '', 'unit_price' => 0, 'tax_rate' => Setting::getValue('tax_enabled', '1') === '1' ? Setting::getValue('tax_rate', 18) : 0, 'unit' => 'unité', 'track_stock' => false, 'stock_quantity' => 0, 'is_active' => true];
        $this->showForm = true;
    }

    public function edit(Product $product): void
    {
        $this->resetValidation();
        $this->editingId = $product->id;
        $this->form = $product->only(['name', 'sku', 'description', 'category_id', 'unit_price', 'tax_rate', 'unit', 'track_stock', 'stock_quantity', 'is_active']);
        $this->showForm = true;
    }

    public function save(): void
    {
        $data = $this->validate([
            'form.name' => ['required', 'max:255'],
            'form.sku' => ['nullable', 'max:100', Rule::unique('products', 'sku')->ignore($this->editingId)],
            'form.description' => ['nullable', 'max:2000'],
            'form.category_id' => ['nullable', 'exists:categories,id'],
            'form.unit_price' => ['required', 'numeric', 'min:0'],
            'form.tax_rate' => ['required', 'numeric', 'min:0', 'max:100'],
            'form.unit' => ['required', 'max:30'],
            'form.track_stock' => ['boolean'],
            'form.stock_quantity' => ['numeric', 'min:0'],
            'form.is_active' => ['boolean'],
        ])['form'];
        $data['category_id'] = $data['category_id'] ?: null;
        $product = Product::updateOrCreate(['id' => $this->editingId], $data);
        ActivityLog::record($this->editingId ? 'updated' : 'created', ($this->editingId ? 'Article modifié : ' : 'Article créé : ').$product->name, $product);
        $this->showForm = false;
        $this->reset(['editingId', 'form']);
        $this->dispatch('notify', message: 'Article enregistré.');
    }

    public function delete(Product $product): void
    {
        ActivityLog::record('deleted', "Article supprimé : {$product->name}", $product);
        $product->delete();
        $this->dispatch('notify', message: 'Article supprimé.');
    }

    public function resetFilters(): void
    {
        $this->reset(['search', 'categoryFilter']);
        $this->resetPage();
    }

    public function render()
    {
        $products = Product::with('category')
            ->when($this->search, fn ($query) => $query->where(fn ($query) => $query->where('name', 'like', "%{$this->search}%")->orWhere('sku', 'like', "%{$this->search}%")))
            ->when($this->categoryFilter, fn ($query) => $query->where('category_id', $this->categoryFilter))
            ->orderBy('name')->paginate(12);

        $metrics = Product::selectRaw(
            'SUM(CASE WHEN is_active THEN 1 ELSE 0 END) AS active,
             SUM(CASE WHEN track_stock AND stock_quantity <= 5 THEN 1 ELSE 0 END) AS low_stock',
        )->first();

        return view('livewire.products.index', [
            'products' => $products,
            'categories' => Category::orderBy('name')->get(),
            'currency' => Setting::getValue('currency', 'XOF'),
            'summary' => [
                'active' => (int) $metrics->active,
                'categories' => Category::count(),
                'lowStock' => (int) $metrics->low_stock,
            ],
            'hasFilters' => $this->search || $this->categoryFilter,
        ])->title('Produits et services');
    }
}