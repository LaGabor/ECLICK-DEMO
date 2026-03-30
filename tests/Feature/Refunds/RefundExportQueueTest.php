<?php

declare(strict_types=1);

namespace Tests\Feature\Refunds;

use App\Contracts\Refunds\ReceiptRefundTotalCalculatorInterface;
use App\Contracts\Refunds\RefundExportGeneratorInterface;
use App\Domain\Receipts\ReceiptSubmissionStatus;
use App\Domain\Refunds\RefundExportStatus;
use App\Domain\Refunds\RefundExportType;
use App\DTO\Refunds\RefundExportRequestData;
use App\Jobs\Refunds\GenerateRefundExportJob;
use App\Models\Product;
use App\Models\Promotion;
use App\Models\Receipt;
use App\Models\ReceiptProduct;
use App\Models\RefundExport;
use App\Models\User;
use App\Support\UserRole;
use Carbon\CarbonImmutable;
use Database\Factories\ProductFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\URL;
use Spatie\Permission\Models\Role;
use Tests\TestCase;
use ZipArchive;

final class RefundExportQueueTest extends TestCase
{
    use RefreshDatabase;

    public function test_generate_job_is_pushed_when_requesting_export(): void
    {
        Queue::fake();

        Role::findOrCreate(UserRole::Admin->value, 'web');
        $admin = User::factory()->create();
        $admin->assignRole(UserRole::Admin);

        $export = app(RefundExportGeneratorInterface::class)->queueRefundExport(
            new RefundExportRequestData(
                CarbonImmutable::parse('2026-01-01'),
                CarbonImmutable::parse('2026-12-31'),
            ),
            $admin,
        );

        $this->assertSame(RefundExportStatus::Pending, $export->status);

        Queue::assertPushedOn(
            (string) config('refund_exports.queue'),
            GenerateRefundExportJob::class,
            function (GenerateRefundExportJob $job) use ($export): bool {
                return $job->refundExportId === (int) $export->getKey();
            },
        );
    }

