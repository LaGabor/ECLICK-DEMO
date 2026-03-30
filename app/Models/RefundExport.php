<?php

declare(strict_types=1);

namespace App\Models;

use App\Domain\Refunds\RefundExportStatus;
use App\Domain\Refunds\RefundExportType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RefundExport extends Model
{
    protected $fillable = [
        'created_by',
        'type',
        'status',
        'exported_at',
        'period_start',
        'period_end',
        'total_rows',
        'zip_path',
        'last_error',
    ];

    protected function casts(): array
    {
        return [
            'type' => RefundExportType::class,
            'status' => RefundExportStatus::class,
            'exported_at' => 'datetime',
            'period_start' => 'date',
            'period_end' => 'date',
            'total_rows' => 'integer',
        ];
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function items(): HasMany
    {
        return $this->hasMany(RefundExportItem::class);
    }
}
