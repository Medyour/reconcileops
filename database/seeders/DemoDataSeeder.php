<?php

namespace Database\Seeders;

use App\DataSourceKind;
use App\ImportBatchStatus;
use App\Models\DataSource;
use App\Models\ImportBatch;
use App\Models\ReconciliationDecision;
use App\Models\ReconciliationException;
use App\Models\ReconciliationMatch;
use App\Models\ReconciliationRun;
use App\Models\SourceRecord;
use App\ReconciliationDecisionAction;
use App\ReconciliationExceptionStatus;
use App\ReconciliationExceptionType;
use App\ReconciliationMatchRule;
use App\ReconciliationRunStatus;
use Carbon\CarbonImmutable;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class DemoDataSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function (): void {
            $importedAt = CarbonImmutable::parse('2026-10-26 08:00:00', 'UTC');

            $internalSource = DataSource::query()->updateOrCreate(
                ['slug' => 'internal-journal'],
                [
                    'name' => 'Journal interne',
                    'kind' => DataSourceKind::Internal,
                    'description' => 'Journal opérationnel fictif produit par le système interne.',
                    'is_active' => true,
                ]
            );
            $providerSource = DataSource::query()->updateOrCreate(
                ['slug' => 'provider-statement'],
                [
                    'name' => 'Relevé prestataire',
                    'kind' => DataSourceKind::Provider,
                    'description' => 'Relevé fictif reçu depuis un prestataire simulé.',
                    'is_active' => true,
                ]
            );

            $internalBatch = $this->upsertBatch(
                $internalSource,
                'internal-journal-2026-10-26.csv',
                'reconcileops-demo-internal-v1',
                9,
                $importedAt
            );
            $providerBatch = $this->upsertBatch(
                $providerSource,
                'provider-statement-2026-10-26.csv',
                'reconcileops-demo-provider-v1',
                10,
                $importedAt->addMinute()
            );

            $internal = $this->upsertRecords($internalBatch, [
                'exact' => ['INT-001', 'TX-1001', '2026-10-01', 10_000, 'EUR'],
                'date-window' => ['INT-002', 'TX-1002', '2026-10-02', 25_000, 'EUR'],
                'amount-mismatch' => ['INT-003', 'TX-1003', '2026-10-03', 15_000, 'EUR'],
                'currency-mismatch' => ['INT-004', 'TX-1004', '2026-10-04', 20_000, 'EUR'],
                'duplicate-a' => ['INT-005-A', 'TX-DUP-I', '2026-10-05', 8_000, 'EUR'],
                'duplicate-b' => ['INT-005-B', 'TX-DUP-I', '2026-10-05', 8_000, 'EUR'],
                'missing-provider' => ['INT-006', 'TX-1006', '2026-10-06', 12_000, 'EUR'],
                'ambiguous' => ['INT-007', 'TX-AMB', '2026-10-07', 5_000, 'EUR'],
                'resolved' => ['INT-008', 'TX-RES', '2026-10-08', 7_500, 'EUR'],
            ]);
            $provider = $this->upsertRecords($providerBatch, [
                'exact' => ['PRO-001', 'TX-1001', '2026-10-01', 10_000, 'EUR'],
                'date-window' => ['PRO-002', 'TX-1002', '2026-10-03', 25_000, 'EUR'],
                'amount-mismatch' => ['PRO-003', 'TX-1003', '2026-10-03', 14_500, 'EUR'],
                'currency-mismatch' => ['PRO-004', 'TX-1004', '2026-10-04', 20_000, 'USD'],
                'duplicate-a' => ['PRO-005-A', 'TX-DUP-P', '2026-10-05', 9_000, 'EUR'],
                'duplicate-b' => ['PRO-005-B', 'TX-DUP-P', '2026-10-05', 9_000, 'EUR'],
                'missing-internal' => ['PRO-006', 'TX-1009', '2026-10-06', 11_000, 'EUR'],
                'ambiguous-a' => ['PRO-007-A', 'TX-AMB', '2026-10-07', 5_000, 'EUR'],
                'ambiguous-b' => ['PRO-007-B', 'TX-AMB', '2026-10-07', 5_000, 'EUR'],
                'resolved' => ['PRO-008', 'TX-RES', '2026-10-09', 7_500, 'EUR'],
            ]);

            $run = ReconciliationRun::query()->firstOrNew([
                'internal_batch_id' => $internalBatch->id,
                'provider_batch_id' => $providerBatch->id,
            ]);
            if (! $run->exists) {
                $run->public_id = (string) Str::ulid();
            }
            $run->fill([
                'status' => ReconciliationRunStatus::Completed,
                'exact_matches_count' => 1,
                'tolerant_matches_count' => 1,
                'exceptions_count' => 8,
                'started_at' => $importedAt->addMinutes(2),
                'completed_at' => $importedAt->addMinutes(2)->addSeconds(4),
                'metadata' => ['rule_version' => 'v1', 'demo' => true],
            ])->save();

            ReconciliationMatch::query()->updateOrCreate(
                [
                    'reconciliation_run_id' => $run->id,
                    'internal_record_id' => $internal['exact']->id,
                ],
                [
                    'provider_record_id' => $provider['exact']->id,
                    'rule' => ReconciliationMatchRule::ExactReference,
                    'amount_difference_minor' => 0,
                    'date_difference_days' => 0,
                    'explanation' => 'Référence, montant, devise et date identiques.',
                ]
            );
            ReconciliationMatch::query()->updateOrCreate(
                [
                    'reconciliation_run_id' => $run->id,
                    'internal_record_id' => $internal['date-window']->id,
                ],
                [
                    'provider_record_id' => $provider['date-window']->id,
                    'rule' => ReconciliationMatchRule::DateWindow,
                    'amount_difference_minor' => 0,
                    'date_difference_days' => 1,
                    'explanation' => 'Même référence, montant et devise avec un décalage de date autorisé d’un jour.',
                ]
            );

            $this->upsertException(
                $run,
                ReconciliationExceptionType::AmountMismatch,
                $internal['amount-mismatch'],
                $provider['amount-mismatch'],
                'La ligne prestataire présente un écart fictif de 5,00 EUR.'
            );
            $this->upsertException(
                $run,
                ReconciliationExceptionType::CurrencyMismatch,
                $internal['currency-mismatch'],
                $provider['currency-mismatch'],
                'Les devises normalisées diffèrent : EUR contre USD.'
            );
            $this->upsertException(
                $run,
                ReconciliationExceptionType::DuplicateInternal,
                $internal['duplicate-a'],
                null,
                'Deux lignes internes partagent la même empreinte normalisée.'
            );
            $this->upsertException(
                $run,
                ReconciliationExceptionType::DuplicateProvider,
                null,
                $provider['duplicate-a'],
                'Deux lignes prestataire partagent la même empreinte normalisée.'
            );
            $this->upsertException(
                $run,
                ReconciliationExceptionType::MissingInternal,
                null,
                $provider['missing-internal'],
                'Aucune ligne interne ne correspond à cette ligne prestataire.'
            );
            $this->upsertException(
                $run,
                ReconciliationExceptionType::MissingProvider,
                $internal['missing-provider'],
                null,
                'Aucune ligne prestataire ne correspond à cette ligne interne.'
            );
            $this->upsertException(
                $run,
                ReconciliationExceptionType::Ambiguous,
                $internal['ambiguous'],
                $provider['ambiguous-a'],
                'Deux lignes prestataire sont des candidates équivalentes.'
            );
            $resolvedException = $this->upsertException(
                $run,
                ReconciliationExceptionType::ManualReview,
                $internal['resolved'],
                $provider['resolved'],
                'Le décalage de date dépasse la fenêtre automatique et nécessite une décision simulée.',
                ReconciliationExceptionStatus::Resolved,
                $importedAt->addMinutes(10)
            );

            ReconciliationDecision::query()->updateOrCreate(
                [
                    'reconciliation_exception_id' => $resolvedException->id,
                    'action' => ReconciliationDecisionAction::AcceptMatch->value,
                ],
                [
                    'decided_by' => 'demo-reviewer',
                    'explanation' => 'Décision fictive : la référence commune justifie le rapprochement manuel.',
                    'decided_at' => $importedAt->addMinutes(10),
                    'metadata' => ['demo' => true],
                ]
            );
        });
    }

    private function upsertBatch(
        DataSource $source,
        string $filename,
        string $checksumSeed,
        int $recordCount,
        CarbonImmutable $importedAt
    ): ImportBatch {
        $checksum = hash('sha256', $checksumSeed);
        $batch = ImportBatch::query()->firstOrNew([
            'data_source_id' => $source->id,
            'checksum' => $checksum,
        ]);
        if (! $batch->exists) {
            $batch->public_id = (string) Str::ulid();
        }
        $batch->fill([
            'source_filename' => $filename,
            'status' => ImportBatchStatus::Imported,
            'record_count' => $recordCount,
            'rejected_count' => 0,
            'imported_at' => $importedAt,
            'metadata' => ['encoding' => 'UTF-8', 'demo' => true],
        ])->save();

        return $batch;
    }

    /**
     * @param  array<string, array{string, string, string, int, string}>  $definitions
     * @return array<string, SourceRecord>
     */
    private function upsertRecords(ImportBatch $batch, array $definitions): array
    {
        $records = [];

        foreach ($definitions as $key => [$recordKey, $reference, $date, $amountMinor, $currency]) {
            $fingerprint = hash('sha256', implode('|', [$reference, $date, $amountMinor, $currency]));
            $records[$key] = SourceRecord::query()->updateOrCreate(
                ['import_batch_id' => $batch->id, 'record_key' => $recordKey],
                [
                    'external_reference' => $reference,
                    'secondary_reference' => null,
                    'occurred_on' => $date,
                    'amount_minor' => $amountMinor,
                    'currency' => $currency,
                    'normalized_reference' => $reference,
                    'fingerprint' => $fingerprint,
                    'raw_payload' => [
                        'reference' => $reference,
                        'date' => $date,
                        'amount_minor' => $amountMinor,
                        'currency' => $currency,
                    ],
                ]
            );
        }

        return $records;
    }

    private function upsertException(
        ReconciliationRun $run,
        ReconciliationExceptionType $type,
        ?SourceRecord $internalRecord,
        ?SourceRecord $providerRecord,
        string $explanation,
        ReconciliationExceptionStatus $status = ReconciliationExceptionStatus::Open,
        ?CarbonImmutable $resolvedAt = null
    ): ReconciliationException {
        return ReconciliationException::query()->updateOrCreate(
            [
                'reconciliation_run_id' => $run->id,
                'type' => $type->value,
                'internal_record_id' => $internalRecord?->id,
                'provider_record_id' => $providerRecord?->id,
            ],
            [
                'status' => $status,
                'explanation' => $explanation,
                'resolved_at' => $resolvedAt,
            ]
        );
    }
}
