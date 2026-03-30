<?php

declare(strict_types=1);

namespace App\Domain\Promotions;

enum PromotionProductRefundKind: string
{
    case FixedAmountPerUnit = 'fixed';
    case PercentOfLineSubtotal = 'percent';

    public function getLabel(): string
    {
        return match ($this) {
            self::FixedAmountPerUnit => __('filament.promotions.refund_kind.fixed'),
            self::PercentOfLineSubtotal => __('filament.promotions.refund_kind.percent'),
        };
    }
}
