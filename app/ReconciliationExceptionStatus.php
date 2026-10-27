<?php

namespace App;

enum ReconciliationExceptionStatus: string
{
    case Open = 'open';
    case Resolved = 'resolved';

    public function label(): string
    {
        return match ($this) {
            self::Open => 'Ouverte',
            self::Resolved => 'Résolue',
        };
    }
}
