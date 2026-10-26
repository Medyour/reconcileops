<?php

namespace App\Models;

use App\ReconciliationMatchRule;
use Database\Factories\ReconciliationMatchFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'reconciliation_run_id',
    'internal_record_id',
    'provider_record_id',
    'rule',
    'amount_difference_minor',
    'date_difference_days',
    'explanation',
])]
class ReconciliationMatch extends Model
{
    /** @use HasFactory<ReconciliationMatchFactory> */
    use HasFactory;

    public function reconciliationRun(): BelongsTo
    {
        return $this->belongsTo(ReconciliationRun::class);
    }

    public function internalRecord(): BelongsTo
    {
        return $this->belongsTo(SourceRecord::class, 'internal_record_id');
    }

    public function providerRecord(): BelongsTo
    {
        return $this->belongsTo(SourceRecord::class, 'provider_record_id');
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'rule' => ReconciliationMatchRule::class,
            'amount_difference_minor' => 'integer',
            'date_difference_days' => 'integer',
        ];
    }
}
