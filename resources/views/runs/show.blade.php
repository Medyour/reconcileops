@extends('layouts.app')

@section('title', 'Détail d’une exécution')

@section('content')
    <section class="border-b border-white/10 pb-9">
        <a href="{{ route('runs.index') }}" class="text-sm font-semibold text-cyan-300 transition hover:text-cyan-200">← Toutes les exécutions</a>
        <div class="mt-6 flex flex-col gap-5 lg:flex-row lg:items-start lg:justify-between">
            <div>
                <div class="flex flex-wrap items-center gap-3">
                    <p class="text-xs font-semibold uppercase tracking-[0.24em] text-slate-500">Exécution</p>
                    <x-status-pill :tone="$reconciliationRun->status === \App\ReconciliationRunStatus::Completed ? 'green' : 'amber'">
                        {{ $reconciliationRun->status->label() }}
                    </x-status-pill>
                </div>
                <h1 class="mt-4 break-all font-mono text-2xl font-semibold tracking-tight text-white sm:text-3xl">{{ $reconciliationRun->public_id }}</h1>
                <p class="mt-4 text-sm text-slate-400">
                    {{ $reconciliationRun->internalBatch->dataSource->name }}
                    <span class="mx-2 text-slate-600">↔</span>
                    {{ $reconciliationRun->providerBatch->dataSource->name }}
                </p>
            </div>
            <dl class="grid grid-cols-2 gap-x-8 gap-y-3 rounded-2xl border border-white/10 bg-white/[0.025] px-5 py-4 text-sm">
                <div>
                    <dt class="text-xs text-slate-600">Démarrée</dt>
                    <dd class="mt-1 text-slate-300">{{ $reconciliationRun->started_at?->format('d/m/Y H:i:s') ?? '—' }}</dd>
                </div>
                <div>
                    <dt class="text-xs text-slate-600">Terminée</dt>
                    <dd class="mt-1 text-slate-300">{{ $reconciliationRun->completed_at?->format('d/m/Y H:i:s') ?? '—' }}</dd>
                </div>
            </dl>
        </div>
    </section>

    <section class="mt-8 grid gap-4 sm:grid-cols-3" aria-label="Résultats agrégés">
        <x-metric-card
            label="Correspondances exactes"
            :value="$reconciliationRun->exact_matches_count"
            note="Référence, montant, devise et date identiques"
            tone="emerald"
        />
        <x-metric-card
            label="Correspondances tolérantes"
            :value="$reconciliationRun->tolerant_matches_count"
            note="Fenêtre de date ou clé composite"
        />
        <x-metric-card
            label="Exceptions"
            :value="$reconciliationRun->exceptions_count"
            note="Écarts, doublons, absences ou ambiguïtés"
            tone="rose"
        />
    </section>

    <section class="mt-8 grid gap-4 lg:grid-cols-2">
        @foreach ([
            ['Interne', $reconciliationRun->internalBatch],
            ['Prestataire', $reconciliationRun->providerBatch],
        ] as [$side, $batch])
            <article class="rounded-2xl border border-white/10 bg-white/[0.025] p-6">
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-600">Source {{ strtolower($side) }}</p>
                        <h2 class="mt-2 text-lg font-semibold text-white">{{ $batch->dataSource->name }}</h2>
                    </div>
                    <span class="rounded-lg border border-white/10 bg-slate-950/70 px-3 py-1.5 font-mono text-xs text-slate-400">{{ $batch->record_count }} lignes</span>
                </div>
                <dl class="mt-5 grid gap-3 border-t border-white/10 pt-5 text-sm sm:grid-cols-2">
                    <div>
                        <dt class="text-xs text-slate-600">Fichier</dt>
                        <dd class="mt-1 break-all text-slate-300">{{ $batch->source_filename }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs text-slate-600">Importé le</dt>
                        <dd class="mt-1 text-slate-300">{{ $batch->imported_at?->format('d/m/Y H:i') ?? '—' }}</dd>
                    </div>
                </dl>
            </article>
        @endforeach
    </section>

    <section class="mt-12">
        <div>
            <p class="text-xs font-semibold uppercase tracking-[0.24em] text-emerald-300">Résultats positifs</p>
            <h2 class="mt-2 text-2xl font-semibold text-white">Correspondances</h2>
            <p class="mt-2 text-sm text-slate-500">Chaque paire indique la règle déterministe et la justification utilisées.</p>
        </div>

        <div class="mt-5 overflow-hidden rounded-2xl border border-white/10">
            @if ($reconciliationRun->matches->isEmpty())
                <p class="px-6 py-10 text-center text-sm text-slate-500">Aucune correspondance enregistrée.</p>
            @else
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-white/10 text-left text-sm">
                        <thead class="bg-white/[0.035] text-xs uppercase tracking-[0.14em] text-slate-500">
                            <tr>
                                <th class="px-5 py-4 font-semibold">Ligne interne</th>
                                <th class="px-5 py-4 font-semibold">Ligne prestataire</th>
                                <th class="px-5 py-4 font-semibold">Règle</th>
                                <th class="px-5 py-4 font-semibold">Justification</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-white/10 bg-white/[0.015]">
                            @foreach ($reconciliationRun->matches as $match)
                                @php
                                    $matchTone = match ($match->rule) {
                                        \App\ReconciliationMatchRule::ExactReference => 'green',
                                        \App\ReconciliationMatchRule::DateWindow => 'cyan',
                                        \App\ReconciliationMatchRule::Composite => 'violet',
                                    };
                                @endphp
                                <tr class="align-top text-slate-300">
                                    <td class="min-w-48 px-5 py-5">
                                        <p class="font-medium text-white">{{ $match->internalRecord->external_reference }}</p>
                                        <p class="mt-1 font-mono text-xs text-slate-500">{{ $match->internalRecord->record_key }}</p>
                                        <p class="mt-2 text-xs text-slate-400">
                                            {{ number_format($match->internalRecord->amount_minor / 100, 2, ',', ' ') }} {{ $match->internalRecord->currency }} ·
                                            {{ $match->internalRecord->occurred_on->format('d/m/Y') }}
                                        </p>
                                    </td>
                                    <td class="min-w-48 px-5 py-5">
                                        <p class="font-medium text-white">{{ $match->providerRecord->external_reference }}</p>
                                        <p class="mt-1 font-mono text-xs text-slate-500">{{ $match->providerRecord->record_key }}</p>
                                        <p class="mt-2 text-xs text-slate-400">
                                            {{ number_format($match->providerRecord->amount_minor / 100, 2, ',', ' ') }} {{ $match->providerRecord->currency }} ·
                                            {{ $match->providerRecord->occurred_on->format('d/m/Y') }}
                                        </p>
                                    </td>
                                    <td class="whitespace-nowrap px-5 py-5">
                                        <x-status-pill :tone="$matchTone">{{ $match->rule->label() }}</x-status-pill>
                                    </td>
                                    <td class="min-w-72 px-5 py-5 text-sm leading-6 text-slate-400">{{ $match->explanation }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </section>

    <section class="mt-12">
        <div>
            <p class="text-xs font-semibold uppercase tracking-[0.24em] text-rose-300">Points d’attention</p>
            <h2 class="mt-2 text-2xl font-semibold text-white">Exceptions</h2>
            <p class="mt-2 text-sm text-slate-500">Les valeurs comparées restent visibles avec leur classification et leur état.</p>
        </div>

        @if ($reconciliationRun->exceptions->isEmpty())
            <div class="mt-5 rounded-2xl border border-white/10 px-6 py-10 text-center text-sm text-slate-500">Aucune exception enregistrée.</div>
        @else
            <div class="mt-5 grid gap-4 xl:grid-cols-2">
                @foreach ($reconciliationRun->exceptions as $exception)
                    <article class="rounded-2xl border border-white/10 bg-white/[0.025] p-5">
                        <div class="flex flex-wrap items-start justify-between gap-3">
                            <div>
                                <x-status-pill tone="rose">{{ $exception->type->label() }}</x-status-pill>
                                <h3 class="mt-3 text-sm font-semibold text-white">{{ $exception->explanation }}</h3>
                            </div>
                            <x-status-pill :tone="$exception->status === \App\ReconciliationExceptionStatus::Resolved ? 'green' : 'amber'">
                                {{ $exception->status->label() }}
                            </x-status-pill>
                        </div>

                        <div class="mt-5 grid gap-3 sm:grid-cols-2">
                            @foreach ([
                                ['Interne', $exception->internalRecord],
                                ['Prestataire', $exception->providerRecord],
                            ] as [$side, $record])
                                <div class="rounded-xl border border-white/10 bg-slate-950/60 p-4">
                                    <p class="text-xs font-semibold uppercase tracking-[0.16em] text-slate-600">{{ $side }}</p>
                                    @if ($record !== null)
                                        <p class="mt-3 font-medium text-white">{{ $record->external_reference }}</p>
                                        <p class="mt-1 font-mono text-xs text-slate-500">{{ $record->record_key }}</p>
                                        <p class="mt-3 text-xs leading-5 text-slate-400">
                                            {{ number_format($record->amount_minor / 100, 2, ',', ' ') }} {{ $record->currency }}<br>
                                            {{ $record->occurred_on->format('d/m/Y') }}
                                        </p>
                                    @else
                                        <p class="mt-3 text-sm text-slate-600">Aucune ligne associée</p>
                                    @endif
                                </div>
                            @endforeach
                        </div>

                        @foreach ($exception->decisions as $decision)
                            <div class="mt-4 rounded-xl border border-emerald-300/15 bg-emerald-300/[0.06] p-4">
                                <div class="flex flex-wrap items-center justify-between gap-2">
                                    <p class="text-xs font-semibold text-emerald-300">{{ $decision->action->label() }}</p>
                                    <p class="text-xs text-emerald-300/50">{{ $decision->decided_at->format('d/m/Y H:i') }}</p>
                                </div>
                                <p class="mt-2 text-sm leading-6 text-slate-400">{{ $decision->explanation }}</p>
                            </div>
                        @endforeach

                        <x-decision-form :exception="$exception" />
                    </article>
                @endforeach
            </div>
        @endif
    </section>

    <section class="mt-12 rounded-2xl border border-cyan-300/15 bg-cyan-300/[0.05] p-5 text-sm leading-6 text-slate-400">
        <strong class="text-cyan-200">Garantie de lecture :</strong>
        cette page expose un instantané fictif. Elle ne permet ni import, ni modification de règle, ni décision publique.
    </section>
@endsection
