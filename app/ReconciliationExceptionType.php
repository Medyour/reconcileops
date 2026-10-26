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
}
