@extends('layouts.app')

@section('title', 'Exceptions')

@section('content')
    <section class="border-b border-white/10 pb-9">
        <p class="text-xs font-semibold uppercase tracking-[0.28em] text-rose-300">File d’analyse</p>
        <h1 class="mt-4 text-4xl font-semibold tracking-tight text-white">Exceptions opérationnelles</h1>
        <p class="mt-4 max-w-2xl text-sm leading-6 text-slate-400">
            Filtrez les écarts, doublons, absences et ambiguïtés sans modifier l’instantané de démonstration.
        </p>
    </section>

    <form method="GET" action="{{ route('exceptions.index') }}" class="mt-8 grid gap-4 rounded-2xl border border-white/10 bg-white/[0.025] p-5 lg:grid-cols-[1fr_0.75fr_0.55fr_auto] lg:items-end">
        <label class="block">
            <span class="text-xs font-semibold uppercase tracking-[0.16em] text-slate-500">Référence ou clé</span>
            <input
                type="search"
                name="q"
                value="{{ $filters['q'] ?? '' }}"
                maxlength="128"
                placeholder="Ex. TX-1003"
                class="mt-2 w-full rounded-xl border border-white/10 bg-slate-950 px-4 py-3 text-sm text-white outline-none transition placeholder:text-slate-700 focus:border-cyan-300/50"
            >
        </label>
        <label class="block">
            <span class="text-xs font-semibold uppercase tracking-[0.16em] text-slate-500">Type</span>
            <select name="type" class="mt-2 w-full rounded-xl border border-white/10 bg-slate-950 px-4 py-3 text-sm text-white outline-none transition focus:border-cyan-300/50">
                <option value="">Tous les types</option>
                @foreach ($types as $type)
                    <option value="{{ $type->value }}" @selected(($filters['type'] ?? '') === $type->value)>{{ $type->label() }}</option>
                @endforeach
            </select>
        </label>
        <label class="block">
            <span class="text-xs font-semibold uppercase tracking-[0.16em] text-slate-500">État</span>
            <select name="status" class="mt-2 w-full rounded-xl border border-white/10 bg-slate-950 px-4 py-3 text-sm text-white outline-none transition focus:border-cyan-300/50">
                <option value="">Tous</option>
                @foreach ($statuses as $status)
                    <option value="{{ $status->value }}" @selected(($filters['status'] ?? '') === $status->value)>{{ $status->label() }}</option>
                @endforeach
            </select>
        </label>
        <div class="flex gap-2">
            <button type="submit" class="flex-1 rounded-xl bg-cyan-300 px-5 py-3 text-sm font-semibold text-slate-950 transition hover:bg-cyan-200">Filtrer</button>
            <a href="{{ route('exceptions.index') }}" class="rounded-xl border border-white/10 px-4 py-3 text-sm font-semibold text-slate-400 transition hover:text-white" aria-label="Réinitialiser les filtres">↺</a>
        </div>
    </form>

    <div class="mt-5 flex items-center justify-between gap-4 text-xs text-slate-500">
        <p>{{ number_format($exceptions->total(), 0, ',', ' ') }} exception(s)</p>
        @if (($filters['q'] ?? '') !== '' || ($filters['type'] ?? '') !== '' || ($filters['status'] ?? '') !== '')
            <p>Filtres actifs</p>
        @endif
    </div>

    @if ($exceptions->isEmpty())
        <section class="mt-4 rounded-2xl border border-white/10 px-6 py-16 text-center">
            <p class="font-semibold text-white">Aucune exception trouvée</p>
            <p class="mt-2 text-sm text-slate-500">Modifiez les filtres pour élargir la recherche.</p>
        </section>
    @else
        <section class="mt-4 grid gap-4 xl:grid-cols-2">
            @foreach ($exceptions as $exception)
                <article class="rounded-2xl border border-white/10 bg-white/[0.025] p-5">
                    <div class="flex flex-wrap items-start justify-between gap-3">
                        <div>
                            <x-status-pill tone="rose">{{ $exception->type->label() }}</x-status-pill>
                            <h2 class="mt-3 text-sm font-semibold leading-6 text-white">{{ $exception->explanation }}</h2>
                        </div>
                        <x-status-pill :tone="$exception->status === \App\ReconciliationExceptionStatus::Resolved ? 'green' : 'amber'">
                            {{ $exception->status->label() }}
                        </x-status-pill>
                    </div>

                    <div class="mt-5 grid gap-3 sm:grid-cols-2">
                        @foreach ([['Interne', $exception->internalRecord], ['Prestataire', $exception->providerRecord]] as [$side, $record])
                            <div class="rounded-xl border border-white/10 bg-slate-950/60 p-4">
                                <p class="text-xs font-semibold uppercase tracking-[0.16em] text-slate-600">{{ $side }}</p>
                                @if ($record !== null)
                                    <p class="mt-3 font-medium text-white">{{ $record->external_reference }}</p>
                                    <p class="mt-1 font-mono text-xs text-slate-500">{{ $record->record_key }}</p>
                                    <p class="mt-3 text-xs leading-5 text-slate-400">
                                        {{ number_format($record->amount_minor / 100, 2, ',', ' ') }} {{ $record->currency }} · {{ $record->occurred_on->format('d/m/Y') }}
                                    </p>
                                @else
                                    <p class="mt-3 text-sm text-slate-600">Aucune ligne associée</p>
                                @endif
                            </div>
                        @endforeach
                    </div>

                    @foreach ($exception->decisions as $decision)
                        <div class="mt-4 rounded-xl border border-emerald-300/15 bg-emerald-300/[0.06] p-4">
                            <p class="text-xs font-semibold text-emerald-300">{{ $decision->action->label() }}</p>
                            <p class="mt-2 text-sm leading-6 text-slate-400">{{ $decision->explanation }}</p>
                        </div>
                    @endforeach

                    <div class="mt-5 flex items-center justify-between border-t border-white/10 pt-4 text-xs text-slate-600">
                        <span>Exécution</span>
                        <a href="{{ route('runs.show', $exception->reconciliationRun) }}" class="font-mono font-semibold text-cyan-300 hover:text-cyan-200">
                            {{ \Illuminate\Support\Str::limit($exception->reconciliationRun->public_id, 16) }}
                        </a>
                    </div>
                </article>
            @endforeach
        </section>
    @endif

    @if ($exceptions->hasPages())
        <div class="mt-6">{{ $exceptions->links() }}</div>
    @endif
@endsection
