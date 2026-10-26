<?php

namespace App\Models;

use Database\Factories\SourceRecordFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'import_batch_id',
    'record_key',
    'external_reference',
    'secondary_reference',
    'occurred_on',
    'amount_minor',
    'currency',
    'normalized_reference',
    'fingerprint',
    'raw_payload',
])]
#[Hidden(['raw_payload'])]
class SourceRecord extends Model
{
    /** @use HasFactory<SourceRecordFactory> */
    use HasFactory;

    public function importBatch(): BelongsTo
    {
        return $this->belongsTo(ImportBatch::class);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'occurred_on' => 'date',
            'amount_minor' => 'integer',
            'raw_payload' => 'array',
        ];
    }
}
