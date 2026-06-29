<div class="space-y-6" x-data="{ filtersOpen: false }">
    <x-page-header :eyebrow="$type === 'invoice' ? 'Ventes & encaissements' : 'Cycle commercial'" :title="$typeEnum->pluralLabel()" :description="$type === 'invoice' ? 'Pilotez les montants facturés, les échéances et les règlements.' : 'Préparez vos offres, suivez leur avancement et convertissez-les sans ressaisie.'">
        <x-slot:actions>
            @if($type === 'invoice')<a href="{{ route('exports.invoices') }}" class="btn-secondary"><x-icon name="download" :size="16"/><span class="hidden sm:inline">Exporter</span><span class="sm:hidden">CSV</span></a>@endif
            <a href="{{ route('documents.create',$type) }}" wire:navigate class="btn-primary"><x-icon name="plus" :size="16"/>{{ $typeEnum->newLabel() }}</a>
        </x-slot:actions>
    </x-page-header>

    @if(session('success'))<div class="flex items-start gap-3 rounded-xl border border-emerald-200 bg-emerald-50 p-4 text-sm font-semibold text-emerald-700"><span class="grid size-6 shrink-0 place-items-center rounded-full bg-emerald-500 text-white"><x-icon name="check" :size="14"/></span>{{ session('success') }}</div>@endif
    @error('action')<div class="flex items-start gap-3 rounded-xl border border-rose-200 bg-rose-50 p-4 text-sm font-semibold text-rose-700"><x-icon name="alert" :size="18" class="mt-0.5 shrink-0"/>{{ $message }}</div>@enderror

    <section class="grid grid-cols-3 gap-3">
        @if($type === 'invoice')
            @foreach([
                ['Facturé',number_format($summary['total'],0,',',' ').' '.$currency,'receipt','bg-indigo-50 text-indigo-600'],
                ['Reste à encaisser',number_format($summary['outstanding'],0,',',' ').' '.$currency,'wallet','bg-amber-50 text-amber-600'],
                ['Factures échues',$summary['attention'],'alert','bg-rose-50 text-rose-600'],
            ] as [$label,$value,$icon,$color])
                <article class="stat-card"><div class="flex items-start justify-between gap-2"><p class="text-[9px] font-extrabold uppercase tracking-wider text-slate-400 sm:text-xs">{{ $label }}</p><span class="hidden size-8 place-items-center rounded-xl sm:grid {{ $color }}"><x-icon :name="$icon" :size="16"/></span></div><p class="mt-3 truncate text-sm font-black text-slate-950 sm:text-xl">{{ $value }}</p></article>
            @endforeach
        @else
            @foreach([
                ['Volume proposé',number_format($summary['total'],0,',',' ').' '.$currency,'file','bg-indigo-50 text-indigo-600'],
                ['En cours',$summary['outstanding'],'clock','bg-amber-50 text-amber-600'],
                ['Convertis',$summary['attention'],'check','bg-emerald-50 text-emerald-600'],
            ] as [$label,$value,$icon,$color])
                <article class="stat-card"><div class="flex items-start justify-between gap-2"><p class="text-[9px] font-extrabold uppercase tracking-wider text-slate-400 sm:text-xs">{{ $label }}</p><span class="hidden size-8 place-items-center rounded-xl sm:grid {{ $color }}"><x-icon :name="$icon" :size="16"/></span></div><p class="mt-3 truncate text-sm font-black text-slate-950 sm:text-xl">{{ $value }}</p></article>
            @endforeach
        @endif
    </section>

    <section class="panel p-3 sm:p-4">
        <div class="flex gap-2 md:hidden"><div class="relative min-w-0 flex-1"><x-icon name="search" :size="17" class="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-slate-400"/><input wire:model.live.debounce.300ms="search" placeholder="N° ou client…" class="field pl-10"></div><button @click="filtersOpen=!filtersOpen" class="topbar-button relative"><x-icon name="filter" :size="18"/>@if($hasFilters)<span class="absolute right-1 top-1 size-2 rounded-full bg-indigo-500"></span>@endif</button></div>
        <div :class="filtersOpen ? 'grid' : 'hidden md:grid'" class="mt-3 gap-3 md:mt-0 md:grid-cols-[minmax(220px,1fr)_190px_155px_155px_auto]">
            <div class="relative hidden md:block"><x-icon name="search" :size="17" class="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-slate-400"/><input wire:model.live.debounce.300ms="search" placeholder="Rechercher par numéro ou client…" class="field pl-10"></div>
            <select wire:model.live="status" class="field"><option value="">Tous les statuts</option>@foreach($statuses as $item)<option value="{{ $item->value }}">{{ $item->label() }}</option>@endforeach</select>
            <label class="relative"><span class="pointer-events-none absolute -top-1.5 left-2 bg-white px-1 text-[9px] font-bold uppercase text-slate-400">Du</span><input wire:model.live="dateFrom" type="date" class="field"></label>
            <label class="relative"><span class="pointer-events-none absolute -top-1.5 left-2 bg-white px-1 text-[9px] font-bold uppercase text-slate-400">Au</span><input wire:model.live="dateTo" type="date" class="field"></label>
            @if($hasFilters)<button wire:click="resetFilters" class="btn-ghost whitespace-nowrap text-rose-600"><x-icon name="x" :size="15"/>Effacer</button>@else<div class="hidden md:block"></div>@endif
        </div>
    </section>

    <section class="panel overflow-hidden">
        <div class="hidden overflow-x-auto lg:block table-scroll">
            <table class="w-full min-w-[980px] text-left text-sm">
                <thead><tr class="border-b border-slate-100 bg-slate-50/80 text-[10px] font-extrabold uppercase tracking-wider text-slate-400"><th class="px-5 py-3.5">Document</th><th class="px-5 py-3.5">Client</th><th class="px-5 py-3.5">Dates</th><th class="px-5 py-3.5">Statut</th><th class="px-5 py-3.5 text-right">Montant</th><th class="w-48 px-4 py-3.5 text-right">Actions</th></tr></thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($documents as $document)
                        <tr class="group transition hover:bg-slate-50/60">
                            <td class="px-5 py-4"><a href="{{ route('documents.edit',[$type,$document]) }}" wire:navigate class="font-extrabold text-slate-900 hover:text-indigo-600">{{ $document->number }}</a><div class="mt-1 flex items-center gap-2 text-[10px] text-slate-400">{{ $typeEnum->label() }}@if($document->source)<span>· depuis {{ $document->source->number }}</span>@endif @if($document->converted_at)<span class="font-bold text-emerald-600">· Converti</span>@endif</div></td>
                            <td class="px-5 py-4"><p class="max-w-[240px] truncate font-semibold text-slate-700">{{ $document->client->company_name }}</p><p class="mt-0.5 max-w-[240px] truncate text-xs text-slate-400">{{ $document->client->email ?: 'Aucun email' }}</p></td>
                            <td class="px-5 py-4"><p class="font-medium text-slate-700">{{ $document->issued_at->format('d/m/Y') }}</p><p class="mt-0.5 text-xs {{ $document->due_at?->isPast() && !in_array($document->status->value,['paid','cancelled']) ? 'font-semibold text-rose-500' : 'text-slate-400' }}">{{ $document->due_at ? ($type === 'invoice' ? 'Échéance ' : 'Validité ').$document->due_at->format('d/m/Y') : ($type === 'invoice' ? 'Sans échéance' : 'Sans limite de validité') }}</p></td>
                            <td class="px-5 py-4"><x-status-badge :status="$document->status"/></td>
                            <td class="px-5 py-4 text-right"><p class="font-extrabold text-slate-950">{{ number_format($document->total,0,',',' ') }} <span class="text-[10px] text-slate-400">{{ $document->currency }}</span></p>@if($type === 'invoice' && $document->balance > 0)<p class="mt-0.5 text-[10px] font-semibold text-rose-500">Solde {{ number_format($document->balance,0,',',' ') }}</p>@elseif($type === 'invoice')<p class="mt-0.5 text-[10px] font-semibold text-emerald-600">Soldée</p>@endif</td>
                            <td class="px-4 py-4"><div class="flex justify-end gap-0.5">
                                <a href="{{ route('documents.edit',[$type,$document]) }}" wire:navigate class="icon-button" title="Modifier"><x-icon name="edit" :size="16"/></a>
                                <a href="{{ route('documents.pdf',$document) }}?download=1" class="icon-button" title="Télécharger le PDF"><x-icon name="download" :size="16"/></a>
                                <button wire:click="sendEmail({{ $document->id }})" class="icon-button" title="Envoyer par email"><x-icon name="mail" :size="16"/></button>
                                @if($type !== 'invoice' && !$document->converted_at)<button wire:click="convert({{ $document->id }})" wire:confirm="Convertir ce {{ strtolower($typeEnum->label()) }} en facture ?" class="icon-button text-emerald-600" title="Convertir en facture"><x-icon name="arrow-right" :size="16"/></button>@else<button wire:click="duplicate({{ $document->id }})" class="icon-button" title="Dupliquer"><x-icon name="copy" :size="16"/></button>@endif
                                <button wire:click="delete({{ $document->id }})" wire:confirm="Supprimer définitivement ce document ?" class="icon-button text-rose-500" title="Supprimer"><x-icon name="trash" :size="16"/></button>
                            </div></td>
                        </tr>
                    @empty<tr><td colspan="6"><x-empty-state :icon="$type === 'invoice' ? 'receipt' : 'file'" :title="$hasFilters ? 'Aucun résultat' : 'Aucun document'" :description="$hasFilters ? 'Modifiez ou effacez les filtres pour élargir la recherche.' : 'Créez votre premier document pour démarrer votre suivi commercial.'"><x-slot:action><a href="{{ route('documents.create',$type) }}" wire:navigate class="btn-primary"><x-icon name="plus" :size="16"/>Créer maintenant</a></x-slot:action></x-empty-state></td></tr>@endforelse
                </tbody>
            </table>
        </div>

        <div class="divide-y divide-slate-100 lg:hidden">
            @forelse($documents as $document)
                <article class="p-4">
                    <div class="flex items-start justify-between gap-3"><div class="min-w-0"><a href="{{ route('documents.edit',[$type,$document]) }}" wire:navigate class="truncate text-sm font-extrabold text-slate-950">{{ $document->number }}</a><p class="mt-1 truncate text-xs text-slate-400">{{ $document->client->company_name }}</p></div><div class="shrink-0 text-right"><p class="text-sm font-black text-slate-950">{{ number_format($document->total,0,',',' ') }} <small>{{ $document->currency }}</small></p>@if($type==='invoice' && $document->balance>0)<p class="text-[10px] font-semibold text-rose-500">Reste {{ number_format($document->balance,0,',',' ') }}</p>@endif</div></div>
                    <div class="mt-3 flex items-center justify-between"><x-status-badge :status="$document->status"/><span class="text-[10px] font-medium text-slate-400">{{ $document->issued_at->format('d/m/Y') }}{{ $document->due_at ? ' → '.$document->due_at->format('d/m/Y') : '' }}</span></div>
                    <div class="mt-4 grid grid-cols-5 gap-1 rounded-xl bg-slate-50 p-1"><a href="{{ route('documents.edit',[$type,$document]) }}" wire:navigate class="mobile-action"><x-icon name="edit" :size="16"/>Modifier</a><a href="{{ route('documents.pdf',$document) }}?download=1" class="mobile-action"><x-icon name="download" :size="16"/>PDF</a><button wire:click="sendEmail({{ $document->id }})" class="mobile-action"><x-icon name="mail" :size="16"/>Email</button>@if($type!=='invoice' && !$document->converted_at)<button wire:click="convert({{ $document->id }})" wire:confirm="Convertir ce {{ strtolower($typeEnum->label()) }} en facture ?" class="mobile-action text-emerald-700"><x-icon name="arrow-right" :size="16"/>Facture</button>@else<button wire:click="duplicate({{ $document->id }})" class="mobile-action"><x-icon name="copy" :size="16"/>Copier</button>@endif<button wire:click="delete({{ $document->id }})" wire:confirm="Supprimer ce document ?" class="mobile-action text-rose-600"><x-icon name="trash" :size="16"/>Suppr.</button></div>
                </article>
            @empty<x-empty-state :icon="$type === 'invoice' ? 'receipt' : 'file'" :title="$hasFilters ? 'Aucun résultat' : 'Aucun document'" description="Commencez par créer un nouveau document."/>@endforelse
        </div>
        @if($documents->hasPages())<div class="border-t border-slate-100 p-4">{{ $documents->links() }}</div>@endif
    </section>
</div>