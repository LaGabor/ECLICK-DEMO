<?php

declare(strict_types=1);

namespace Tests\Feature\Database;

use App\Domain\Receipts\ReceiptSubmissionStatus;
use App\Models\ContactMessage;
use App\Models\Product;
use App\Models\Promotion;
use App\Models\Receipt;
use App\Models\User;
use Database\Factories\ProductFactory;
use Database\Seeders\DemoUsersSeeder;
use Database\Seeders\LargeDemoDatasetSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

final class LargeDemoDatasetSeederTest extends TestCase
{
    use RefreshDatabase;

    private function useWritablePublicDiskRoot(): string
    {
        $root = sys_get_temp_dir().'/eclick_large_demo_'.uniqid('', true);
        File::ensureDirectoryExists($root);
        config(['filesystems.disks.public.root' => $root]);

        return $root;
    }

    public function test_large_demo_seeder_creates_expected_volume(): void
    {
        $this->useWritablePublicDiskRoot();

        $this->seed(RoleSeeder::class);
        $this->seed(DemoUsersSeeder::class);
        $this->seed(LargeDemoDatasetSeeder::class);

        $this->assertSame(50, Product::query()->count());
        $this->assertSame(10, Promotion::query()->count());
        $this->assertSame(440, Receipt::query()->count());

        $firstTwoPromotionIds = Promotion::query()->orderBy('id')->limit(2)->pluck('id')->all();
        $approvedOnActivePair = Receipt::query()
            ->whereIn('promotion_id', $firstTwoPromotionIds)
            ->where('status', ReceiptSubmissionStatus::Approved)
            ->count();
        $this->assertGreaterThanOrEqual(220, $approvedOnActivePair);

        $orphanLines = DB::table('receipt_products as rp')
            ->join('receipts as r', 'r.id', '=', 'rp.receipt_id')
            ->whereNotExists(function ($query): void {
                $query->selectRaw('1')
                    ->from('promotion_product as pp')
                    ->whereColumn('pp.promotion_id', 'r.promotion_id')
                    ->whereColumn('pp.product_id', 'rp.product_id');
            })
            ->count();
        $this->assertSame(0, $orphanLines);

        $this->assertSame(
            440,
            Receipt::query()
                ->where('ap_code', 'like', 'SEED-%')
                ->where('receipt_image', ProductFactory::DEMO_SEED_RECEIPT_IMAGE_PATH)
                ->count(),
        );

        $this->assertSame(10, ContactMessage::query()->count());
        $this->assertSame(
            4,
            ContactMessage::query()->whereNull('admin_reply')->count(),
        );
        $this->assertSame(
            10,
            ContactMessage::query()->whereNotNull('user_id')->count(),
        );
        $admin = User::query()->where('email', 'admin@test.com')->first();
        $this->assertNotNull($admin);
        $this->assertSame(
            6,
            ContactMessage::query()
                ->whereNotNull('admin_reply')
                ->where('replied_by', $admin->getKey())
                ->count(),
        );

        $this->assertSame(
            32,
            User::query()->count(),
        );

        $this->assertTrue(
            Storage::disk((string) config('image_upload.disk'))->exists(ProductFactory::DEMO_SEED_IMAGE_PATH),
        );
        $this->assertTrue(
            Storage::disk((string) config('image_upload.disk'))->exists(ProductFactory::DEMO_SEED_RECEIPT_IMAGE_PATH),
        );
    }

    public function test_large_demo_seeder_is_idempotent(): void
    {
        $this->useWritablePublicDiskRoot();

        $this->seed(RoleSeeder::class);
        $this->seed(DemoUsersSeeder::class);
        $this->seed(LargeDemoDatasetSeeder::class);
        $this->seed(LargeDemoDatasetSeeder::class);

        $this->assertSame(50, Product::query()->count());
        $this->assertSame(440, Receipt::query()->count());
    }
}
