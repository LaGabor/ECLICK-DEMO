<?php

namespace App\Models;

use App\Support\Validation\ProductListPriceRules;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Product extends Model
{
    use HasFactory, SoftDeletes;

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
            'price' => 'decimal:2',
            'active' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (Product $product): void {
            if (! array_key_exists('price', $product->getAttributes())) {
                return;
            }

            $product->setAttribute(
                'price',
                ProductListPriceRules::normalizeTwoDecimalString($product->getAttributes()['price']),
            );
        });
    }

    public function promotions(): BelongsToMany
    {
        return $this->belongsToMany(Promotion::class, 'promotion_product')
            ->withPivot(['refund_type', 'refund_value'])
            ->withTimestamps();
    }

    public function receiptProducts(): HasMany
    {
        return $this->hasMany(ReceiptProduct::class);
    }
}
