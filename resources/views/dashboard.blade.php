@extends('layouts.app')

@section('title', 'Vue d’ensemble')

@section('content')
    <section class="flex flex-col gap-8 border-b border-white/10 pb-10 lg:flex-row lg:items-end lg:justify-between">
        <div class="max-w-3xl">
            <p class="text-xs font-semibold uppercase tracking-[0.28em] text-cyan-300">Observabilité opérationnelle</p>
            <h1 class="mt-4 text-4xl font-semibold tracking-tight text-white sm:text-5xl">Chaque rapprochement reste explicable.</h1>
            <p class="mt-5 max-w-2xl text-base leading-7 text-slate-400">
                Comparez les lots, suivez les règles appliquées et isolez immédiatement les écarts qui demandent une revue.
            </p>
        </div>
        <div class="rounded-2xl border border-emerald-300/20 bg-emerald-300/[0.07] px-5 py-4">
            <div class="flex items-center gap-3">
                <span class="h-2.5 w-2.5 rounded-full bg-emerald-400 shadow-[0_0_0_5px_rgba(52,211,153,0.1)]"></span>
                <div>
                    <p class="text-sm font-semibold text-emerald-200">Démonstration en lecture seule</p>
                    <p class="mt-0.5 text-xs text-emerald-300/60">Aucune donnée réelle ni écriture publique</p>
                </div>
            </div>
        </div>
    </section>

    @if ($latestRun !== null)
        <section class="mt-8 grid gap-4 sm:grid-cols-2 xl:grid-cols-5" aria-label="Indicateurs du dernier rapprochement">
            <x-metric-card
                label="Lignes analysées"
                :value="number_format($metrics['total_records'], 0, ',', ' ')"
                note="Cumul des deux sources"
            />
            <x-metric-card
                label="Taux exact"
                :value="number_format($metrics['exact_rate'], 1, ',', ' ').' %'"
                note="Références strictement identiques"
                tone="emerald"
            />
            <x-metric-card
                label="Doublons"
                :value="$metrics['duplicates']"
                note="Empreintes exclues du moteur"
                tone="amber"
            />
            <x-metric-card
                label="Exceptions ouvertes"
                :value="$metrics['open_exceptions']"
                note="Interventions encore requises"
                tone="rose"
            />
            <x-metric-card
                label="Écart fictif"
                :value="number_format($metrics['amount_gap_minor'] / 100, 2, ',', ' ').' €'"
                note="Total des écarts de montant"
                tone="violet"
            />
        </section>

        <section class="mt-8 overflow-hidden rounded-3xl border border-white/10 bg-white/[0.025]">
            <div class="grid gap-8 p-6 lg:grid-cols-[1.3fr_0.7fr] lg:p-8">
                <div>
                    <div class="flex flex-wrap items-center gap-3">
                        <p class="text-xs font-semibold uppercase tracking-[0.24em] text-slate-500">Dernier rapprochement</p>
                        <x-status-pill tone="green">{{ $latestRun->status->label() }}</x-status-pill>
                    </div>
                    <h2 class="mt-4 text-2xl font-semibold text-white">
                        {{ $latestRun->internalBatch->dataSource->name }}
                        <span class="text-slate-600">↔</span>
                        {{ $latestRun->providerBatch->dataSource->name }}
                    </h2>
                    <p class="mt-3 font-mono text-xs text-slate-500">{{ $latestRun->public_id }}</p>
                    <p class="mt-5 max-w-2xl text-sm leading-6 text-slate-400">
                        {{ $latestRun->exact_matches_count }} correspondance(s) exacte(s),
                        {{ $latestRun->tolerant_matches_count }} correspondance(s) tolérante(s) et
                        {{ $latestRun->exceptions_count }} exception(s), avec une justification conservée pour chaque résultat.
                    </p>
                </div>
                <div class="flex flex-col justify-between gap-5 rounded-2xl border border-white/10 bg-slate-950/60 p-5">
                    <div>
                        <p class="text-xs uppercase tracking-[0.2em] text-slate-600">Terminé le</p>
                        <p class="mt-2 text-sm font-medium text-slate-200">{{ $latestRun->completed_at?->format('d/m/Y · H:i') }}</p>
                    </div>
                    <a href="{{ route('runs.show', $latestRun) }}" class="inline-flex items-center justify-center rounded-xl bg-cyan-300 px-4 py-3 text-sm font-semibold text-slate-950 transition hover:bg-cyan-200">
                        Examiner le résultat
                    </a>
                </div>
            </div>
        </section>
    @else
        <section class="mt-8 rounded-3xl border border-dashed border-white/15 bg-white/[0.02] px-6 py-16 text-center">
            <p class="text-lg font-semibold text-white">Aucun rapprochement terminé</p>
            <p class="mx-auto mt-3 max-w-xl text-sm leading-6 text-slate-500">
                Chargez les données fictives de démonstration pour afficher les métriques et les explications du moteur.
            </p>
        </section>
    @endif

    <section class="mt-12">
        <div class="flex items-end justify-between gap-5">
            <div>
                <p class="text-xs font-semibold uppercase tracking-[0.24em] text-slate-500">Historique</p>
                <h2 class="mt-2 text-2xl font-semibold text-white">Exécutions récentes</h2>
            </div>
            <a href="{{ route('runs.index') }}" class="text-sm font-semibold text-cyan-300 transition hover:text-cyan-200">Tout consulter →</a>
        </div>

        <div class="mt-5 overflow-hidden rounded-2xl border border-white/10">
            @if ($recentRuns->isEmpty())
                <p class="px-6 py-10 text-center text-sm text-slate-500">Aucune exécution enregistrée.</p>
            @else
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-white/10 text-left text-sm">
                        <thead class="bg-white/[0.035] text-xs uppercase tracking-[0.16em] text-slate-500">
                            <tr>
                                <th class="px-5 py-4 font-semibold">Exécution</th>
                                <th class="px-5 py-4 font-semibold">Sources</th>
                                <th class="px-5 py-4 font-semibold">Statut</th>
                                <th class="px-5 py-4 text-right font-semibold">Résultats</th>
                                <th class="px-5 py-4 font-semibold"><span class="sr-only">Consulter</span></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-white/10 bg-white/[0.015]">
                            @foreach ($recentRuns as $run)
                                <tr class="text-slate-300 transition hover:bg-white/[0.03]">
                                    <td class="whitespace-nowrap px-5 py-4">
                                        <p class="font-mono text-xs text-slate-300">{{ \Illuminate\Support\Str::limit($run->public_id, 16) }}</p>
                                        <p class="mt-1 text-xs text-slate-600">{{ $run->created_at->format('d/m/Y H:i') }}</p>
                                    </td>
                                    <td class="whitespace-nowrap px-5 py-4 text-xs">
                                        {{ $run->internalBatch->dataSource->name }} ↔ {{ $run->providerBatch->dataSource->name }}
                                    </td>
                                    <td class="whitespace-nowrap px-5 py-4">
                                        <x-status-pill :tone="$run->status === \App\ReconciliationRunStatus::Completed ? 'green' : 'amber'">
                                            {{ $run->status->label() }}
                                        </x-status-pill>
                                    </td>
                                    <td class="whitespace-nowrap px-5 py-4 text-right text-xs text-slate-400">
                                        {{ $run->exact_matches_count + $run->tolerant_matches_count }} rapprochée(s) · {{ $run->exceptions_count }} exception(s)
                                    </td>
                                    <td class="whitespace-nowrap px-5 py-4 text-right">
                                        <a href="{{ route('runs.show', $run) }}" class="font-semibold text-cyan-300 hover:text-cyan-200">Voir</a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </section>

    <section class="mt-12 rounded-3xl border border-white/10 bg-gradient-to-br from-cyan-300/[0.07] to-transparent p-6 lg:p-8">
        <p class="text-xs font-semibold uppercase tracking-[0.24em] text-cyan-300">Pipeline déterministe</p>
        <div class="mt-6 grid gap-4 md:grid-cols-4">
            @foreach ([
                ['01', 'Valider', 'Structure, encodage et types du fichier.'],
                ['02', 'Normaliser', 'Références, dates, montants et devises.'],
                ['03', 'Rapprocher', 'Règles ordonnées sans score opaque.'],
                ['04', 'Expliquer', 'Justification et exception pour chaque ligne.'],
            ] as [$number, $title, $description])
                <article class="rounded-2xl border border-white/10 bg-slate-950/50 p-5">
                    <p class="font-mono text-xs text-cyan-300/70">{{ $number }}</p>
                    <h3 class="mt-4 font-semibold text-white">{{ $title }}</h3>
                    <p class="mt-2 text-sm leading-6 text-slate-500">{{ $description }}</p>
                </article>
            @endforeach
        </div>
    </section>
@endsection
