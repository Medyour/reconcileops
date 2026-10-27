<?php

namespace App\Models;

use App\ReconciliationRunStatus;
use Database\Factories\ReconciliationRunFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'public_id',
    'internal_batch_id',
    'provider_batch_id',
    'status',
    'exact_matches_count',
    'tolerant_matches_count',
    'exceptions_count',
    'started_at',
    'completed_at',
    'metadata',
])]
class ReconciliationRun extends Model
{
    /** @use HasFactory<ReconciliationRunFactory> */
    use HasFactory;

    public function getRouteKeyName(): string
    {
        return 'public_id';
    }

    public function internalBatch(): BelongsTo
    {
        return $this->belongsTo(ImportBatch::class, 'internal_batch_id');
    }

    public function providerBatch(): BelongsTo
    {
        return $this->belongsTo(ImportBatch::class, 'provider_batch_id');
    }

    public function matches(): HasMany
    {
        return $this->hasMany(ReconciliationMatch::class);
    }

    public function exceptions(): HasMany
    {
        return $this->hasMany(ReconciliationException::class);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => ReconciliationRunStatus::class,
            'exact_matches_count' => 'integer',
            'tolerant_matches_count' => 'integer',
            'exceptions_count' => 'integer',
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
            'metadata' => 'array',
        ];
    }
}