    public function test_export_completes_with_zip_when_eligible_receipts_exist(): void
    {
        $this->seedTinyPngToPrivateDisk(ProductFactory::DEMO_SEED_IMAGE_PATH);
        $this->seedTinyPngToPrivateDisk(ProductFactory::DEMO_SEED_RECEIPT_IMAGE_PATH);

        $exportRoot = sys_get_temp_dir().'/eclick-refund-export-'.uniqid('', true);
        mkdir($exportRoot, 0777, true);
        config([
            'filesystems.disks.refund_export_test' => [
                'driver' => 'local',
                'root' => $exportRoot,
                'throw' => true,
            ],
            'refund_exports.disk' => 'refund_export_test',
            'excel.temporary_files.local_path' => $exportRoot.'/laravel-excel',
        ]);
        mkdir($exportRoot.'/laravel-excel', 0777, true);

        $calculator = \Mockery::mock(ReceiptRefundTotalCalculatorInterface::class);
        $calculator->allows('calculateTotalRefundAmountForReceipt')->andReturn('5.0000');
        $calculator->allows('summarizePurchaseAndRefundByLine')->andReturn(['lines' => [], 'purchase_total_display' => '0$', 'refund_total_display' => '0$']);
        $calculator->allows('estimateRefundTotalDisplayForDraft')->andReturn('0.00$');
        $this->instance(ReceiptRefundTotalCalculatorInterface::class, $calculator);

        Role::findOrCreate(UserRole::Admin->value, 'web');
        $admin = User::factory()->create();
        $admin->assignRole(UserRole::Admin);

        $participant = User::factory()->create([
            'bank_account' => '1234567890123456',
        ]);

        $product = Product::query()->create([
            'name' => 'Test product',
            'product_image' => ProductFactory::DEMO_SEED_IMAGE_PATH,
            'sku' => 'SKU-1',
            'price' => '10.00',
            'active' => true,
        ]);

        $promotion = Promotion::query()->create([
            'name' => 'Test campaign',
            'purchase_start' => '2026-01-01',
            'purchase_end' => '2026-12-31',
            'upload_end' => '2026-12-31',
        ]);

        $promotion->products()->attach($product->getKey(), [
            'refund_type' => 'fixed',
            'refund_value' => '2.5000',
        ]);

        $receipt = Receipt::query()->create([
            'user_id' => $participant->getKey(),
            'promotion_id' => $promotion->getKey(),
            'receipt_image' => ProductFactory::DEMO_SEED_RECEIPT_IMAGE_PATH,
            'ap_code' => 'AP-EXPORT',
            'purchase_date' => '2026-03-15',
            'status' => ReceiptSubmissionStatus::Approved,
        ]);

        ReceiptProduct::query()->create([
            'receipt_id' => $receipt->getKey(),
            'product_id' => $product->getKey(),
            'quantity' => 2,
            'line_subtotal' => '20.0000',
        ]);

        $export = app(RefundExportGeneratorInterface::class)->queueRefundExport(
            new RefundExportRequestData(
                CarbonImmutable::parse('2026-03-01'),
                CarbonImmutable::parse('2026-03-31'),
            ),
            $admin,
        );

        $export->refresh();

        $this->assertSame(RefundExportStatus::Done, $export->status);
        $this->assertNotNull($export->zip_path);
        $this->assertSame(1, $export->total_rows);
        $this->assertFileExists($exportRoot.'/'.ltrim((string) $export->zip_path, '/'));

        $receipt->refresh();
        $this->assertSame(ReceiptSubmissionStatus::PaymentPending, $receipt->status);

        $downloadUrl = route('filament.admin.downloads.refund-export-zip', ['refundExport' => $export], absolute: true);

        $this->actingAs($admin)
            ->get($downloadUrl)
            ->assertOk()
            ->assertHeader('content-type', 'application/zip');

        $relativeSigned = URL::temporarySignedRoute(
            'filament.admin.refund-exports.download-signed',
            now()->addDay(),
            ['refundExport' => $export->getKey()],
            false,
        );

        $this->actingAs($admin)
            ->get('http://admin.localhost:9999'.$relativeSigned)
            ->assertOk()
            ->assertHeader('content-type', 'application/zip');
    }

