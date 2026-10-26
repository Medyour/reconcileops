<?php

return [
    'max_import_bytes' => (int) env('RECONCILEOPS_MAX_IMPORT_BYTES', 1_048_576),

    'csv_headers' => [
        'record_key',
        'external_reference',
        'secondary_reference',
        'occurred_on',
        'amount_minor',
        'currency',
    ],
];
