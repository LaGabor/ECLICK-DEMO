<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Contracts\Receipts\ReceiptStatusNotificationServiceInterface;
use App\Events\ReceiptSubmissionStatusChanged;
use App\Jobs\Receipts\SendReceiptStatusNotificationJob;
use App\Models\Receipt;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Reacts to status transitions by enqueueing {@see SendReceiptStatusNotificationJob}
 * via {@see ReceiptStatusNotificationServiceInterface} when the new status requires a participant email.
 *
 * Runs synchronously so the request thread only dispatches the dedicated mail job (retries, idempotency live there).
 */
final class SendReceiptStatusNotificationListener
{
    public function __construct(
        private readonly ReceiptStatusNotificationServiceInterface $notifications,
    ) {}

    public function handle(ReceiptSubmissionStatusChanged $event): void
    {
        try {
            $receipt = Receipt::query()->find($event->receipt->getKey());
            if ($receipt === null) {
                Log::warning('Receipt status notification listener skipped: receipt no longer exists.', [
                    'receipt_id' => $event->receipt->getKey(),
                    'new_status' => $event->newStatus->value,
                ]);

                return;
            }

            $this->notifications->queueNotificationForReceipt($receipt);
        } catch (Throwable $exception) {
            Log::error('Receipt status notification listener failed before job dispatch.', [
                'receipt_id' => $event->receipt->getKey(),
                'previous_status' => $event->previousStatus->value,
                'new_status' => $event->newStatus->value,
                'exception_class' => $exception::class,
                'message' => $exception->getMessage(),
            ]);

            throw $exception;
        }
    }
}
