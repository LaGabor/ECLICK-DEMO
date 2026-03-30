<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReceiptProduct extends Model
{
    private const BCMATH_SCALE_FOR_MONEY = 4;

    protected $fillable = [
        'receipt_id',
        'product_id',
        'quantity',
        'line_subtotal',
    ];

    protected function casts(): array
    {
        return [
            'line_subtotal' => 'decimal:4',
        ];
    }

    public function receipt(): BelongsTo
    {
        return $this->belongsTo(Receipt::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    protected static function booted(): void
    {
        static::saving(function (ReceiptProduct $receiptProduct): void {
            $product = Product::query()->find($receiptProduct->product_id);
            if ($product === null) {
                return;
            }

            $quantity = max(0, (int) ($receiptProduct->quantity ?? 0));
            $unitPrice = (string) $product->getAttribute('price');
            $receiptProduct->line_subtotal = self::multiplyUnitPriceByQuantity(
                $unitPrice,
                $quantity,
            );
        });
    }

    private static function multiplyUnitPriceByQuantity(string $unitPrice, int $quantity): string
    {
        if (\function_exists('bcmul')) {
            return \bcmul($unitPrice, (string) $quantity, self::BCMATH_SCALE_FOR_MONEY);
        }

        return number_format(
            (float) $unitPrice * $quantity,
            self::BCMATH_SCALE_FOR_MONEY,
            '.',
            '',
        );
    }
}
