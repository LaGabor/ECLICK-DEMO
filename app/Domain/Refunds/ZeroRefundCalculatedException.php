<?php

declare(strict_types=1);

namespace App\Domain\Refunds;

use DomainException;

final class ZeroRefundCalculatedException extends DomainException
{
    public static function forReceipt(int|string $receiptId): self
    {
        return new self(sprintf(
            'Calculated refund total is zero for receipt %s; verify promotional products on the receipt.',
            $receiptId,
        ));
    }
}
