@props(['tone' => 'slate'])

@php
    $toneClasses = match ($tone) {
        'green' => 'border-emerald-300/25 bg-emerald-300/10 text-emerald-300',
        'cyan' => 'border-cyan-300/25 bg-cyan-300/10 text-cyan-300',
        'amber' => 'border-amber-300/25 bg-amber-300/10 text-amber-300',
        'rose' => 'border-rose-300/25 bg-rose-300/10 text-rose-300',
        'violet' => 'border-violet-300/25 bg-violet-300/10 text-violet-300',
        default => 'border-slate-300/20 bg-slate-300/10 text-slate-300',
    };
@endphp

<span {{ $attributes->class(['inline-flex items-center gap-1.5 rounded-full border px-2.5 py-1 text-xs font-semibold', $toneClasses]) }}>
    <span class="h-1.5 w-1.5 rounded-full bg-current" aria-hidden="true"></span>
    {{ $slot }}
</span>
