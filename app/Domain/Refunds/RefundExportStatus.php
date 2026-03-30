<?php

declare(strict_types=1);

namespace App\Domain\Refunds;

enum RefundExportStatus: string
{
    case Pending = 'pending';
    case Processing = 'processing';
    case Done = 'done';
    case Failed = 'failed';

    public function getLabel(): string
    {
        return match ($this) {
            self::Pending => __('filament.refund_exports.status.pending'),
            self::Processing => __('filament.refund_exports.status.processing'),
            self::Done => __('filament.refund_exports.status.done'),
            self::Failed => __('filament.refund_exports.status.failed'),
        };
    }
}
