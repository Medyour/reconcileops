<?php

namespace App;

enum ReconciliationMatchRule: string
{
    case ExactReference = 'exact_reference';
    case DateWindow = 'date_window';
    case Composite = 'composite';
}
