<?php

namespace App\Actions;

use App\Exceptions\InvalidReconciliationDecision;
use App\Models\ReconciliationDecision;
use App\Models\ReconciliationException;
use App\ReconciliationDecisionAction;
use App\ReconciliationExceptionStatus;
use Illuminate\Support\Facades\DB;

class DecideReconciliationException
{
    public function handle(
        ReconciliationException $exception,
        ReconciliationDecisionAction $action,
        string $explanation,
        string $decidedBy = 'local-reviewer'
    ): ReconciliationDecision {
        $this->ensureFeatureIsAvailable();

        return DB::transaction(function () use ($exception, $action, $explanation, $decidedBy): ReconciliationDecision {
            $lockedException = ReconciliationException::query()
                ->lockForUpdate()
                ->findOrFail($exception->id);

            if ($lockedException->status !== ReconciliationExceptionStatus::Open) {
                throw new InvalidReconciliationDecision('Cette exception possède déjà une décision finale.');
            }

            $decidedAt = now();
            $decision = $lockedException->decisions()->create([
                'action' => $action,
                'decided_by' => $decidedBy,
                'explanation' => trim($explanation),
                'decided_at' => $decidedAt,
                'metadata' => [
                    'environment' => app()->environment(),
                    'source' => 'local-demo-interface',
                ],
            ]);

            $lockedException->update([
                'status' => ReconciliationExceptionStatus::Resolved,
                'resolved_at' => $decidedAt,
            ]);

            return $decision;
        });
    }

    private function ensureFeatureIsAvailable(): void
    {
        if (
            ! app()->environment(['local', 'testing'])
            || ! config('reconcileops.allow_demo_decisions')
        ) {
            throw new InvalidReconciliationDecision(
                'Les décisions de démonstration sont désactivées dans cet environnement.'
            );
        }
    }
}
