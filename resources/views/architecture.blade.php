@extends('layouts.app')

@section('title', 'Architecture')

@section('content')
    <section class="border-b border-white/10 pb-10">
        <p class="text-xs font-semibold uppercase tracking-[0.28em] text-cyan-300">Conception du système</p>
        <h1 class="mt-4 max-w-4xl text-4xl font-semibold tracking-tight text-white sm:text-5xl">Un pipeline lisible, déterministe et vérifiable.</h1>
        <p class="mt-5 max-w-3xl text-base leading-7 text-slate-400">
            ReconcileOps transforme deux lots fictifs en un instantané explicable. Aucune règle probabiliste : une même entrée produit toujours le même résultat.
        </p>
    </section>

    <section class="mt-10">
        <div class="flex items-end justify-between gap-4">
            <div>
                <p class="text-xs font-semibold uppercase tracking-[0.24em] text-slate-500">Flux de traitement</p>
                <h2 class="mt-2 text-2xl font-semibold text-white">Du fichier au résultat</h2>
            </div>
            <x-status-pill tone="green">Transaction atomique</x-status-pill>
        </div>

        <div class="mt-6 grid gap-3 lg:grid-cols-5">
            @foreach ([
                ['01', 'Importer', 'CSV UTF-8, taille limitée et contrat d’en-tête strict.'],
                ['02', 'Normaliser', 'Références, dates, montants entiers et devises.'],
                ['03', 'Dédupliquer', 'Empreintes stables calculées pour chaque ligne.'],
                ['04', 'Rapprocher', 'Règles fortes avant les règles tolérantes.'],
                ['05', 'Publier', 'Correspondances, exceptions et métriques ensemble.'],
            ] as [$number, $title, $description])
                <article class="relative rounded-2xl border border-white/10 bg-white/[0.025] p-5">
                    <span class="font-mono text-xs text-cyan-300">{{ $number }}</span>
                    <h3 class="mt-5 font-semibold text-white">{{ $title }}</h3>
                    <p class="mt-2 text-sm leading-6 text-slate-500">{{ $description }}</p>
                    @if (! $loop->last)
                        <span class="absolute -right-2 top-1/2 z-10 hidden h-4 w-4 -translate-y-1/2 rotate-45 border-r border-t border-white/15 bg-slate-950 lg:block" aria-hidden="true"></span>
                    @endif
                </article>
            @endforeach
        </div>
    </section>

    <section class="mt-12 grid gap-6 lg:grid-cols-[0.85fr_1.15fr]">
        <article class="rounded-3xl border border-white/10 bg-white/[0.025] p-6 lg:p-8">
            <p class="text-xs font-semibold uppercase tracking-[0.24em] text-violet-300">Priorité des règles</p>
            <h2 class="mt-3 text-2xl font-semibold text-white">La règle la plus forte gagne.</h2>
            <ol class="mt-7 space-y-4">
                @foreach ([
                    ['1', 'Référence exacte', 'Référence, montant, devise et date identiques.'],
                    ['2', 'Fenêtre de date', 'Même référence et mêmes valeurs dans la tolérance configurée.'],
                    ['3', 'Clé composite', 'Référence secondaire et valeurs compatibles.'],
                    ['4', 'Exception', 'Écart, doublon, absence ou ambiguïté à expliquer.'],
                ] as [$rank, $title, $description])
                    <li class="flex gap-4 rounded-2xl border border-white/10 bg-slate-950/50 p-4">
                        <span class="grid h-8 w-8 shrink-0 place-items-center rounded-full border border-violet-300/25 bg-violet-300/10 font-mono text-xs text-violet-300">{{ $rank }}</span>
                        <div>
                            <h3 class="text-sm font-semibold text-white">{{ $title }}</h3>
                            <p class="mt-1 text-xs leading-5 text-slate-500">{{ $description }}</p>
                        </div>
                    </li>
                @endforeach
            </ol>
        </article>

        <div class="grid gap-6 sm:grid-cols-2">
            <article class="rounded-3xl border border-emerald-300/15 bg-emerald-300/[0.05] p-6">
                <p class="text-xs font-semibold uppercase tracking-[0.22em] text-emerald-300">Garanties</p>
                <ul class="mt-5 space-y-4 text-sm leading-6 text-slate-400">
                    @foreach ([
                        'Idempotence par empreinte du lot et des lignes.',
                        'Montants stockés en unités mineures entières.',
                        'Une ligne prestataire utilisée une seule fois.',
                        'Aucun résultat partiel en cas d’échec.',
                        'ULID public séparé de la clé interne.',
                    ] as $guarantee)
                        <li class="flex gap-3"><span class="mt-2 h-1.5 w-1.5 shrink-0 rounded-full bg-emerald-400"></span>{{ $guarantee }}</li>
                    @endforeach
                </ul>
            </article>

            <article class="rounded-3xl border border-amber-300/15 bg-amber-300/[0.05] p-6">
                <p class="text-xs font-semibold uppercase tracking-[0.22em] text-amber-300">Limites assumées</p>
                <ul class="mt-5 space-y-4 text-sm leading-6 text-slate-400">
                    @foreach ([
                        'Données exclusivement fictives.',
                        'Aucune connexion bancaire, ERP ou prestataire réel.',
                        'Aucun score probabiliste ou apprentissage automatique.',
                        'Aucun import ni arbitrage depuis la version publique.',
                        'Règles fixes pour garder la démonstration vérifiable.',
                    ] as $limit)
                        <li class="flex gap-3"><span class="mt-2 h-1.5 w-1.5 shrink-0 rounded-full bg-amber-400"></span>{{ $limit }}</li>
                    @endforeach
                </ul>
            </article>

            <article class="rounded-3xl border border-white/10 bg-white/[0.025] p-6 sm:col-span-2">
                <p class="text-xs font-semibold uppercase tracking-[0.22em] text-slate-500">Modèle de données</p>
                <div class="mt-5 flex flex-wrap gap-2">
                    @foreach (['data_sources', 'import_batches', 'source_records', 'reconciliation_runs', 'reconciliation_matches', 'reconciliation_exceptions', 'reconciliation_decisions'] as $table)
                        <span class="rounded-lg border border-white/10 bg-slate-950/70 px-3 py-2 font-mono text-xs text-slate-400">{{ $table }}</span>
                    @endforeach
                </div>
            </article>
        </div>
    </section>

    <section class="mt-12 rounded-3xl border border-cyan-300/15 bg-gradient-to-r from-cyan-300/[0.08] to-transparent p-6 lg:p-8">
        <p class="text-xs font-semibold uppercase tracking-[0.24em] text-cyan-300">Validation continue</p>
        <h2 class="mt-3 text-2xl font-semibold text-white">Le même comportement sous SQLite et PostgreSQL.</h2>
        <p class="mt-3 max-w-3xl text-sm leading-6 text-slate-400">
            La CI valide le schéma, les règles métier, les contrôleurs publics, le formatage, les dépendances et le build frontal dans les deux environnements de données.
        </p>
    </section>
@endsection
