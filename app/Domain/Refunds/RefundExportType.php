<?php

declare(strict_types=1);

namespace App\Domain\Refunds;

enum RefundExportType: string
{
    case Refund = 'refund';
}
