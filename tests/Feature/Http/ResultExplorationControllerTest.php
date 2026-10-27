<?php

namespace Tests\Feature\Http;

use App\ReconciliationExceptionStatus;
use App\ReconciliationExceptionType;
use App\ReconciliationMatchRule;
use Database\Seeders\DemoDataSeeder;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class ResultExplorationControllerTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_matches_can_be_filtered_by_rule_and_reference(): void
    {
        $this->seed(DemoDataSeeder::class);

        $this->get(route('matches.index', [
            'rule' => ReconciliationMatchRule::DateWindow->value,
        ]))
            ->assertOk()
            ->assertSeeText('Fenêtre de date')
            ->assertSeeText('TX-1002')
            ->assertDontSeeText('TX-1001');

        $this->get(route('matches.index', ['q' => 'PRO-001']))
            ->assertOk()
            ->assertSeeText('TX-1001')
            ->assertDontSeeText('TX-1002');
    }

    public function test_invalid_match_filter_is_rejected(): void
    {
        $this->get(route('matches.index', ['rule' => 'unknown-rule']))
            ->assertRedirect()
            ->assertSessionHasErrors('rule');
    }

    public function test_exceptions_can_be_filtered_by_type_status_and_reference(): void
    {
        $this->seed(DemoDataSeeder::class);

        $this->get(route('exceptions.index', [
            'type' => ReconciliationExceptionType::AmountMismatch->value,
        ]))
            ->assertOk()
            ->assertSeeText('TX-1003')
            ->assertDontSeeText('TX-1004');

        $this->get(route('exceptions.index', [
            'status' => ReconciliationExceptionStatus::Resolved->value,
            'q' => 'TX-RES',
        ]))
            ->assertOk()
            ->assertSeeText('Rapprochement accepté')
            ->assertSeeText('TX-RES')
            ->assertDontSeeText('TX-1003');
    }

    public function test_invalid_exception_filters_are_rejected(): void
    {
        $this->get(route('exceptions.index', [
            'type' => 'unsupported',
            'status' => 'unknown',
        ]))
            ->assertRedirect()
            ->assertSessionHasErrors(['type', 'status']);
    }

    public function test_architecture_page_documents_pipeline_guarantees_and_limits(): void
    {
        $this->get(route('architecture'))
            ->assertOk()
            ->assertSeeText('Un pipeline lisible, déterministe et vérifiable.')
            ->assertSeeText('La règle la plus forte gagne.')
            ->assertSeeText('Idempotence par empreinte du lot et des lignes.')
            ->assertSeeText('Aucun import ni arbitrage depuis la version publique.');
    }
}
