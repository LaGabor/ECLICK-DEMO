<?php

declare(strict_types=1);

namespace App\Services\Receipts;

use App\Contracts\Receipts\ReceiptStatusNotificationServiceInterface;
use App\Domain\Receipts\ReceiptStatusNotificationKind;
use App\Domain\Receipts\ReceiptSubmissionStatus;
use App\DTO\Receipts\ReceiptStatusNotificationDto;
use App\Jobs\Receipts\SendReceiptStatusNotificationJob;
use App\Mail\ReceiptAwaitingInformationMail;
use App\Mail\ReceiptBankTransferFailedMail;
use App\Mail\ReceiptPaidMail;
use App\Mail\ReceiptRejectedMail;
use App\Models\Receipt;
use App\Models\ReceiptStatusNotificationLog;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

final class ReceiptStatusNotificationService implements ReceiptStatusNotificationServiceInterface
{
    public function queueNotificationForReceipt(Receipt $receipt): void
    {
        $status = $receipt->status;
        if (! $status instanceof ReceiptSubmissionStatus) {
            return;
        }

        $kind = ReceiptStatusNotificationKind::tryFromSubmissionStatus($status);
        if ($kind === null) {
            return;
        }

        SendReceiptStatusNotificationJob::dispatch(
            new ReceiptStatusNotificationDto((int) $receipt->getKey(), $kind),
        );
    }

    public function deliverNotification(ReceiptStatusNotificationDto $dto): void
    {
        $receipt = Receipt::query()->with(['user', 'promotion'])->find($dto->receiptId);
        if ($receipt === null) {
            Log::warning('Receipt status notification skipped: receipt missing.', [
                'receipt_id' => $dto->receiptId,
                'kind' => $dto->kind->value,
            ]);

            return;
        }

        $email = $receipt->user?->email;
        if (blank($email)) {
            Log::warning('Receipt status notification skipped: participant email missing.', [
                'receipt_id' => $dto->receiptId,
                'kind' => $dto->kind->value,
            ]);

            return;
        }

        $lockKey = 'receipt-status-mail:'.$dto->uniqueQueueKey();

        try {
            Cache::lock($lockKey, 120)->block(15, function () use ($dto, $receipt, $email): void {
                if (ReceiptStatusNotificationLog::query()
                    ->where('receipt_id', $dto->receiptId)
                    ->where('kind', $dto->kind->value)
                    ->exists()) {
                    Log::info('Receipt status notification skipped: already sent (idempotent).', [
                        'receipt_id' => $dto->receiptId,
                        'kind' => $dto->kind->value,
                    ]);

                    return;
                }

                $mailable = match ($dto->kind) {
                    ReceiptStatusNotificationKind::Paid => new ReceiptPaidMail($receipt),
                    ReceiptStatusNotificationKind::Rejected => new ReceiptRejectedMail($receipt),
                    ReceiptStatusNotificationKind::AwaitingUserInformation => new ReceiptAwaitingInformationMail($receipt),
                    ReceiptStatusNotificationKind::PaymentFailed => new ReceiptBankTransferFailedMail($receipt),
                };

                Mail::to($email)->send($mailable);

                ReceiptStatusNotificationLog::query()->create([
                    'receipt_id' => $dto->receiptId,
                    'kind' => $dto->kind->value,
                    'sent_at' => now(),
                ]);
            });
        } catch (Throwable $exception) {
            Log::error('Receipt status notification mail delivery failed.', [
                'receipt_id' => $dto->receiptId,
                'kind' => $dto->kind->value,
                'participant_email' => $email,
                'exception_class' => $exception::class,
                'message' => $exception->getMessage(),
            ]);

            throw $exception;
        }
    }
}
