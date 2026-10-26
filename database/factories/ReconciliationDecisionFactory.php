<?php

namespace Database\Factories;

use App\Models\ReconciliationDecision;
use App\Models\ReconciliationException;
use App\ReconciliationDecisionAction;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ReconciliationDecision>
 */
class ReconciliationDecisionFactory extends Factory
{
    public function definition(): array
    {
        return [
            'reconciliation_exception_id' => ReconciliationException::factory(),
            'action' => ReconciliationDecisionAction::KeepException,
            'decided_by' => 'demo-reviewer',
            'explanation' => fake()->sentence(),
            'decided_at' => now(),
            'metadata' => ['environment' => 'testing'],
        ];
    }
}
