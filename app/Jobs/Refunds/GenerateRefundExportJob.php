<?php

declare(strict_types=1);

namespace App\Jobs\Refunds;

use App\Domain\Refunds\RefundExportStatus;
use App\Mail\RefundExportFailedMail;
use App\Models\RefundExport;
use App\Services\Refunds\RefundExportProcessor;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

final class GenerateRefundExportJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Four attempts with 30s, 3m, and 5m backoff between failures (per product spec).
     */
    public int $tries = 4;

    /**
     * @return array<int, int>
     */
    public function backoff(): array
    {
        return [30, 180, 300];
    }

    public function __construct(
        public readonly int $refundExportId,
    ) {
        $this->onQueue((string) config('refund_exports.queue'));
    }

    public function handle(RefundExportProcessor $processor): void
    {
        $export = RefundExport::query()->find($this->refundExportId);

        if ($export === null) {
            return;
        }

        $processor->process($export);
    }

    public function failed(?Throwable $exception): void
    {
        Log::error('GenerateRefundExportJob exhausted retries.', [
            'refund_export_id' => $this->refundExportId,
            'attempts' => $this->attempts(),
            'exception_class' => $exception ? $exception::class : null,
            'message' => $exception?->getMessage(),
        ]);

        $export = RefundExport::query()->find($this->refundExportId);

        if ($export === null) {
            return;
        }

        if ($export->status !== RefundExportStatus::Failed) {
            $export->update([
                'status' => RefundExportStatus::Failed,
                'last_error' => $exception?->getMessage() ?? 'Unknown error',
            ]);
        }

        $export->loadMissing('creator');

        if ($export->creator !== null && $export->creator->email !== null) {
            Mail::to($export->creator)->send(new RefundExportFailedMail($export->fresh(), $exception));
        }
    }
}
