<?php

declare(strict_types=1);

namespace App\Services\Refunds;

use App\Contracts\Refunds\ReceiptRefundTotalCalculatorInterface;
use App\Domain\Promotions\PromotionProductRefundKind;
use App\Domain\Refunds\ReceiptPromotionMissingException;
use App\Domain\Refunds\ZeroRefundCalculatedException;
use App\Models\Product;
use App\Models\Promotion;
use App\Models\Receipt;
use App\Models\ReceiptProduct;
use Illuminate\Support\Collection;

final class ReceiptPromotionalRefundTotalCalculator implements ReceiptRefundTotalCalculatorInterface
{
    private const int BCMATH_SCALE_FOR_INTERMEDIATE_PERCENT = 6;

    private const int BCMATH_SCALE_FOR_MONEY = 4;

    public function estimateRefundTotalDisplayForDraft(Promotion $promotion, array $draftLines): string
    {
        $promotion->loadMissing([
            'products' => fn ($q) => $q->where('products.active', true),
        ]);

        $promotionProductsByProductId = $promotion->products->keyBy(
            static fn (Product $product): int => (int) $product->getKey(),
        );

        $runningTotal = '0.0000';

        foreach ($draftLines as $line) {
            if (! is_array($line)) {
                continue;
            }

            $productId = (int) ($line['product_id'] ?? 0);
            $quantity = (int) ($line['quantity'] ?? 0);

            if ($productId < 1 || $quantity < 1) {
                continue;
            }

            $promotionProduct = $promotionProductsByProductId->get($productId);

            if ($promotionProduct === null) {
                continue;
            }

            $unitPrice = bcadd((string) $promotionProduct->price, '0', self::BCMATH_SCALE_FOR_MONEY);
            $lineSubtotal = bcmul($unitPrice, (string) $quantity, self::BCMATH_SCALE_FOR_MONEY);

            $lineRefundAmount = $this->calculateLineRefundForConfiguredPromotionProduct(
                $promotionProduct,
                (string) $quantity,
                $lineSubtotal,
            );

            if ($lineRefundAmount === null) {
                continue;
            }

            $runningTotal = bcadd($runningTotal, $lineRefundAmount, self::BCMATH_SCALE_FOR_MONEY);
        }

        return $this->formatDollarSuffixTwoDecimals($runningTotal);
    }

    public function calculateTotalRefundAmountForReceipt(Receipt $receipt): string
    {
        $receipt->loadMissing(['receiptProducts', 'promotion.products']);

        if ($receipt->promotion === null) {
            throw ReceiptPromotionMissingException::forReceipt($receipt->getKey());
        }

        $promotionProductsByProductId = $receipt->promotion->products->keyBy(
            static fn (Product $product): int => (int) $product->getKey(),
        );

        $runningTotal = '0.0000';

        foreach ($receipt->receiptProducts as $receiptProductLine) {
            $lineRefundAmount = $this->calculateLineRefundAmountForReceiptProductLine(
                $receiptProductLine,
                $promotionProductsByProductId,
            );

            if ($lineRefundAmount === null) {
                continue;
            }

            $runningTotal = bcadd($runningTotal, $lineRefundAmount, self::BCMATH_SCALE_FOR_MONEY);
        }

        if (bccomp($runningTotal, '0', self::BCMATH_SCALE_FOR_MONEY) === 0) {
            throw ZeroRefundCalculatedException::forReceipt($receipt->getKey());
        }

        return $runningTotal;
    }

    public function summarizePurchaseAndRefundByLine(Receipt $receipt): array
    {
        $receipt->loadMissing(['receiptProducts.product', 'promotion.products']);

        $promotionProductsByProductId = $receipt->promotion !== null
            ? $receipt->promotion->products->keyBy(
                static fn (Product $product): int => (int) $product->getKey(),
            )
            : collect();

        $lines = [];
        $purchaseRunning = '0.0000';
        $refundRunning = '0.0000';

        foreach ($receipt->receiptProducts as $receiptProductLine) {
            $lineSubtotal = (string) $receiptProductLine->line_subtotal;
            $purchaseRunning = bcadd($purchaseRunning, $lineSubtotal, self::BCMATH_SCALE_FOR_MONEY);

            $promotionProduct = $promotionProductsByProductId->get((int) $receiptProductLine->product_id);
            $onPromotion = $promotionProduct !== null;

            $refundPerUnitDisplay = $onPromotion
                ? $this->formatRefundPerUnitDisplay(
                    PromotionProductRefundKind::from((string) $promotionProduct->pivot->refund_type),
                    (string) $promotionProduct->pivot->refund_value,
                )
                : '—';

            $refundAmount = $this->calculateLineRefundAmountForReceiptProductLine(
                $receiptProductLine,
                $promotionProductsByProductId,
            );

            $expectedRefundDisplay = $refundAmount !== null
                ? $this->formatDollarSuffixTwoDecimals($refundAmount)
                : '—';

            if ($refundAmount !== null) {
                $refundRunning = bcadd($refundRunning, $refundAmount, self::BCMATH_SCALE_FOR_MONEY);
            }

            $catalogPrice = $receiptProductLine->product?->price;

            $lines[] = [
                'on_promotion' => (bool) $onPromotion,
                'product_code' => (string) ($receiptProductLine->product?->sku ?? '—'),
                'quantity_display' => (string) max(0, (int) $receiptProductLine->quantity),
                'product_price_display' => $catalogPrice !== null
                    ? $this->formatDollarSuffixAmount((string) $catalogPrice)
                    : '—',
                'line_subtotal_display' => $this->formatDollarSuffixAmount($lineSubtotal),
                'refund_per_unit_display' => $refundPerUnitDisplay,
                'expected_refund_display' => $expectedRefundDisplay,
            ];
        }

        return [
            'lines' => $lines,
            'purchase_total_display' => $this->formatDollarSuffixAmount($purchaseRunning),
            'refund_total_display' => $this->formatDollarSuffixTwoDecimals($refundRunning),
        ];
    }

