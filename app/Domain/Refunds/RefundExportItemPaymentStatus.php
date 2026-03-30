<?php

declare(strict_types=1);

namespace App\Domain\Refunds;

enum RefundExportItemPaymentStatus: string
{
    case Pending = 'pending';
    case Paid = 'paid';
    case Failed = 'failed';

    public function getLabel(): string
    {
        return match ($this) {
            self::Pending => __('filament.refund_exports.item_payment.pending'),
            self::Paid => __('filament.refund_exports.item_payment.paid'),
            self::Failed => __('filament.refund_exports.item_payment.failed'),
        };
    }
}
