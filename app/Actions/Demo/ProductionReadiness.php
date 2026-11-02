<?php

namespace App\Actions\Demo;

use App\Models\DataSource;
use App\Models\ImportBatch;
use App\Models\ReconciliationDecision;
use App\Models\ReconciliationException;
use App\Models\ReconciliationMatch;
use App\Models\ReconciliationRun;
use App\Models\SourceRecord;
use Illuminate\Encryption\Encrypter;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use LogicException;

class ProductionReadiness
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

    public function check(bool $web = false, bool $database = false, bool $ready = false): void
    {
        $this->assertConfiguration([
            'APP_ENV' => app()->isProduction(),
            'APP_DEBUG' => config('app.debug') === false,
            'APP_URL' => is_string(config('app.url')) && str_starts_with(config('app.url'), 'https://'),
            'RECONCILEOPS_DEMO_MODE' => config('reconcileops.demo_mode') === true,
            'RECONCILEOPS_ALLOW_DEMO_DECISIONS' => config('reconcileops.allow_demo_decisions') === false,
            'DB_CONNECTION' => config('database.default') === 'pgsql',
            'DATABASE_URL' => $this->hasValidPostgresUrl(),
            'CACHE_STORE' => config('cache.default') === 'database',
            'SESSION_DRIVER' => config('session.driver') === 'database',
            'SESSION_ENCRYPT' => config('session.encrypt') === true,
            'SESSION_SECURE_COOKIE' => config('session.secure') === true,
            'SESSION_HTTP_ONLY' => config('session.http_only') === true,
            'SESSION_SAME_SITE' => config('session.same_site') === 'lax',
            'QUEUE_CONNECTION' => config('queue.default') === 'sync',
            'LOG_CHANNEL' => config('logging.default') === 'stderr',
            'LOG_LEVEL' => config('logging.channels.stderr.level') === 'warning',
            'MAIL_MAILER' => config('mail.default') === 'array',
            'APP_LOCALE' => config('app.locale') === 'fr',
            'APP_FALLBACK_LOCALE' => config('app.fallback_locale') === 'fr',
        ]);

        if ($web) {
            $this->assertApplicationKey();
        }

        if ($database || $ready) {
            DB::connection()->getPdo();
            $this->assertDatabaseOwnership();
        }

        if ($ready) {
            $this->assertDemoReady();
        }
    }

    private function assertApplicationKey(): void
    {
        $key = config('app.key');
        $decodedKey = is_string($key) && str_starts_with($key, 'base64:')
            ? base64_decode(substr($key, 7), true)
            : $key;

        $this->assertConfiguration([
            'APP_KEY' => is_string($decodedKey) && Encrypter::supported($decodedKey, (string) config('app.cipher')),
        ]);
    }

    private function hasValidPostgresUrl(): bool
    {
        $url = config('database.connections.pgsql.url');

        if (! is_string($url) || $url === '') {
            return false;
        }

        $parts = parse_url($url);

        return is_array($parts)
            && in_array($parts['scheme'] ?? null, ['postgres', 'postgresql'], true)
            && isset($parts['host'], $parts['user'], $parts['path'])
            && $parts['path'] !== '/';
    }

    private function assertDatabaseOwnership(): void
    {
        if (Schema::hasTable('users') && DB::table('users')->exists()) {
            throw new LogicException('Database safety check failed: USERS.');
        }

        if (! Schema::hasTable('data_sources')) {
            return;
        }

        if (DataSource::query()->whereNotIn('slug', self::DEMO_SOURCES)->exists()) {
            throw new LogicException('Database safety check failed: DATA_SOURCES.');
        }

        if (Schema::hasTable('import_batches') && ImportBatch::query()->whereNotIn('source_filename', self::DEMO_BATCHES)->exists()) {
            throw new LogicException('Database safety check failed: IMPORT_BATCHES.');
        }

        if (Schema::hasTable('source_records') && SourceRecord::query()->whereNotIn('record_key', self::DEMO_RECORD_KEYS)->exists()) {
            throw new LogicException('Database safety check failed: SOURCE_RECORDS.');
        }
    }

    private function assertDemoReady(): void
    {
        foreach ([
            'data_sources',
            'import_batches',
            'source_records',
            'reconciliation_runs',
            'reconciliation_matches',
            'reconciliation_exceptions',
            'reconciliation_decisions',
        ] as $table) {
            if (! Schema::hasTable($table)) {
                throw new LogicException('Database readiness check failed: TABLES.');
            }
        }

        $this->assertConfiguration([
            'DEMO_SOURCES' => DataSource::query()->count() === 2
                && DataSource::query()->orderBy('slug')->pluck('slug')->all() === self::DEMO_SOURCES,
            'DEMO_BATCHES' => ImportBatch::query()->count() === 2
                && ImportBatch::query()->orderBy('source_filename')->pluck('source_filename')->all() === self::DEMO_BATCHES,
            'DEMO_RECORDS' => SourceRecord::query()->count() === 19
                && SourceRecord::query()->orderBy('record_key')->pluck('record_key')->all() === self::DEMO_RECORD_KEYS,
            'DEMO_RUNS' => ReconciliationRun::query()->count() === 1,
            'DEMO_MATCHES' => ReconciliationMatch::query()->count() === 2,
            'DEMO_EXCEPTIONS' => ReconciliationException::query()->count() === 8,
            'DEMO_DECISIONS' => ReconciliationDecision::query()->count() === 1,
        ]);
    }

    /** @param array<string, bool> $checks */
    private function assertConfiguration(array $checks): void
    {
        foreach ($checks as $name => $valid) {
            if (! $valid) {
                throw new LogicException("Production readiness check failed: {$name}.");
            }
        }
    }
}
