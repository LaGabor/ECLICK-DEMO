<?php

declare(strict_types=1);

namespace App\Jobs\Media;

use App\Domain\Media\StoredImageKind;
use App\Services\Media\ImageStorageService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

final class ProcessStoredImageJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    /**
     * @return array<int, int>
     */
    public function backoff(): array
    {
        return [15, 30, 180];
    }

    public function __construct(
        public readonly StoredImageKind $kind,
        public readonly int $modelId,
        public readonly string $stagingRelativePath,
        public readonly ?string $previousFinalRelativePath = null,
    ) {
        $this->onQueue((string) config('image_upload.queue'));
        $this->afterCommit();
    }

    public function handle(ImageStorageService $images): void
    {
        match ($this->kind) {
            StoredImageKind::Product => $images->finalizeProductImage(
                $this->modelId,
                $this->stagingRelativePath,
                $this->previousFinalRelativePath,
            ),
            StoredImageKind::Receipt => $images->finalizeReceiptImage(
                $this->modelId,
                $this->stagingRelativePath,
                $this->previousFinalRelativePath,
            ),
        };
    }

    public function failed(?Throwable $exception): void
    {
        Log::error('ProcessStoredImageJob failed permanently.', [
            'kind' => $this->kind->value,
            'model_id' => $this->modelId,
            'staging_path' => $this->stagingRelativePath,
            'exception' => $exception ? $exception::class : null,
            'message' => $exception?->getMessage(),
        ]);
    }
}
