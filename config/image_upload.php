<?php

declare(strict_types=1);

return [

    'disk' => env('IMAGE_UPLOAD_DISK', 'local'),

    'queue' => env('IMAGE_UPLOAD_QUEUE', 'upload-pics'),

    /** Max upload size in kilobytes (HTTP / FormRequest). */
    'max_upload_kb' => (int) env('IMAGE_UPLOAD_MAX_KB', 10240),

    /** Target maximum encoded JPEG size in bytes after resize/compress. */
    'max_output_bytes' => (int) env('IMAGE_UPLOAD_MAX_OUTPUT_BYTES', 3 * 1024 * 1024),

    'max_dimension' => (int) env('IMAGE_UPLOAD_MAX_DIMENSION', 1920),

    'jpeg_quality_start' => (int) env('IMAGE_UPLOAD_JPEG_QUALITY', 82),

    'jpeg_quality_floor' => (int) env('IMAGE_UPLOAD_JPEG_QUALITY_FLOOR', 55),

    'path' => [
        'product_staging' => 'staging/products',
        'product_final' => 'products',
        'receipt_staging' => 'staging/receipts',
        'receipt_final' => 'receipts',
        'demo_product' => 'demo/product-pic.png',
        'demo_receipt' => 'demo/receipt-pic.jpg',
    ],
];
