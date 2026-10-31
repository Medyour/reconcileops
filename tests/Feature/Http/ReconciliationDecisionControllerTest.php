<?php

namespace Tests\Feature\Http;

use App\Models\ReconciliationException;
use App\ReconciliationDecisionAction;
use App\ReconciliationExceptionStatus;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class ReconciliationDecisionControllerTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_decision_endpoint_is_forbidden_by_default(): void
    {
        config()->set('reconcileops.allow_demo_decisions', false);
        $exception = ReconciliationException::factory()->create();

        $this->post(route('exceptions.decisions.store', $exception), [
            'action' => ReconciliationDecisionAction::AcceptMatch->value,
            'explanation' => 'Cette requête doit être refusée par défaut.',
        ])->assertForbidden();

        $this->assertDatabaseCount('reconciliation_decisions', 0);
    }

    public function test_enabled_local_decision_redirects_to_run_detail(): void
    {
        config()->set('reconcileops.allow_demo_decisions', true);
        $exception = ReconciliationException::factory()->create();

        $this->post(route('exceptions.decisions.store', $exception), [
            'action' => ReconciliationDecisionAction::KeepException->value,
            'explanation' => 'La différence fictive est confirmée après vérification locale.',
        ])
            ->assertRedirect(route('runs.show', $exception->reconciliationRun))
            ->assertSessionHas('status');

        $this->assertDatabaseHas('reconciliation_decisions', [
            'reconciliation_exception_id' => $exception->id,
            'action' => ReconciliationDecisionAction::KeepException->value,
        ]);
        $this->assertSame(ReconciliationExceptionStatus::Resolved, $exception->fresh()->status);
    }

    public function test_enabled_endpoint_validates_action_and_explanation(): void
    {
        config()->set('reconcileops.allow_demo_decisions', true);
        $exception = ReconciliationException::factory()->create();

        $this->post(route('exceptions.decisions.store', $exception), [
            'action' => 'unsupported',
            'explanation' => 'court',
        ])
            ->assertSessionHasErrors(['action', 'explanation']);

        $this->assertDatabaseCount('reconciliation_decisions', 0);
        $this->assertSame(ReconciliationExceptionStatus::Open, $exception->fresh()->status);
    }

    public function test_local_decision_form_is_visible_only_when_enabled(): void
    {
        $exception = ReconciliationException::factory()->create();
        $run = $exception->reconciliationRun;

        config()->set('reconcileops.allow_demo_decisions', false);
        $this->get(route('runs.show', $run))
            ->assertOk()
            ->assertDontSeeText('Simuler une décision locale');

        config()->set('reconcileops.allow_demo_decisions', true);
        $this->get(route('runs.show', $run))
            ->assertOk()
            ->assertSeeText('Simuler une décision locale');
    }
}
