<?php

declare(strict_types=1);

namespace App\Jobs\Receipts;

use App\Contracts\Receipts\ReceiptStatusNotificationServiceInterface;
use App\DTO\Receipts\ReceiptStatusNotificationDto;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

final class SendReceiptStatusNotificationJob implements ShouldBeUnique, ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 6;

    /**
     * @return list<int>
     */
    public function backoff(): array
    {
        return [60, 300, 900, 3600, 43200];
    }

    public function __construct(
        public readonly ReceiptStatusNotificationDto $dto,
    ) {
        /** @see docker/php/wait-for-migrations.sh {@code queue:work database --queue=mail} */
        $this->onQueue('mail');
    }

    public function uniqueId(): string
    {
        return $this->dto->uniqueQueueKey();
    }

    public function handle(ReceiptStatusNotificationServiceInterface $notifications): void
    {
        $notifications->deliverNotification($this->dto);
    }

    public function failed(?Throwable $exception): void
    {
        Log::error('SendReceiptStatusNotificationJob exhausted retries.', [
            'receipt_id' => $this->dto->receiptId,
            'kind' => $this->dto->kind->value,
            'attempts' => $this->attempts(),
            'exception_class' => $exception ? $exception::class : null,
            'message' => $exception?->getMessage(),
        ]);
    }
}
