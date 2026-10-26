<?php

namespace App\Actions;

use App\DataSourceKind;
use App\Exceptions\InvalidReconciliationRun;
use App\ImportBatchStatus;
use App\Models\ImportBatch;
use App\Models\ReconciliationException;
use App\Models\ReconciliationMatch;
use App\Models\ReconciliationRun;
use App\Models\SourceRecord;
use App\ReconciliationExceptionStatus;
use App\ReconciliationExceptionType;
use App\ReconciliationMatchRule;
use App\ReconciliationRunStatus;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class RunReconciliation
{
    public function handle(ImportBatch $internalBatch, ImportBatch $providerBatch): ReconciliationRun
    {
        $this->validateBatches($internalBatch, $providerBatch);

        return DB::transaction(function () use ($internalBatch, $providerBatch): ReconciliationRun {
            $dateWindowDays = (int) config('reconcileops.date_window_days');
            $run = ReconciliationRun::query()->firstOrCreate(
                [
                    'internal_batch_id' => $internalBatch->id,
                    'provider_batch_id' => $providerBatch->id,
                ],
                [
                    'public_id' => (string) Str::ulid(),
                    'status' => ReconciliationRunStatus::Pending,
                    'metadata' => [
                        'rule_version' => 'v1',
                        'date_window_days' => $dateWindowDays,
                    ],
                ]
            );

            if (! $run->wasRecentlyCreated) {
                return $run;
            }

            $run->update([
                'status' => ReconciliationRunStatus::Running,
                'started_at' => now(),
            ]);

            $internalRecords = $internalBatch->sourceRecords()->orderBy('id')->get();
            $providerRecords = $providerBatch->sourceRecords()->orderBy('id')->get();
            $blockedInternalIds = $this->recordDuplicateExceptions(
                $run,
                $internalRecords,
                ReconciliationExceptionType::DuplicateInternal
            );
            $blockedProviderIds = $this->recordDuplicateExceptions(
                $run,
                $providerRecords,
                ReconciliationExceptionType::DuplicateProvider
            );
            $internalRecords = $this->sortByBestRule(
                $internalRecords,
                $providerRecords,
                $blockedProviderIds,
                $dateWindowDays
            );
            $matchedProviderIds = [];
            $handledProviderIds = [];

            foreach ($internalRecords as $internalRecord) {
                if (isset($blockedInternalIds[$internalRecord->id])) {
                    continue;
                }

                $availableProviders = $providerRecords->reject(
                    static fn (SourceRecord $providerRecord): bool => isset($blockedProviderIds[$providerRecord->id])
                        || isset($matchedProviderIds[$providerRecord->id])
                        || isset($handledProviderIds[$providerRecord->id])
                );

                $exactCandidates = $availableProviders->filter(
                    fn (SourceRecord $providerRecord): bool => $this->sameReference($internalRecord, $providerRecord)
                        && $internalRecord->amount_minor === $providerRecord->amount_minor
                        && $internalRecord->currency === $providerRecord->currency
                        && $this->dateDifference($internalRecord, $providerRecord) === 0
                );

                if ($this->recordAmbiguityWhenNeeded($run, $internalRecord, $exactCandidates, $handledProviderIds)) {
                    continue;
                }

                if ($exactCandidates->count() === 1) {
                    $providerRecord = $exactCandidates->first();
                    $this->createMatch(
                        $run,
                        $internalRecord,
                        $providerRecord,
                        ReconciliationMatchRule::ExactReference,
                        'Référence, montant, devise et date identiques.'
                    );
                    $matchedProviderIds[$providerRecord->id] = true;

                    continue;
                }

                $dateCandidates = $availableProviders->filter(
                    fn (SourceRecord $providerRecord): bool => $this->sameReference($internalRecord, $providerRecord)
                        && $internalRecord->amount_minor === $providerRecord->amount_minor
                        && $internalRecord->currency === $providerRecord->currency
                        && $this->dateDifference($internalRecord, $providerRecord) <= $dateWindowDays
                );

                if ($this->recordAmbiguityWhenNeeded($run, $internalRecord, $dateCandidates, $handledProviderIds)) {
                    continue;
                }

                if ($dateCandidates->count() === 1) {
                    $providerRecord = $dateCandidates->first();
                    $this->createMatch(
                        $run,
                        $internalRecord,
                        $providerRecord,
                        ReconciliationMatchRule::DateWindow,
                        "Même référence, montant et devise dans la fenêtre autorisée de {$dateWindowDays} jour(s)."
                    );
                    $matchedProviderIds[$providerRecord->id] = true;

                    continue;
                }

                $compositeCandidates = $availableProviders->filter(
                    fn (SourceRecord $providerRecord): bool => $internalRecord->secondary_reference !== null
                        && $internalRecord->secondary_reference === $providerRecord->secondary_reference
                        && $internalRecord->amount_minor === $providerRecord->amount_minor
                        && $internalRecord->currency === $providerRecord->currency
                        && $this->dateDifference($internalRecord, $providerRecord) <= $dateWindowDays
                );

                if ($this->recordAmbiguityWhenNeeded($run, $internalRecord, $compositeCandidates, $handledProviderIds)) {
                    continue;
                }

                if ($compositeCandidates->count() === 1) {
                    $providerRecord = $compositeCandidates->first();
                    $this->createMatch(
                        $run,
                        $internalRecord,
                        $providerRecord,
                        ReconciliationMatchRule::Composite,
                        'Référence secondaire, montant, devise et fenêtre de date compatibles.'
                    );
                    $matchedProviderIds[$providerRecord->id] = true;

                    continue;
                }

                $sameReferenceCandidates = $availableProviders->filter(
                    fn (SourceRecord $providerRecord): bool => $this->sameReference($internalRecord, $providerRecord)
                );

                if ($this->recordAmbiguityWhenNeeded(
                    $run,
                    $internalRecord,
                    $sameReferenceCandidates,
                    $handledProviderIds
                )) {
                    continue;
                }

                if ($sameReferenceCandidates->count() === 1) {
                    $providerRecord = $sameReferenceCandidates->first();
                    $type = $this->differenceType($internalRecord, $providerRecord);
                    $this->createException(
                        $run,
                        $type,
                        $internalRecord,
                        $providerRecord,
                        $this->differenceExplanation($type)
                    );
                    $handledProviderIds[$providerRecord->id] = true;

                    continue;
                }

                $this->createException(
                    $run,
                    ReconciliationExceptionType::MissingProvider,
                    $internalRecord,
                    null,
                    'Aucune ligne prestataire disponible ne correspond à cette ligne interne.'
                );
            }

            foreach ($providerRecords as $providerRecord) {
                if (
                    isset($blockedProviderIds[$providerRecord->id])
                    || isset($matchedProviderIds[$providerRecord->id])
                    || isset($handledProviderIds[$providerRecord->id])
                ) {
                    continue;
                }

                $this->createException(
                    $run,
                    ReconciliationExceptionType::MissingInternal,
                    null,
                    $providerRecord,
                    'Aucune ligne interne disponible ne correspond à cette ligne prestataire.'
                );
            }

            $exactMatchesCount = $run->matches()
                ->where('rule', ReconciliationMatchRule::ExactReference->value)
                ->count();
            $totalMatchesCount = $run->matches()->count();

            $run->update([
                'status' => ReconciliationRunStatus::Completed,
                'exact_matches_count' => $exactMatchesCount,
                'tolerant_matches_count' => $totalMatchesCount - $exactMatchesCount,
                'exceptions_count' => $run->exceptions()->count(),
                'completed_at' => now(),
            ]);

            return $run->fresh(['matches', 'exceptions']);
        });
    }

    private function validateBatches(ImportBatch $internalBatch, ImportBatch $providerBatch): void
    {
        $internalBatch->loadMissing('dataSource');
        $providerBatch->loadMissing('dataSource');

        if ($internalBatch->is($providerBatch)) {
            throw new InvalidReconciliationRun('Les deux lots doivent être distincts.');
        }

        if ($internalBatch->dataSource->kind !== DataSourceKind::Internal) {
            throw new InvalidReconciliationRun('Le premier lot doit appartenir à une source interne.');
        }

        if ($providerBatch->dataSource->kind !== DataSourceKind::Provider) {
            throw new InvalidReconciliationRun('Le second lot doit appartenir à une source prestataire.');
        }

        if (
            $internalBatch->status !== ImportBatchStatus::Imported
            || $providerBatch->status !== ImportBatchStatus::Imported
        ) {
            throw new InvalidReconciliationRun('Les deux lots doivent être entièrement importés.');
        }

        if ($internalBatch->sourceRecords()->doesntExist() || $providerBatch->sourceRecords()->doesntExist()) {
            throw new InvalidReconciliationRun('Les deux lots doivent contenir au moins une ligne.');
        }
    }

    /**
     * @param  Collection<int, SourceRecord>  $records
     * @return array<int, true>
     */
    private function recordDuplicateExceptions(
        ReconciliationRun $run,
        Collection $records,
        ReconciliationExceptionType $type
    ): array {
        $blockedIds = [];

        foreach ($records->groupBy('fingerprint') as $duplicateRecords) {
            if ($duplicateRecords->count() < 2) {
                continue;
            }

            foreach ($duplicateRecords as $record) {
                $blockedIds[$record->id] = true;
                $this->createException(
                    $run,
                    $type,
                    $type === ReconciliationExceptionType::DuplicateInternal ? $record : null,
                    $type === ReconciliationExceptionType::DuplicateProvider ? $record : null,
                    "Cette empreinte apparaît {$duplicateRecords->count()} fois dans la même source."
                );
            }
        }

        return $blockedIds;
    }

    /**
     * @param  Collection<int, SourceRecord>  $internalRecords
     * @param  Collection<int, SourceRecord>  $providerRecords
     * @param  array<int, true>  $blockedProviderIds
     * @return Collection<int, SourceRecord>
     */
    private function sortByBestRule(
        Collection $internalRecords,
        Collection $providerRecords,
        array $blockedProviderIds,
        int $dateWindowDays
    ): Collection {
        return $internalRecords->sort(function (
            SourceRecord $first,
            SourceRecord $second
        ) use ($providerRecords, $blockedProviderIds, $dateWindowDays): int {
            $firstRank = $this->bestRuleRank($first, $providerRecords, $blockedProviderIds, $dateWindowDays);
            $secondRank = $this->bestRuleRank($second, $providerRecords, $blockedProviderIds, $dateWindowDays);

            return ($firstRank <=> $secondRank) ?: ($first->id <=> $second->id);
        })->values();
    }

    /**
     * @param  Collection<int, SourceRecord>  $providerRecords
     * @param  array<int, true>  $blockedProviderIds
     */
    private function bestRuleRank(
        SourceRecord $internalRecord,
        Collection $providerRecords,
        array $blockedProviderIds,
        int $dateWindowDays
    ): int {
        $availableProviders = $providerRecords->reject(
            static fn (SourceRecord $providerRecord): bool => isset($blockedProviderIds[$providerRecord->id])
        );

        $hasExactCandidate = $availableProviders->contains(
            fn (SourceRecord $providerRecord): bool => $this->sameReference($internalRecord, $providerRecord)
                && $internalRecord->amount_minor === $providerRecord->amount_minor
                && $internalRecord->currency === $providerRecord->currency
                && $this->dateDifference($internalRecord, $providerRecord) === 0
        );

        if ($hasExactCandidate) {
            return 0;
        }

        $hasDateCandidate = $availableProviders->contains(
            fn (SourceRecord $providerRecord): bool => $this->sameReference($internalRecord, $providerRecord)
                && $internalRecord->amount_minor === $providerRecord->amount_minor
                && $internalRecord->currency === $providerRecord->currency
                && $this->dateDifference($internalRecord, $providerRecord) <= $dateWindowDays
        );

        if ($hasDateCandidate) {
            return 1;
        }

        $hasCompositeCandidate = $availableProviders->contains(
            fn (SourceRecord $providerRecord): bool => $internalRecord->secondary_reference !== null
                && $internalRecord->secondary_reference === $providerRecord->secondary_reference
                && $internalRecord->amount_minor === $providerRecord->amount_minor
                && $internalRecord->currency === $providerRecord->currency
                && $this->dateDifference($internalRecord, $providerRecord) <= $dateWindowDays
        );

        return $hasCompositeCandidate ? 2 : 3;
    }

    /**
     * @param  Collection<int, SourceRecord>  $candidates
     * @param  array<int, true>  $handledProviderIds
     */
    private function recordAmbiguityWhenNeeded(
        ReconciliationRun $run,
        SourceRecord $internalRecord,
        Collection $candidates,
        array &$handledProviderIds
    ): bool {
        if ($candidates->count() < 2) {
            return false;
        }

        foreach ($candidates as $candidate) {
            $handledProviderIds[$candidate->id] = true;
        }

        $this->createException(
            $run,
            ReconciliationExceptionType::Ambiguous,
            $internalRecord,
            $candidates->first(),
            "{$candidates->count()} lignes prestataire sont des candidates équivalentes."
        );

        return true;
    }

    private function createMatch(
        ReconciliationRun $run,
        SourceRecord $internalRecord,
        SourceRecord $providerRecord,
        ReconciliationMatchRule $rule,
        string $explanation
    ): ReconciliationMatch {
        return $run->matches()->create([
            'internal_record_id' => $internalRecord->id,
            'provider_record_id' => $providerRecord->id,
            'rule' => $rule,
            'amount_difference_minor' => $providerRecord->amount_minor - $internalRecord->amount_minor,
            'date_difference_days' => $this->dateDifference($internalRecord, $providerRecord),
            'explanation' => $explanation,
        ]);
    }

    private function createException(
        ReconciliationRun $run,
        ReconciliationExceptionType $type,
        ?SourceRecord $internalRecord,
        ?SourceRecord $providerRecord,
        string $explanation
    ): ReconciliationException {
        return $run->exceptions()->create([
            'internal_record_id' => $internalRecord?->id,
            'provider_record_id' => $providerRecord?->id,
            'type' => $type,
            'status' => ReconciliationExceptionStatus::Open,
            'explanation' => $explanation,
        ]);
    }

    private function sameReference(SourceRecord $internalRecord, SourceRecord $providerRecord): bool
    {
        return $internalRecord->normalized_reference !== null
            && $internalRecord->normalized_reference === $providerRecord->normalized_reference;
    }

    private function dateDifference(SourceRecord $internalRecord, SourceRecord $providerRecord): int
    {
        return (int) round($internalRecord->occurred_on->diffInDays($providerRecord->occurred_on));
    }

    private function differenceType(
        SourceRecord $internalRecord,
        SourceRecord $providerRecord
    ): ReconciliationExceptionType {
        if ($internalRecord->currency !== $providerRecord->currency) {
            return ReconciliationExceptionType::CurrencyMismatch;
        }

        if ($internalRecord->amount_minor !== $providerRecord->amount_minor) {
            return ReconciliationExceptionType::AmountMismatch;
        }

        return ReconciliationExceptionType::ManualReview;
    }

    private function differenceExplanation(ReconciliationExceptionType $type): string
    {
        return match ($type) {
            ReconciliationExceptionType::CurrencyMismatch => 'Les devises normalisées diffèrent.',
            ReconciliationExceptionType::AmountMismatch => 'Les montants en unités mineures diffèrent.',
            ReconciliationExceptionType::ManualReview => 'La référence correspond, mais la date dépasse la fenêtre autorisée.',
            default => 'La paire nécessite une intervention.',
        };
    }
}
