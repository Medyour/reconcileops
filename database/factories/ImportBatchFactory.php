<?php

namespace Database\Factories;

use App\ImportBatchStatus;
use App\Models\DataSource;
use App\Models\ImportBatch;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<ImportBatch>
 */
class ImportBatchFactory extends Factory
{
    public function definition(): array
    {
        return [
            'public_id' => (string) Str::ulid(),
            'data_source_id' => DataSource::factory(),
            'source_filename' => fake()->lexify('batch-??????.csv'),
            'checksum' => hash('sha256', fake()->uuid()),
            'status' => ImportBatchStatus::Imported,
            'record_count' => fake()->numberBetween(1, 100),
            'rejected_count' => 0,
            'imported_at' => now(),
            'metadata' => ['encoding' => 'UTF-8'],
        ];
    }
}
