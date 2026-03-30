<?php

declare(strict_types=1);

namespace App\Services\Receipts;

use App\Contracts\Refunds\ReceiptRefundTotalCalculatorInterface;
use App\Domain\Refunds\ReceiptPromotionMissingException;
use App\Domain\Refunds\ZeroRefundCalculatedException;
use App\Models\Receipt;
use Illuminate\Support\Facades\Storage;

final class ReceiptApprovalEligibilityService
{
    public function __construct(
        private readonly ReceiptRefundTotalCalculatorInterface $refundCalculator,
    ) {}

    public function canApprove(Receipt $receipt): bool
    {
        if (blank(trim((string) $receipt->ap_code))) {
            return false;
        }

        if (blank((string) $receipt->receipt_image)) {
            return false;
        }

        if (! Storage::disk((string) config('image_upload.disk'))->exists((string) $receipt->receipt_image)) {
            return false;
        }

        $receipt->loadMissing(['receiptProducts', 'promotion.products']);

        try {
            $amount = $this->refundCalculator->calculateTotalRefundAmountForReceipt($receipt);

            return bccomp($amount, '0', 4) > 0;
        } catch (ReceiptPromotionMissingException|ZeroRefundCalculatedException) {
            return false;
        }
    }
}
