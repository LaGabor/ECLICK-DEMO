<?php

declare(strict_types=1);

namespace Tests\Feature\Receipts;

use App\Domain\Receipts\ReceiptSubmissionStatus;
use App\Models\Promotion;
use App\Models\Receipt;
use App\Models\User;
use Database\Factories\ProductFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class ReceiptSubmissionShowTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_can_view_receipt_submission_page(): void
    {
        $this->seedTinyPngToPrivateDisk(ProductFactory::DEMO_SEED_RECEIPT_IMAGE_PATH);

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
            'ap_code' => 'AP-1',
            'purchase_date' => now()->toDateString(),
            'status' => ReceiptSubmissionStatus::PaymentFailed,
            'admin_note' => 'Please fix IBAN.',
        ]);

        $response = $this->actingAs($user)->get(route('receipts.show', $receipt));

        $response->assertOk();
        $response->assertSee('Please fix IBAN.', false);
    }

    public function test_guest_is_redirected_to_login_when_opening_receipt_link(): void
    {
        $this->seedTinyPngToPrivateDisk(ProductFactory::DEMO_SEED_RECEIPT_IMAGE_PATH);

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
            'ap_code' => 'AP-1',
            'purchase_date' => now()->toDateString(),
            'status' => ReceiptSubmissionStatus::Approved,
        ]);

        $response = $this->get(route('receipts.show', $receipt));

        $response->assertRedirect(route('login'));
    }

    public function test_other_user_cannot_view_receipt_submission_page(): void
    {
        $this->seedTinyPngToPrivateDisk(ProductFactory::DEMO_SEED_RECEIPT_IMAGE_PATH);

        $owner = User::factory()->create();
        $other = User::factory()->create();
        $promotion = Promotion::query()->create([
            'name' => 'Test campaign',
            'purchase_start' => now()->subMonth()->toDateString(),
            'purchase_end' => now()->addMonth()->toDateString(),
            'upload_end' => now()->addMonth()->toDateString(),
        ]);

        $receipt = Receipt::query()->create([
            'user_id' => $owner->id,
            'promotion_id' => $promotion->id,
            'receipt_image' => ProductFactory::DEMO_SEED_RECEIPT_IMAGE_PATH,
            'ap_code' => 'AP-1',
            'purchase_date' => now()->toDateString(),
            'status' => ReceiptSubmissionStatus::Approved,
        ]);

        $response = $this->actingAs($other)->get(route('receipts.show', $receipt));

        $response->assertForbidden();
    }
}
