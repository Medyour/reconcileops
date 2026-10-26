<?php

namespace App\Models;

use App\ImportBatchStatus;
use Database\Factories\ImportBatchFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'public_id',
    'data_source_id',
    'source_filename',
    'checksum',
    'status',
    'record_count',
    'rejected_count',
    'imported_at',
    'metadata',
])]
class ImportBatch extends Model
{
    /** @use HasFactory<ImportBatchFactory> */
    use HasFactory;

    public function dataSource(): BelongsTo
    {
        return $this->belongsTo(DataSource::class);
    }

    public function sourceRecords(): HasMany
    {
        return $this->hasMany(SourceRecord::class);
    }

    public function internalReconciliationRuns(): HasMany
    {
        return $this->hasMany(ReconciliationRun::class, 'internal_batch_id');
    }

    public function providerReconciliationRuns(): HasMany
    {
        return $this->hasMany(ReconciliationRun::class, 'provider_batch_id');
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => ImportBatchStatus::class,
            'record_count' => 'integer',
            'rejected_count' => 'integer',
            'imported_at' => 'datetime',
            'metadata' => 'array',
        ];
    }
}
