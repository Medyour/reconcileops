<?php

namespace App\Exceptions;

use RuntimeException;

class InvalidImportFile extends RuntimeException
{
    public static function atRow(int $rowNumber, string $message): self
    {
        return new self("Ligne {$rowNumber} : {$message}");
    }
}
