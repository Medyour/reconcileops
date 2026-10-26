<?php

namespace Tests\Feature\Actions;

use App\Actions\ImportCsvBatch;
use App\DataSourceKind;
use App\Exceptions\InvalidImportFile;
use App\ImportBatchStatus;
use App\Models\DataSource;
use App\Models\SourceRecord;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ImportCsvBatchTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_it_imports_and_normalizes_a_valid_csv_atomically(): void
    {
        Storage::fake('local');
        $source = DataSource::factory()->create(['kind' => DataSourceKind::Internal]);
        $path = $this->writeCsv(<<<'CSV'
record_key,external_reference,secondary_reference,occurred_on,amount_minor,currency
record-1," tx 1001 "," secondary 7 ",2026-10-26,1250,eur
record-2,TX-1002,,2026-10-27,-500,USD
CSV);

        $batch = app(ImportCsvBatch::class)->handle($source, $path);

        $this->assertSame(ImportBatchStatus::Imported, $batch->status);
        $this->assertSame(2, $batch->record_count);
        $this->assertSame(0, $batch->rejected_count);
        $this->assertNotNull($batch->imported_at);
        $this->assertCount(2, $batch->sourceRecords);

        $firstRecord = SourceRecord::query()->where('record_key', 'record-1')->sole();

        $this->assertSame('tx 1001', $firstRecord->external_reference);
        $this->assertSame('TX-1001', $firstRecord->normalized_reference);
        $this->assertSame('SECONDARY-7', $firstRecord->secondary_reference);
        $this->assertSame(1250, $firstRecord->amount_minor);
        $this->assertSame('EUR', $firstRecord->currency);
        $this->assertSame(64, strlen($firstRecord->fingerprint));
        $this->assertArrayNotHasKey('raw_payload', $firstRecord->toArray());
    }

    public function test_importing_the_same_file_twice_returns_the_existing_batch(): void
    {
        Storage::fake('local');
        $source = DataSource::factory()->create();
        $path = $this->writeCsv(<<<'CSV'
record_key,external_reference,secondary_reference,occurred_on,amount_minor,currency
record-1,TX-1001,,2026-10-26,1250,EUR
CSV);
        $action = app(ImportCsvBatch::class);

        $firstBatch = $action->handle($source, $path);
        $secondBatch = $action->handle($source, $path);

        $this->assertTrue($firstBatch->is($secondBatch));
        $this->assertDatabaseCount('import_batches', 1);
        $this->assertDatabaseCount('source_records', 1);
    }

    public function test_invalid_row_does_not_publish_a_partial_batch(): void
    {
        Storage::fake('local');
        $source = DataSource::factory()->create();
        $path = $this->writeCsv(<<<'CSV'
record_key,external_reference,secondary_reference,occurred_on,amount_minor,currency
record-1,TX-1001,,2026-10-26,1250,EUR
record-2,TX-1002,,26/10/2026,900,EUR
CSV);

        try {
            app(ImportCsvBatch::class)->handle($source, $path);
            $this->fail('Une ligne invalide aurait dû interrompre l’import.');
        } catch (InvalidImportFile $exception) {
            $this->assertStringContainsString('Ligne 3', $exception->getMessage());
            $this->assertStringContainsString('YYYY-MM-DD', $exception->getMessage());
        }

        $this->assertDatabaseCount('import_batches', 0);
        $this->assertDatabaseCount('source_records', 0);
    }

    public function test_duplicate_record_key_is_rejected_before_database_writes(): void
    {
        Storage::fake('local');
        $source = DataSource::factory()->create();
        $path = $this->writeCsv(<<<'CSV'
record_key,external_reference,secondary_reference,occurred_on,amount_minor,currency
record-1,TX-1001,,2026-10-26,1250,EUR
record-1,TX-1002,,2026-10-27,900,EUR
CSV);

        $this->expectException(InvalidImportFile::class);
        $this->expectExceptionMessage('record_key est dupliqué');

        app(ImportCsvBatch::class)->handle($source, $path);
    }

    public function test_inactive_source_and_oversized_file_are_rejected(): void
    {
        Storage::fake('local');
        $inactiveSource = DataSource::factory()->create(['is_active' => false]);
        $path = $this->writeCsv(<<<'CSV'
record_key,external_reference,secondary_reference,occurred_on,amount_minor,currency
record-1,TX-1001,,2026-10-26,1250,EUR
CSV);

        try {
            app(ImportCsvBatch::class)->handle($inactiveSource, $path);
            $this->fail('Une source inactive aurait dû être refusée.');
        } catch (InvalidImportFile $exception) {
            $this->assertSame('La source est inactive.', $exception->getMessage());
        }

        config()->set('reconcileops.max_import_bytes', 20);
        $activeSource = DataSource::factory()->create(['is_active' => true]);

        try {
            app(ImportCsvBatch::class)->handle($activeSource, $path);
            $this->fail('Un fichier trop volumineux aurait dû être refusé.');
        } catch (InvalidImportFile $exception) {
            $this->assertStringContainsString('taille maximale', $exception->getMessage());
        }

        $this->assertDatabaseCount('import_batches', 0);
    }

    private function writeCsv(string $contents): string
    {
        Storage::disk('local')->put('imports/source.csv', $contents."\n");

        return Storage::disk('local')->path('imports/source.csv');
    }
}
