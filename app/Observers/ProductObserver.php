<?php

declare(strict_types=1);

namespace App\Observers;

use App\Models\Product;
use App\Services\Media\ImageStorageService;
use App\Support\Media\DemoImagePaths;

final class ProductObserver
{
    public function deleted(Product $product): void
    {
        $path = (string) ($product->getAttributes()['product_image'] ?? '');

        if ($path === '') {
            return;
        }

        if (in_array($path, DemoImagePaths::protectedRelativePaths(), true)) {
            return;
        }

        $images = app(ImageStorageService::class);
        $images->deleteIfOwned($path, [
            rtrim((string) config('image_upload.path.product_final'), '/').'/',
            rtrim((string) config('image_upload.path.product_staging'), '/').'/',
            'demo/',
        ]);
    }
}
