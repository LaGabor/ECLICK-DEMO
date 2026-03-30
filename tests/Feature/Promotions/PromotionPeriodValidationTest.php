<?php

declare(strict_types=1);

namespace Tests\Feature\Promotions;

use App\Models\Promotion;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

final class PromotionPeriodValidationTest extends TestCase
{
    use RefreshDatabase;

    public function test_saves_when_periods_are_ordered_correctly(): void
    {
        $promotion = Promotion::query()->create([
            'name' => 'Valid campaign',
            'purchase_start' => '2026-01-01',
            'purchase_end' => '2026-01-31',
            'upload_start' => '2026-01-01',
            'upload_end' => '2026-02-28',
        ]);

        $this->assertSame(1, Promotion::query()->count());
        $this->assertSame('2026-01-01', $promotion->purchase_start->format('Y-m-d'));
    }

    public function test_rejects_upload_start_before_purchase_start(): void
    {
        $this->expectException(ValidationException::class);

        Promotion::query()->create([
            'name' => 'Bad upload start',
            'purchase_start' => '2026-02-01',
            'purchase_end' => '2026-02-28',
            'upload_start' => '2026-01-01',
            'upload_end' => '2026-03-31',
        ]);
    }

    public function test_rejects_purchase_end_before_purchase_start(): void
    {
        $this->expectException(ValidationException::class);

        Promotion::query()->create([
            'name' => 'Bad purchase range',
            'purchase_start' => '2026-02-01',
            'purchase_end' => '2026-01-01',
            'upload_start' => '2026-02-01',
            'upload_end' => '2026-03-31',
        ]);
    }

    public function test_rejects_upload_end_before_purchase_end(): void
    {
        $this->expectException(ValidationException::class);

        Promotion::query()->create([
            'name' => 'Bad upload end vs purchase',
            'purchase_start' => '2026-01-01',
            'purchase_end' => '2026-03-31',
            'upload_start' => '2026-01-01',
            'upload_end' => '2026-02-01',
        ]);
    }

    public function test_rejects_upload_end_before_upload_start(): void
    {
        $this->expectException(ValidationException::class);

        Promotion::query()->create([
            'name' => 'Bad upload range',
            'purchase_start' => '2026-01-01',
            'purchase_end' => '2026-01-31',
            'upload_start' => '2026-02-15',
            'upload_end' => '2026-02-01',
        ]);
    }
}
