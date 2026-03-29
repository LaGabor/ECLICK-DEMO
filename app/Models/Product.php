<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Product extends Model
{
    protected $fillable = [
        'name',
        'product_image',
        'sku',
        'price',
        'active',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'decimal:4',
            'active' => 'boolean',
        ];
    }

    public function promotions(): BelongsToMany
    {
        return $this->belongsToMany(Promotion::class)
            ->withPivot(['refund_type', 'refund_value'])
            ->withTimestamps();
    }

    public function receiptProducts(): HasMany
    {
        return $this->hasMany(ReceiptProduct::class);
    }
}
