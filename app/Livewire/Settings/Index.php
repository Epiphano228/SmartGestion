<?php

namespace App\Livewire\Settings;

use App\Models\ActivityLog;
use App\Models\Setting;
use App\Models\User;
use App\Services\AvatarService;
use App\Services\LogoService;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithFileUploads;

#[Layout('components.layouts.app')]
class Index extends Component
{
    use WithFileUploads;

    #[Url(except: 'company')]
    public string $section = 'company';
    public array $form = [];
    public $logo;
    public bool $showUserForm = false;
    public ?int $editingUserId = null;
    public array $userForm = [];
    public $userAvatar;
    public ?string $currentUserAvatar = null;
    public bool $removeUserAvatar = false;

    public function mount(): void
    {
        abort_unless(auth()->user()->role === 'admin', 403);
        if (! in_array($this->section, ['company', 'billing', 'documents', 'users'])) $this->section = 'company';
        $defaults = [
            'company_name' => 'SmartGestion', 'company_description' => '', 'company_address' => '', 'company_phone' => '',
            'company_email' => '', 'company_tax_number' => '', 'currency' => 'XOF',
            'tax_enabled' => '1', 'tax_rate' => '18', 'invoice_prefix' => 'FAC',
            'proforma_prefix' => 'PRO', 'quotation_prefix' => 'DEV', 'number_digits' => '5', 'payment_terms_days' => '30',
            'document_header' => '', 'document_footer' => '', 'document_terms' => '', 'legal_notice' => '',
        ];
        $stored = Setting::whereIn('key', array_keys($defaults))->pluck('value', 'key');
        foreach ($defaults as $key => $default) $this->form[$key] = $stored->get($key, $default);
    }

    public function selectSection(string $section): void
    {
        if (in_array($section, ['company', 'billing', 'documents', 'users'])) $this->section = $section;
    }

    public function save(LogoService $logos): void
    {
        $data = $this->validate([
            'form.company_name' => ['required', 'max:255'],
            'form.company_description' => ['nullable', 'max:500'],
            'form.company_address' => ['nullable', 'max:1000'],
            'form.company_phone' => ['nullable', 'max:50'],
            'form.company_email' => ['nullable', 'email'],
            'form.company_tax_number' => ['nullable', 'max:100'],
            'form.currency' => ['required', 'max:10'],
            'form.tax_enabled' => ['required', 'in:0,1'],
            'form.tax_rate' => ['required', 'numeric', 'min:0', 'max:100'],
            'form.invoice_prefix' => ['required', 'alpha_dash', 'max:20'],
            'form.proforma_prefix' => ['required', 'alpha_dash', 'max:20'],
            'form.quotation_prefix' => ['required', 'alpha_dash', 'max:20'],
            'form.number_digits' => ['required', 'integer', 'between:3,10'],
            'form.payment_terms_days' => ['required', 'integer', 'between:0,365'],
            'form.document_header' => ['nullable', 'max:3000'],
            'form.document_footer' => ['nullable', 'max:3000'],
            'form.document_terms' => ['nullable', 'max:5000'],
            'form.legal_notice' => ['nullable', 'max:5000'],
            'logo' => ['nullable', 'image', 'max:2048'],
        ]);
        Setting::setMany($data['form']);
        if ($this->logo) {
            Setting::setValue('logo_path', $logos->storeUploaded($this->logo, Setting::getValue('logo_path')));
            $this->reset('logo');
        }
        ActivityLog::record('settings', 'Paramètres de l’entreprise mis à jour');
        $this->dispatch('notify', message: 'Paramètres enregistrés.');
    }

    public function createUser(): void
    {
        $this->resetValidation();
        $this->editingUserId = null;
        $this->reset(['userAvatar', 'currentUserAvatar', 'removeUserAvatar']);
        $this->userForm = ['name' => '', 'email' => '', 'role' => 'manager', 'is_active' => true, 'password' => ''];
        $this->showUserForm = true;
    }

    public function editUser(User $user): void
    {
        $this->resetValidation();
        $this->editingUserId = $user->id;
        $this->reset(['userAvatar', 'removeUserAvatar']);
        $this->currentUserAvatar = $user->avatar_url ? $user->avatar_path : null;
        $this->userForm = $user->only(['name', 'email', 'role', 'is_active']) + ['password' => ''];
        $this->showUserForm = true;
    }

    public function saveUser(AvatarService $avatars): void
    {
        $validated = $this->validate([
            'userForm.name' => ['required', 'max:255'],
            'userForm.email' => ['required', 'email', Rule::unique('users', 'email')->ignore($this->editingUserId)],
            'userForm.role' => ['required', 'in:admin,manager'],
            'userForm.is_active' => ['boolean'],
            'userForm.password' => [$this->editingUserId ? 'nullable' : 'required', 'min:8'],
            'userAvatar' => ['nullable', 'image', 'max:2048'],
        ]);
        $data = $validated['userForm'];

        if ($this->editingUserId === auth()->id() && (! $data['is_active'] || $data['role'] !== 'admin')) {
            $this->addError('userForm.role', 'Vous ne pouvez pas désactiver ni rétrograder votre propre compte administrateur.');
            return;
        }
        if ($data['password']) $data['password'] = Hash::make($data['password']); else unset($data['password']);
        $user = $this->editingUserId ? User::findOrFail($this->editingUserId) : new User();
        $user->forceFill($data)->save();

        if ($this->userAvatar) {
            $user->forceFill(['avatar_path' => $avatars->storeUploaded($this->userAvatar, $user->avatar_path)])->save();
        } elseif ($this->removeUserAvatar && $user->avatar_path) {
            $avatars->delete($user->avatar_path);
            $user->forceFill(['avatar_path' => null])->save();
        }

        ActivityLog::record('user', "Utilisateur enregistré : {$user->name}", $user);
        $this->showUserForm = false;
        $this->reset(['editingUserId', 'userForm', 'userAvatar', 'currentUserAvatar', 'removeUserAvatar']);
        $this->dispatch('notify', message: 'Utilisateur enregistré.');
    }

    public function render()
    {
        $logoPath = Setting::getValue('logo_path');

        return view('livewire.settings.index', [
            'users' => User::orderBy('name')->get(),
            'logoPath' => $logoPath && Storage::disk('public')->exists($logoPath) ? $logoPath : null,
        ])->title('Paramètres');
    }
}