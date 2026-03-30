<?php

declare(strict_types=1);

namespace App\Jobs\Refunds;

use App\Mail\RefundExportReadyMail;
use App\Models\RefundExport;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

final class SendRefundExportReadyNotificationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    /**
     * @return array<int, int>
     */
    public function backoff(): array
    {
        return [60, 300];
    }

    public function __construct(
        public readonly int $refundExportId,
    ) {
        $this->onQueue((string) config('refund_exports.queue'));
    }

    public function handle(): void
    {
        $export = RefundExport::query()->find($this->refundExportId);

        if ($export === null || $export->zip_path === null || $export->zip_path === '') {
            return;
        }

        $export->loadMissing('creator');

        if ($export->creator === null || $export->creator->email === null) {
            return;
        }

        Mail::to($export->creator)->send(new RefundExportReadyMail($export));
    }

    public function failed(?Throwable $exception): void
    {
        Log::error('SendRefundExportReadyNotificationJob exhausted retries.', [
            'refund_export_id' => $this->refundExportId,
            'exception_class' => $exception ? $exception::class : null,
            'message' => $exception?->getMessage(),
        ]);
    }
}
