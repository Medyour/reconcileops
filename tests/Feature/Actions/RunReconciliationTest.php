<?php

namespace Tests\Feature\Actions;

use App\Actions\RunReconciliation;
use App\DataSourceKind;
use App\Exceptions\InvalidReconciliationRun;
use App\ImportBatchStatus;
use App\Models\DataSource;
use App\Models\ImportBatch;
use App\Models\SourceRecord;
use App\ReconciliationExceptionType;
use App\ReconciliationMatchRule;
use App\ReconciliationRunStatus;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class RunReconciliationTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_it_applies_rules_in_order_and_classifies_remaining_differences(): void
    {
        config()->set('reconcileops.date_window_days', 1);
        $internalBatch = $this->batch(DataSourceKind::Internal);
        $providerBatch = $this->batch(DataSourceKind::Provider);

        $this->record($internalBatch, 'INT-1', 'TX-1', '2026-10-01', 1_000, 'EUR');
        $this->record($providerBatch, 'PRO-1', 'TX-1', '2026-10-01', 1_000, 'EUR');
        $this->record($internalBatch, 'INT-2', 'TX-2', '2026-10-02', 2_000, 'EUR');
        $this->record($providerBatch, 'PRO-2', 'TX-2', '2026-10-03', 2_000, 'EUR');
        $this->record($internalBatch, 'INT-3', 'INT-C', '2026-10-04', 3_000, 'EUR', 'SECONDARY-1');
        $this->record($providerBatch, 'PRO-3', 'PRO-C', '2026-10-04', 3_000, 'EUR', 'SECONDARY-1');
        $this->record($internalBatch, 'INT-4', 'TX-4', '2026-10-05', 4_000, 'EUR');
        $this->record($providerBatch, 'PRO-4', 'TX-4', '2026-10-05', 3_900, 'EUR');
        $this->record($internalBatch, 'INT-5', 'TX-5', '2026-10-06', 5_000, 'EUR');
        $this->record($providerBatch, 'PRO-5', 'TX-5', '2026-10-06', 5_000, 'USD');
        $this->record($internalBatch, 'INT-6', 'TX-6', '2026-10-01', 6_000, 'EUR');
        $this->record($providerBatch, 'PRO-6', 'TX-6', '2026-10-05', 6_000, 'EUR');
        $this->record($internalBatch, 'INT-7', 'TX-7', '2026-10-07', 7_000, 'EUR');
        $this->record($providerBatch, 'PRO-8', 'TX-8', '2026-10-08', 8_000, 'EUR');

        $run = app(RunReconciliation::class)->handle($internalBatch, $providerBatch);

        $this->assertSame(ReconciliationRunStatus::Completed, $run->status);
        $this->assertSame(1, $run->exact_matches_count);
        $this->assertSame(2, $run->tolerant_matches_count);
        $this->assertSame(5, $run->exceptions_count);
        $this->assertCount(3, $run->matches);
        $this->assertCount(5, $run->exceptions);

        $rules = $run->matches->map(
            static fn ($match): string => $match->rule->value
        )->sort()->values()->all();
        $this->assertSame([
            ReconciliationMatchRule::Composite->value,
            ReconciliationMatchRule::DateWindow->value,
            ReconciliationMatchRule::ExactReference->value,
        ], $rules);

        $types = $run->exceptions->map(
            static fn ($exception): string => $exception->type->value
        )->sort()->values()->all();
        $this->assertSame([
            ReconciliationExceptionType::AmountMismatch->value,
            ReconciliationExceptionType::CurrencyMismatch->value,
            ReconciliationExceptionType::ManualReview->value,
            ReconciliationExceptionType::MissingInternal->value,
            ReconciliationExceptionType::MissingProvider->value,
        ], $types);
    }

    public function test_it_excludes_duplicates_and_records_ambiguous_candidates(): void
    {
        $internalBatch = $this->batch(DataSourceKind::Internal);
        $providerBatch = $this->batch(DataSourceKind::Provider);
        $duplicateInternalFingerprint = hash('sha256', 'duplicate-internal');
        $duplicateProviderFingerprint = hash('sha256', 'duplicate-provider');

        $this->record(
            $internalBatch,
            'INT-DUP-1',
            'TX-DUP-I',
            '2026-10-01',
            1_000,
            'EUR',
            fingerprint: $duplicateInternalFingerprint
        );
        $this->record(
            $internalBatch,
            'INT-DUP-2',
            'TX-DUP-I',
            '2026-10-01',
            1_000,
            'EUR',
            fingerprint: $duplicateInternalFingerprint
        );
        $this->record(
            $providerBatch,
            'PRO-DUP-1',
            'TX-DUP-P',
            '2026-10-01',
            2_000,
            'EUR',
            fingerprint: $duplicateProviderFingerprint
        );
        $this->record(
            $providerBatch,
            'PRO-DUP-2',
            'TX-DUP-P',
            '2026-10-01',
            2_000,
            'EUR',
            fingerprint: $duplicateProviderFingerprint
        );
        $this->record($internalBatch, 'INT-AMB', 'TX-AMB', '2026-10-02', 3_000, 'EUR');
        $this->record($providerBatch, 'PRO-AMB-1', 'TX-AMB', '2026-10-02', 2_900, 'EUR');
        $this->record($providerBatch, 'PRO-AMB-2', 'TX-AMB', '2026-10-02', 3_100, 'EUR');

        $run = app(RunReconciliation::class)->handle($internalBatch, $providerBatch);

        $this->assertSame(0, $run->matches()->count());
        $this->assertSame(5, $run->exceptions_count);
        $this->assertSame(
            2,
            $run->exceptions()->where('type', ReconciliationExceptionType::DuplicateInternal->value)->count()
        );
        $this->assertSame(
            2,
            $run->exceptions()->where('type', ReconciliationExceptionType::DuplicateProvider->value)->count()
        );
        $this->assertSame(
            1,
            $run->exceptions()->where('type', ReconciliationExceptionType::Ambiguous->value)->count()
        );
    }

    public function test_running_the_same_batch_pair_twice_returns_the_existing_run(): void
    {
        $internalBatch = $this->batch(DataSourceKind::Internal);
        $providerBatch = $this->batch(DataSourceKind::Provider);
        $this->record($internalBatch, 'INT-1', 'TX-1', '2026-10-01', 1_000, 'EUR');
        $this->record($providerBatch, 'PRO-1', 'TX-1', '2026-10-01', 1_000, 'EUR');
        $action = app(RunReconciliation::class);

        $firstRun = $action->handle($internalBatch, $providerBatch);
        $secondRun = $action->handle($internalBatch, $providerBatch);

        $this->assertTrue($firstRun->is($secondRun));
        $this->assertDatabaseCount('reconciliation_runs', 1);
        $this->assertDatabaseCount('reconciliation_matches', 1);
        $this->assertDatabaseCount('reconciliation_exceptions', 0);
    }

    public function test_lower_priority_difference_cannot_preempt_a_later_exact_match(): void
    {
        $internalBatch = $this->batch(DataSourceKind::Internal);
        $providerBatch = $this->batch(DataSourceKind::Provider);
        $this->record($internalBatch, 'INT-DIFFERENCE', 'TX-SHARED', '2026-10-01', 1_000, 'EUR');
        $this->record($internalBatch, 'INT-EXACT', 'TX-SHARED', '2026-10-02', 2_000, 'EUR');
        $this->record($providerBatch, 'PRO-EXACT', 'TX-SHARED', '2026-10-02', 2_000, 'EUR');
        $this->record($providerBatch, 'PRO-DIFFERENCE', 'TX-SHARED', '2026-10-05', 1_000, 'EUR');

        $run = app(RunReconciliation::class)->handle($internalBatch, $providerBatch);

        $this->assertSame(1, $run->exact_matches_count);
        $this->assertSame(0, $run->tolerant_matches_count);
        $this->assertSame(1, $run->exceptions_count);
        $this->assertDatabaseHas('reconciliation_matches', [
            'reconciliation_run_id' => $run->id,
            'rule' => ReconciliationMatchRule::ExactReference->value,
        ]);
        $this->assertDatabaseHas('reconciliation_exceptions', [
            'reconciliation_run_id' => $run->id,
            'type' => ReconciliationExceptionType::ManualReview->value,
        ]);
        $this->assertDatabaseMissing('reconciliation_exceptions', [
            'reconciliation_run_id' => $run->id,
            'type' => ReconciliationExceptionType::Ambiguous->value,
        ]);
    }

    public function test_it_rejects_wrong_source_order_or_incomplete_batches(): void
    {
        $internalBatch = $this->batch(DataSourceKind::Internal);
        $providerBatch = $this->batch(DataSourceKind::Provider);
        $this->record($internalBatch, 'INT-1', 'TX-1', '2026-10-01', 1_000, 'EUR');
        $this->record($providerBatch, 'PRO-1', 'TX-1', '2026-10-01', 1_000, 'EUR');

        try {
            app(RunReconciliation::class)->handle($providerBatch, $internalBatch);
            $this->fail('L’ordre incorrect des sources aurait dû être refusé.');
        } catch (InvalidReconciliationRun $exception) {
            $this->assertStringContainsString('source interne', $exception->getMessage());
        }

        $internalBatch->update(['status' => ImportBatchStatus::Pending]);

        try {
            app(RunReconciliation::class)->handle($internalBatch, $providerBatch);
            $this->fail('Un lot incomplet aurait dû être refusé.');
        } catch (InvalidReconciliationRun $exception) {
            $this->assertStringContainsString('entièrement importés', $exception->getMessage());
        }

        $this->assertDatabaseCount('reconciliation_runs', 0);
    }

    private function batch(DataSourceKind $kind): ImportBatch
    {
        $source = DataSource::factory()->create(['kind' => $kind]);

        return ImportBatch::factory()->for($source)->create([
            'status' => ImportBatchStatus::Imported,
            'record_count' => 0,
        ]);
    }

    private function record(
        ImportBatch $batch,
        string $recordKey,
        string $reference,
        string $occurredOn,
        int $amountMinor,
        string $currency,
        ?string $secondaryReference = null,
        ?string $fingerprint = null
    ): SourceRecord {
        return SourceRecord::factory()->for($batch)->create([
            'record_key' => $recordKey,
            'external_reference' => $reference,
            'secondary_reference' => $secondaryReference,
            'occurred_on' => $occurredOn,
            'amount_minor' => $amountMinor,
            'currency' => $currency,
            'normalized_reference' => $reference,
            'fingerprint' => $fingerprint ?? hash(
                'sha256',
                implode('|', [$reference, $occurredOn, $amountMinor, $currency])
            ),
            'raw_payload' => ['record_key' => $recordKey],
        ]);
    }
}
