<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Domain\Promotions\PromotionProductRefundKind;
use App\Domain\Receipts\ReceiptSubmissionStatus;
use App\Models\ContactMessage;
use App\Models\Product;
use App\Models\Promotion;
use App\Models\Receipt;
use App\Models\ReceiptProduct;
use App\Models\User;
use App\Support\UserRole;
use Carbon\Carbon;
use Carbon\CarbonImmutable;
use Database\Factories\ProductFactory;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

final class LargeDemoDatasetSeeder extends Seeder
{
    private const string MINI_PNG_BASE64 = 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg==';

    private const int RECEIPTS_PER_ACTIVE_PROMOTION = 120;

    private const int APPROVED_RECEIPTS_PER_ACTIVE_PROMOTION = 115;

    private const int RECEIPTS_PER_EXPIRED_PROMOTION = 25;

    private const int ACTIVE_PROMOTION_SLOTS = 2;

    public function run(): void
    {
        if (Product::query()->where('sku', 'SEED-P-00001')->exists()) {
            $this->command?->info('LargeDemoDatasetSeeder skipped (already present).');

            return;
        }

        $now = CarbonImmutable::now();
        $password = Hash::make((string) env('SEED_DEMO_PASSWORD', 'admin123'));
        $admin = User::query()->where('email', (string) env('SEED_ADMIN_EMAIL', 'admin@test.com'))->first();

        $this->ensureDemoSeedImages();

        $this->seedParticipants($password);
        $products = $this->seedProducts();

        $priceByProductId = collect($products)->mapWithKeys(
            static fn (Product $p): array => [(int) $p->getKey() => (string) $p->price],
        )->all();
        $promotions = $this->seedPromotions($now, $products);
        $this->seedReceiptsForPromotions($promotions, $priceByProductId);
        $this->seedContactMessages($admin);
    }

    private static function demoImagePublicSources(): array
    {
        return [
            ProductFactory::DEMO_SEED_IMAGE_PATH => 'pics/product-pic.png',
            ProductFactory::DEMO_SEED_RECEIPT_IMAGE_PATH => 'pics/receipt-pic.jpg',
        ];
    }

    private function ensureDemoSeedImages(): void
    {
        $disk = Storage::disk((string) config('image_upload.disk'));

        foreach (self::demoImagePublicSources() as $relativePath => $publicSuffix) {
            if ($disk->exists($relativePath)) {
                continue;
            }

            $publicFile = public_path($publicSuffix);

            if (is_file($publicFile) && is_readable($publicFile)) {
                $dir = dirname($relativePath);
                if ($dir !== '.' && $dir !== '') {
                    $disk->makeDirectory($dir);
                }
                $contents = file_get_contents($publicFile);
                if ($contents === false) {
                    throw new \RuntimeException('Could not read demo image: '.$publicFile);
                }
                $disk->put($relativePath, $contents);
                $this->command?->info('Seeded private demo image from public/'.$publicSuffix.' → '.$relativePath);

                continue;
            }

            $this->command?->warn(sprintf(
                'Missing %s — using 1×1 PNG placeholder at %s (add the real file for production-like demos).',
                $publicFile,
                $relativePath,
            ));
            $this->putMiniPngPlaceholder($disk, $relativePath);
        }
    }

    private function putMiniPngPlaceholder(Filesystem $disk, string $relativePath): void
    {
        $binary = base64_decode(self::MINI_PNG_BASE64, true);

        if ($binary === false) {
            throw new \RuntimeException('Invalid embedded demo image payload.');
        }

        $dir = dirname($relativePath);

        if ($dir !== '.' && $dir !== '') {
            $disk->makeDirectory($dir);
        }

        $disk->put($relativePath, $binary);
    }

    private function fakePromotionName(): string
    {
        return Str::limit(
            rtrim(fake()->unique()->sentence(random_int(3, 7)), '.'),
            255,
            '',
        );
    }

    private function productIdsForPromotion(int $promotionId): array
    {
        return DB::table('promotion_product')
            ->where('promotion_id', $promotionId)
            ->pluck('product_id')
            ->map(static fn ($id): int => (int) $id)
            ->values()
            ->all();
    }

