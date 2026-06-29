<div class="relative grid min-h-screen lg:grid-cols-[1.05fr_.95fr]">
    <div class="relative hidden overflow-hidden lg:block">
        <div class="absolute inset-0 bg-[radial-gradient(circle_at_30%_20%,#4f46e555,transparent_35%),radial-gradient(circle_at_75%_65%,#22d3ee33,transparent_32%)]"></div>
        <div class="relative flex h-full flex-col justify-between p-14">
            <div class="flex items-center gap-3 text-white"><span class="grid size-11 place-items-center rounded-xl bg-indigo-500 text-xl font-black">S</span><span class="text-xl font-bold">SmartGestion</span></div>
            <div class="max-w-xl">
                <span class="rounded-full border border-cyan-300/20 bg-cyan-300/10 px-4 py-2 text-xs font-bold uppercase tracking-[.2em] text-cyan-300">Gestion commerciale unifiée</span>
                <h1 class="mt-7 text-5xl font-semibold leading-[1.08] tracking-tight text-white">Votre activité.<br><span class="text-cyan-300">Clairement maîtrisée.</span></h1>
                <p class="mt-6 max-w-lg text-lg leading-8 text-slate-400">Clients, devis, proformas, factures et encaissements réunis dans un espace de pilotage rapide et élégant.</p>
            </div>
            <p class="text-sm text-slate-600">© {{ date('Y') }} SmartGestion</p>
        </div>
    </div>
    <div class="flex items-center justify-center bg-white px-6 py-12">
        <div class="w-full max-w-md">
            <div class="mb-10 flex items-center gap-3 lg:hidden"><span class="grid size-10 place-items-center rounded-xl bg-indigo-600 font-black text-white">S</span><b class="text-xl">SmartGestion</b></div>
            <p class="text-sm font-bold uppercase tracking-[.18em] text-indigo-600">Ravi de vous revoir</p>
            <h2 class="mt-3 text-3xl font-bold tracking-tight text-slate-950">Connexion à votre espace</h2>
            <p class="mt-2 text-slate-500">Saisissez vos identifiants pour continuer.</p>
            <form wire:submit="login" class="mt-9 space-y-5">
                <div><label class="mb-2 block text-sm font-semibold">Adresse email</label><input wire:model="email" type="email" autofocus autocomplete="email" placeholder="vous@entreprise.com" class="w-full rounded-xl border border-slate-200 bg-slate-50 px-4 py-3.5 outline-none transition focus:border-indigo-500 focus:bg-white focus:ring-4 focus:ring-indigo-100">@error('email')<p class="mt-1 text-sm text-rose-600">{{ $message }}</p>@enderror</div>
                <div><label class="mb-2 block text-sm font-semibold">Mot de passe</label><input wire:model="password" type="password" autocomplete="current-password" placeholder="••••••••" class="w-full rounded-xl border border-slate-200 bg-slate-50 px-4 py-3.5 outline-none transition focus:border-indigo-500 focus:bg-white focus:ring-4 focus:ring-indigo-100">@error('password')<p class="mt-1 text-sm text-rose-600">{{ $message }}</p>@enderror</div>
                <label class="flex items-center gap-2 text-sm text-slate-600"><input wire:model="remember" type="checkbox" class="rounded border-slate-300 text-indigo-600"> Rester connecté</label>
                <button class="w-full rounded-xl bg-indigo-600 px-5 py-3.5 font-bold text-white shadow-xl shadow-indigo-200 transition hover:bg-indigo-700" wire:loading.attr="disabled">Se connecter <span wire:loading>···</span></button>
            </form>
        </div>
    </div>
</div>
