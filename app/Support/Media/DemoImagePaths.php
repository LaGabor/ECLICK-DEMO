<?php

declare(strict_types=1);

namespace App\Support\Media;

/**
 * Shared filesystem demo assets — must not be deleted when models are removed.
 */
final class DemoImagePaths
{
    /**
     * @return list<string>
     */
    public static function protectedRelativePaths(): array
    {
        return array_values(array_unique(array_filter([
            (string) config('image_upload.path.demo_product'),
            (string) config('image_upload.path.demo_receipt'),
        ])));
    }
}
