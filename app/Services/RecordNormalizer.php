<?php

namespace App\Services;

use Illuminate\Support\Str;

class RecordNormalizer
{
    public function reference(string $reference): string
    {
        $normalized = preg_replace('/[^A-Z0-9]+/', '-', Str::upper(trim($reference)));

        return trim((string) $normalized, '-');
    }

    public function optionalReference(?string $reference): ?string
    {
        if ($reference === null || trim($reference) === '') {
            return null;
        }

        return $this->reference($reference);
    }

    public function fingerprint(
        string $normalizedReference,
        string $occurredOn,
        int $amountMinor,
        string $currency
    ): string {
        return hash('sha256', implode('|', [
            $normalizedReference,
            $occurredOn,
            (string) $amountMinor,
            $currency,
        ]));
    }
}
