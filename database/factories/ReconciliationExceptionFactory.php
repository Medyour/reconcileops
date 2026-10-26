<?php

namespace Database\Factories;

use App\Models\ReconciliationException;
use App\Models\ReconciliationRun;
use App\ReconciliationExceptionStatus;
use App\ReconciliationExceptionType;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ReconciliationException>
 */
class ReconciliationExceptionFactory extends Factory
{
    public function definition(): array
    {
        return [
            'reconciliation_run_id' => ReconciliationRun::factory(),
            'internal_record_id' => null,
            'provider_record_id' => null,
            'type' => ReconciliationExceptionType::ManualReview,
            'status' => ReconciliationExceptionStatus::Open,
            'explanation' => fake()->sentence(),
            'resolved_at' => null,
        ];
    }
}
