<?php

namespace App\Models;

use App\ReconciliationDecisionAction;
use Database\Factories\ReconciliationDecisionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'reconciliation_exception_id',
    'action',
    'decided_by',
    'explanation',
    'decided_at',
    'metadata',
])]
class ReconciliationDecision extends Model
{
    /** @use HasFactory<ReconciliationDecisionFactory> */
    use HasFactory;

    public function reconciliationException(): BelongsTo
    {
        return $this->belongsTo(ReconciliationException::class);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'action' => ReconciliationDecisionAction::class,
            'decided_at' => 'datetime',
            'metadata' => 'array',
        ];
    }
}
