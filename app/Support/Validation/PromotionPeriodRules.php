<?php

declare(strict_types=1);

namespace App\Support\Validation;

use App\Filament\Resources\Promotions\Schemas\PromotionForm;
use App\Models\Promotion;
use DateTimeInterface;

/**
 * Date ordering for promotions: shared by {@see Promotion} persistence and Filament {@see PromotionForm}.
 */
final class PromotionPeriodRules
{
    /**
     * @return array<string, string>
     */
    public static function messages(): array
    {
        return [
            'purchase_end.after_or_equal' => __('filament.promotions.validation.purchase_end_after_or_equal'),
            'upload_start.after_or_equal' => __('filament.promotions.validation.upload_start_after_or_equal'),
            'upload_end.after_or_equal' => __('filament.promotions.validation.upload_end_after_or_equal'),
        ];
    }

    /**
     * @return array<string, list<string>>
     */
    public static function rules(): array
    {
        return [
            'purchase_start' => ['required', 'date'],
            'purchase_end' => ['required', 'date', 'after_or_equal:purchase_start'],
            'upload_start' => ['required', 'date', 'after_or_equal:purchase_start'],
            'upload_end' => ['required', 'date', 'after_or_equal:upload_start', 'after_or_equal:purchase_end'],
        ];
    }

    /**
     * @return array{purchase_start: string, purchase_end: string, upload_start: string, upload_end: string}
     */
    public static function datePayloadFromPromotion(Promotion $promotion): array
    {
        $toYmd = static function (mixed $value): string {
            if ($value instanceof DateTimeInterface) {
                return $value->format('Y-m-d');
            }

            if ($value === null || $value === '') {
                return '';
            }

            return (string) $value;
        };

        return [
            'purchase_start' => $toYmd($promotion->purchase_start),
            'purchase_end' => $toYmd($promotion->purchase_end),
            'upload_start' => $toYmd($promotion->upload_start),
            'upload_end' => $toYmd($promotion->upload_end),
        ];
    }
}
