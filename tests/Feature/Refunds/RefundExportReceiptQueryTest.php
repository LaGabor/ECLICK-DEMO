<?php

declare(strict_types=1);

namespace Tests\Feature\Refunds;

use App\Domain\Receipts\ReceiptSubmissionStatus;
use App\Domain\Refunds\RefundExportItemPaymentStatus;
use App\Domain\Refunds\RefundExportStatus;
use App\Domain\Refunds\RefundExportType;
use App\DTO\Refunds\RefundExportRequestData;
use App\Models\Product;
use App\Models\Promotion;
use App\Models\Receipt;
use App\Models\ReceiptProduct;
use App\Models\RefundExport;
use App\Models\RefundExportItem;
use App\Models\User;
use App\Services\Refunds\RefundExportReceiptQuery;
use Carbon\CarbonImmutable;
use Database\Factories\ProductFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class RefundExportReceiptQueryTest extends TestCase
{
    use RefreshDatabase;

    public function test_has_eligible_is_false_when_no_approved_receipts_in_range(): void
    {
        $request = new RefundExportRequestData(
            CarbonImmutable::parse('2026-03-01'),
            CarbonImmutable::parse('2026-03-31'),
        );

        $this->assertFalse(RefundExportReceiptQuery::hasEligibleApprovedInRange($request));
    }

    public function test_has_eligible_is_true_when_approved_receipt_in_range_not_yet_exported(): void
    {
        $this->seedTinyPngToPrivateDisk(ProductFactory::DEMO_SEED_IMAGE_PATH);
        $this->seedTinyPngToPrivateDisk(ProductFactory::DEMO_SEED_RECEIPT_IMAGE_PATH);

        $participant = User::factory()->create();
        $product = Product::query()->create([
            'name' => 'P',
            'product_image' => ProductFactory::DEMO_SEED_IMAGE_PATH,
            'sku' => 'SKU-Q1',
            'price' => '10.00',
            'active' => true,
        ]);
        $promotion = Promotion::query()->create([
            'name' => 'Camp',
            'purchase_start' => '2026-01-01',
            'purchase_end' => '2026-12-31',
            'upload_end' => '2026-12-31',
        ]);
        $promotion->products()->attach($product->getKey(), [
            'refund_type' => 'fixed',
            'refund_value' => '1.0000',
        ]);

        Receipt::query()->create([
            'user_id' => $participant->getKey(),
            'promotion_id' => $promotion->getKey(),
            'receipt_image' => 'demo/r.jpg',
            'ap_code' => 'AP-1',
            'purchase_date' => '2026-03-10',
            'status' => ReceiptSubmissionStatus::Approved,
        ]);

        $request = new RefundExportRequestData(
            CarbonImmutable::parse('2026-03-01'),
            CarbonImmutable::parse('2026-03-31'),
        );

        $this->assertTrue(RefundExportReceiptQuery::hasEligibleApprovedInRange($request));
    }

    public function test_has_eligible_is_false_when_receipt_already_in_pending_batch(): void
    {
        $this->seedTinyPngToPrivateDisk(ProductFactory::DEMO_SEED_IMAGE_PATH);
        $this->seedTinyPngToPrivateDisk(ProductFactory::DEMO_SEED_RECEIPT_IMAGE_PATH);

        $admin = User::factory()->create();
        $participant = User::factory()->create();
        $product = Product::query()->create([
            'name' => 'P',
            'product_image' => ProductFactory::DEMO_SEED_IMAGE_PATH,
            'sku' => 'SKU-Q2',
            'price' => '10.00',
            'active' => true,
        ]);
        $promotion = Promotion::query()->create([
            'name' => 'Camp',
            'purchase_start' => '2026-01-01',
            'purchase_end' => '2026-12-31',
            'upload_end' => '2026-12-31',
        ]);
        $promotion->products()->attach($product->getKey(), [
            'refund_type' => 'fixed',
            'refund_value' => '1.0000',
        ]);

        $receipt = Receipt::query()->create([
            'user_id' => $participant->getKey(),
            'promotion_id' => $promotion->getKey(),
            'receipt_image' => ProductFactory::DEMO_SEED_RECEIPT_IMAGE_PATH,
            'ap_code' => 'AP-2',
            'purchase_date' => '2026-03-10',
            'status' => ReceiptSubmissionStatus::Approved,
        ]);

        ReceiptProduct::query()->create([
            'receipt_id' => $receipt->getKey(),
            'product_id' => $product->getKey(),
            'quantity' => 1,
            'line_subtotal' => '10.0000',
        ]);

        $export = RefundExport::query()->create([
            'created_by' => $admin->getKey(),
            'type' => RefundExportType::Refund,
            'status' => RefundExportStatus::Processing,
            'exported_at' => null,
            'period_start' => '2026-03-01',
            'period_end' => '2026-03-31',
            'total_rows' => 1,
            'zip_path' => null,
        ]);

        RefundExportItem::query()->create([
            'refund_export_id' => $export->getKey(),
            'receipt_id' => $receipt->getKey(),
            'refund_amount' => '1.0000',
            'payment_status' => RefundExportItemPaymentStatus::Pending,
        ]);

        $request = new RefundExportRequestData(
            CarbonImmutable::parse('2026-03-01'),
            CarbonImmutable::parse('2026-03-31'),
        );

        $this->assertFalse(RefundExportReceiptQuery::hasEligibleApprovedInRange($request));
    }
}
