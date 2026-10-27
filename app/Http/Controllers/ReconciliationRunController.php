<?php

namespace App\Http\Controllers;

use App\Models\ReconciliationRun;
use Illuminate\Contracts\View\View;

class ReconciliationRunController extends Controller
{
    public function index(): View
    {
        $runs = ReconciliationRun::query()
            ->with(['internalBatch.dataSource', 'providerBatch.dataSource'])
            ->latest('created_at')
            ->paginate(10);

        return view('runs.index', compact('runs'));
    }

    public function show(ReconciliationRun $reconciliationRun): View
    {
        $reconciliationRun->load([
            'internalBatch.dataSource',
            'providerBatch.dataSource',
            'matches' => fn ($query) => $query->orderBy('id'),
            'matches.internalRecord',
            'matches.providerRecord',
            'exceptions' => fn ($query) => $query->orderBy('id'),
            'exceptions.internalRecord',
            'exceptions.providerRecord',
            'exceptions.decisions' => fn ($query) => $query->orderBy('decided_at'),
        ]);

        return view('runs.show', compact('reconciliationRun'));
    }
}
