<?php

declare(strict_types=1);

namespace App\Filament\Resources\Products\Concerns;

use App\Domain\Media\StoredImageKind;
use App\Jobs\Media\ProcessStoredImageJob;

trait DispatchesProductImageProcessingJob
{
    protected function dispatchProductImageJobIfNeeded(?string $previousFinalRelativePath): void
    {
        $record = $this->record;
        $path = $record->product_image;

        if (! is_string($path) || $path === '') {
            return;
        }

        $stagingDir = trim((string) config('image_upload.path.product_staging'), '/');

        if (! str_starts_with($path, $stagingDir.'/')) {
            return;
        }

        ProcessStoredImageJob::dispatch(
            StoredImageKind::Product,
            (int) $record->getKey(),
            $path,
            $previousFinalRelativePath,
        )->afterCommit();
    }
}
