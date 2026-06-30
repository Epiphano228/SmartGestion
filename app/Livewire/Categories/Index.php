<?php

namespace App\Livewire\Categories;

use App\Models\ActivityLog;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Support\Str;
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

    public bool $showForm = false;
    public ?int $editingId = null;
    public array $form = [];

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function create(): void
    {
        $this->resetValidation();
        $this->editingId = null;
        $this->form = ['name' => '', 'color' => '#4f46e5'];
        $this->showForm = true;
    }

    public function edit(Category $category): void
    {
        $this->resetValidation();
        $this->editingId = $category->id;
        $this->form = $category->only(['name', 'color']);
        $this->showForm = true;
    }

    public function save(): void
    {
        $this->form['name'] = Str::squish($this->form['name'] ?? '');

        $data = $this->validate([
            'form.name' => ['required', 'max:255', Rule::unique('categories', 'name')->ignore($this->editingId)],
            'form.color' => ['required', 'regex:/^#[0-9A-Fa-f]{6}$/'],
        ], [
            'form.name.unique' => 'Une catégorie porte déjà ce nom.',
            'form.color.regex' => 'Choisissez une couleur valide.',
        ])['form'];

        if ($this->editingId) {
            $category = Category::findOrFail($this->editingId);
            $category->update($data);
        } else {
            $category = Category::create($data);
        }
        ActivityLog::record(
            $this->editingId ? 'updated' : 'created',
            ($this->editingId ? 'Catégorie modifiée : ' : 'Catégorie créée : ').$category->name,
            $category,
        );

        $this->showForm = false;
        $this->reset(['editingId', 'form']);
        $this->dispatch('notify', message: 'Catégorie enregistrée.');
    }

    public function delete(Category $category): void
    {
        if ($category->products()->exists()) {
            $this->addError('action', 'Cette catégorie contient des produits. Reclassez-les avant de la supprimer.');

            return;
        }

        ActivityLog::record('deleted', "Catégorie supprimée : {$category->name}", $category);
        $category->delete();
        $this->dispatch('notify', message: 'Catégorie supprimée.');
    }

    public function resetFilters(): void
    {
        $this->search = '';
        $this->resetPage();
    }

    public function render()
    {
        $categories = Category::query()
            ->withCount('products')
            ->when($this->search, fn ($query) => $query->where('name', 'like', "%{$this->search}%"))
            ->orderBy('name')
            ->paginate(12);

        $metrics = Category::selectRaw(
            'COUNT(*) AS total,
             SUM(CASE WHEN EXISTS (
                SELECT 1 FROM products WHERE products.category_id = categories.id
             ) THEN 1 ELSE 0 END) AS used_count',
        )->first();

        return view('livewire.categories.index', [
            'categories' => $categories,
            'summary' => [
                'total' => (int) $metrics->total,
                'used' => (int) $metrics->used_count,
                'unclassified' => Product::whereNull('category_id')->count(),
            ],
            'hasFilters' => filled($this->search),
        ])->title('Catégories');
    }
}
