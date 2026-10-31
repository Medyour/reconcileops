@props(['exception'])

@if (
    app()->environment(['local', 'testing'])
    && config('reconcileops.allow_demo_decisions')
    && $exception->status === \App\ReconciliationExceptionStatus::Open
)
    <details class="mt-4 rounded-xl border border-violet-300/20 bg-violet-300/[0.06] p-4">
        <summary class="cursor-pointer text-sm font-semibold text-violet-200">Simuler une décision locale</summary>
        <form method="POST" action="{{ route('exceptions.decisions.store', $exception) }}" class="mt-5 space-y-4">
            @csrf
            <label class="block">
                <span class="text-xs font-semibold uppercase tracking-[0.14em] text-slate-500">Décision</span>
                <select name="action" required class="mt-2 w-full rounded-xl border border-white/10 bg-slate-950 px-4 py-3 text-sm text-white outline-none focus:border-violet-300/50">
                    @foreach (\App\ReconciliationDecisionAction::cases() as $action)
                        <option value="{{ $action->value }}">{{ $action->label() }}</option>
                    @endforeach
                </select>
            </label>
            <label class="block">
                <span class="text-xs font-semibold uppercase tracking-[0.14em] text-slate-500">Justification fictive</span>
                <textarea
                    name="explanation"
                    required
                    minlength="10"
                    maxlength="1000"
                    rows="3"
                    placeholder="Expliquez la décision de démonstration…"
                    class="mt-2 w-full resize-y rounded-xl border border-white/10 bg-slate-950 px-4 py-3 text-sm text-white outline-none placeholder:text-slate-700 focus:border-violet-300/50"
                ></textarea>
            </label>
            @error('action')
                <p class="text-xs text-rose-300">{{ $message }}</p>
            @enderror
            @error('explanation')
                <p class="text-xs text-rose-300">{{ $message }}</p>
            @enderror
            <button type="submit" class="rounded-xl bg-violet-300 px-4 py-2.5 text-sm font-semibold text-slate-950 transition hover:bg-violet-200">
                Enregistrer la décision fictive
            </button>
        </form>
    </details>
@endif
