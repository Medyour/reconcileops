<?php

namespace App\Http\Controllers;

use App\Http\Requests\ListReconciliationExceptionsRequest;
use App\Models\ReconciliationException;
use App\ReconciliationExceptionStatus;
use App\ReconciliationExceptionType;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;

class ReconciliationExceptionController extends Controller
{
    public function index(ListReconciliationExceptionsRequest $request): View
    {
        $filters = $request->validated();
        $search = trim((string) ($filters['q'] ?? ''));

        $exceptions = ReconciliationException::query()
            ->with([
                'reconciliationRun',
                'internalRecord',
                'providerRecord',
                'decisions' => fn ($query) => $query->orderBy('decided_at'),
            ])
            ->when(
                $filters['type'] ?? null,
                fn (Builder $query, string $type): Builder => $query->where('type', $type)
            )
            ->when(
                $filters['status'] ?? null,
                fn (Builder $query, string $status): Builder => $query->where('status', $status)
            )
            ->when($search !== '', function (Builder $query) use ($search): void {
                $query->where(function (Builder $query) use ($search): void {
                    $query
                        ->whereHas('internalRecord', fn (Builder $recordQuery): Builder => $recordQuery
                            ->where('external_reference', 'like', "%{$search}%")
                            ->orWhere('record_key', 'like', "%{$search}%"))
                        ->orWhereHas('providerRecord', fn (Builder $recordQuery): Builder => $recordQuery
                            ->where('external_reference', 'like', "%{$search}%")
                            ->orWhere('record_key', 'like', "%{$search}%"));
                });
            })
            ->latest('id')
            ->paginate(15)
            ->withQueryString();

        return view('exceptions.index', [
            'exceptions' => $exceptions,
            'types' => ReconciliationExceptionType::cases(),
            'statuses' => ReconciliationExceptionStatus::cases(),
            'filters' => $filters,
        ]);
    }
}
