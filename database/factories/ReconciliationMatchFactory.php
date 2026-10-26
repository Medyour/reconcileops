<?php

namespace Database\Factories;

use App\Models\ReconciliationMatch;
use App\Models\ReconciliationRun;
use App\Models\SourceRecord;
use App\ReconciliationMatchRule;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ReconciliationMatch>
 */
class ReconciliationMatchFactory extends Factory
{
    public function definition(): array
    {
        return [
            'reconciliation_run_id' => ReconciliationRun::factory(),
            'internal_record_id' => SourceRecord::factory(),
            'provider_record_id' => SourceRecord::factory(),
            'rule' => ReconciliationMatchRule::ExactReference,
            'amount_difference_minor' => 0,
            'date_difference_days' => 0,
            'explanation' => 'Les références, montants et devises normalisés sont identiques.',
        ];
    }
}
