<?php

namespace App;

enum ReconciliationMatchRule: string
{
    case ExactReference = 'exact_reference';
    case DateWindow = 'date_window';
    case Composite = 'composite';

    public function label(): string
    {
        return match ($this) {
            self::ExactReference => 'Référence exacte',
            self::DateWindow => 'Fenêtre de date',
            self::Composite => 'Clé composite',
        };
    }
}
