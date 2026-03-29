<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Promotion extends Model
{
    protected $fillable = [
        'name',
        'purchase_start',
        'purchase_end',
        'upload_start',
        'upload_end',
    ];

    protected function casts(): array
    {
        return [
            'purchase_start' => 'date',
            'purchase_end' => 'date',
            'upload_start' => 'date',
            'upload_end' => 'date',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (Promotion $promotion): void {
            if ($promotion->upload_start === null && $promotion->purchase_start !== null) {
                $promotion->upload_start = $promotion->purchase_start;
            }
        });
    }

    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class)
            ->withPivot(['refund_type', 'refund_value'])
            ->withTimestamps();
    }

    public function receipts(): HasMany
    {
        return $this->hasMany(Receipt::class);
    }
}