    private function seedParticipants(string $password): void
    {
        for ($slot = 1; $slot <= 30; $slot++) {
            $email = sprintf('seed-participant-%02d@demo.eclick.test', $slot);
            $user = User::query()->firstOrCreate(
                ['email' => $email],
                [
                    'name' => fake()->name(),
                    'phone' => sprintf('+3620%07d', 5_000_000 + $slot),
                    'bank_account' => '11773090'.str_pad((string) (8_000_000 + $slot), 16, '0', STR_PAD_LEFT),
                    'password' => $password,
                    'terms_accepted_at' => now(),
                    'email_verified_at' => now(),
                ],
            );

            if (! $user->hasRole(UserRole::User)) {
                $user->assignRole(UserRole::User);
            }
        }
    }

    private function seedProducts(): array
    {
        $products = [];

        for ($i = 1; $i <= 50; $i++) {
            $products[] = Product::query()->create([
                'name' => sprintf('Catalog %02d — %s', $i, fake()->words(rand(2, 3), true)),
                'product_image' => ProductFactory::DEMO_SEED_IMAGE_PATH,
                'sku' => sprintf('SEED-P-%05d', $i),
                'price' => number_format(fake()->randomFloat(4, 4.99, 799.99), 2, '.', ''),
                'active' => true,
            ]);
        }

        return $products;
    }

    private function seedPromotions(CarbonImmutable $now, array $products): array
    {
        $dateRows = [
            [
                'purchase_start' => $now->subDays(14)->toDateString(),
                'purchase_end' => $now->addDays(75)->toDateString(),
                'upload_start' => $now->subDays(7)->toDateString(),
                'upload_end' => $now->addDays(90)->toDateString(),
            ],
            [
                'purchase_start' => $now->subDays(30)->toDateString(),
                'purchase_end' => $now->addDays(45)->toDateString(),
                'upload_start' => $now->subDays(20)->toDateString(),
                'upload_end' => $now->addDays(60)->toDateString(),
            ],
            [
                'purchase_start' => $now->subMonths(4)->toDateString(),
                'purchase_end' => $now->subDays(2)->toDateString(),
                'upload_start' => $now->subMonths(4)->toDateString(),
                'upload_end' => $now->subDay()->toDateString(),
            ],
            [
                'purchase_start' => $now->subMonths(14)->toDateString(),
                'purchase_end' => $now->subMonths(11)->toDateString(),
                'upload_start' => $now->subMonths(14)->toDateString(),
                'upload_end' => $now->subMonths(10)->subDay()->toDateString(),
            ],
            [
                'purchase_start' => $now->subMonths(20)->toDateString(),
                'purchase_end' => $now->subMonths(16)->toDateString(),
                'upload_start' => $now->subMonths(20)->toDateString(),
                'upload_end' => $now->subMonths(15)->subDay()->toDateString(),
            ],
            [
                'purchase_start' => $now->subMonths(9)->toDateString(),
                'purchase_end' => $now->subMonths(6)->toDateString(),
                'upload_start' => $now->subMonths(9)->toDateString(),
                'upload_end' => $now->subMonths(5)->subDay()->toDateString(),
            ],
            [
                'purchase_start' => $now->subMonths(24)->toDateString(),
                'purchase_end' => $now->subMonths(20)->toDateString(),
                'upload_start' => $now->subMonths(24)->toDateString(),
                'upload_end' => $now->subMonths(19)->subDay()->toDateString(),
            ],
            [
                'purchase_start' => $now->subMonths(7)->toDateString(),
                'purchase_end' => $now->subMonths(5)->toDateString(),
                'upload_start' => $now->subMonths(7)->toDateString(),
                'upload_end' => $now->subMonths(4)->subDay()->toDateString(),
            ],
            [
                'purchase_start' => $now->subMonths(18)->toDateString(),
                'purchase_end' => $now->subMonths(13)->toDateString(),
                'upload_start' => $now->subMonths(18)->toDateString(),
                'upload_end' => $now->subMonths(12)->subDay()->toDateString(),
            ],
            [
                'purchase_start' => $now->subMonths(11)->toDateString(),
                'purchase_end' => $now->subMonths(8)->toDateString(),
                'upload_start' => $now->subMonths(11)->toDateString(),
                'upload_end' => $now->subMonths(7)->subDay()->toDateString(),
            ],
        ];

        $promotions = [];

        foreach ($dateRows as $dates) {
            $promotion = Promotion::query()->create(array_merge($dates, [
                'name' => $this->fakePromotionName(),
            ]));
            $promotions[] = $promotion;

            $ids = collect($products)->pluck('id')->shuffle()->take(12)->values()->all();
            $fixedWhole = ['25.00', '50.00', '75.00', '100.00', '10.00', '15.00'];
            $percentVals = ['5.00', '7.50', '10.00', '12.50', '15.00', '20.00', '8.00', '11.00'];

            foreach ($ids as $idx => $productId) {
                $usePercent = $idx % 2 === 1;
                $promotion->products()->attach($productId, [
                    'refund_type' => $usePercent
                        ? PromotionProductRefundKind::PercentOfLineSubtotal->value
                        : PromotionProductRefundKind::FixedAmountPerUnit->value,
                    'refund_value' => $usePercent
                        ? $percentVals[$idx % count($percentVals)]
                        : $fixedWhole[$idx % count($fixedWhole)],
                ]);
            }
        }

        return $promotions;
    }

