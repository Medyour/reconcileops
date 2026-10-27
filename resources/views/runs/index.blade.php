@extends('layouts.app')

@section('title', 'Exécutions')

@section('content')
    <section class="flex flex-col gap-6 border-b border-white/10 pb-9 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <p class="text-xs font-semibold uppercase tracking-[0.28em] text-cyan-300">Journal immuable</p>
            <h1 class="mt-4 text-4xl font-semibold tracking-tight text-white">Exécutions de rapprochement</h1>
            <p class="mt-4 max-w-2xl text-sm leading-6 text-slate-400">
                Chaque exécution conserve ses lots, ses règles appliquées et ses résultats sans réécriture du passé.
            </p>
        </div>
        <p class="rounded-xl border border-white/10 bg-white/[0.03] px-4 py-2 text-sm text-slate-400">
            {{ number_format($runs->total(), 0, ',', ' ') }} exécution(s)
        </p>
    </section>

    <section class="mt-8 overflow-hidden rounded-2xl border border-white/10">
        @if ($runs->isEmpty())
            <div class="px-6 py-16 text-center">
                <p class="font-semibold text-white">Aucune exécution disponible</p>
                <p class="mt-2 text-sm text-slate-500">Le journal apparaîtra ici après le premier rapprochement.</p>
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-white/10 text-left text-sm">
                    <thead class="bg-white/[0.035] text-xs uppercase tracking-[0.16em] text-slate-500">
                        <tr>
                            <th class="px-5 py-4 font-semibold">Identifiant</th>
                            <th class="px-5 py-4 font-semibold">Lots comparés</th>
                            <th class="px-5 py-4 font-semibold">Statut</th>
                            <th class="px-5 py-4 text-right font-semibold">Exactes</th>
                            <th class="px-5 py-4 text-right font-semibold">Tolérantes</th>
                            <th class="px-5 py-4 text-right font-semibold">Exceptions</th>
                            <th class="px-5 py-4 font-semibold"><span class="sr-only">Consulter</span></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-white/10 bg-white/[0.015]">
                        @foreach ($runs as $run)
                            <tr class="text-slate-300 transition hover:bg-white/[0.03]">
                                <td class="whitespace-nowrap px-5 py-5">
                                    <p class="font-mono text-xs">{{ \Illuminate\Support\Str::limit($run->public_id, 18) }}</p>
                                    <p class="mt-1 text-xs text-slate-600">{{ $run->created_at->format('d/m/Y H:i') }}</p>
                                </td>
                                <td class="min-w-64 px-5 py-5">
                                    <p class="font-medium text-slate-200">{{ $run->internalBatch->source_filename }}</p>
                                    <p class="mt-1 text-xs text-slate-500">{{ $run->providerBatch->source_filename }}</p>
                                </td>
                                <td class="whitespace-nowrap px-5 py-5">
                                    <x-status-pill :tone="$run->status === \App\ReconciliationRunStatus::Completed ? 'green' : 'amber'">
                                        {{ $run->status->label() }}
                                    </x-status-pill>
                                </td>
                                <td class="px-5 py-5 text-right font-mono text-xs">{{ $run->exact_matches_count }}</td>
                                <td class="px-5 py-5 text-right font-mono text-xs">{{ $run->tolerant_matches_count }}</td>
                                <td class="px-5 py-5 text-right font-mono text-xs">{{ $run->exceptions_count }}</td>
                                <td class="px-5 py-5 text-right">
                                    <a href="{{ route('runs.show', $run) }}" class="font-semibold text-cyan-300 hover:text-cyan-200">Détail</a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </section>

    @if ($runs->hasPages())
        <div class="mt-6">{{ $runs->links() }}</div>
    @endif
@endsection
