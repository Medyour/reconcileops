<?php

namespace App;

enum ReconciliationExceptionType: string
{
    case AmountMismatch = 'amount_mismatch';
    case CurrencyMismatch = 'currency_mismatch';
    case DuplicateInternal = 'duplicate_internal';
    case DuplicateProvider = 'duplicate_provider';
    case MissingInternal = 'missing_internal';
    case MissingProvider = 'missing_provider';
    case Ambiguous = 'ambiguous';
    case ManualReview = 'manual_review';

    public function label(): string
    {
        return match ($this) {
            self::AmountMismatch => 'Écart de montant',
            self::CurrencyMismatch => 'Devise incohérente',
            self::DuplicateInternal => 'Doublon interne',
            self::DuplicateProvider => 'Doublon prestataire',
            self::MissingInternal => 'Absence côté interne',
            self::MissingProvider => 'Absence côté prestataire',
            self::Ambiguous => 'Correspondance ambiguë',
            self::ManualReview => 'Revue manuelle',
        };
    }
}
