@props(['label', 'value', 'note', 'tone' => 'cyan'])

@php
    $toneClasses = match ($tone) {
        'emerald' => 'border-emerald-300/15 bg-emerald-300/[0.06] text-emerald-300',
        'amber' => 'border-amber-300/15 bg-amber-300/[0.06] text-amber-300',
        'rose' => 'border-rose-300/15 bg-rose-300/[0.06] text-rose-300',
        'violet' => 'border-violet-300/15 bg-violet-300/[0.06] text-violet-300',
        default => 'border-cyan-300/15 bg-cyan-300/[0.06] text-cyan-300',
    };
@endphp

<article {{ $attributes->class(['rounded-2xl border p-5', $toneClasses]) }}>
    <p class="text-sm font-medium text-slate-400">{{ $label }}</p>
    <p class="mt-5 text-3xl font-semibold tracking-tight text-white">{{ $value }}</p>
    <p class="mt-2 text-xs leading-5 text-slate-500">{{ $note }}</p>
</article>