    /**
     * Currency display for refund columns (2 fractional digits, half-up).
     */
    private function formatDollarSuffixTwoDecimals(string $amount): string
    {
        $normalized = bcadd($amount, '0', 4);

        if (bccomp($normalized, '0', 4) === 0) {
            return '0.00$';
        }

        $negative = str_starts_with($normalized, '-');
        $abs = $negative ? substr($normalized, 1) : $normalized;
        $rounded = round((float) $abs, 2);
        $body = number_format($rounded, 2, '.', '');

        return ($negative ? '-' : '').$body.'$';
    }

    private function formatDollarSuffixAmount(string $amount): string
    {
        $normalized = bcadd($amount, '0', 4);

        if (bccomp($normalized, '0', 4) === 0) {
            return '0$';
        }

        $negative = str_starts_with($normalized, '-');
        if ($negative) {
            $normalized = substr($normalized, 1);
        }

        $parts = explode('.', $normalized, 2);
        $intPart = $parts[0] !== '' ? $parts[0] : '0';
        $frac = isset($parts[1]) ? substr(str_pad($parts[1], 4, '0', STR_PAD_RIGHT), 0, 4) : '0000';
        $frac = rtrim($frac, '0');

        $body = $frac === '' ? $intPart : $intPart.'.'.$frac;
        $prefix = $negative ? '-' : '';

        return $prefix.$body.'$';
    }

    private function formatRefundPerUnitDisplay(PromotionProductRefundKind $kind, string $configuredValue): string
    {
        return match ($kind) {
            PromotionProductRefundKind::FixedAmountPerUnit => $this->formatDollarSuffixAmount($configuredValue),
            PromotionProductRefundKind::PercentOfLineSubtotal => $this->formatPercentForDisplay($configuredValue).'%',
        };
    }

    private function formatPercentForDisplay(string $value): string
    {
        if (! is_numeric($value)) {
            return $value;
        }

        $normalized = rtrim(rtrim(number_format((float) $value, 4, '.', ''), '0'), '.');

        return $normalized === '' ? '0' : $normalized;
    }

    private function calculateLineRefundAmountForReceiptProductLine(
        ReceiptProduct $receiptProductLine,
        Collection $promotionProductsByProductId,
    ): ?string {
        $promotionProduct = $promotionProductsByProductId->get((int) $receiptProductLine->product_id);

        if ($promotionProduct === null) {
            return null;
        }

        return $this->calculateLineRefundForConfiguredPromotionProduct(
            $promotionProduct,
            (string) $receiptProductLine->quantity,
            (string) $receiptProductLine->line_subtotal,
        );
    }

    private function calculateLineRefundForConfiguredPromotionProduct(
        Product $promotionProduct,
        string $purchasedQuantity,
        string $lineSubtotal,
    ): ?string {
        $refundKind = PromotionProductRefundKind::from((string) $promotionProduct->pivot->refund_type);
        $configuredValue = (string) $promotionProduct->pivot->refund_value;

        return match ($refundKind) {
            PromotionProductRefundKind::FixedAmountPerUnit => bcmul(
                $configuredValue,
                $purchasedQuantity,
                self::BCMATH_SCALE_FOR_MONEY,
            ),
            PromotionProductRefundKind::PercentOfLineSubtotal => $this->percentOfLineSubtotal(
                $lineSubtotal,
                $configuredValue,
            ),
        };
    }

    private function percentOfLineSubtotal(string $lineSubtotal, string $percentValue): string
    {
        $product = bcmul($lineSubtotal, $percentValue, self::BCMATH_SCALE_FOR_INTERMEDIATE_PERCENT);

        return bcdiv($product, '100', self::BCMATH_SCALE_FOR_MONEY);
    }
}
