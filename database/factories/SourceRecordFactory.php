<?php

namespace Database\Factories;

use App\Models\ImportBatch;
use App\Models\SourceRecord;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<SourceRecord>
 */
class SourceRecordFactory extends Factory
{
    public function definition(): array
    {
        $reference = Str::upper(fake()->unique()->bothify('TX-####-??'));
        $amountMinor = fake()->numberBetween(100, 100_000);

        return [
            'import_batch_id' => ImportBatch::factory(),
            'record_key' => fake()->unique()->bothify('record-####-??'),
            'external_reference' => $reference,
            'secondary_reference' => fake()->optional()->bothify('SECONDARY-####'),
            'occurred_on' => fake()->dateTimeBetween('-30 days'),
            'amount_minor' => $amountMinor,
            'currency' => 'EUR',
            'normalized_reference' => $reference,
            'fingerprint' => hash('sha256', $reference.'|'.$amountMinor.'|EUR'),
            'raw_payload' => [
                'reference' => $reference,
                'amount_minor' => $amountMinor,
                'currency' => 'EUR',
            ],
        ];
    }
}
