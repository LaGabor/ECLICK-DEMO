<?php

declare(strict_types=1);

namespace App\Services\Media;

use App\Models\Product;
use App\Models\Receipt;
use App\Support\Media\SecureImagePath;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

final class ImageStorageService
{
    public function __construct(
        private readonly ImageMimeValidator $mimeValidator,
        private readonly ImageEncodingService $encodingService,
    ) {}

    public function diskName(): string
    {
        return (string) config('image_upload.disk');
    }

    /**
     * Extra guard after FormRequest "image" rules — blocks MIME spoofing.
     */
    public function assertHttpUploadIsSafeRaster(UploadedFile $file): void
    {
        $realPath = $file->getRealPath();

        if ($realPath === false || $realPath === '') {
            throw new \RuntimeException('Upload temp path unavailable.');
        }

        $this->mimeValidator->assertFileIsAllowedRasterImage($realPath);
    }

    /**
     * Store participant upload with a random name (no original client filename).
     */
    public function storeReceiptUploadToStaging(UploadedFile $file): string
    {
        $directory = (string) config('image_upload.path.receipt_staging');

        return $file->store($directory, ['disk' => $this->diskName()]);
    }

    public function streamPrivateFile(string $relativePath, array $allowedPrefixes): BinaryFileResponse
    {
        SecureImagePath::assertRelativePathMatches($relativePath, $allowedPrefixes);

        $disk = Storage::disk($this->diskName());

        if (! $disk->exists($relativePath)) {
            abort(404);
        }

        $absolute = $disk->path($relativePath);

        if (! is_file($absolute)) {
            abort(404);
        }

        return response()->file($absolute, [
            'Content-Type' => mime_content_type($absolute) ?: 'application/octet-stream',
            'Cache-Control' => 'private, no-store, must-revalidate',
        ]);
    }

    public function deleteIfOwned(?string $relativePath, array $allowedPrefixes): void
    {
        if ($relativePath === null || $relativePath === '') {
            return;
        }

        try {
            SecureImagePath::assertRelativePathMatches($relativePath, $allowedPrefixes);
        } catch (\InvalidArgumentException) {
            Log::warning('Skipped deleting image path outside allowed prefixes.', [
                'path' => $relativePath,
            ]);

            return;
        }

        $disk = Storage::disk($this->diskName());

        if ($disk->exists($relativePath)) {
            $disk->delete($relativePath);
        }
    }

    public function finalizeProductImage(int $productId, string $stagingRelativePath, ?string $previousFinalPath): void
    {
        $stagingPrefix = rtrim((string) config('image_upload.path.product_staging'), '/').'/';
        SecureImagePath::assertRelativePathMatches($stagingRelativePath, [$stagingPrefix]);

        $product = Product::query()->find($productId);

        if ($product === null) {
            return;
        }

        if (!Storage::disk($this->diskName())->exists($stagingRelativePath)) {
            Log::warning('Staging file missing, skipping finalize.', [
                'product_id' => $productId,
                'path' => $stagingRelativePath,
            ]);
            return;
        }

        $disk = Storage::disk($this->diskName());
        $stagingAbsolute = $disk->path($stagingRelativePath);

        $this->mimeValidator->assertFileIsAllowedRasterImage($stagingAbsolute);

        $tempJpeg = $this->encodingService->rasterToCompressedJpegTempFile($stagingAbsolute);

        try {
            $finalRelative = (string) config('image_upload.path.product_final').'/'.Str::uuid()->toString().'.jpg';
            $disk->put($finalRelative, File::get($tempJpeg));

            DB::transaction(function () use ($product, $finalRelative): void {
                $product->refresh();
                $product->update(['product_image' => $finalRelative]);
            });

            $disk->delete($stagingRelativePath);

            if ($previousFinalPath !== null
                && $previousFinalPath !== ''
                && $previousFinalPath !== $stagingRelativePath
                && $previousFinalPath !== $finalRelative) {
                $this->deleteIfOwned($previousFinalPath, [rtrim((string) config('image_upload.path.product_final'), '/').'/']);
            }
        } finally {
            if (is_file($tempJpeg)) {
                @unlink($tempJpeg);
            }
        }
    }

    public function finalizeReceiptImage(int $receiptId, string $stagingRelativePath, ?string $previousFinalPath): void
    {
        $stagingPrefix = rtrim((string) config('image_upload.path.receipt_staging'), '/').'/';
        SecureImagePath::assertRelativePathMatches($stagingRelativePath, [$stagingPrefix]);

        $receipt = Receipt::query()->find($receiptId);

        if ($receipt === null) {
            return;
        }

        $disk = Storage::disk($this->diskName());

        if (! $disk->exists($stagingRelativePath)) {
            Log::warning('Staging file missing, skipping finalize.', [
                'receipt_id' => $receiptId,
                'path' => $stagingRelativePath,
            ]);
            return;
        }

        $stagingAbsolute = $disk->path($stagingRelativePath);

        $this->mimeValidator->assertFileIsAllowedRasterImage($stagingAbsolute);

        $tempJpeg = $this->encodingService->rasterToCompressedJpegTempFile($stagingAbsolute);

        try {
            $finalRelative = (string) config('image_upload.path.receipt_final').'/'.Str::uuid()->toString().'.jpg';
            $disk->put($finalRelative, File::get($tempJpeg));

            DB::transaction(function () use ($receipt, $finalRelative): void {
                $receipt->refresh();
                $receipt->update(['receipt_image' => $finalRelative]);
            });

            $disk->delete($stagingRelativePath);

            if ($previousFinalPath !== null
                && $previousFinalPath !== ''
                && $previousFinalPath !== $stagingRelativePath
                && $previousFinalPath !== $finalRelative) {
                $this->deleteIfOwned($previousFinalPath, [rtrim((string) config('image_upload.path.receipt_final'), '/').'/']);
            }
        } finally {
            if (is_file($tempJpeg)) {
                @unlink($tempJpeg);
            }
        }
    }
}
