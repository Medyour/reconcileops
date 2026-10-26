<?php

namespace App\Models;

use App\ReconciliationExceptionStatus;
use App\ReconciliationExceptionType;
use Database\Factories\ReconciliationExceptionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'reconciliation_run_id',
    'internal_record_id',
    'provider_record_id',
    'type',
    'status',
    'explanation',
    'resolved_at',
])]
class ReconciliationException extends Model
{
    /** @use HasFactory<ReconciliationExceptionFactory> */
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

    public function decisions(): HasMany
    {
        return $this->hasMany(ReconciliationDecision::class);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => ReconciliationExceptionType::class,
            'status' => ReconciliationExceptionStatus::class,
            'resolved_at' => 'datetime',
        ];
    }
}
