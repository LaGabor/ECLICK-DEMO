<?php

declare(strict_types=1);

namespace App\Http\Controllers\Media;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Services\Media\ImageStorageService;

final class StreamProductImageController extends Controller
{
    public function __invoke(Product $product, ImageStorageService $images): mixed
    {
        $this->authorize('viewCatalogImage', $product);

        $relative = (string) $product->product_image;

        if ($relative === '') {
            abort(404);
        }

        $prefixes = [
            rtrim((string) config('image_upload.path.product_final'), '/').'/',
            rtrim((string) config('image_upload.path.product_staging'), '/').'/',
            'demo/',
        ];

        return $images->streamPrivateFile($relative, $prefixes);
    }
}
