<?php

declare(strict_types=1);

namespace Tests\Feature\Receipts;

use App\Contracts\Receipts\ReceiptWorkflowServiceInterface;
use App\Domain\Receipts\ReceiptStatusNotificationKind;
use App\Domain\Receipts\ReceiptSubmissionStatus;
use App\Jobs\Receipts\SendReceiptStatusNotificationJob;
use App\Models\Promotion;
use App\Models\Receipt;
use App\Models\User;
use Database\Factories\ProductFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

final class PaymentFailedMailTest extends TestCase
{
    use RefreshDatabase;

    public function test_payment_failed_notification_job_is_queued_when_marking_failure_from_approved(): void
    {
        $this->seedTinyPngToPrivateDisk(ProductFactory::DEMO_SEED_RECEIPT_IMAGE_PATH);

        Queue::fake();

        $user = User::factory()->create();
        $promotion = Promotion::query()->create([
            'name' => 'Test campaign',
            'purchase_start' => now()->subMonth()->toDateString(),
            'purchase_end' => now()->addMonth()->toDateString(),
            'upload_end' => now()->addMonth()->toDateString(),
        ]);

        $receipt = Receipt::query()->create([
            'user_id' => $user->id,
            'promotion_id' => $promotion->id,
            'receipt_image' => ProductFactory::DEMO_SEED_RECEIPT_IMAGE_PATH,
            'ap_code' => 'AP-TEST',
            'purchase_date' => now()->toDateString(),
            'status' => ReceiptSubmissionStatus::Approved,
        ]);

        app(ReceiptWorkflowServiceInterface::class)->markPaymentFailed($receipt->fresh(), 'Bank rejected the transfer.');

        Queue::assertPushedOn('mail', SendReceiptStatusNotificationJob::class, function (SendReceiptStatusNotificationJob $job) use ($receipt): bool {
            return $job->dto->receiptId === (int) $receipt->id
                && $job->dto->kind === ReceiptStatusNotificationKind::PaymentFailed;
        });
    }
}
