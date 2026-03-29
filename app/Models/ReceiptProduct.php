<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReceiptProduct extends Model
{
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
}
