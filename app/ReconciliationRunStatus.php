<?php

namespace App;

enum ReconciliationRunStatus: string
{
    case Pending = 'pending';
    case Running = 'running';
    case Completed = 'completed';
    case Failed = 'failed';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'En attente',
            self::Running => 'En cours',
            self::Completed => 'Terminée',
            self::Failed => 'Échec',
        };
    }
}
