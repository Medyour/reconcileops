<?php

namespace Tests\Feature\Seeders;

use App\Models\ReconciliationException;
use App\Models\ReconciliationRun;
use App\ReconciliationExceptionStatus;
use App\ReconciliationExceptionType;
use App\ReconciliationRunStatus;
use Database\Seeders\DemoDataSeeder;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class DemoDataSeederTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_demo_seeder_creates_all_deterministic_scenarios(): void
    {
        $this->seed(DemoDataSeeder::class);

        $this->assertDatabaseCount('data_sources', 2);
        $this->assertDatabaseCount('import_batches', 2);
        $this->assertDatabaseCount('source_records', 19);
        $this->assertDatabaseCount('reconciliation_runs', 1);
        $this->assertDatabaseCount('reconciliation_matches', 2);
        $this->assertDatabaseCount('reconciliation_exceptions', 8);
        $this->assertDatabaseCount('reconciliation_decisions', 1);

        $run = ReconciliationRun::query()->sole();

        $this->assertSame(ReconciliationRunStatus::Completed, $run->status);
        $this->assertSame(1, $run->exact_matches_count);
        $this->assertSame(1, $run->tolerant_matches_count);
        $this->assertSame(8, $run->exceptions_count);
        $this->assertCount(8, ReconciliationExceptionType::cases());

        foreach (ReconciliationExceptionType::cases() as $type) {
            $this->assertDatabaseHas('reconciliation_exceptions', [
                'reconciliation_run_id' => $run->id,
                'type' => $type->value,
            ]);
        }

        $resolvedException = ReconciliationException::query()
            ->where('type', ReconciliationExceptionType::ManualReview)
            ->sole();

        $this->assertSame(ReconciliationExceptionStatus::Resolved, $resolvedException->status);
        $this->assertNotNull($resolvedException->resolved_at);
        $this->assertCount(1, $resolvedException->decisions);
    }

    public function test_demo_seeder_is_idempotent(): void
    {
        $this->seed(DemoDataSeeder::class);
        $this->seed(DemoDataSeeder::class);

        $this->assertDatabaseCount('data_sources', 2);
        $this->assertDatabaseCount('import_batches', 2);
        $this->assertDatabaseCount('source_records', 19);
        $this->assertDatabaseCount('reconciliation_runs', 1);
        $this->assertDatabaseCount('reconciliation_matches', 2);
        $this->assertDatabaseCount('reconciliation_exceptions', 8);
        $this->assertDatabaseCount('reconciliation_decisions', 1);
    }
}
