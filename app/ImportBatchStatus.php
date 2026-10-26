<?php

namespace App;

enum ImportBatchStatus: string
{
    case Pending = 'pending';
    case Imported = 'imported';
    case Rejected = 'rejected';
}
