<?php

namespace App\Models;

use App\Support\Validation\PromotionPeriodRules;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class Promotion extends Model
{
    use SoftDeletes;

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

            $validator = Validator::make(
                PromotionPeriodRules::datePayloadFromPromotion($promotion),
                PromotionPeriodRules::rules(),
                PromotionPeriodRules::messages(),
            );

            if ($validator->fails()) {
                throw new ValidationException($validator);
            }
        });
    }

    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'promotion_product')
            ->withPivot(['refund_type', 'refund_value'])
            ->withTimestamps();
    }

    public function receipts(): HasMany
    {
        return $this->hasMany(Receipt::class);
    }

    public function scopeAcceptingParticipantUploadsOn(Builder $query, CarbonInterface $on): Builder
    {
        $date = $on->toDateString();

        return $query
            ->whereDate('upload_start', '<=', $date)
            ->whereDate('upload_end', '>=', $date);
    }

    public function isPurchaseDateWithinPurchasePeriod(CarbonInterface $purchaseDate): bool
    {
        $d = $purchaseDate->toDateString();

        return $this->purchase_start->toDateString() <= $d
            && $d <= $this->purchase_end->toDateString();
    }

    public function earliestAllowedPurchaseDate(): CarbonImmutable
    {
        return CarbonImmutable::parse($this->purchase_start)->startOfDay();
    }

    public function latestAllowedPurchaseDateAsOf(CarbonInterface $asOf): CarbonImmutable
    {
        $purchaseEnd = CarbonImmutable::parse($this->purchase_end)->startOfDay();
        $reference = CarbonImmutable::parse($asOf)->startOfDay();

        return $purchaseEnd->lte($reference) ? $purchaseEnd : $reference;
    }
}
