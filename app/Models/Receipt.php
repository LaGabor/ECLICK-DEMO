<?php

declare(strict_types=1);

namespace App\Models;

use App\Domain\Receipts\ReceiptSubmissionStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Receipt extends Model
{
    protected $fillable = [
        'user_id',
        'promotion_id',
        'receipt_image',
        'ap_code',
        'purchase_date',
        'status',
        'admin_note',
        'appeal_message',
        'reviewed_at',
        'paid_at',
        'appeal_submitted_at',
    ];

    protected function casts(): array
    {
        return [
            'purchase_date' => 'date',
            'reviewed_at' => 'datetime',
            'paid_at' => 'datetime',
            'appeal_submitted_at' => 'datetime',
            'status' => ReceiptSubmissionStatus::class,
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function promotion(): BelongsTo
    {
        return $this->belongsTo(Promotion::class);
    }

    public function receiptProducts(): HasMany
    {
        return $this->hasMany(ReceiptProduct::class);
    }

    public function refundExportItems(): HasMany
    {
        return $this->hasMany(RefundExportItem::class);
    }
}
