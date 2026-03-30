<?php

declare(strict_types=1);

namespace App\DTO\Refunds;

use Carbon\CarbonImmutable;

final readonly class RefundExportRequestData
{
    public function __construct(
        public CarbonImmutable $purchasePeriodStartsOn,
        public CarbonImmutable $purchasePeriodEndsOn,
    ) {
        if ($this->purchasePeriodEndsOn->lessThan($this->purchasePeriodStartsOn)) {
            throw new \InvalidArgumentException('Refund export period end date must be on or after the start date.');
        }
    }
}
