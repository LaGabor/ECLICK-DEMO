<?php

declare(strict_types=1);

namespace App\Domain\Refunds;

use DomainException;

final class ReceiptPromotionMissingException extends DomainException
{
    public static function forReceipt(int|string $receiptId): self
    {
        return new self(sprintf(
            'Receipt %s is not linked to a promotion; cannot calculate promotional refund.',
            $receiptId,
        ));
    }
}
