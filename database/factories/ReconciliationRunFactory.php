<?php

namespace Database\Factories;

use App\Models\ImportBatch;
use App\Models\ReconciliationRun;
use App\ReconciliationRunStatus;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<ReconciliationRun>
 */
class ReconciliationRunFactory extends Factory
{
    public function definition(): array
    {
        return [
            'public_id' => (string) Str::ulid(),
            'internal_batch_id' => ImportBatch::factory(),
            'provider_batch_id' => ImportBatch::factory(),
            'status' => ReconciliationRunStatus::Completed,
            'exact_matches_count' => 0,
            'tolerant_matches_count' => 0,
            'exceptions_count' => 0,
            'started_at' => now()->subSecond(),
            'completed_at' => now(),
            'metadata' => ['rule_version' => 'v1'],
        ];
    }
}
