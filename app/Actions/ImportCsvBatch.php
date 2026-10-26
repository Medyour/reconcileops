<?php

namespace App\Actions;

use App\Exceptions\InvalidImportFile;
use App\ImportBatchStatus;
use App\Models\DataSource;
use App\Models\ImportBatch;
use App\Models\SourceRecord;
use App\Services\RecordNormalizer;
use DateTimeImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ImportCsvBatch
{
    public function __construct(private readonly RecordNormalizer $normalizer) {}

    public function handle(DataSource $source, string $path): ImportBatch
    {
        if (! $source->is_active) {
            throw new InvalidImportFile('La source est inactive.');
        }

        $contents = $this->readFile($path);
        $checksum = hash('sha256', $contents);
        $records = $this->parseRecords($contents);

        return DB::transaction(function () use ($source, $path, $checksum, $records): ImportBatch {
            $batch = ImportBatch::query()->firstOrCreate(
                [
                    'data_source_id' => $source->id,
                    'checksum' => $checksum,
                ],
                [
                    'public_id' => (string) Str::ulid(),
                    'source_filename' => basename($path),
                    'status' => ImportBatchStatus::Pending,
                    'record_count' => 0,
                    'rejected_count' => 0,
                    'metadata' => [
                        'encoding' => 'UTF-8',
                        'headers' => config('reconcileops.csv_headers'),
                    ],
                ]
            );

            if (! $batch->wasRecentlyCreated) {
                return $batch;
            }

            foreach ($records as $record) {
                SourceRecord::query()->create([
                    'import_batch_id' => $batch->id,
                    ...$record,
                ]);
            }

            $batch->update([
                'status' => ImportBatchStatus::Imported,
                'record_count' => count($records),
                'imported_at' => now(),
            ]);

            return $batch->fresh();
        });
    }

    private function readFile(string $path): string
    {
        if (! is_file($path) || ! is_readable($path)) {
            throw new InvalidImportFile('Le fichier CSV est introuvable ou illisible.');
        }

        $size = filesize($path);
        $maximumSize = (int) config('reconcileops.max_import_bytes');

        if ($size === false || $size > $maximumSize) {
            throw new InvalidImportFile("Le fichier CSV dépasse la taille maximale de {$maximumSize} octets.");
        }

        $contents = file_get_contents($path);

        if ($contents === false || $contents === '') {
            throw new InvalidImportFile('Le fichier CSV est vide ou illisible.');
        }

        if (! mb_check_encoding($contents, 'UTF-8')) {
            throw new InvalidImportFile('Le fichier CSV doit être encodé en UTF-8.');
        }

        return $contents;
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function parseRecords(string $contents): array
    {
        $stream = fopen('php://temp', 'r+');

        if ($stream === false) {
            throw new InvalidImportFile('Le fichier CSV ne peut pas être préparé.');
        }

        try {
            fwrite($stream, $contents);
            rewind($stream);

            $header = fgetcsv($stream, null, ',', '"', '');

            if ($header === false) {
                throw new InvalidImportFile('L’en-tête CSV est absent.');
            }

            $header = array_map(static fn (mixed $value): string => trim((string) $value), $header);
            $header[0] = preg_replace('/^\xEF\xBB\xBF/', '', $header[0]) ?? $header[0];
            $expectedHeaders = config('reconcileops.csv_headers');

            if ($header !== $expectedHeaders) {
                throw new InvalidImportFile('L’en-tête CSV ne correspond pas au contrat attendu.');
            }

            $records = [];
            $recordKeys = [];
            $rowNumber = 1;

            while (($values = fgetcsv($stream, null, ',', '"', '')) !== false) {
                $rowNumber++;

                if ($this->isBlankRow($values)) {
                    continue;
                }

                if (count($values) !== count($expectedHeaders)) {
                    throw InvalidImportFile::atRow($rowNumber, 'le nombre de colonnes est incorrect.');
                }

                $row = array_combine($expectedHeaders, array_map(
                    static fn (mixed $value): string => trim((string) $value),
                    $values
                ));

                if ($row === false) {
                    throw InvalidImportFile::atRow($rowNumber, 'la ligne ne peut pas être interprétée.');
                }

                $record = $this->validateRow($row, $rowNumber);

                if (isset($recordKeys[$record['record_key']])) {
                    throw InvalidImportFile::atRow($rowNumber, 'record_key est dupliqué dans le fichier.');
                }

                $recordKeys[$record['record_key']] = true;
                $records[] = $record;
            }

            if ($records === []) {
                throw new InvalidImportFile('Le fichier CSV ne contient aucune ligne de données.');
            }

            return $records;
        } finally {
            fclose($stream);
        }
    }

    /**
     * @param  array<string, string>  $row
     * @return array<string, mixed>
     */
    private function validateRow(array $row, int $rowNumber): array
    {
        $recordKey = $this->requiredValue($row['record_key'], 'record_key', $rowNumber, 128);
        $externalReference = $this->requiredValue(
            $row['external_reference'],
            'external_reference',
            $rowNumber,
            128
        );
        $secondaryReference = $row['secondary_reference'] === '' ? null : $row['secondary_reference'];

        if ($secondaryReference !== null && mb_strlen($secondaryReference) > 128) {
            throw InvalidImportFile::atRow($rowNumber, 'secondary_reference dépasse 128 caractères.');
        }

        $date = DateTimeImmutable::createFromFormat('!Y-m-d', $row['occurred_on']);
        $dateErrors = DateTimeImmutable::getLastErrors();

        if (
            $date === false
            || $date->format('Y-m-d') !== $row['occurred_on']
            || ($dateErrors !== false && ($dateErrors['warning_count'] > 0 || $dateErrors['error_count'] > 0))
        ) {
            throw InvalidImportFile::atRow($rowNumber, 'occurred_on doit respecter le format YYYY-MM-DD.');
        }

        if (preg_match('/^-?\d+$/D', $row['amount_minor']) !== 1) {
            throw InvalidImportFile::atRow($rowNumber, 'amount_minor doit être un entier.');
        }

        $amountMinor = filter_var($row['amount_minor'], FILTER_VALIDATE_INT);

        if ($amountMinor === false) {
            throw InvalidImportFile::atRow($rowNumber, 'amount_minor dépasse la capacité entière autorisée.');
        }

        $currency = Str::upper($row['currency']);

        if (preg_match('/^[A-Z]{3}$/D', $currency) !== 1) {
            throw InvalidImportFile::atRow($rowNumber, 'currency doit contenir exactement trois lettres.');
        }

        $normalizedReference = $this->normalizer->reference($externalReference);

        if ($normalizedReference === '') {
            throw InvalidImportFile::atRow($rowNumber, 'external_reference ne produit aucune valeur normalisée.');
        }

        return [
            'record_key' => $recordKey,
            'external_reference' => $externalReference,
            'secondary_reference' => $this->normalizer->optionalReference($secondaryReference),
            'occurred_on' => $date->format('Y-m-d'),
            'amount_minor' => $amountMinor,
            'currency' => $currency,
            'normalized_reference' => $normalizedReference,
            'fingerprint' => $this->normalizer->fingerprint(
                $normalizedReference,
                $date->format('Y-m-d'),
                $amountMinor,
                $currency
            ),
            'raw_payload' => $row,
        ];
    }

    private function requiredValue(string $value, string $field, int $rowNumber, int $maximumLength): string
    {
        if ($value === '') {
            throw InvalidImportFile::atRow($rowNumber, "{$field} est obligatoire.");
        }

        if (mb_strlen($value) > $maximumLength) {
            throw InvalidImportFile::atRow($rowNumber, "{$field} dépasse {$maximumLength} caractères.");
        }

        return $value;
    }

    /**
     * @param  array<int, string|null>  $values
     */
    private function isBlankRow(array $values): bool
    {
        foreach ($values as $value) {
            if ($value !== null && trim($value) !== '') {
                return false;
            }
        }

        return true;
    }
}
