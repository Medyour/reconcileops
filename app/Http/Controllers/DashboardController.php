<?php

namespace App\Http\Controllers;

use App\Models\ReconciliationRun;
use App\ReconciliationExceptionStatus;
use App\ReconciliationExceptionType;
use App\ReconciliationRunStatus;
use Illuminate\Contracts\View\View;

class DashboardController extends Controller
{
    public function __invoke(): View
    {
        $latestRun = ReconciliationRun::query()
            ->where('status', ReconciliationRunStatus::Completed->value)
            ->with([
                'internalBatch.dataSource',
                'providerBatch.dataSource',
                'exceptions.internalRecord',
                'exceptions.providerRecord',
            ])
            ->latest('completed_at')
            ->first();

        $recentRuns = ReconciliationRun::query()
            ->with(['internalBatch.dataSource', 'providerBatch.dataSource'])
            ->latest('created_at')
            ->limit(5)
            ->get();

        $metrics = [
            'total_records' => 0,
            'exact_rate' => 0.0,
            'duplicates' => 0,
            'open_exceptions' => 0,
            'amount_gap_minor' => 0,
        ];

        if ($latestRun !== null) {
            $internalCount = $latestRun->internalBatch->record_count;
            $metrics = [
                'total_records' => $internalCount + $latestRun->providerBatch->record_count,
                'exact_rate' => $internalCount > 0
                    ? round(($latestRun->exact_matches_count / $internalCount) * 100, 1)
                    : 0.0,
                'duplicates' => $latestRun->exceptions
                    ->whereIn('type', [
                        ReconciliationExceptionType::DuplicateInternal,
                        ReconciliationExceptionType::DuplicateProvider,
                    ])
                    ->count(),
                'open_exceptions' => $latestRun->exceptions
                    ->where('status', ReconciliationExceptionStatus::Open)
                    ->count(),
                'amount_gap_minor' => $latestRun->exceptions
                    ->where('type', ReconciliationExceptionType::AmountMismatch)
                    ->sum(static function ($exception): int {
                        if ($exception->internalRecord === null || $exception->providerRecord === null) {
                            return 0;
                        }

                        return abs(
                            $exception->providerRecord->amount_minor - $exception->internalRecord->amount_minor
                        );
                    }),
            ];
        }

        return view('dashboard', compact('latestRun', 'recentRuns', 'metrics'));
    }
}
