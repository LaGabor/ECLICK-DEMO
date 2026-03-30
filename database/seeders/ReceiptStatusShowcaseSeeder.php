<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Domain\Receipts\ReceiptSubmissionStatus;
use App\Models\Promotion;
use App\Models\Receipt;
use App\Models\ReceiptProduct;
use App\Models\User;
use Database\Factories\ProductFactory;
use Illuminate\Database\Seeder;

class ReceiptStatusShowcaseSeeder extends Seeder
{
    public function run(): void
    {
        $promotion = Promotion::query()->orderBy('id')->first();
        $product = $promotion?->products()->orderBy('products.id')->first();

        if ($promotion === null || $product === null) {
            $this->command?->warn('ReceiptStatusShowcaseSeeder skipped: run LargeDemoDatasetSeeder first.');

            return;
        }

        $userEmail = (string) env('SEED_USER_EMAIL', 'user@test.com');
        $user = User::query()->where('email', $userEmail)->first();

        if ($user === null) {
            $this->command?->warn("ReceiptStatusShowcaseSeeder skipped: no user with email {$userEmail}. Run DemoUsersSeeder first (or set SEED_USER_EMAIL).");

            return;
        }

        foreach (ReceiptSubmissionStatus::cases() as $status) {
            $attrs = [
                'user_id' => $user->getKey(),
                'promotion_id' => $promotion->getKey(),
                'receipt_image' => ProductFactory::DEMO_SEED_RECEIPT_IMAGE_PATH,
                'ap_code' => 'SHOWCASE-AP-'.strtoupper($status->value),
                'purchase_date' => now()->subDays(5)->toDateString(),
                'status' => $status,
                'admin_note' => null,
                'reviewed_at' => null,
                'paid_at' => null,
                'appeal_submitted_at' => null,
            ];

            if (Receipt::query()->where('user_id', $user->getKey())->where('ap_code', $attrs['ap_code'])->exists()) {
                continue;
            }

            switch ($status) {
                case ReceiptSubmissionStatus::Approved:
                    $attrs['reviewed_at'] = now()->subDays(2);
                    break;
                case ReceiptSubmissionStatus::Rejected:
                    $this->fillRejected($attrs);
                    break;
                case ReceiptSubmissionStatus::Appealed:
                    $this->fillAppealed($attrs);
                    break;
                case ReceiptSubmissionStatus::AwaitingUserInformation:
                    $this->fillAwaiting($attrs);
                    break;
                case ReceiptSubmissionStatus::PaymentPending:
                    $this->fillPaymentPending($attrs);
                    break;
                case ReceiptSubmissionStatus::Paid:
                    $this->fillPaid($attrs);
                    break;
                case ReceiptSubmissionStatus::PaymentFailed:
                    $this->fillPaymentFailed($attrs);
                    break;
                default:
                    break;
            }

            $receipt = Receipt::query()->create($attrs);

            $qty = 1;
            $lineSubtotal = number_format((float) $product->getAttribute('price') * $qty, 2, '.', '');

            ReceiptProduct::query()->create([
                'receipt_id' => $receipt->getKey(),
                'product_id' => $product->getKey(),
                'quantity' => $qty,
                'line_subtotal' => $lineSubtotal,
            ]);
        }
    }

    private function fillRejected(array &$attrs): void
    {
        $attrs['reviewed_at'] = now()->subDays(3);
        $attrs['admin_note'] = 'Seeded rejection reason for admin UI testing.';
    }

    private function fillAppealed(array &$attrs): void
    {
        $attrs['reviewed_at'] = now()->subDays(4);
        $attrs['admin_note'] = 'Initially rejected; participant appealed.';
        $attrs['appeal_submitted_at'] = now()->subDays(2);
    }

    private function fillAwaiting(array &$attrs): void
    {
        $attrs['reviewed_at'] = now()->subDays(2);
        $attrs['admin_note'] = 'Please upload a clearer IBAN or correct bank account number.';
    }

    private function fillPaymentPending(array &$attrs): void
    {
        $attrs['reviewed_at'] = now()->subDays(2);
        $attrs['admin_note'] = 'Approved; awaiting bank batch.';
    }

    private function fillPaid(array &$attrs): void
    {
        $attrs['reviewed_at'] = now()->subDays(3);
        $attrs['paid_at'] = now()->subDay();
        $attrs['admin_note'] = 'Paid via seeded demo transfer.';
    }

    private function fillPaymentFailed(array &$attrs): void
    {
        $attrs['reviewed_at'] = now()->subDays(3);
        $attrs['admin_note'] = 'Bank returned invalid account for test user.';
    }
}
