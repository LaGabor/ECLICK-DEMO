<?php

declare(strict_types=1);

namespace App\DTO\Receipts;

use App\Domain\Receipts\ReceiptStatusNotificationKind;

final readonly class ReceiptStatusNotificationDto
{
    public function __construct(
        public int $receiptId,
        public ReceiptStatusNotificationKind $kind,
    ) {}

    public function uniqueQueueKey(): string
    {
        return "{$this->receiptId}-{$this->kind->value}";
    }
}
