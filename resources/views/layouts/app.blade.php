<!DOCTYPE html>
<html lang="fr">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="description" content="ReconcileOps — démonstration de rapprochement de données déterministe et explicable.">

        <title>@yield('title', 'Vue d’ensemble') · ReconcileOps</title>

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="min-h-screen bg-slate-950 text-slate-100 antialiased">
        <header class="border-b border-white/10 bg-slate-950/95">
            <div class="mx-auto flex max-w-7xl flex-col gap-5 px-5 py-5 sm:px-8 lg:flex-row lg:items-center lg:justify-between">
                <a href="{{ route('dashboard') }}" class="group flex items-center gap-3" aria-label="ReconcileOps — accueil">
                    <span class="grid h-11 w-11 place-items-center rounded-2xl border border-cyan-300/25 bg-cyan-300/10 text-cyan-300 transition group-hover:bg-cyan-300/15">
                        <svg viewBox="0 0 24 24" class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                            <path d="M4 7h10M4 17h16M10 12h10" stroke-linecap="round" />
                            <circle cx="17" cy="7" r="2" />
                            <circle cx="7" cy="12" r="2" />
                        </svg>
                    </span>
                    <span>
                        <span class="block text-lg font-semibold tracking-tight text-white">ReconcileOps</span>
                        <span class="block text-xs font-medium uppercase tracking-[0.24em] text-slate-500">Data reconciliation</span>
                    </span>
                </a>

                <nav class="flex items-center gap-1 rounded-2xl border border-white/10 bg-white/[0.03] p-1" aria-label="Navigation principale">
                    <a href="{{ route('dashboard') }}" @class([
                        'rounded-xl px-4 py-2 text-sm font-medium transition',
                        'bg-white/10 text-white' => request()->routeIs('dashboard'),
                        'text-slate-400 hover:text-white' => ! request()->routeIs('dashboard'),
                    ])>Vue d’ensemble</a>
                    <a href="{{ route('runs.index') }}" @class([
                        'rounded-xl px-4 py-2 text-sm font-medium transition',
                        'bg-white/10 text-white' => request()->routeIs('runs.*'),
                        'text-slate-400 hover:text-white' => ! request()->routeIs('runs.*'),
                    ])>Exécutions</a>
                </nav>
            </div>
        </header>

        <main class="mx-auto w-full max-w-7xl px-5 py-10 sm:px-8 lg:py-14">
            @yield('content')
        </main>

        <footer class="border-t border-white/10">
            <div class="mx-auto flex max-w-7xl flex-col gap-2 px-5 py-7 text-xs text-slate-500 sm:px-8 md:flex-row md:items-center md:justify-between">
                <p>Démonstration publique en lecture seule · données entièrement fictives.</p>
                <p>Règles déterministes · résultats explicables · aucune donnée réelle.</p>
            </div>
        </footer>
    </body>
</html>
