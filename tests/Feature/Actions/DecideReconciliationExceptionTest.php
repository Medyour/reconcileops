<?php

namespace Tests\Feature\Actions;

use App\Actions\DecideReconciliationException;
use App\Exceptions\InvalidReconciliationDecision;
use App\Models\ReconciliationException;
use App\ReconciliationDecisionAction;
use App\ReconciliationExceptionStatus;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class DecideReconciliationExceptionTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_local_decision_resolves_exception_and_records_immutable_context(): void
    {
        config()->set('reconcileops.allow_demo_decisions', true);
        $exception = ReconciliationException::factory()->create();

        $decision = app(DecideReconciliationException::class)->handle(
            $exception,
            ReconciliationDecisionAction::AcceptMatch,
            'La référence et les pièces fictives justifient ce rapprochement.'
        );

        $this->assertSame(ReconciliationDecisionAction::AcceptMatch, $decision->action);
        $this->assertSame('local-reviewer', $decision->decided_by);
        $this->assertSame('testing', $decision->metadata['environment']);
        $this->assertSame('local-demo-interface', $decision->metadata['source']);
        $this->assertSame(ReconciliationExceptionStatus::Resolved, $exception->fresh()->status);
        $this->assertNotNull($exception->fresh()->resolved_at);
    }

    public function test_decisions_are_refused_when_feature_is_disabled(): void
    {
        config()->set('reconcileops.allow_demo_decisions', false);
        $exception = ReconciliationException::factory()->create();

        try {
            app(DecideReconciliationException::class)->handle(
                $exception,
                ReconciliationDecisionAction::KeepException,
                'Cette décision ne doit jamais être enregistrée.'
            );
            $this->fail('La décision aurait dû être refusée.');
        } catch (InvalidReconciliationDecision $invalidDecision) {
            $this->assertStringContainsString('désactivées', $invalidDecision->getMessage());
        }

        $this->assertDatabaseCount('reconciliation_decisions', 0);
        $this->assertSame(ReconciliationExceptionStatus::Open, $exception->fresh()->status);
    }

    public function test_resolved_exception_cannot_receive_a_second_decision(): void
    {
        config()->set('reconcileops.allow_demo_decisions', true);
        $exception = ReconciliationException::factory()->create();
        $action = app(DecideReconciliationException::class);
        $action->handle(
            $exception,
            ReconciliationDecisionAction::Ignore,
            'Première décision fictive conservée dans le journal.'
        );

        try {
            $action->handle(
                $exception,
                ReconciliationDecisionAction::AcceptMatch,
                'Une seconde décision ne doit pas remplacer la première.'
            );
            $this->fail('Une exception résolue aurait dû refuser la seconde décision.');
        } catch (InvalidReconciliationDecision $invalidDecision) {
            $this->assertStringContainsString('déjà une décision finale', $invalidDecision->getMessage());
        }

        $this->assertDatabaseCount('reconciliation_decisions', 1);
    }
}
