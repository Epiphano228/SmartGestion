<div class="space-y-6">
    <x-page-header eyebrow="Répertoire commercial" title="Clients" description="Centralisez les contacts, retrouvez leur activité et démarrez rapidement un nouveau document.">
        <x-slot:actions><button wire:click="create" class="btn-primary"><x-icon name="plus" :size="16"/>Nouveau client</button></x-slot:actions>
    </x-page-header>

    @error('action')<div class="flex items-start gap-3 rounded-xl border border-amber-200 bg-amber-50 p-4 text-sm font-semibold text-amber-800"><x-icon name="alert" :size="18" class="mt-0.5 shrink-0"/>{{ $message }}</div>@enderror

    <section class="grid grid-cols-3 gap-3">
        @foreach([['Total clients',$summary['total'],'users','bg-indigo-50 text-indigo-600'],['Clients actifs',$summary['active'],'check','bg-emerald-50 text-emerald-600'],['Nouveaux ce mois',$summary['new'],'chart','bg-cyan-50 text-cyan-600']] as [$label,$value,$icon,$color])
            <article class="stat-card"><div class="flex items-start justify-between gap-2"><p class="text-[9px] font-extrabold uppercase tracking-wider text-slate-400 sm:text-xs">{{ $label }}</p><span class="hidden size-8 place-items-center rounded-xl sm:grid {{ $color }}"><x-icon :name="$icon" :size="16"/></span></div><p class="mt-3 text-xl font-black text-slate-950">{{ $value }}</p></article>
        @endforeach
    </section>

    <section class="panel flex flex-col gap-3 p-3 sm:flex-row sm:p-4">
        <div class="relative min-w-0 flex-1"><x-icon name="search" :size="17" class="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-slate-400"/><input wire:model.live.debounce.300ms="search" placeholder="Nom, contact, email ou téléphone…" class="field pl-10"></div>
        <div class="flex gap-2"><select wire:model.live="status" class="field min-w-0 flex-1 sm:w-44"><option value="all">Tous les clients</option><option value="active">Clients actifs</option><option value="inactive">Clients inactifs</option></select>@if($hasFilters)<button wire:click="resetFilters" class="topbar-button shrink-0 text-rose-500" title="Effacer les filtres"><x-icon name="x" :size="17"/></button>@endif</div>
    </section>

    <section class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3 2xl:grid-cols-4">
        @forelse($clients as $client)
            <article class="group panel relative p-5 transition duration-200 hover:-translate-y-0.5 hover:border-indigo-200 hover:shadow-lg hover:shadow-slate-200/60">
                <div class="flex items-start gap-3"><span class="grid size-11 shrink-0 place-items-center rounded-xl bg-gradient-to-br from-indigo-50 to-cyan-50 text-sm font-black text-indigo-600 ring-1 ring-indigo-100">{{ strtoupper(substr($client->company_name,0,2)) }}</span><div class="min-w-0 flex-1"><div class="flex items-center gap-2"><h3 class="truncate font-extrabold text-slate-950">{{ $client->company_name }}</h3><span class="size-2 shrink-0 rounded-full {{ $client->is_active ? 'bg-emerald-400' : 'bg-slate-300' }}" title="{{ $client->is_active ? 'Actif' : 'Inactif' }}"></span></div><p class="mt-0.5 truncate text-xs text-slate-400">{{ $client->contact_name ?: 'Aucun contact renseigné' }}</p></div><div x-data="{open:false}" class="relative"><button @click="open=!open" @click.outside="open=false" class="icon-button -mr-2 -mt-1"><x-icon name="more" :size="18"/></button><div x-cloak x-show="open" x-transition class="dropdown-panel right-0 top-8 w-40 p-1.5"><button wire:click="edit({{ $client->id }})" @click="open=false" class="flex w-full items-center gap-2 rounded-lg px-3 py-2 text-xs font-semibold hover:bg-slate-50"><x-icon name="edit" :size="14"/>Modifier</button><button wire:click="delete({{ $client->id }})" wire:confirm="Supprimer définitivement ce client ?" class="flex w-full items-center gap-2 rounded-lg px-3 py-2 text-xs font-semibold text-rose-600 hover:bg-rose-50"><x-icon name="trash" :size="14"/>Supprimer</button></div></div></div>
                <div class="mt-5 space-y-2.5 text-xs text-slate-500"><div class="flex items-center gap-2"><x-icon name="mail" :size="15" class="text-slate-300"/><span class="truncate">{{ $client->email ?: 'Email non renseigné' }}</span></div><div class="flex items-center gap-2"><x-icon name="wallet" :size="15" class="text-slate-300"/><span>{{ $client->phone ?: 'Téléphone non renseigné' }}</span></div><div class="flex items-center gap-2"><x-icon name="building" :size="15" class="text-slate-300"/><span class="truncate">{{ collect([$client->city,$client->country])->filter()->join(', ') ?: 'Localisation non renseignée' }}</span></div></div>
                <div class="mt-5 flex items-center justify-between border-t border-slate-100 pt-4"><div><p class="text-lg font-black text-slate-900">{{ $client->documents_count }}</p><p class="text-[9px] font-bold uppercase tracking-wider text-slate-400">Document(s)</p></div><div class="flex gap-1"><button wire:click="edit({{ $client->id }})" class="btn-ghost"><x-icon name="edit" :size="14"/>Modifier</button><a href="{{ route('documents.create','quotation') }}?client={{ $client->id }}" wire:navigate class="btn-ghost text-indigo-600">Créer<x-icon name="arrow-right" :size="14"/></a></div></div>
            </article>
        @empty
            <div class="panel col-span-full"><x-empty-state icon="users" :title="$hasFilters ? 'Aucun client trouvé' : 'Votre répertoire est vide'" :description="$hasFilters ? 'Essayez un autre terme ou effacez les filtres.' : 'Ajoutez votre premier client pour créer des devis, proformas et factures.'"><x-slot:action><button wire:click="create" class="btn-primary"><x-icon name="plus" :size="16"/>Ajouter un client</button></x-slot:action></x-empty-state></div>
        @endforelse
    </section>
    @if($clients->hasPages())<div>{{ $clients->links() }}</div>@endif

    @if($showForm)
        <div class="mobile-sheet fixed inset-0 z-[90] flex items-center justify-center bg-slate-950/60 p-4 backdrop-blur-sm" x-data x-on:keydown.escape.window="$wire.set('showForm',false)">
            <form wire:submit="save" class="max-h-[92dvh] w-full max-w-2xl overflow-y-auto rounded-2xl bg-white shadow-2xl">
                <div class="sticky top-0 z-10 flex items-center justify-between border-b border-slate-100 bg-white/95 px-5 py-4 backdrop-blur"><div><h3 class="text-lg font-extrabold text-slate-950">{{ $editingId ? 'Modifier le client' : 'Nouveau client' }}</h3><p class="mt-0.5 text-xs text-slate-400">Coordonnées commerciales et fiscales</p></div><button type="button" wire:click="$set('showForm',false)" class="topbar-button border-0 shadow-none"><x-icon name="x" :size="19"/></button></div>
                <div class="grid gap-5 p-5 sm:grid-cols-2 sm:p-6">
                    <label class="sm:col-span-2"><span class="label">Entreprise *</span><input wire:model="form.company_name" class="field" autofocus aria-invalid="{{ $errors->has('form.company_name') ? 'true' : 'false' }}">@error('form.company_name')<small class="mt-1 block text-xs font-medium text-rose-600">{{ $message }}</small>@enderror</label>
                    <label><span class="label">Personne de contact</span><input wire:model="form.contact_name" class="field" placeholder="Nom complet"></label><label><span class="label">NIF</span><input wire:model="form.tax_number" class="field" placeholder="IFU, NIF, RCCM…"></label>
                    <label><span class="label">Adresse email</span><input wire:model="form.email" type="email" class="field" placeholder="contact@entreprise.com">@error('form.email')<small class="mt-1 block text-xs text-rose-600">{{ $message }}</small>@enderror</label><label><span class="label">Téléphone</span><input wire:model="form.phone" type="tel" class="field" placeholder="+229 …"></label>
                    <label class="sm:col-span-2"><span class="label">Adresse</span><input wire:model="form.address" class="field" placeholder="Rue, quartier, immeuble…"></label><label><span class="label">Ville</span><input wire:model="form.city" class="field"></label><label><span class="label">Pays</span><input wire:model="form.country" class="field"></label>
                    <label class="sm:col-span-2"><span class="label">Notes internes</span><textarea wire:model="form.notes" rows="3" class="field" placeholder="Informations utiles à votre équipe…"></textarea></label>
                    <label class="flex items-center gap-2.5 rounded-xl bg-slate-50 p-3 text-sm font-semibold text-slate-700"><input wire:model="form.is_active" type="checkbox">Client actif</label>
                </div>
                <div class="sticky bottom-0 flex justify-end gap-2 border-t border-slate-100 bg-slate-50/95 p-4 backdrop-blur sm:p-5"><button type="button" wire:click="$set('showForm',false)" class="btn-secondary">Annuler</button><button class="btn-primary" wire:loading.attr="disabled"><span wire:loading.remove>Enregistrer</span><span wire:loading>Enregistrement…</span></button></div>
            </form>
        </div>
    @endif
</div>