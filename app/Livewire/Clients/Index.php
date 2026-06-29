<?php

namespace App\Livewire\Clients;

use App\Models\ActivityLog;
use App\Models\Client;
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
    #[Url(except: 'all')]
    public string $status = 'all';
    public bool $showForm = false;
    public ?int $editingId = null;
    public array $form = [];

    public function updated($property): void
    {
        if (in_array($property, ['search', 'status'])) $this->resetPage();
    }

    public function create(): void
    {
        $this->resetValidation();
        $this->editingId = null;
        $this->form = ['company_name' => '', 'contact_name' => '', 'email' => '', 'phone' => '', 'tax_number' => '', 'address' => '', 'city' => '', 'country' => '', 'notes' => '', 'is_active' => true];
        $this->showForm = true;
    }

    public function edit(Client $client): void
    {
        $this->resetValidation();
        $this->editingId = $client->id;
        $this->form = $client->only(['company_name', 'contact_name', 'email', 'phone', 'tax_number', 'address', 'city', 'country', 'notes', 'is_active']);
        $this->showForm = true;
    }

    public function save(): void
    {
        $data = $this->validate([
            'form.company_name' => ['required', 'max:255'],
            'form.contact_name' => ['nullable', 'max:255'],
            'form.email' => ['nullable', 'email', 'max:255'],
            'form.phone' => ['nullable', 'max:50'],
            'form.tax_number' => ['nullable', 'max:100'],
            'form.address' => ['nullable', 'max:255'],
            'form.city' => ['nullable', 'max:100'],
            'form.country' => ['nullable', 'max:100'],
            'form.notes' => ['nullable', 'max:2000'],
            'form.is_active' => ['boolean'],
        ])['form'];

        $client = Client::updateOrCreate(['id' => $this->editingId], $data);
        ActivityLog::record($this->editingId ? 'updated' : 'created', ($this->editingId ? 'Client modifié : ' : 'Client créé : ').$client->company_name, $client);
        $this->showForm = false;
        $this->reset(['editingId', 'form']);
        $this->dispatch('notify', message: 'Client enregistré.');
    }

    public function delete(Client $client): void
    {
        if ($client->documents()->exists()) {
            $this->addError('action', 'Ce client possède des documents. Désactivez-le plutôt que de le supprimer.');
            return;
        }
        ActivityLog::record('deleted', "Client supprimé : {$client->company_name}", $client);
        $client->delete();
        $this->dispatch('notify', message: 'Client supprimé.');
    }

    public function resetFilters(): void
    {
        $this->reset(['search', 'status']);
        $this->status = 'all';
        $this->resetPage();
    }

    public function render()
    {
        $clients = Client::withCount('documents')
            ->when($this->search, fn ($query) => $query->where(function ($query) {
                $query->where('company_name', 'like', "%{$this->search}%")
                    ->orWhere('contact_name', 'like', "%{$this->search}%")
                    ->orWhere('email', 'like', "%{$this->search}%")
                    ->orWhere('phone', 'like', "%{$this->search}%");
            }))
            ->when($this->status !== 'all', fn ($query) => $query->where('is_active', $this->status === 'active'))
            ->orderBy('company_name')->paginate(12);

        $metrics = Client::selectRaw(
            'COUNT(*) AS total,
             SUM(CASE WHEN is_active THEN 1 ELSE 0 END) AS active,
             SUM(CASE WHEN created_at >= ? THEN 1 ELSE 0 END) AS new_count',
            [now()->startOfMonth()],
        )->first();

        return view('livewire.clients.index', [
            'clients' => $clients,
            'summary' => [
                'total' => (int) $metrics->total,
                'active' => (int) $metrics->active,
                'new' => (int) $metrics->new_count,
            ],
            'hasFilters' => $this->search || $this->status !== 'all',
        ])->title('Clients');
    }
}