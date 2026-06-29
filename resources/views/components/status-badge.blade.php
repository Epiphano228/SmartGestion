@props(['status'])
@php
    $value = $status instanceof \BackedEnum ? $status->value : $status;
    $label = $status instanceof \App\Enums\DocumentStatus ? $status->label() : ucfirst((string) $status);
    [$classes, $dot] = match($value) {
        'paid', 'accepted' => ['bg-emerald-50 text-emerald-700 ring-emerald-600/15', 'bg-emerald-500'],
        'partial', 'sent' => ['bg-amber-50 text-amber-700 ring-amber-600/15', 'bg-amber-500'],
        'overdue', 'rejected' => ['bg-rose-50 text-rose-700 ring-rose-600/15', 'bg-rose-500'],
        'cancelled' => ['bg-slate-100 text-slate-600 ring-slate-500/15', 'bg-slate-400'],
        default => ['bg-indigo-50 text-indigo-700 ring-indigo-600/15', 'bg-indigo-500'],
    };
@endphp
<span {{ $attributes->class(["inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-[11px] font-bold ring-1 ring-inset $classes"]) }}>
    <span class="size-1.5 rounded-full {{ $dot }}"></span>{{ $label }}
</span>
