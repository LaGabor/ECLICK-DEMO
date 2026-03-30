<?php

declare(strict_types=1);

return [
    // Max lines per CSV file including the header row (RefundBankCsvChunkExport uses WithHeadings).
    'max_csv_rows_per_bank_batch_file' => (int) env('REFUND_EXPORT_MAX_CSV_ROWS', 100),
    'disk' => env('REFUND_EXPORT_DISK', 'local'),
    'directory' => env('REFUND_EXPORT_DIRECTORY', 'refund-exports'),
    'queue' => env('REFUND_EXPORT_QUEUE', 'refund-exports'),
    'signed_download_ttl_days' => (int) env('REFUND_EXPORT_SIGNED_LINK_DAYS', 7),
];
