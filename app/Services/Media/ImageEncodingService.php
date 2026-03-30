<?php

declare(strict_types=1);

namespace App\Services\Media;

use Illuminate\Support\Facades\File;
use Intervention\Image\Laravel\Facades\Image;

final class ImageEncodingService
{
    /**
     * Resize (max edge), re-encode as JPEG, aim for max output size in bytes.
     *
     * @return non-empty-string Absolute path to a temp file containing JPEG bytes
     */
    public function rasterToCompressedJpegTempFile(string $sourceAbsolutePath): string
    {
        $maxDimension = max(1, (int) config('image_upload.max_dimension'));
        $maxBytes = max(1024, (int) config('image_upload.max_output_bytes'));
        $qualityStart = max(40, min(95, (int) config('image_upload.jpeg_quality_start')));
        $qualityFloor = max(40, min($qualityStart, (int) config('image_upload.jpeg_quality_floor')));

        $image = Image::read($sourceAbsolutePath);
        $image->orient();
        $image->scaleDown(width: $maxDimension, height: $maxDimension);

        $quality = $qualityStart;
        $binary = '';

        while ($quality >= $qualityFloor) {
            $encoded = $image->toJpeg(quality: $quality);
            $binary = $encoded->toString();

            if (strlen($binary) <= $maxBytes) {
                break;
            }

            $quality -= 6;
        }

        if ($binary === '') {
            throw new \RuntimeException('Failed to encode image.');
        }

        $temp = tempnam(sys_get_temp_dir(), 'imgjpg_');

        if ($temp === false) {
            throw new \RuntimeException('Unable to create temp file for encoded image.');
        }

        File::put($temp, $binary);

        return $temp;
    }
}
