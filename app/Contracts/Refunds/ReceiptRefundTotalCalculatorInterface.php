<?php

declare(strict_types=1);

namespace App\Contracts\Refunds;

use App\Models\Promotion;
use App\Models\Receipt;

interface ReceiptRefundTotalCalculatorInterface
{
    /**
     * Live form preview: same rules as saved receipts (catalog price × quantity per line, promotion pivot refund).
     *
     * @param  list<array{product_id?: mixed, quantity?: mixed}>  $draftLines
     */
    public function estimateRefundTotalDisplayForDraft(Promotion $promotion, array $draftLines): string;

    public function calculateTotalRefundAmountForReceipt(Receipt $receipt): string;

    /**
     * purchase_total_display and refund_total_display use the same dollar-suffix formatting as line refunds
     * (integer part, optional trimmed fractional cents up to 4 digits, then "$").
     *
     * @return array{
     *     lines: list<array{
     *         on_promotion: bool,
     *         product_code: string,
     *         quantity_display: string,
     *         product_price_display: string,
     *         line_subtotal_display: string,
     *         refund_per_unit_display: string,
     *         expected_refund_display: string,
     *     }>,
     *     purchase_total_display: string,
     *     refund_total_display: string,
     * }
     */
    public function summarizePurchaseAndRefundByLine(Receipt $receipt): array;
}