    private function seedReceiptsForPromotions(array $promotions, array $priceByProductId): void
    {
        $demoEmail = (string) env('SEED_USER_EMAIL', 'user@example.com');
        $demoUser = User::query()->where('email', $demoEmail)->first();

        $participantIds = User::query()
            ->where('email', 'like', 'seed-participant-%@demo.eclick.test')
            ->orderBy('email')
            ->pluck('id')
            ->all();

        $pool = array_values(array_filter(array_merge(
            $demoUser !== null ? [$demoUser->getKey()] : [],
            $participantIds,
        )));

        if ($pool === []) {
            return;
        }

        $receiptIndex = 0;

        $receiptImagePath = ProductFactory::DEMO_SEED_RECEIPT_IMAGE_PATH;

        DB::transaction(function () use ($promotions, $priceByProductId, $pool, &$receiptIndex, $receiptImagePath): void {
            foreach (array_values($promotions) as $slot => $promotion) {
                $productIdsForPromotion = $this->productIdsForPromotion((int) $promotion->getKey());

                if ($productIdsForPromotion === []) {
                    continue;
                }

                $isActiveSlot = $slot < self::ACTIVE_PROMOTION_SLOTS;
                $receiptCount = $isActiveSlot
                    ? self::RECEIPTS_PER_ACTIVE_PROMOTION
                    : self::RECEIPTS_PER_EXPIRED_PROMOTION;
                $approvedQuota = $isActiveSlot ? self::APPROVED_RECEIPTS_PER_ACTIVE_PROMOTION : 0;

                for ($r = 0; $r < $receiptCount; $r++) {
                    $receiptIndex++;
                    $userId = $pool[($receiptIndex - 1) % count($pool)];
                    $purchaseDate = $this->randomPurchaseDateForPromotion($promotion);
                    $status = ($isActiveSlot && $r < $approvedQuota)
                        ? ReceiptSubmissionStatus::Approved
                        : $this->randomBulkReceiptStatus();
                    $attrs = [
                        'user_id' => $userId,
                        'promotion_id' => $promotion->getKey(),
                        'receipt_image' => $receiptImagePath,
                        'ap_code' => sprintf('SEED-%d-%04d', $promotion->getKey(), $receiptIndex),
                        'purchase_date' => $purchaseDate->toDateString(),
                        'status' => $status,
                        'admin_note' => fake()->optional(0.25)->sentence(),
                        'reviewed_at' => null,
                        'paid_at' => null,
                        'appeal_submitted_at' => null,
                    ];

                    $this->applyStatusSideEffects($attrs, $purchaseDate, $status);

                    $receipt = Receipt::query()->create($attrs);

                    $this->createReceiptLinesForPromotion(
                        $receipt,
                        $productIdsForPromotion,
                        $priceByProductId,
                    );
                }
            }
        });
    }

    private function createReceiptLinesForPromotion(
        Receipt $receipt,
        array $productIdsForPromotion,
        array $priceByProductId,
    ): void {
        $lineCount = random_int(1, min(8, count($productIdsForPromotion)));
        $picked = collect($productIdsForPromotion)
            ->shuffle()
            ->take($lineCount)
            ->values()
            ->all();

        foreach ($picked as $productId) {
            $unitPrice = $priceByProductId[$productId] ?? '0.00';
            $qty = random_int(1, 5);
            $lineSubtotal = number_format((float) $unitPrice * $qty, 2, '.', '');

            ReceiptProduct::query()->create([
                'receipt_id' => $receipt->getKey(),
                'product_id' => $productId,
                'quantity' => $qty,
                'line_subtotal' => $lineSubtotal,
            ]);
        }
    }

