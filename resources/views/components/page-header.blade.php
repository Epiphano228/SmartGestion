@props(['eyebrow' => null, 'title', 'description' => null])
<div {{ $attributes->class('flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between') }}>
    <div class="min-w-0">
        @if($eyebrow)<p class="text-xs font-bold uppercase tracking-[.16em] text-indigo-600">{{ $eyebrow }}</p>@endif
        <h2 class="mt-1 text-2xl font-extrabold tracking-[-.025em] text-slate-950 sm:text-[1.7rem]">{{ $title }}</h2>
        @if($description)<p class="mt-1.5 max-w-2xl text-sm leading-6 text-slate-500">{{ $description }}</p>@endif
    </div>
    @if(isset($actions))<div class="flex shrink-0 flex-wrap items-center gap-2">{{ $actions }}</div>@endif
</div>
