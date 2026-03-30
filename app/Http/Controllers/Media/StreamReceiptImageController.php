<?php

declare(strict_types=1);

namespace App\Http\Controllers\Media;

use App\Http\Controllers\Controller;
use App\Models\Receipt;
use App\Services\Media\ImageStorageService;

final class StreamReceiptImageController extends Controller
{
    public function __invoke(Receipt $receipt, ImageStorageService $images): mixed
    {
        $this->authorize('view', $receipt);

        $relative = (string) $receipt->receipt_image;

        if ($relative === '') {
            abort(404);
        }

        $prefixes = [
            rtrim((string) config('image_upload.path.receipt_final'), '/').'/',
            rtrim((string) config('image_upload.path.receipt_staging'), '/').'/',
            'demo/',
        ];

        return $images->streamPrivateFile($relative, $prefixes);
    }
}
