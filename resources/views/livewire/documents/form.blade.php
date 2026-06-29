<form wire:submit="save" class="space-y-6 pb-4">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
        <div class="min-w-0"><a href="{{ route('documents.index',$type) }}" wire:navigate class="inline-flex items-center gap-1.5 text-xs font-bold text-indigo-600 hover:text-indigo-700"><x-icon name="arrow-left" :size="14"/>Retour aux {{ strtolower($documentType->pluralLabel()) }}</a><div class="mt-2 flex flex-wrap items-center gap-2"><h2 class="text-2xl font-extrabold tracking-tight text-slate-950">{{ $document ? $document->number : $documentType->newLabel() }}</h2>@if($document)<x-status-badge :status="$document->status"/>@else<span class="rounded-full bg-slate-100 px-2.5 py-1 text-[10px] font-bold text-slate-500">Non enregistré</span>@endif</div><p class="mt-1 text-sm text-slate-500">{{ $document ? 'Modifiez les informations puis enregistrez vos changements.' : 'Renseignez le client et ajoutez au moins une ligne.' }}</p></div>
        <div class="mobile-stack-actions flex gap-2"><a href="{{ route('documents.index',$type) }}" wire:navigate class="btn-secondary">Annuler</a>@if($document)<a href="{{ route('documents.pdf',$document) }}?download=1" class="btn-secondary"><x-icon name="download" :size="16"/><span class="hidden sm:inline">Télécharger PDF</span><span class="sm:hidden">PDF</span></a>@endif<button class="btn-primary" wire:loading.attr="disabled"><x-icon name="check" :size="16"/><span wire:loading.remove>Enregistrer</span><span wire:loading>Enregistrement…</span></button></div>
    </div>

    @if($errors->any())<div class="flex items-start gap-3 rounded-xl border border-rose-200 bg-rose-50 p-4 text-sm text-rose-700"><x-icon name="alert" :size="18" class="mt-0.5 shrink-0"/><div><p class="font-bold">Certaines informations doivent être corrigées.</p><p class="mt-0.5 text-xs text-rose-600">Vérifiez les champs signalés avant d’enregistrer.</p>@error('items')<p class="mt-1 text-xs font-semibold">{{ $message }}</p>@enderror</div></div>@endif

    <div class="grid gap-5 xl:grid-cols-[minmax(0,1fr)_340px]">
        <div class="min-w-0 space-y-5">
            <section class="panel overflow-hidden line-items-container">
                <div class="border-b border-slate-100 px-5 py-4 sm:px-6"><h3 class="font-extrabold text-slate-950">Informations générales</h3><p class="mt-0.5 text-xs text-slate-400">Client, dates et état du document</p></div>
                <div class="grid gap-5 p-5 sm:grid-cols-2 sm:p-6 lg:grid-cols-4">
                    <label class="sm:col-span-2"><span class="label">Client *</span>@if($clients->isEmpty())<a href="{{ route('clients.index') }}" wire:navigate class="flex min-h-11 items-center justify-between rounded-xl border border-dashed border-amber-300 bg-amber-50 px-4 text-sm font-semibold text-amber-800">Ajoutez d’abord un client<x-icon name="arrow-right" :size="16"/></a>@else<select wire:model="client_id" class="field" aria-invalid="{{ $errors->has('client_id') ? 'true' : 'false' }}"><option value="">Sélectionner un client</option>@foreach($clients as $client)<option value="{{ $client->id }}">{{ $client->company_name }}{{ $client->contact_name ? ' — '.$client->contact_name : '' }}</option>@endforeach</select>@endif @error('client_id')<small class="mt-1 block text-xs font-semibold text-rose-600">{{ $message }}</small>@enderror</label>
                    <label><span class="label">Date d’émission *</span><input wire:model="issued_at" type="date" class="field">@error('issued_at')<small class="mt-1 block text-xs text-rose-600">{{ $message }}</small>@enderror</label>
                    <label><span class="label">{{ $type === 'invoice' ? 'Date d’échéance' : 'Validité jusqu’au' }}</span><input wire:model="due_at" type="date" class="field">@error('due_at')<small class="mt-1 block text-xs text-rose-600">{{ $message }}</small>@enderror</label>
                    <label class="sm:col-span-1"><span class="label">Statut</span><select wire:model="status" class="field" @disabled($statusLocked)>@foreach($statuses as $item)<option value="{{ $item->value }}">{{ $item->label() }}</option>@endforeach</select>@if($statusLocked)<small class="mt-1 block text-[10px] text-slate-400">Piloté par les paiements</small>@endif</label>
                    <label><span class="label">Devise</span><input wire:model="currency" class="field" maxlength="10" placeholder="XOF"></label>
                </div>
            </section>

            <section class="panel overflow-hidden line-items-container">
                <div class="flex items-center justify-between gap-3 border-b border-slate-100 px-5 py-4 sm:px-6"><div><h3 class="font-extrabold text-slate-950">Produits et services</h3><p class="mt-0.5 text-xs text-slate-400">{{ count($items) }} ligne(s) · les totaux sont recalculés instantanément</p></div><button type="button" wire:click="addItem" class="btn-secondary shrink-0"><x-icon name="plus" :size="15"/><span class="hidden sm:inline">Ajouter une ligne</span><span class="sm:hidden">Ligne</span></button></div>
                <div class="space-y-3 p-3 sm:p-5">
                    @foreach($items as $index => $item)
                        @php
                            $lineBase = (float)($item['quantity'] ?? 0) * (float)($item['unit_price'] ?? 0);
                            $lineTotal = $lineBase * (1 + (float)($item['tax_rate'] ?? 0)/100);
                        @endphp
                        <article wire:key="item-{{ $index }}" class="rounded-xl border border-slate-200 bg-slate-50/50 p-3 sm:p-4">
                            <div class="mb-3 flex items-center justify-between"><span class="grid size-7 place-items-center rounded-lg bg-white text-[10px] font-black text-slate-400 ring-1 ring-slate-200">{{ $index + 1 }}</span><div class="flex items-center gap-3"><span class="text-xs font-extrabold text-slate-700 sm:hidden">{{ number_format($lineTotal,2,',',' ') }} {{ $currency }}</span><button type="button" wire:click="removeItem({{ $index }})" class="icon-button size-8 text-rose-500" title="Supprimer la ligne"><x-icon name="trash" :size="15"/></button></div></div>
                            <div class="document-line-grid">
                                <label class="document-line-wide"><span class="line-label">Catalogue</span><select wire:model="items.{{ $index }}.product_id" wire:change="selectProduct({{ $index }})" class="field text-xs"><option value="">Saisie libre</option>@foreach($products as $product)<option value="{{ $product->id }}">{{ $product->name }} · {{ number_format($product->unit_price,0,',',' ') }}</option>@endforeach</select></label>
                                <label class="document-line-wide"><span class="line-label">Produit ou service *</span><input wire:model="items.{{ $index }}.name" class="field" placeholder="Nom affiché sur le document">@error("items.$index.name")<small class="mt-1 block text-[10px] font-semibold text-rose-600">{{ $message }}</small>@enderror</label>
                                <label class="document-line-wide"><span class="line-label">Description <span class="normal-case text-slate-300">(facultative)</span></span><input wire:model="items.{{ $index }}.description" class="field" placeholder="Précision affichée sous le nom"></label>
                                <label class="min-w-0"><span class="line-label">Qté</span><input wire:model.blur="items.{{ $index }}.quantity" type="number" min="0.001" step="0.001" class="field min-w-0 text-right tabular-nums"></label>
                                <label class="min-w-0"><span class="line-label">Prix HT</span><input wire:model.blur="items.{{ $index }}.unit_price" type="number" min="0" step="0.01" class="field min-w-0 text-right tabular-nums"></label>
                                <label class="min-w-0"><span class="line-label">TVA</span><input wire:model.blur="items.{{ $index }}.tax_rate" type="number" min="0" max="100" step="0.01" class="field min-w-0 text-right tabular-nums"></label>
                            </div>
                            <div class="mt-3 hidden items-center justify-end border-t border-slate-200/70 pt-3 text-xs sm:flex"><span class="mr-3 text-slate-400">Total de la ligne</span><strong class="text-sm text-slate-900">{{ number_format($lineTotal,2,',',' ') }} {{ $currency }}</strong></div>
                        </article>
                    @endforeach
                    <button type="button" wire:click="addItem" class="flex w-full items-center justify-center gap-2 rounded-xl border border-dashed border-slate-300 py-3 text-xs font-bold text-slate-500 transition hover:border-indigo-300 hover:bg-indigo-50 hover:text-indigo-600"><x-icon name="plus" :size="15"/>Ajouter une autre ligne</button>
                </div>
            </section>

            <section class="panel grid gap-5 p-5 sm:grid-cols-2 sm:p-6"><label><span class="label">Note visible par le client</span><textarea wire:model="notes" rows="4" class="field" placeholder="Message, précision ou référence…"></textarea></label><label><span class="label">Conditions et mentions</span><textarea wire:model="terms" rows="4" class="field" placeholder="Conditions de règlement, validité…"></textarea></label></section>
        </div>

        <aside class="h-fit space-y-4 xl:sticky xl:top-[100px]">
            <section class="overflow-hidden rounded-2xl bg-[#111c31] text-white shadow-xl shadow-slate-200/70">
                <div class="border-b border-white/[.08] p-5"><div class="flex items-center justify-between"><span class="text-[10px] font-black uppercase tracking-[.18em] text-cyan-300">Récapitulatif</span><x-icon :name="$type === 'invoice' ? 'receipt' : 'file'" :size="19" class="text-slate-500"/></div><h3 class="mt-2 truncate text-lg font-extrabold">{{ $document?->number ?? $documentType->label().' brouillon' }}</h3><p class="mt-1 text-xs text-slate-500">{{ count($items) }} ligne(s) · {{ $currency }}</p></div>
                <div class="space-y-3 p-5 text-sm"><div class="flex justify-between gap-3 text-slate-400"><span>Sous-total HT</span><strong class="text-white">{{ number_format($this->totals['subtotal'],2,',',' ') }}</strong></div><div class="flex justify-between gap-3 text-slate-400"><span>TVA</span><strong class="text-white">{{ number_format($this->totals['tax'],2,',',' ') }}</strong></div><div class="mt-5 border-t border-white/10 pt-5"><div class="flex items-end justify-between gap-3"><span class="text-sm font-bold text-slate-300">Total TTC</span><span class="text-right text-2xl font-black tracking-tight text-cyan-300">{{ number_format($this->totals['total'],2,',',' ') }}<small class="ml-1 text-xs">{{ $currency }}</small></span></div></div>@if($document && $document->paid_total > 0)<div class="rounded-xl bg-white/[.06] p-3"><div class="flex justify-between text-xs text-slate-400"><span>Déjà encaissé</span><b class="text-emerald-300">{{ number_format($document->paid_total,2,',',' ') }} {{ $currency }}</b></div></div>@endif</div>
            </section>
            <div class="rounded-xl border border-indigo-100 bg-indigo-50 p-4 text-xs leading-5 text-indigo-700"><div class="flex gap-2"><x-icon name="alert" :size="16" class="mt-0.5 shrink-0"/><p>Les montants définitifs sont calculés côté serveur lors de l’enregistrement.</p></div></div>
        </aside>
    </div>
</form>