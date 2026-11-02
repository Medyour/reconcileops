<?php

namespace App\Actions\Demo;

use App\Models\DataSource;
use App\Models\ImportBatch;
use App\Models\ReconciliationDecision;
use App\Models\ReconciliationException;
use App\Models\ReconciliationMatch;
use App\Models\ReconciliationRun;
use App\Models\SourceRecord;
use Database\Seeders\DemoDataSeeder;
use Illuminate\Support\Facades\DB;
use LogicException;

class ResetDemo
{
    /** @var list<string> */
    private const DEMO_SOURCES = ['internal-journal', 'provider-statement'];

    /** @var list<string> */
    private const DEMO_BATCHES = [
        'internal-journal-2026-10-26.csv',
        'provider-statement-2026-10-26.csv',
    ];

    /** @var list<string> */
    private const DEMO_RECORD_KEYS = [
        'INT-001',
        'INT-002',
        'INT-003',
        'INT-004',
        'INT-005-A',
        'INT-005-B',
        'INT-006',
        'INT-007',
        'INT-008',
        'PRO-001',
        'PRO-002',
        'PRO-003',
        'PRO-004',
        'PRO-005-A',
        'PRO-005-B',
        'PRO-006',
        'PRO-007-A',
        'PRO-007-B',
        'PRO-008',
    ];

    /**
     * @return array{sources: int, batches: int, records: int, runs: int, matches: int, exceptions: int, decisions: int}
     */
    public function execute(bool $exclusive): array
    {
        return DB::transaction(function () use ($exclusive): array {
            $this->lockTables();

            if ($exclusive) {
                $this->assertExclusiveDemoDatabase();
            }

            foreach ([
                'failed_jobs',
                'job_batches',
                'jobs',
                'sessions',
                'password_reset_tokens',
                'cache_locks',
                'cache',
                'reconciliation_decisions',
                'reconciliation_exceptions',
                'reconciliation_matches',
                'reconciliation_runs',
                'source_records',
                'import_batches',
                'data_sources',
            ] as $table) {
                DB::table($table)->delete();
            }

            app(DemoDataSeeder::class)->run();

            return [
                'sources' => DataSource::query()->count(),
                'batches' => ImportBatch::query()->count(),
                'records' => SourceRecord::query()->count(),
                'runs' => ReconciliationRun::query()->count(),
                'matches' => ReconciliationMatch::query()->count(),
                'exceptions' => ReconciliationException::query()->count(),
                'decisions' => ReconciliationDecision::query()->count(),
            ];
        }, 3);
    }

    private function assertExclusiveDemoDatabase(): void
    {
        if (DB::table('users')->exists()) {
            throw new LogicException('Reset refused: user accounts exist.');
        }

        if (DataSource::query()->whereNotIn('slug', self::DEMO_SOURCES)->exists()) {
            throw new LogicException('Reset refused: unknown data source exists.');
        }

        if (ImportBatch::query()->whereNotIn('source_filename', self::DEMO_BATCHES)->exists()) {
            throw new LogicException('Reset refused: unknown import batch exists.');
        }

        if (SourceRecord::query()->whereNotIn('record_key', self::DEMO_RECORD_KEYS)->exists()) {
            throw new LogicException('Reset refused: unknown source record exists.');
        }

        $withinExpectedBounds = DataSource::query()->count() <= 2
            && ImportBatch::query()->count() <= 2
            && SourceRecord::query()->count() <= 19
            && ReconciliationRun::query()->count() <= 1
            && ReconciliationMatch::query()->count() <= 2
            && ReconciliationException::query()->count() <= 8
            && ReconciliationDecision::query()->count() <= 1;

        if (! $withinExpectedBounds) {
            throw new LogicException('Reset refused: unexpected reconciliation data exists.');
        }

        if (ReconciliationDecision::query()->where('decided_by', '!=', 'demo-reviewer')->exists()) {
            throw new LogicException('Reset refused: unknown decision exists.');
        }
    }

    private function lockTables(): void
    {
        if (DB::connection()->getDriverName() !== 'pgsql') {
            return;
        }

        DB::statement('LOCK TABLE users, password_reset_tokens, sessions, cache, cache_locks, jobs, job_batches, failed_jobs, data_sources, import_batches, source_records, reconciliation_runs, reconciliation_matches, reconciliation_exceptions, reconciliation_decisions IN ACCESS EXCLUSIVE MODE');
    }
}