    private function randomPurchaseDateForPromotion(Promotion $promotion): Carbon
    {
        $start = Carbon::parse($promotion->purchase_start)->startOfDay();
        $purchaseEnd = Carbon::parse($promotion->purchase_end)->endOfDay();
        $todayEnd = Carbon::today()->endOfDay();

        $end = $purchaseEnd->gt($todayEnd) ? $todayEnd : $purchaseEnd;

        if ($start->gt($end)) {
            return $start->copy();
        }

        return Carbon::createFromTimestamp(random_int($start->timestamp, $end->timestamp))->startOfDay();
    }

    private function randomBulkReceiptStatus(): ReceiptSubmissionStatus
    {
        $roll = random_int(1, 100);

        return match (true) {
            $roll <= 50 => ReceiptSubmissionStatus::Approved,
            $roll <= 68 => ReceiptSubmissionStatus::UnderReview,
            $roll <= 78 => ReceiptSubmissionStatus::Pending,
            $roll <= 84 => ReceiptSubmissionStatus::Rejected,
            $roll <= 89 => ReceiptSubmissionStatus::Paid,
            $roll <= 92 => ReceiptSubmissionStatus::AwaitingUserInformation,
            $roll <= 95 => ReceiptSubmissionStatus::PaymentPending,
            $roll <= 97 => ReceiptSubmissionStatus::Appealed,
            default => ReceiptSubmissionStatus::PaymentFailed,
        };
    }

    private function applyStatusSideEffects(array &$attrs, Carbon $purchaseDate, ReceiptSubmissionStatus $status): void
    {
        switch ($status) {
            case ReceiptSubmissionStatus::Approved:
                $attrs['reviewed_at'] = $purchaseDate->copy()->addDays(random_int(1, 6));
                break;

            case ReceiptSubmissionStatus::PaymentPending:
                $attrs['reviewed_at'] = $purchaseDate->copy()->addDays(random_int(1, 5));
                $attrs['admin_note'] = $attrs['admin_note'] ?? 'Approved for bank batch.';
                break;

            case ReceiptSubmissionStatus::Rejected:
                $attrs['reviewed_at'] = $purchaseDate->copy()->addDays(random_int(2, 10));
                $attrs['admin_note'] = $attrs['admin_note'] ?? 'Seeded rejection note.';
                break;

            case ReceiptSubmissionStatus::Paid:
                $attrs['reviewed_at'] = $purchaseDate->copy()->addDays(random_int(1, 5));
                $attrs['paid_at'] = $purchaseDate->copy()->addDays(random_int(8, 20));
                break;

            case ReceiptSubmissionStatus::AwaitingUserInformation:
                $attrs['reviewed_at'] = $purchaseDate->copy()->addDays(random_int(1, 4));
                $attrs['admin_note'] = $attrs['admin_note'] ?? 'Please confirm your bank details.';
                break;

            case ReceiptSubmissionStatus::Appealed:
                $attrs['reviewed_at'] = $purchaseDate->copy()->addDays(random_int(2, 8));
                $attrs['appeal_submitted_at'] = $purchaseDate->copy()->addDays(random_int(9, 14));
                $attrs['admin_note'] = $attrs['admin_note'] ?? 'Participant appealed.';
                break;

            case ReceiptSubmissionStatus::PaymentFailed:
                $attrs['reviewed_at'] = $purchaseDate->copy()->addDays(random_int(2, 8));
                $attrs['admin_note'] = $attrs['admin_note'] ?? 'Bank transfer failed (seed).';
                break;

            default:
                break;
        }
    }

    private function seedContactMessages(?User $admin): void
    {
        $replierId = $admin?->getKey();

        for ($i = 1; $i <= 10; $i++) {
            $participantEmail = sprintf('seed-participant-%02d@demo.eclick.test', $i);
            $user = User::query()->where('email', $participantEmail)->first();

            if ($user === null) {
                continue;
            }

            if (blank($user->phone)) {
                $user->forceFill([
                    'phone' => sprintf('+3620%07d', 5_000_000 + $i),
                ])->save();
            }

            $answered = $i >= 5;

            ContactMessage::query()->firstOrCreate(
                ['user_id' => $user->getKey()],
                [
                    'name' => $user->name,
                    'email' => $user->email,
                    'subject' => sprintf('[%02d] %s', $i, fake()->sentence(rand(3, 6))),
                    'message' => fake()->paragraphs(rand(2, 4), true),
                    'admin_reply' => $answered ? fake()->paragraphs(rand(1, 3), true) : null,
                    'replied_at' => $answered ? now()->subDays(random_int(1, 25)) : null,
                    'replied_by' => $answered ? $replierId : null,
                ],
            );
        }
    }
}
