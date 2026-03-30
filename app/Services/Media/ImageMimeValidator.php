<?php

declare(strict_types=1);

namespace App\Services\Media;

final class ImageMimeValidator
{
    private const array ALLOWED_FINETYPES = [
        'image/jpeg',
        'image/png',
    ];

    /**
     * @throws \RuntimeException
     */
    public function assertFileIsAllowedRasterImage(string $absolutePath): void
    {
        if (! is_file($absolutePath) || ! is_readable($absolutePath)) {
            throw new \RuntimeException('Image file is missing or unreadable.');
        }

        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $mime = $finfo->file($absolutePath);

        if ($mime === false || ! in_array($mime, self::ALLOWED_FINETYPES, true)) {
            throw new \RuntimeException('Invalid image type (MIME check failed).');
        }

        $info = @getimagesize($absolutePath);

        if ($info === false) {
            throw new \RuntimeException('File is not a valid raster image.');
        }

        $type = $info[2] ?? 0;

        if (! in_array($type, [IMAGETYPE_JPEG, IMAGETYPE_PNG], true)) {
            throw new \RuntimeException('Only JPEG and PNG images are allowed.');
        }
    }
}
