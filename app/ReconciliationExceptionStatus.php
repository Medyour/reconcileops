<?php

namespace App;

enum ReconciliationExceptionStatus: string
{
    case Open = 'open';
    case Resolved = 'resolved';
}
