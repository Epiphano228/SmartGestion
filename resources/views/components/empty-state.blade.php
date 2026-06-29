@props(['icon' => 'file', 'title', 'description' => null])
<div {{ $attributes->class('flex flex-col items-center justify-center px-6 py-16 text-center') }}>
    <span class="grid size-14 place-items-center rounded-2xl bg-slate-100 text-slate-400"><x-icon :name="$icon" :size="25"/></span>
    <h3 class="mt-4 font-bold text-slate-800">{{ $title }}</h3>
    @if($description)<p class="mt-1 max-w-sm text-sm leading-6 text-slate-400">{{ $description }}</p>@endif
    @if(isset($action))<div class="mt-5">{{ $action }}</div>@endif
</div>
