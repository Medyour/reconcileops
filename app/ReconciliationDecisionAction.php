<?php

namespace App;

enum ReconciliationDecisionAction: string
{
    case AcceptMatch = 'accept_match';
    case KeepException = 'keep_exception';
    case Ignore = 'ignore';
}
