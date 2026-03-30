<?php

declare(strict_types=1);

namespace App\Contracts\Receipts;

use App\DTO\Receipts\ReceiptStatusNotificationDto;
use App\Models\Receipt;

interface ReceiptStatusNotificationServiceInterface
{
    /**
     * Queue a notification job when the receipt's current status requires a participant email.
     */
    public function queueNotificationForReceipt(Receipt $receipt): void;

    /**
     * Send the email for this DTO, with idempotency and structured logging on failure.
     */
    public function deliverNotification(ReceiptStatusNotificationDto $dto): void;
}
