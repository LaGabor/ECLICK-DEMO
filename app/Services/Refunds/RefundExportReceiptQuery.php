<?php

declare(strict_types=1);

namespace App\Services\Refunds;

use App\Domain\Receipts\ReceiptSubmissionStatus;
use App\Domain\Refunds\RefundExportItemPaymentStatus;
use App\DTO\Refunds\RefundExportRequestData;
use App\Models\Receipt;
use Illuminate\Database\Eloquent\Builder;

final class RefundExportReceiptQuery
{
    /**
     * Approved receipts in the purchase-date range that are not already tied to a pending or paid export batch.
     */
    public static function eligibleApprovedForRange(RefundExportRequestData $request): Builder
    {
        return Receipt::query()
            ->where('status', ReceiptSubmissionStatus::Approved)
            ->whereBetween('purchase_date', [
                $request->purchasePeriodStartsOn->toDateString(),
                $request->purchasePeriodEndsOn->toDateString(),
            ])
            ->whereDoesntHave('refundExportItems', function ($query): void {
                $query->whereIn('payment_status', [
                    RefundExportItemPaymentStatus::Pending,
                    RefundExportItemPaymentStatus::Paid,
                ]);
            });
    }

    public static function hasEligibleApprovedInRange(RefundExportRequestData $request): bool
    {
        return self::eligibleApprovedForRange($request)->exists();
    }
}
