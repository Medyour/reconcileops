@extends('layouts.app')

@section('title', 'Correspondances')

@section('content')
    <section class="border-b border-white/10 pb-9">
        <p class="text-xs font-semibold uppercase tracking-[0.28em] text-emerald-300">Résultats positifs</p>
        <h1 class="mt-4 text-4xl font-semibold tracking-tight text-white">Correspondances expliquées</h1>
        <p class="mt-4 max-w-2xl text-sm leading-6 text-slate-400">
            Explorez les paires rapprochées et la règle déterministe qui justifie chaque résultat.
        </p>
    </section>

    <form method="GET" action="{{ route('matches.index') }}" class="mt-8 grid gap-4 rounded-2xl border border-white/10 bg-white/[0.025] p-5 md:grid-cols-[1fr_0.7fr_auto] md:items-end">
        <label class="block">
            <span class="text-xs font-semibold uppercase tracking-[0.16em] text-slate-500">Référence ou clé</span>
            <input
                type="search"
                name="q"
                value="{{ $filters['q'] ?? '' }}"
                maxlength="128"
                placeholder="Ex. TX-1001"
                class="mt-2 w-full rounded-xl border border-white/10 bg-slate-950 px-4 py-3 text-sm text-white outline-none transition placeholder:text-slate-700 focus:border-cyan-300/50"
            >
        </label>
        <label class="block">
            <span class="text-xs font-semibold uppercase tracking-[0.16em] text-slate-500">Règle</span>
            <select name="rule" class="mt-2 w-full rounded-xl border border-white/10 bg-slate-950 px-4 py-3 text-sm text-white outline-none transition focus:border-cyan-300/50">
                <option value="">Toutes les règles</option>
                @foreach ($rules as $rule)
                    <option value="{{ $rule->value }}" @selected(($filters['rule'] ?? '') === $rule->value)>{{ $rule->label() }}</option>
                @endforeach
            </select>
        </label>
        <div class="flex gap-2">
            <button type="submit" class="flex-1 rounded-xl bg-cyan-300 px-5 py-3 text-sm font-semibold text-slate-950 transition hover:bg-cyan-200">Filtrer</button>
            <a href="{{ route('matches.index') }}" class="rounded-xl border border-white/10 px-4 py-3 text-sm font-semibold text-slate-400 transition hover:text-white" aria-label="Réinitialiser les filtres">↺</a>
        </div>
    </form>

    <div class="mt-5 flex items-center justify-between gap-4 text-xs text-slate-500">
        <p>{{ number_format($matches->total(), 0, ',', ' ') }} correspondance(s)</p>
        @if (($filters['q'] ?? '') !== '' || ($filters['rule'] ?? '') !== '')
            <p>Filtres actifs</p>
        @endif
    </div>

    <section class="mt-4 overflow-hidden rounded-2xl border border-white/10">
        @if ($matches->isEmpty())
            <div class="px-6 py-16 text-center">
                <p class="font-semibold text-white">Aucune correspondance trouvée</p>
                <p class="mt-2 text-sm text-slate-500">Modifiez les filtres pour élargir la recherche.</p>
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-white/10 text-left text-sm">
                    <thead class="bg-white/[0.035] text-xs uppercase tracking-[0.14em] text-slate-500">
                        <tr>
                            <th class="px-5 py-4 font-semibold">Interne</th>
                            <th class="px-5 py-4 font-semibold">Prestataire</th>
                            <th class="px-5 py-4 font-semibold">Règle</th>
                            <th class="px-5 py-4 font-semibold">Écarts</th>
                            <th class="px-5 py-4 font-semibold">Exécution</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-white/10 bg-white/[0.015]">
                        @foreach ($matches as $match)
                            @php
                                $tone = match ($match->rule) {
                                    \App\ReconciliationMatchRule::ExactReference => 'green',
                                    \App\ReconciliationMatchRule::DateWindow => 'cyan',
                                    \App\ReconciliationMatchRule::Composite => 'violet',
                                };
                            @endphp
                            <tr class="align-top text-slate-300">
                                @foreach ([$match->internalRecord, $match->providerRecord] as $record)
                                    <td class="min-w-52 px-5 py-5">
                                        <p class="font-medium text-white">{{ $record->external_reference }}</p>
                                        <p class="mt-1 font-mono text-xs text-slate-500">{{ $record->record_key }}</p>
                                        <p class="mt-2 text-xs text-slate-400">
                                            {{ number_format($record->amount_minor / 100, 2, ',', ' ') }} {{ $record->currency }} · {{ $record->occurred_on->format('d/m/Y') }}
                                        </p>
                                    </td>
                                @endforeach
                                <td class="whitespace-nowrap px-5 py-5">
                                    <x-status-pill :tone="$tone">{{ $match->rule->label() }}</x-status-pill>
                                    <p class="mt-3 max-w-72 text-xs leading-5 text-slate-500">{{ $match->explanation }}</p>
                                </td>
                                <td class="whitespace-nowrap px-5 py-5 font-mono text-xs text-slate-400">
                                    {{ number_format($match->amount_difference_minor / 100, 2, ',', ' ') }}<br>
                                    <span class="text-slate-600">{{ $match->date_difference_days }} jour(s)</span>
                                </td>
                                <td class="whitespace-nowrap px-5 py-5">
                                    <a href="{{ route('runs.show', $match->reconciliationRun) }}" class="font-mono text-xs font-semibold text-cyan-300 hover:text-cyan-200">
                                        {{ \Illuminate\Support\Str::limit($match->reconciliationRun->public_id, 14) }}
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </section>

    @if ($matches->hasPages())
        <div class="mt-6">{{ $matches->links() }}</div>
    @endif
@endsection
