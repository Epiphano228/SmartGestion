<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="theme-color" content="#0b1220">
    <title>{{ $title ?? 'SmartGestion' }} — {{ $companyName }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="min-h-screen bg-slate-50 font-sans text-slate-800 antialiased"
      x-data="{ sidebarOpen: false, collapsed: localStorage.getItem('sg-sidebar') === '1', quickOpen: false, userOpen: false, alertsOpen: false }"
      x-on:keydown.escape.window="sidebarOpen=false;quickOpen=false;userOpen=false;alertsOpen=false">
    @php
        $titleText = $title ?? 'SmartGestion';
        $documentType = request()->route('type');
        $primaryNav = [
            ['name' => 'dashboard', 'label' => 'Tableau de bord', 'icon' => 'home', 'url' => route('dashboard'), 'active' => request()->routeIs('dashboard'), 'count' => null],
            ['name' => 'clients', 'label' => 'Clients', 'icon' => 'users', 'url' => route('clients.index'), 'active' => request()->routeIs('clients.*'), 'count' => $navigationCounts['clients']],
            ['name' => 'products', 'label' => 'Produits & services', 'icon' => 'box', 'url' => route('products.index'), 'active' => request()->routeIs('products.*'), 'count' => $navigationCounts['products']],
            ['name' => 'categories', 'label' => 'Catégories', 'icon' => 'filter', 'url' => route('categories.index'), 'active' => request()->routeIs('categories.*'), 'count' => null],
        ];
        $salesNav = [
            ['name' => 'quotations', 'label' => 'Devis', 'icon' => 'file', 'url' => route('documents.index', 'quotation'), 'active' => request()->routeIs('documents.*') && $documentType === 'quotation', 'count' => $navigationCounts['quotations']],
            ['name' => 'proformas', 'label' => 'Proformas', 'icon' => 'file', 'url' => route('documents.index', 'proforma'), 'active' => request()->routeIs('documents.*') && $documentType === 'proforma', 'count' => $navigationCounts['proformas']],
            ['name' => 'invoices', 'label' => 'Factures', 'icon' => 'receipt', 'url' => route('documents.index', 'invoice'), 'active' => request()->routeIs('documents.*') && $documentType === 'invoice', 'count' => $navigationCounts['invoices']],
            ['name' => 'payments', 'label' => 'Paiements', 'icon' => 'wallet', 'url' => route('payments.index'), 'active' => request()->routeIs('payments.*'), 'count' => null],
            ['name' => 'reports', 'label' => 'Statistiques', 'icon' => 'chart', 'url' => route('reports.index'), 'active' => request()->routeIs('reports.*'), 'count' => null],
        ];
        $mobileNav = [$primaryNav[0], $primaryNav[1], $salesNav[0], $salesNav[2]];
    @endphp

    <div wire:loading.delay.longer class="fixed inset-x-0 top-0 z-[120] h-0.5 overflow-hidden bg-indigo-100">
        <div class="h-full w-1/3 animate-[loading_1s_ease-in-out_infinite] rounded-full bg-indigo-600"></div>
    </div>

    <div x-cloak x-show="sidebarOpen" x-transition.opacity class="fixed inset-0 z-40 bg-slate-950/55 backdrop-blur-[2px] lg:hidden" @click="sidebarOpen=false"></div>

    <aside
        :class="[sidebarOpen ? 'translate-x-0' : '-translate-x-full', collapsed ? 'lg:w-[88px]' : 'lg:w-[276px]']"
        class="fixed inset-y-0 left-0 z-50 flex w-[276px] flex-col bg-[#0b1220] text-slate-300 shadow-2xl shadow-slate-950/20 transition-all duration-300 lg:translate-x-0">
        <div class="flex h-[76px] items-center border-b border-white/[.07] px-5" :class="collapsed ? 'lg:justify-center lg:px-0' : 'justify-between'">
            <a href="{{ route('dashboard') }}" wire:navigate @click="sidebarOpen=false" class="flex min-w-0 items-center gap-3">
                @if($companyLogo)
                    <span class="grid size-10 shrink-0 place-items-center overflow-hidden rounded-xl bg-white"><img src="{{ asset('storage/'.$companyLogo) }}" alt="Logo" class="max-h-9 max-w-9 object-contain"></span>
                @else
                    <span class="grid size-10 shrink-0 place-items-center rounded-xl bg-gradient-to-br from-indigo-500 to-cyan-400 text-lg font-black text-white shadow-lg shadow-indigo-950/60">S</span>
                @endif
                <span x-show="!collapsed" x-transition.opacity class="min-w-0 lg:block"><b class="block truncate text-[17px] tracking-tight text-white">{{ $companyName }}</b><small class="block truncate text-[9px] font-bold uppercase tracking-[.19em] text-slate-500">Gestion commerciale</small></span>
            </a>
            <button @click="sidebarOpen=false" class="grid size-9 place-items-center rounded-lg text-slate-500 hover:bg-white/5 hover:text-white lg:hidden" aria-label="Fermer le menu"><x-icon name="x" :size="20"/></button>
        </div>

        <nav class="sidebar-scroll flex-1 overflow-y-auto px-3 py-5">
            <p x-show="!collapsed" class="mb-2 px-3 text-[9px] font-black uppercase tracking-[.2em] text-slate-600">Principal</p>
            <div class="space-y-1">
                @foreach($primaryNav as $item)
                    <a href="{{ $item['url'] }}" wire:navigate @click="sidebarOpen=false" title="{{ $item['label'] }}"
                       class="nav-link {{ $item['active'] ? 'nav-link-active' : '' }}" :class="collapsed ? 'lg:justify-center lg:px-0' : ''">
                        <x-icon :name="$item['icon']" :size="19"/><span x-show="!collapsed" class="min-w-0 flex-1 truncate">{{ $item['label'] }}</span>
                        @if($item['count'] !== null)<span x-show="!collapsed" class="nav-count {{ $item['active'] ? 'bg-white/15 text-white' : '' }}">{{ $item['count'] }}</span>@endif
                    </a>
                @endforeach
            </div>
            <p x-show="!collapsed" class="mb-2 mt-6 px-3 text-[9px] font-black uppercase tracking-[.2em] text-slate-600">Ventes & finances</p>
            <div class="space-y-1">
                @foreach($salesNav as $item)
                    <a href="{{ $item['url'] }}" wire:navigate @click="sidebarOpen=false" title="{{ $item['label'] }}"
                       class="nav-link {{ $item['active'] ? 'nav-link-active' : '' }}" :class="collapsed ? 'lg:justify-center lg:px-0' : ''">
                        <x-icon :name="$item['icon']" :size="19"/><span x-show="!collapsed" class="min-w-0 flex-1 truncate">{{ $item['label'] }}</span>
                        @if($item['count'] !== null && $item['count'] > 0)<span x-show="!collapsed" class="nav-count {{ $item['active'] ? 'bg-white/15 text-white' : '' }}">{{ $item['count'] }}</span>@endif
                    </a>
                @endforeach
            </div>
            @if(auth()->user()->role === 'admin')
                <p x-show="!collapsed" class="mb-2 mt-6 px-3 text-[9px] font-black uppercase tracking-[.2em] text-slate-600">Administration</p>
                <a href="{{ route('settings.index') }}" wire:navigate @click="sidebarOpen=false" title="Paramètres" class="nav-link {{ request()->routeIs('settings.*') ? 'nav-link-active' : '' }}" :class="collapsed ? 'lg:justify-center lg:px-0' : ''"><x-icon name="settings" :size="19"/><span x-show="!collapsed" class="flex-1">Paramètres</span></a>
            @endif
        </nav>

        <div class="hidden border-t border-white/[.07] p-3 lg:block">
            <button @click="collapsed=!collapsed;localStorage.setItem('sg-sidebar', collapsed ? '1' : '0')" class="nav-link w-full" :class="collapsed ? 'justify-center px-0' : ''" :title="collapsed ? 'Déplier le menu' : 'Réduire le menu'">
                <x-icon name="panel" :size="18" class="transition-transform" x-bind:class="collapsed ? 'rotate-180' : ''"/><span x-show="!collapsed" class="flex-1 text-left">Réduire le menu</span>
            </button>
        </div>
        <div class="border-t border-white/[.07] p-3">
            <div class="flex items-center gap-3 rounded-xl p-2" :class="collapsed ? 'lg:justify-center' : ''">
                <span class="grid size-9 shrink-0 place-items-center overflow-hidden rounded-xl bg-white/10 text-xs font-black text-cyan-300 ring-1 ring-white/10">@if(auth()->user()->avatar_url)<img src="{{ auth()->user()->avatar_url }}" alt="Photo de profil" class="h-full w-full object-cover">@else{{ strtoupper(substr(auth()->user()->name, 0, 2)) }}@endif</span>
                <div x-show="!collapsed" class="min-w-0 flex-1"><p class="truncate text-xs font-bold text-white">{{ auth()->user()->name }}</p><p class="truncate text-[10px] text-slate-500">{{ auth()->user()->role === 'admin' ? 'Administrateur' : 'Gestionnaire' }}</p></div>
                <form x-show="!collapsed" method="POST" action="{{ route('logout') }}">@csrf<button class="grid size-8 place-items-center rounded-lg text-slate-500 hover:bg-rose-500/10 hover:text-rose-400" title="Se déconnecter"><x-icon name="logout" :size="17"/></button></form>
            </div>
        </div>
    </aside>

    <main :class="collapsed ? 'lg:pl-[88px]' : 'lg:pl-[276px]'" class="min-h-screen pb-24 transition-all duration-300 lg:pb-0">
        <header class="sticky top-0 z-30 flex h-[76px] items-center justify-between gap-3 border-b border-slate-200/80 bg-white/90 px-4 backdrop-blur-xl sm:px-6 lg:px-8">
            <div class="flex min-w-0 items-center gap-3">
                <button @click="sidebarOpen=true" class="grid size-10 shrink-0 place-items-center rounded-xl border border-slate-200 bg-white text-slate-600 shadow-sm hover:border-indigo-200 hover:text-indigo-600 lg:hidden" aria-label="Ouvrir le menu"><x-icon name="menu" :size="20"/></button>
                <div class="min-w-0">
                    <div class="hidden items-center gap-1.5 text-[11px] font-semibold text-slate-400 sm:flex"><span>SmartGestion</span><x-icon name="chevron-right" :size="12"/><span class="truncate text-slate-500">{{ $titleText }}</span></div>
                    <h1 class="truncate text-base font-extrabold tracking-tight text-slate-950 sm:mt-0.5 sm:text-lg">{{ $titleText }}</h1>
                </div>
            </div>
            <div class="flex shrink-0 items-center gap-1.5 sm:gap-2">
                <div class="relative" @click.outside="alertsOpen=false">
                    <button @click="alertsOpen=!alertsOpen" class="topbar-button relative" aria-label="Alertes"><x-icon name="bell" :size="19"/>@if($navigationCounts['overdue'] > 0)<span class="absolute right-2 top-2 size-2 rounded-full bg-rose-500 ring-2 ring-white"></span>@endif</button>
                    <div x-cloak x-show="alertsOpen" x-transition.origin.top.right class="dropdown-panel right-0 w-[min(22rem,calc(100vw-2rem))]">
                        <div class="border-b border-slate-100 px-4 py-3"><p class="text-sm font-bold text-slate-900">Points d’attention</p><p class="text-xs text-slate-400">Données mises à jour en temps réel</p></div>
                        <a href="{{ route('documents.index','invoice') }}" wire:navigate @click="alertsOpen=false" class="flex gap-3 p-4 hover:bg-slate-50"><span class="grid size-9 place-items-center rounded-xl bg-rose-50 text-rose-600"><x-icon name="alert" :size="17"/></span><div><p class="text-sm font-semibold text-slate-800">{{ $navigationCounts['overdue'] }} facture(s) échue(s)</p><p class="mt-0.5 text-xs text-slate-400">À relancer ou encaisser</p></div></a>
                        <a href="{{ route('documents.index','quotation') }}" wire:navigate @click="alertsOpen=false" class="flex gap-3 border-t border-slate-100 p-4 hover:bg-slate-50"><span class="grid size-9 place-items-center rounded-xl bg-amber-50 text-amber-600"><x-icon name="clock" :size="17"/></span><div><p class="text-sm font-semibold text-slate-800">{{ $navigationCounts['quotations'] }} devis en cours</p><p class="mt-0.5 text-xs text-slate-400">Propositions à suivre</p></div></a>
                        <a href="{{ route('documents.index','proforma') }}" wire:navigate @click="alertsOpen=false" class="flex gap-3 border-t border-slate-100 p-4 hover:bg-slate-50"><span class="grid size-9 place-items-center rounded-xl bg-cyan-50 text-cyan-600"><x-icon name="clock" :size="17"/></span><div><p class="text-sm font-semibold text-slate-800">{{ $navigationCounts['proformas'] }} proforma(s) en cours</p><p class="mt-0.5 text-xs text-slate-400">Brouillons et offres envoyées</p></div></a>
                    </div>
                </div>
                <div class="relative" @click.outside="quickOpen=false">
                    <button @click="quickOpen=!quickOpen" class="btn-primary h-10 px-3 sm:px-4"><x-icon name="plus" :size="17"/><span class="hidden sm:inline">Créer</span><x-icon name="chevron-down" :size="14" class="hidden sm:block"/></button>
                    <div x-cloak x-show="quickOpen" x-transition.origin.top.right class="dropdown-panel right-0 w-60 p-2">
                        <a href="{{ route('documents.create','invoice') }}" wire:navigate @click="quickOpen=false" class="dropdown-item"><span class="grid size-9 place-items-center rounded-xl bg-indigo-50 text-indigo-600"><x-icon name="receipt" :size="17"/></span><span><b>Nouvelle facture</b><small>Facturer un client</small></span></a>
                        <a href="{{ route('documents.create','quotation') }}" wire:navigate @click="quickOpen=false" class="dropdown-item"><span class="grid size-9 place-items-center rounded-xl bg-amber-50 text-amber-600"><x-icon name="file" :size="17"/></span><span><b>Nouveau devis</b><small>Chiffrer une proposition</small></span></a>
                        <a href="{{ route('documents.create','proforma') }}" wire:navigate @click="quickOpen=false" class="dropdown-item"><span class="grid size-9 place-items-center rounded-xl bg-cyan-50 text-cyan-600"><x-icon name="file" :size="17"/></span><span><b>Nouvelle proforma</b><small>Préparer une offre</small></span></a>
                        <a href="{{ route('clients.index') }}" wire:navigate @click="quickOpen=false" class="dropdown-item"><span class="grid size-9 place-items-center rounded-xl bg-emerald-50 text-emerald-600"><x-icon name="users" :size="17"/></span><span><b>Gérer les clients</b><small>Ouvrir le répertoire</small></span></a>
                    </div>
                </div>
                <div class="relative hidden sm:block" @click.outside="userOpen=false">
                    <button @click="userOpen=!userOpen" class="flex h-10 items-center gap-2 rounded-xl border border-slate-200 bg-white pl-1.5 pr-2.5 shadow-sm hover:border-slate-300"><span class="grid size-7 place-items-center overflow-hidden rounded-lg bg-slate-900 text-[10px] font-black text-white">@if(auth()->user()->avatar_url)<img src="{{ auth()->user()->avatar_url }}" alt="Photo de profil" class="h-full w-full object-cover">@else{{ strtoupper(substr(auth()->user()->name,0,2)) }}@endif</span><x-icon name="chevron-down" :size="14" class="text-slate-400"/></button>
                    <div x-cloak x-show="userOpen" x-transition.origin.top.right class="dropdown-panel right-0 w-64 p-2">
                        <div class="border-b border-slate-100 px-3 py-3"><p class="truncate text-sm font-bold text-slate-900">{{ auth()->user()->name }}</p><p class="truncate text-xs text-slate-400">{{ auth()->user()->email }}</p></div>
                        @if(auth()->user()->role === 'admin')<a href="{{ route('settings.index') }}" wire:navigate @click="userOpen=false" class="mt-1 flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-semibold text-slate-600 hover:bg-slate-50"><x-icon name="settings" :size="17"/>Paramètres</a>@endif
                        <form method="POST" action="{{ route('logout') }}">@csrf<button class="flex w-full items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-semibold text-rose-600 hover:bg-rose-50"><x-icon name="logout" :size="17"/>Se déconnecter</button></form>
                    </div>
                </div>
            </div>
        </header>

        <div class="mx-auto w-full max-w-[1600px] p-4 sm:p-6 lg:p-8">{{ $slot }}</div>
    </main>

    <nav class="fixed inset-x-0 bottom-0 z-30 grid grid-cols-5 border-t border-slate-200 bg-white/95 px-2 pb-[max(.45rem,env(safe-area-inset-bottom))] pt-2 shadow-[0_-10px_30px_rgba(15,23,42,.08)] backdrop-blur-xl lg:hidden">
        @foreach($mobileNav as $item)
            <a href="{{ $item['url'] }}" wire:navigate class="mobile-nav-link {{ $item['active'] ? 'text-indigo-600' : 'text-slate-400' }}"><span class="relative"><x-icon :name="$item['icon']" :size="20"/>@if(($item['count'] ?? 0) > 0 && in_array($item['name'], ['quotations','proformas','invoices']))<span class="absolute -right-2 -top-2 grid min-w-4 place-items-center rounded-full bg-rose-500 px-1 text-[8px] font-black text-white">{{ min($item['count'],99) }}</span>@endif</span><span>{{ $item['label'] === 'Tableau de bord' ? 'Accueil' : $item['label'] }}</span></a>
        @endforeach
        <button @click="sidebarOpen=true" class="mobile-nav-link text-slate-400"><x-icon name="menu" :size="20"/><span>Plus</span></button>
    </nav>

    <div x-data="{ show: false, message: '' }" x-on:notify.window="message=$event.detail.message;show=true;setTimeout(()=>show=false,3200)" x-show="show" x-transition class="fixed bottom-24 right-4 z-[110] flex max-w-sm items-center gap-3 rounded-2xl bg-slate-950 px-4 py-3 text-sm font-semibold text-white shadow-2xl lg:bottom-6" x-cloak><span class="grid size-7 place-items-center rounded-full bg-emerald-500"><x-icon name="check" :size="15"/></span><span x-text="message"></span></div>
    @livewireScripts
</body>
</html>