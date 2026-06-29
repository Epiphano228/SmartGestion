<div class="space-y-6 lg:space-y-7">
    <section class="relative overflow-hidden rounded-[1.35rem] bg-[#111c31] px-5 py-6 text-white shadow-xl shadow-slate-200/70 sm:px-7 sm:py-7">
        <div class="absolute -right-16 -top-24 size-64 rounded-full bg-indigo-500/20 blur-3xl"></div>
        <div class="absolute -bottom-24 right-1/3 size-52 rounded-full bg-cyan-400/10 blur-3xl"></div>
        <div class="relative flex flex-col justify-between gap-6 lg:flex-row lg:items-center">
            <div>
                <div class="mb-3 flex items-center gap-2 text-xs font-bold uppercase tracking-[.16em] text-cyan-300"><span class="size-1.5 rounded-full bg-cyan-300"></span>{{ now()->translatedFormat('l d F Y') }}</div>
                <h2 class="text-2xl font-extrabold tracking-tight sm:text-3xl">Bonjour {{ explode(' ', auth()->user()->name)[0] }}.</h2>
                <p class="mt-2 max-w-2xl text-sm leading-6 text-slate-400">Votre activité en un coup d’œil : facturation, encaissements et actions commerciales prioritaires.</p>
            </div>
            <div class="flex flex-wrap gap-2">
                <a href="{{ route('documents.create','quotation') }}" wire:navigate class="inline-flex items-center gap-2 rounded-xl border border-white/10 bg-white/[.06] px-4 py-2.5 text-sm font-bold text-white transition hover:bg-white/10"><x-icon name="file" :size="17"/>Créer un devis</a>
                <a href="{{ route('documents.create','proforma') }}" wire:navigate class="hidden items-center gap-2 rounded-xl border border-white/10 bg-white/[.06] px-4 py-2.5 text-sm font-bold text-white transition hover:bg-white/10 sm:inline-flex"><x-icon name="file" :size="17"/>Proforma</a>
                <a href="{{ route('documents.create','invoice') }}" wire:navigate class="inline-flex items-center gap-2 rounded-xl bg-white px-4 py-2.5 text-sm font-bold text-slate-900 transition hover:bg-cyan-50"><x-icon name="plus" :size="17"/>Créer une facture</a>
            </div>
        </div>
    </section>

    @php
        $cards = [
            ['label'=>'Chiffre d’affaires','value'=>number_format($metrics['turnover'],0,',',' ').' '.$currency,'hint'=>'Total facturé hors annulations','icon'=>'chart','box'=>'bg-indigo-50 text-indigo-600'],
            ['label'=>'Encaissé','value'=>number_format($metrics['collected'],0,',',' ').' '.$currency,'hint'=>'Paiements enregistrés','icon'=>'wallet','box'=>'bg-emerald-50 text-emerald-600'],
            ['label'=>'Reste à encaisser','value'=>number_format($metrics['outstanding'],0,',',' ').' '.$currency,'hint'=>$metrics['overdueCount'].' facture(s) échue(s)','icon'=>'alert','box'=>'bg-rose-50 text-rose-600'],
            ['label'=>'Offres en cours','value'=>$metrics['pendingOffers'],'hint'=>'Devis et proformas à suivre','icon'=>'clock','box'=>'bg-amber-50 text-amber-600'],
            ['label'=>'Nouveaux clients','value'=>$metrics['newClients'],'hint'=>'Depuis le début du mois','icon'=>'users','box'=>'bg-cyan-50 text-cyan-600'],
        ];
    @endphp
    <section class="grid grid-cols-2 gap-3 lg:grid-cols-3 xl:grid-cols-5">
        @foreach($cards as $card)
            <article class="stat-card min-w-0">
                <div class="flex items-start justify-between gap-2"><p class="min-w-0 text-[10px] font-extrabold uppercase tracking-[.11em] text-slate-400 sm:text-xs">{{ $card['label'] }}</p><span class="grid size-8 shrink-0 place-items-center rounded-xl sm:size-9 {{ $card['box'] }}"><x-icon :name="$card['icon']" :size="17"/></span></div>
                <p class="mt-4 truncate text-lg font-black tracking-tight text-slate-950 sm:text-xl xl:text-[1.35rem]">{{ $card['value'] }}</p>
                <p class="mt-1 truncate text-[10px] text-slate-400 sm:text-xs">{{ $card['hint'] }}</p>
            </article>
        @endforeach
    </section>

    <section class="grid gap-5 xl:grid-cols-[minmax(0,1.65fr)_minmax(280px,.75fr)]">
        <article class="panel min-w-0 p-5 sm:p-6">
            <div class="flex items-start justify-between gap-3">
                <div><h3 class="font-extrabold text-slate-950">Évolution de la facturation</h3><p class="mt-1 text-xs text-slate-400 sm:text-sm">Montants facturés sur les 12 derniers mois</p></div>
                <div class="text-right"><p class="text-xs text-slate-400">Ce mois</p><p class="mt-0.5 text-sm font-black text-slate-900">{{ number_format($metrics['currentMonth'],0,',',' ') }} {{ $currency }}</p>@if($metrics['trend'] !== null)<p class="text-[10px] font-bold {{ $metrics['trend'] >= 0 ? 'text-emerald-600' : 'text-rose-600' }}">{{ $metrics['trend'] >= 0 ? '+' : '' }}{{ $metrics['trend'] }} % vs mois dernier</p>@endif</div>
            </div>
            @php $max = max(1, $chart->max('value')); @endphp
            <div class="mt-7 flex h-56 items-end gap-1.5 sm:h-64 sm:gap-3">
                @foreach($chart as $point)
                    <div class="group flex h-full min-w-0 flex-1 flex-col justify-end gap-2" title="{{ $point['full'] }} : {{ number_format($point['value'],0,',',' ') }} {{ $currency }}">
                        <div class="relative flex flex-1 items-end rounded-t-lg bg-slate-50">
                            <div class="w-full rounded-t-lg bg-gradient-to-t from-indigo-600 to-indigo-400 transition-all duration-300 group-hover:from-indigo-700 group-hover:to-cyan-400" style="height: {{ max(2.5, $point['value'] / $max * 100) }}%"></div>
                        </div>
                        <span class="truncate text-center text-[9px] font-bold uppercase text-slate-400 sm:text-[10px]">{{ $point['label'] }}</span>
                    </div>
                @endforeach
            </div>
        </article>

        <article class="panel p-5 sm:p-6">
            <div><h3 class="font-extrabold text-slate-950">Santé des paiements</h3><p class="mt-1 text-xs text-slate-400 sm:text-sm">Répartition des factures actives</p></div>
            @php $healthTotal = max(1, array_sum($paymentHealth)); @endphp
            <div class="mt-7 flex items-center justify-center">
                <div class="relative grid size-40 place-items-center rounded-full" style="background:conic-gradient(#10b981 0 {{ $paymentHealth['paid']/$healthTotal*100 }}%,#f59e0b 0 {{ ($paymentHealth['paid']+$paymentHealth['partial'])/$healthTotal*100 }}%,#f43f5e 0)">
                    <div class="grid size-28 place-items-center rounded-full bg-white text-center"><div><p class="text-3xl font-black text-slate-950">{{ array_sum($paymentHealth) }}</p><p class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Factures</p></div></div>
                </div>
            </div>
            <div class="mt-7 grid grid-cols-3 gap-2">
                @foreach([['Payées',$paymentHealth['paid'],'bg-emerald-500'],['Partielles',$paymentHealth['partial'],'bg-amber-500'],['Échues',$paymentHealth['overdue'],'bg-rose-500']] as [$label,$value,$dot])
                    <div class="rounded-xl bg-slate-50 p-3 text-center"><span class="mx-auto block size-2 rounded-full {{ $dot }}"></span><p class="mt-2 text-lg font-black text-slate-900">{{ $value }}</p><p class="text-[9px] font-bold uppercase text-slate-400">{{ $label }}</p></div>
                @endforeach
            </div>
        </article>
    </section>

    <section class="grid gap-5 xl:grid-cols-[minmax(0,1.45fr)_minmax(300px,.75fr)]">
        <article class="panel overflow-hidden">
            <div class="flex items-center justify-between border-b border-slate-100 px-5 py-4 sm:px-6"><div><h3 class="font-extrabold text-slate-950">Documents récents</h3><p class="mt-0.5 text-xs text-slate-400">Dernières opérations commerciales</p></div><a href="{{ route('documents.index','invoice') }}" wire:navigate class="btn-ghost text-indigo-600">Voir tout<x-icon name="arrow-right" :size="14"/></a></div>
            <div class="hidden overflow-x-auto md:block"><table class="w-full text-left text-sm"><thead><tr class="border-b border-slate-100 bg-slate-50/70 text-[10px] font-extrabold uppercase tracking-wider text-slate-400"><th class="px-6 py-3">Document</th><th class="px-6 py-3">Client</th><th class="px-6 py-3">Statut</th><th class="px-6 py-3 text-right">Montant</th></tr></thead><tbody class="divide-y divide-slate-100">
                @forelse($recentDocuments as $document)<tr class="transition hover:bg-slate-50/70"><td class="px-6 py-3.5"><a href="{{ route('documents.edit',[$document->type->value,$document]) }}" wire:navigate class="font-bold text-slate-900 hover:text-indigo-600">{{ $document->number }}</a><p class="text-[10px] text-slate-400">{{ $document->type->label() }} · {{ $document->issued_at->format('d/m/Y') }}</p></td><td class="px-6 py-3.5 font-medium text-slate-600">{{ $document->client->company_name }}</td><td class="px-6 py-3.5"><x-status-badge :status="$document->status"/></td><td class="px-6 py-3.5 text-right font-extrabold text-slate-900">{{ number_format($document->total,0,',',' ') }} <span class="text-[10px] text-slate-400">{{ $document->currency }}</span></td></tr>
                @empty<tr><td colspan="4"><x-empty-state title="Aucun document" description="Les derniers devis, proformas et factures apparaîtront ici."/></td></tr>@endforelse
            </tbody></table></div>
            <div class="divide-y divide-slate-100 md:hidden">@forelse($recentDocuments as $document)<a href="{{ route('documents.edit',[$document->type->value,$document]) }}" wire:navigate class="block p-4 active:bg-slate-50"><div class="flex items-start justify-between gap-3"><div class="min-w-0"><p class="truncate text-sm font-extrabold text-slate-900">{{ $document->number }}</p><p class="mt-0.5 truncate text-xs text-slate-400">{{ $document->client->company_name }} · {{ $document->issued_at->format('d/m') }}</p></div><p class="shrink-0 text-sm font-black text-slate-900">{{ number_format($document->total,0,',',' ') }} <small>{{ $document->currency }}</small></p></div><div class="mt-2"><x-status-badge :status="$document->status"/></div></a>@empty<x-empty-state title="Aucun document"/>@endforelse</div>
        </article>

        <article class="panel p-5 sm:p-6">
            <div><h3 class="font-extrabold text-slate-950">Activité récente</h3><p class="mt-1 text-xs text-slate-400">Actions de votre équipe</p></div>
            <div class="mt-5 space-y-5">
                @forelse($activities as $activity)
                    <div class="relative flex gap-3 before:absolute before:left-[15px] before:top-8 before:h-[calc(100%+8px)] before:w-px before:bg-slate-100 last:before:hidden"><span class="z-10 grid size-8 shrink-0 place-items-center rounded-full bg-indigo-50 text-indigo-600 ring-4 ring-white"><x-icon name="check" :size="14"/></span><div class="min-w-0 pt-0.5"><p class="text-xs font-semibold leading-5 text-slate-700">{{ $activity->description }}</p><p class="mt-0.5 text-[10px] text-slate-400">{{ $activity->user?->name ?? 'Système' }} · {{ $activity->created_at->diffForHumans() }}</p></div></div>
                @empty<x-empty-state icon="clock" title="Aucune activité" description="Les actions récentes seront affichées ici." class="py-10"/>@endforelse
            </div>
        </article>
    </section>
</div>