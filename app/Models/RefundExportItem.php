<?php

declare(strict_types=1);

namespace App\Models;

use App\Domain\Refunds\RefundExportItemPaymentStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RefundExportItem extends Model
{
    protected $fillable = [
        'refund_export_id',
        'receipt_id',
        'refund_amount',
        'payment_status',
        'payment_error',
    ];

    protected function casts(): array
    {
        return [
            'refund_amount' => 'decimal:4',
            'payment_status' => RefundExportItemPaymentStatus::class,
        ];
    }

    public function refundExport(): BelongsTo
    {
        return $this->belongsTo(RefundExport::class);
    }

    public function receipt(): BelongsTo
    {
        return $this->belongsTo(Receipt::class);
    }
}