    public function test_export_csv_aggregates_multiple_receipts_per_participant_into_one_row(): void
    {
        $this->seedTinyPngToPrivateDisk(ProductFactory::DEMO_SEED_IMAGE_PATH);
        $this->seedTinyPngToPrivateDisk(ProductFactory::DEMO_SEED_RECEIPT_IMAGE_PATH);

        $exportRoot = sys_get_temp_dir().'/eclick-refund-export-agg-'.uniqid('', true);
        mkdir($exportRoot, 0777, true);
        config([
            'filesystems.disks.refund_export_test' => [
                'driver' => 'local',
                'root' => $exportRoot,
                'throw' => true,
            ],
            'refund_exports.disk' => 'refund_export_test',
            'excel.temporary_files.local_path' => $exportRoot.'/laravel-excel',
        ]);
        mkdir($exportRoot.'/laravel-excel', 0777, true);

        $calculator = \Mockery::mock(ReceiptRefundTotalCalculatorInterface::class);
        $calculator->allows('calculateTotalRefundAmountForReceipt')->andReturn('5.0000');
        $calculator->allows('summarizePurchaseAndRefundByLine')->andReturn(['lines' => [], 'purchase_total_display' => '0$', 'refund_total_display' => '0$']);
        $calculator->allows('estimateRefundTotalDisplayForDraft')->andReturn('0.00$');
        $this->instance(ReceiptRefundTotalCalculatorInterface::class, $calculator);

        Role::findOrCreate(UserRole::Admin->value, 'web');
        $admin = User::factory()->create();
        $admin->assignRole(UserRole::Admin);

        $participant = User::factory()->create([
            'bank_account' => '117730909900000099990099',
        ]);

        $product = Product::query()->create([
            'name' => 'Test product',
            'product_image' => ProductFactory::DEMO_SEED_IMAGE_PATH,
            'sku' => 'SKU-AGG',
            'price' => '10.00',
            'active' => true,
        ]);

        $promotion = Promotion::query()->create([
            'name' => 'Test campaign agg',
            'purchase_start' => '2026-01-01',
            'purchase_end' => '2026-12-31',
            'upload_end' => '2026-12-31',
        ]);

        $promotion->products()->attach($product->getKey(), [
            'refund_type' => 'fixed',
            'refund_value' => '2.5000',
        ]);

        foreach (['AP-AGG-1', 'AP-AGG-2'] as $apCode) {
            $receipt = Receipt::query()->create([
                'user_id' => $participant->getKey(),
                'promotion_id' => $promotion->getKey(),
                'receipt_image' => ProductFactory::DEMO_SEED_RECEIPT_IMAGE_PATH,
                'ap_code' => $apCode,
                'purchase_date' => '2026-03-15',
                'status' => ReceiptSubmissionStatus::Approved,
            ]);

            ReceiptProduct::query()->create([
                'receipt_id' => $receipt->getKey(),
                'product_id' => $product->getKey(),
                'quantity' => 2,
                'line_subtotal' => '20.0000',
            ]);
        }

        $export = app(RefundExportGeneratorInterface::class)->queueRefundExport(
            new RefundExportRequestData(
                CarbonImmutable::parse('2026-03-01'),
                CarbonImmutable::parse('2026-03-31'),
            ),
            $admin,
        );

        $export->refresh();

        $this->assertSame(RefundExportStatus::Done, $export->status);
        $this->assertSame(1, $export->total_rows);
        $this->assertSame(2, $export->items()->count());

        $zipAbsolute = $exportRoot.'/'.ltrim((string) $export->zip_path, '/');
        $this->assertFileExists($zipAbsolute);

        $zip = new ZipArchive;
        $this->assertTrue($zip->open($zipAbsolute) === true);
        $csvContents = $zip->getFromIndex(0);
        $zip->close();

        $this->assertIsString($csvContents);
        $this->assertStringContainsString('"10.00"', $csvContents);
        $lines = explode("\n", str_replace("\r\n", "\n", trim($csvContents)));
        $dataLines = array_values(array_filter(
            array_slice($lines, 1),
            static fn (string $line): bool => trim($line) !== '',
        ));
        $this->assertCount(1, $dataLines);
    }

    public function test_signed_refund_export_download_redirects_guests_to_admin_login(): void
    {
        Role::findOrCreate(UserRole::Admin->value, 'web');
        $admin = User::factory()->create();
        $admin->assignRole(UserRole::Admin);

        $export = RefundExport::query()->create([
            'created_by' => $admin->getKey(),
            'type' => RefundExportType::Refund,
            'status' => RefundExportStatus::Pending,
            'exported_at' => null,
            'period_start' => '2026-03-01',
            'period_end' => '2026-03-31',
            'total_rows' => 0,
            'zip_path' => null,
        ]);

        $relativeSigned = URL::temporarySignedRoute(
            'filament.admin.refund-exports.download-signed',
            now()->addDay(),
            ['refundExport' => $export->getKey()],
            false,
        );

        $this->get('http://admin.localhost:9999'.$relativeSigned)
            ->assertRedirect();
    }

    public function test_export_fails_when_no_eligible_receipts(): void
    {
        Role::findOrCreate(UserRole::Admin->value, 'web');
        $admin = User::factory()->create();
        $admin->assignRole(UserRole::Admin);

        $export = app(RefundExportGeneratorInterface::class)->queueRefundExport(
            new RefundExportRequestData(
                CarbonImmutable::parse('2026-01-01'),
                CarbonImmutable::parse('2026-01-31'),
            ),
            $admin,
        );

        $export->refresh();

        $this->assertSame(RefundExportStatus::Failed, $export->status);
        $this->assertNotNull($export->last_error);
    }
}
