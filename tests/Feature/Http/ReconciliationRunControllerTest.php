<?php

namespace Tests\Feature\Http;

use App\Models\ReconciliationRun;
use Database\Seeders\DemoDataSeeder;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class ReconciliationRunControllerTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_run_index_lists_demo_executions_with_public_links(): void
    {
        $this->seed(DemoDataSeeder::class);
        $run = ReconciliationRun::query()->firstOrFail();

        $response = $this->get(route('runs.index'));

        $response
            ->assertOk()
            ->assertSeeText('Exécutions de rapprochement')
            ->assertSeeText('internal-journal-2026-10-26.csv')
            ->assertSeeText('provider-statement-2026-10-26.csv')
            ->assertSee(route('runs.show', $run));

        $this->assertStringContainsString($run->public_id, route('runs.show', $run));
        $this->assertStringNotContainsString("/runs/{$run->id}", route('runs.show', $run));
    }

    public function test_run_detail_explains_matches_exceptions_and_decisions(): void
    {
        $this->seed(DemoDataSeeder::class);
        $run = ReconciliationRun::query()->firstOrFail();

        $response = $this->get(route('runs.show', $run));

        $response
            ->assertOk()
            ->assertSeeText($run->public_id)
            ->assertSeeText('Correspondances')
            ->assertSeeText('Référence exacte')
            ->assertSeeText('Fenêtre de date')
            ->assertSeeText('Exceptions')
            ->assertSeeText('Écart de montant')
            ->assertSeeText('Correspondance ambiguë')
            ->assertSeeText('Rapprochement accepté')
            ->assertSeeText('Décision fictive');
    }

    public function test_unknown_public_run_returns_not_found(): void
    {
        $this->get('/runs/01AAAAAAAAAAAAAAAAAAAAAAAA')->assertNotFound();
    }
}
