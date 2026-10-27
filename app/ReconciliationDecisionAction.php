<?php

namespace App;

enum ReconciliationDecisionAction: string
{
    case AcceptMatch = 'accept_match';
    case KeepException = 'keep_exception';
    case Ignore = 'ignore';

    public function label(): string
    {
        return match ($this) {
            self::AcceptMatch => 'Rapprochement accepté',
            self::KeepException => 'Exception conservée',
            self::Ignore => 'Élément ignoré',
        };
    }
}
