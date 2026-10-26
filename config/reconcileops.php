<?php

return [
    'max_import_bytes' => (int) env('RECONCILEOPS_MAX_IMPORT_BYTES', 1_048_576),
    'date_window_days' => (int) env('RECONCILEOPS_DATE_WINDOW_DAYS', 1),

    'csv_headers' => [
        'record_key',
        'external_reference',
        'secondary_reference',
        'occurred_on',
        'amount_minor',
        'currency',
    ],
];
