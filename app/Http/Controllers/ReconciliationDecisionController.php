<?php

namespace App\Http\Controllers;

use App\Actions\DecideReconciliationException;
use App\Exceptions\InvalidReconciliationDecision;
use App\Http\Requests\StoreReconciliationDecisionRequest;
use App\Models\ReconciliationException;
use App\ReconciliationDecisionAction;
use Illuminate\Http\RedirectResponse;

class ReconciliationDecisionController extends Controller
{
    public function store(
        StoreReconciliationDecisionRequest $request,
        ReconciliationException $reconciliationException,
        DecideReconciliationException $decide
    ): RedirectResponse {
        $validated = $request->validated();

        try {
            $decide->handle(
                $reconciliationException,
                ReconciliationDecisionAction::from($validated['action']),
                $validated['explanation']
            );
        } catch (InvalidReconciliationDecision $exception) {
            return back()->withErrors(['decision' => $exception->getMessage()]);
        }

        return redirect()
            ->route('runs.show', $reconciliationException->reconciliationRun)
            ->with('status', 'Décision fictive enregistrée. L’exception est maintenant résolue.');
    }
}
