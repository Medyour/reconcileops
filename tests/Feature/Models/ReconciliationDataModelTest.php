<?php

namespace Tests\Feature\Models;

use App\ImportBatchStatus;
use App\Models\DataSource;
use App\Models\ImportBatch;
use App\Models\ReconciliationDecision;
use App\Models\ReconciliationException;
use App\Models\ReconciliationMatch;
use App\Models\ReconciliationRun;
use App\Models\SourceRecord;
use App\ReconciliationDecisionAction;
use App\ReconciliationExceptionType;
use App\ReconciliationMatchRule;
use App\ReconciliationRunStatus;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class ReconciliationDataModelTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_source_record_casts_values_and_does_not_serialize_raw_payload(): void
    {
        $record = SourceRecord::factory()->create([
            'occurred_on' => '2026-10-26',
            'amount_minor' => 12_345,
            'raw_payload' => ['reference' => 'PRIVATE-DEMO-VALUE'],
        ]);

        $this->assertSame('2026-10-26', $record->occurred_on->toDateString());
        $this->assertSame(12_345, $record->amount_minor);
        $this->assertSame(['reference' => 'PRIVATE-DEMO-VALUE'], $record->raw_payload);
        $this->assertArrayNotHasKey('raw_payload', $record->toArray());
        $this->assertTrue($record->importBatch->is($record->importBatch));
    }

    public function test_batch_checksum_is_unique_per_source_but_reusable_across_sources(): void
    {
        $checksum = hash('sha256', 'shared-demo-batch');
        $firstSource = DataSource::factory()->create();
        $secondSource = DataSource::factory()->create();

        ImportBatch::factory()->for($firstSource)->create(['checksum' => $checksum]);
        $secondBatch = ImportBatch::factory()->for($secondSource)->create(['checksum' => $checksum]);

        $this->assertSame($checksum, $secondBatch->checksum);

        $this->expectException(QueryException::class);

        ImportBatch::factory()->for($firstSource)->create(['checksum' => $checksum]);
    }

    public function test_one_reconciliation_run_is_allowed_for_one_batch_pair(): void
    {
        $internalBatch = ImportBatch::factory()->create();
        $providerBatch = ImportBatch::factory()->create();

        ReconciliationRun::factory()->create([
            'internal_batch_id' => $internalBatch->id,
            'provider_batch_id' => $providerBatch->id,
        ]);

        $this->expectException(QueryException::class);

        ReconciliationRun::factory()->create([
            'internal_batch_id' => $internalBatch->id,
            'provider_batch_id' => $providerBatch->id,
        ]);
    }

    public function test_deleting_run_removes_results_and_decisions(): void
    {
        $run = ReconciliationRun::factory()->create();
        $match = ReconciliationMatch::factory()->for($run)->create();
        $exception = ReconciliationException::factory()->for($run)->create();
        $decision = ReconciliationDecision::factory()->for($exception)->create();

        $run->delete();

        $this->assertModelMissing($match);
        $this->assertModelMissing($exception);
        $this->assertModelMissing($decision);
    }

    public function test_models_expose_typed_domain_states(): void
    {
        $batch = ImportBatch::factory()->create(['status' => ImportBatchStatus::Imported]);
        $run = ReconciliationRun::factory()->create(['status' => ReconciliationRunStatus::Completed]);
        $match = ReconciliationMatch::factory()->for($run)->create([
            'rule' => ReconciliationMatchRule::DateWindow,
        ]);
        $exception = ReconciliationException::factory()->for($run)->create([
            'type' => ReconciliationExceptionType::Ambiguous,
        ]);
        $decision = ReconciliationDecision::factory()->for($exception)->create([
            'action' => ReconciliationDecisionAction::KeepException,
        ]);

        $this->assertSame(ImportBatchStatus::Imported, $batch->status);
        $this->assertSame(ReconciliationRunStatus::Completed, $run->status);
        $this->assertSame(ReconciliationMatchRule::DateWindow, $match->rule);
        $this->assertSame(ReconciliationExceptionType::Ambiguous, $exception->type);
        $this->assertSame(ReconciliationDecisionAction::KeepException, $decision->action);
    }
}
