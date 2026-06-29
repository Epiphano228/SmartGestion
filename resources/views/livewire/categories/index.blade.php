<div class="space-y-6">
    <x-page-header eyebrow="Catalogue" title="Catégories" description="Structurez vos produits et services pour retrouver rapidement chaque offre.">
        <x-slot:actions>
            <a href="{{ route('products.index') }}" wire:navigate class="btn-secondary"><x-icon name="box" :size="16"/>Voir les articles</a>
            <button wire:click="create" class="btn-primary"><x-icon name="plus" :size="16"/>Nouvelle catégorie</button>
        </x-slot:actions>
    </x-page-header>

    @error('action')
        <div class="flex items-start gap-3 rounded-xl border border-amber-200 bg-amber-50 p-4 text-sm font-semibold text-amber-800">
            <x-icon name="alert" :size="18" class="mt-0.5 shrink-0"/><span>{{ $message }}</span>
        </div>
    @enderror

    <section class="grid grid-cols-3 gap-3">
        @foreach([
            ['Catégories', $summary['total'], 'filter', 'bg-indigo-50 text-indigo-600'],
            ['Utilisées', $summary['used'], 'check', 'bg-emerald-50 text-emerald-600'],
            ['Non classés', $summary['unclassified'], 'box', $summary['unclassified'] ? 'bg-amber-50 text-amber-600' : 'bg-slate-50 text-slate-500'],
        ] as [$label, $value, $icon, $color])
            <article class="stat-card">
                <div class="flex items-start justify-between gap-2">
                    <p class="text-[9px] font-extrabold uppercase tracking-wider text-slate-400 sm:text-xs">{{ $label }}</p>
                    <span class="hidden size-8 place-items-center rounded-xl sm:grid {{ $color }}"><x-icon :name="$icon" :size="16"/></span>
                </div>
                <p class="mt-3 text-xl font-black text-slate-950">{{ $value }}</p>
            </article>
        @endforeach
    </section>

    <section class="panel p-3 sm:p-4">
        <div class="flex gap-2">
            <div class="relative min-w-0 flex-1">
                <x-icon name="search" :size="17" class="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-slate-400"/>
                <input wire:model.live.debounce.300ms="search" class="field pl-10" placeholder="Rechercher une catégorie…">
            </div>
            @if($hasFilters)
                <button wire:click="resetFilters" class="topbar-button shrink-0 text-rose-500" title="Effacer la recherche"><x-icon name="x" :size="17"/></button>
            @endif
        </div>
    </section>

    <section class="panel overflow-hidden">
        <div class="hidden overflow-x-auto md:block table-scroll">
            <table class="w-full min-w-[680px] text-left text-sm">
                <thead>
                    <tr class="border-b border-slate-100 bg-slate-50/80 text-[10px] font-extrabold uppercase tracking-wider text-slate-400">
                        <th class="px-5 py-3.5">Catégorie</th>
                        <th class="px-5 py-3.5 text-center">Articles</th>
                        <th class="px-5 py-3.5">Utilisation</th>
                        <th class="w-28 px-5 py-3.5 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($categories as $category)
                        <tr class="transition hover:bg-slate-50/60">
                            <td class="px-5 py-4">
                                <div class="flex items-center gap-3">
                                    <span class="size-3 shrink-0 rounded-full ring-4 ring-slate-100" style="background-color: {{ $category->color }}"></span>
                                    <div>
                                        <p class="font-extrabold text-slate-900">{{ $category->name }}</p>
                                        <p class="mt-0.5 font-mono text-[10px] uppercase text-slate-400">{{ $category->color }}</p>
                                    </div>
                                </div>
                            </td>
                            <td class="px-5 py-4 text-center">
                                <span class="inline-grid min-w-8 place-items-center rounded-full bg-slate-100 px-2 py-1 text-xs font-extrabold text-slate-700">{{ $category->products_count }}</span>
                            </td>
                            <td class="px-5 py-4">
                                @if($category->products_count)
                                    <a href="{{ route('products.index') }}?categorie={{ $category->id }}" wire:navigate class="inline-flex items-center gap-1.5 text-xs font-bold text-indigo-600 hover:text-indigo-700">Afficher les articles<x-icon name="arrow-right" :size="14"/></a>
                                @else
                                    <span class="text-xs text-slate-400">Aucun article associé</span>
                                @endif
                            </td>
                            <td class="px-5 py-4">
                                <div class="flex justify-end gap-1">
                                    <button wire:click="edit({{ $category->id }})" class="icon-button" title="Modifier"><x-icon name="edit" :size="16"/></button>
                                    <button wire:click="delete({{ $category->id }})" wire:confirm="Supprimer définitivement cette catégorie ?" class="icon-button text-rose-500" title="Supprimer"><x-icon name="trash" :size="16"/></button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4">
                                <x-empty-state icon="filter" :title="$hasFilters ? 'Aucune catégorie trouvée' : 'Aucune catégorie'" :description="$hasFilters ? 'Essayez un autre nom ou effacez la recherche.' : 'Créez une catégorie pour organiser votre catalogue.'">
                                    <x-slot:action><button wire:click="create" class="btn-primary"><x-icon name="plus" :size="16"/>Créer une catégorie</button></x-slot:action>
                                </x-empty-state>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="divide-y divide-slate-100 md:hidden">
            @forelse($categories as $category)
                <article class="p-4">
                    <div class="flex items-start gap-3">
                        <span class="mt-1 size-3 shrink-0 rounded-full ring-4 ring-slate-100" style="background-color: {{ $category->color }}"></span>
                        <div class="min-w-0 flex-1">
                            <div class="flex items-start justify-between gap-3">
                                <div class="min-w-0"><h3 class="truncate text-sm font-extrabold text-slate-950">{{ $category->name }}</h3><p class="mt-1 text-xs text-slate-400">{{ $category->products_count }} article(s)</p></div>
                                <span class="font-mono text-[10px] uppercase text-slate-400">{{ $category->color }}</span>
                            </div>
                            <div class="mt-4 flex items-center justify-between border-t border-slate-100 pt-3">
                                @if($category->products_count)<a href="{{ route('products.index') }}?categorie={{ $category->id }}" wire:navigate class="btn-ghost text-indigo-600">Articles<x-icon name="arrow-right" :size="14"/></a>@else<span class="text-[10px] text-slate-400">Catégorie disponible</span>@endif
                                <div class="flex gap-1"><button wire:click="edit({{ $category->id }})" class="icon-button"><x-icon name="edit" :size="16"/></button><button wire:click="delete({{ $category->id }})" wire:confirm="Supprimer cette catégorie ?" class="icon-button text-rose-500"><x-icon name="trash" :size="16"/></button></div>
                            </div>
                        </div>
                    </div>
                </article>
            @empty
                <x-empty-state icon="filter" title="Aucune catégorie"/>
            @endforelse
        </div>

        @if($categories->hasPages())<div class="border-t border-slate-100 p-4">{{ $categories->links() }}</div>@endif
    </section>

    @if($showForm)
        <div class="mobile-sheet fixed inset-0 z-[90] flex items-center justify-center bg-slate-950/60 p-4 backdrop-blur-sm" x-data x-on:keydown.escape.window="$wire.set('showForm',false)">
            <form wire:submit="save" class="max-h-[92dvh] w-full max-w-lg overflow-y-auto rounded-2xl bg-white shadow-2xl">
                <div class="sticky top-0 z-10 flex items-center justify-between border-b border-slate-100 bg-white/95 px-5 py-4 backdrop-blur">
                    <div><h3 class="text-lg font-extrabold text-slate-950">{{ $editingId ? 'Modifier la catégorie' : 'Nouvelle catégorie' }}</h3><p class="mt-0.5 text-xs text-slate-400">Nom et repère visuel dans le catalogue</p></div>
                    <button type="button" wire:click="$set('showForm',false)" class="topbar-button border-0 shadow-none"><x-icon name="x" :size="19"/></button>
                </div>
                <div class="space-y-5 p-5 sm:p-6">
                    <label><span class="label">Nom de la catégorie *</span><input wire:model="form.name" class="field" maxlength="255" autofocus placeholder="Ex. Prestations de service">@error('form.name')<small class="mt-1 block text-xs font-semibold text-rose-600">{{ $message }}</small>@enderror</label>
                    <div>
                        <span class="label">Couleur</span>
                        <div class="flex items-center gap-3">
                            <input wire:model.live="form.color" type="color" class="h-11 w-16 cursor-pointer rounded-xl border border-slate-200 bg-white p-1">
                            <input wire:model.live="form.color" class="field font-mono uppercase" maxlength="7" placeholder="#4F46E5">
                        </div>
                        @error('form.color')<small class="mt-1 block text-xs font-semibold text-rose-600">{{ $message }}</small>@enderror
                        <div class="mt-3 flex flex-wrap gap-2">
                            @foreach(['#4f46e5','#0891b2','#059669','#d97706','#e11d48','#7c3aed','#475569'] as $color)
                                <button type="button" wire:click="$set('form.color','{{ $color }}')" class="size-8 rounded-full border-2 border-white shadow-sm ring-1 ring-slate-200 transition hover:scale-110" style="background-color: {{ $color }}" title="{{ $color }}"></button>
                            @endforeach
                        </div>
                    </div>
                    <div class="flex items-center gap-3 rounded-xl bg-slate-50 p-4"><span class="size-4 shrink-0 rounded-full ring-4 ring-white" style="background-color: {{ $form['color'] ?? '#4f46e5' }}"></span><p class="text-xs text-slate-500">Cette couleur permet d’identifier rapidement la catégorie.</p></div>
                </div>
                <div class="sticky bottom-0 flex justify-end gap-2 border-t border-slate-100 bg-slate-50/95 p-4 backdrop-blur">
                    <button type="button" wire:click="$set('showForm',false)" class="btn-secondary">Annuler</button>
                    <button class="btn-primary" wire:loading.attr="disabled"><span wire:loading.remove>Enregistrer</span><span wire:loading>Enregistrement…</span></button>
                </div>
            </form>
        </div>
    @endif
</div>
