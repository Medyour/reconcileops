<?php

namespace App\Http\Controllers;

use App\Http\Requests\ListReconciliationMatchesRequest;
use App\Models\ReconciliationMatch;
use App\ReconciliationMatchRule;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;

class ReconciliationMatchController extends Controller
{
    public function index(ListReconciliationMatchesRequest $request): View
    {
        $filters = $request->validated();
        $search = trim((string) ($filters['q'] ?? ''));

        $matches = ReconciliationMatch::query()
            ->with(['reconciliationRun', 'internalRecord', 'providerRecord'])
            ->when(
                $filters['rule'] ?? null,
                fn (Builder $query, string $rule): Builder => $query->where('rule', $rule)
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

        return view('matches.index', [
            'matches' => $matches,
            'rules' => ReconciliationMatchRule::cases(),
            'filters' => $filters,
        ]);
    }
}
