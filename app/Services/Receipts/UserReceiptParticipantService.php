<?php

declare(strict_types=1);

namespace App\Services\Receipts;

use App\Contracts\Refunds\ReceiptRefundTotalCalculatorInterface;
use App\Domain\Receipts\ReceiptSubmissionStatus;
use App\Domain\Refunds\ReceiptPromotionMissingException;
use App\Domain\Refunds\ZeroRefundCalculatedException;
use App\Models\Promotion;
use App\Models\Receipt;
use App\Models\ReceiptProduct;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class UserReceiptParticipantService
{
    public function __construct(
        private readonly ReceiptRefundTotalCalculatorInterface $refundCalculator,
    ) {}

    public function assertUserOwnsReceipt(User $user, Receipt $receipt): void
    {
        if ((string) $receipt->user_id !== (string) $user->getKey()) {
            abort(403);
        }
    }

    public function userCanEditReceipt(Receipt $receipt): bool
    {
        return in_array($receipt->status, [
            ReceiptSubmissionStatus::AwaitingUserInformation,
            ReceiptSubmissionStatus::PaymentFailed,
        ], true);
    }

    public function assertUserMayEditReceipt(User $user, Receipt $receipt): void
    {
        $this->assertUserOwnsReceipt($user, $receipt);

        if (! $this->userCanEditReceipt($receipt)) {
            throw ValidationException::withMessages([
                'status' => [__('user.receipts.cannot_edit_status')],
            ]);
        }
    }

    public function createForParticipant(User $user, Promotion $promotion, string $apCode, string $purchaseDateYmd, string $receiptImageRelativePath, array $lines): Receipt
    {
        $this->validatePromotionAndPurchaseDate($promotion, $purchaseDateYmd);
        $this->validateUploadWindow($promotion);
        $this->validateLinesBelongToPromotion($promotion, $lines);

        return DB::transaction(function () use ($user, $promotion, $apCode, $purchaseDateYmd, $receiptImageRelativePath, $lines): Receipt {
            $receipt = Receipt::query()->create([
                'user_id' => $user->getKey(),
                'promotion_id' => $promotion->getKey(),
                'receipt_image' => $receiptImageRelativePath,
                'ap_code' => $apCode,
                'purchase_date' => $purchaseDateYmd,
                'status' => ReceiptSubmissionStatus::Pending,
            ]);

            $this->replaceReceiptLines($receipt, $lines);
            $receipt->load(['receiptProducts', 'promotion.products']);
            $this->assertExpectedRefundIsPositive($receipt);

            return $receipt->refresh();
        });
    }

    public function updateParticipantReceipt(Receipt $receipt, User $user, Promotion $promotion, string $apCode, string $purchaseDateYmd, ?string $receiptImageRelativePath, array $lines): Receipt
    {
        $this->assertUserMayEditReceipt($user, $receipt);

        if ((int) $promotion->getKey() !== (int) $receipt->promotion_id) {
            throw ValidationException::withMessages([
                'promotion_id' => [__('user.receipts.cannot_change_promotion')],
            ]);
        }

        $this->validatePromotionAndPurchaseDate($promotion, $purchaseDateYmd);
        $this->validateLinesBelongToPromotion($promotion, $lines);

        return DB::transaction(function () use ($receipt, $apCode, $purchaseDateYmd, $receiptImageRelativePath, $lines): Receipt {
            $wasAwaitingUserInformation = $receipt->status === ReceiptSubmissionStatus::AwaitingUserInformation;
            $wasPaymentFailed = $receipt->status === ReceiptSubmissionStatus::PaymentFailed;

            $data = [
                'ap_code' => $apCode,
                'purchase_date' => $purchaseDateYmd,
            ];

            if ($receiptImageRelativePath !== null && $receiptImageRelativePath !== '') {
                $data['receipt_image'] = $receiptImageRelativePath;
            }

            $receipt->update($data);

            $this->replaceReceiptLines($receipt, $lines);
            $receipt->load(['receiptProducts', 'promotion.products']);
            $this->assertExpectedRefundIsPositive($receipt);

            if ($wasAwaitingUserInformation || $wasPaymentFailed) {
                $receipt->update([
                    'status' => ReceiptSubmissionStatus::Pending,
                    'reviewed_at' => null,
                ]);
            }

            return $receipt->refresh();
        });
    }

    public function submitAppeal(User $user, Receipt $receipt, string $message): void
    {
        $this->assertUserOwnsReceipt($user, $receipt);

        if ($receipt->status !== ReceiptSubmissionStatus::Rejected) {
            throw ValidationException::withMessages([
                'appeal_message' => [__('user.receipts.appeal_only_when_rejected')],
            ]);
        }

        if ($receipt->appeal_submitted_at !== null) {
            throw ValidationException::withMessages([
                'appeal_message' => [__('user.receipts.appeal_already_used')],
            ]);
        }

        if (trim($message) === '') {
            throw ValidationException::withMessages([
                'appeal_message' => [__('user.receipts.appeal_message_required')],
            ]);
        }

        $receipt->update([
            'status' => ReceiptSubmissionStatus::Appealed,
            'appeal_message' => $message,
            'appeal_submitted_at' => now(),
        ]);
    }

    public function validatePromotionAndPurchaseDate(Promotion $promotion, string $purchaseDateYmd): void
    {
        $purchaseDate = CarbonImmutable::parse($purchaseDateYmd)->startOfDay();
        $today = CarbonImmutable::now()->startOfDay();

        if ($purchaseDate->gt($today)) {
            throw ValidationException::withMessages([
                'purchase_date' => [__('user.receipts.purchase_date_not_in_future')],
            ]);
        }

        if (! $promotion->isPurchaseDateWithinPurchasePeriod($purchaseDate)) {
            throw ValidationException::withMessages([
                'purchase_date' => [__('user.receipts.purchase_date_outside_campaign')],
            ]);
        }
    }

    public function validateUploadWindow(Promotion $promotion): void
    {
        $today = CarbonImmutable::now()->startOfDay();

        if (! Promotion::query()
            ->whereKey($promotion->getKey())
            ->acceptingParticipantUploadsOn($today)
            ->exists()) {
            throw ValidationException::withMessages([
                'promotion_id' => [__('user.receipts.upload_period_closed')],
            ]);
        }
    }

    public function validateLinesBelongToPromotion(Promotion $promotion, array $lines): void
    {
        if ($lines === []) {
            throw ValidationException::withMessages([
                'lines' => [__('user.receipts.at_least_one_product')],
            ]);
        }

        $allowedIds = $promotion->products()
            ->where('products.active', true)
            ->pluck('products.id')
            ->map(static fn ($id): int => (int) $id)
            ->all();

        $selectedProductIds = [];
        foreach ($lines as $line) {
            $selectedProductIds[] = (int) ($line['product_id'] ?? 0);
        }

        $positiveIds = array_values(array_filter($selectedProductIds, static fn (int $id): bool => $id > 0));
        if (count($positiveIds) !== count(array_unique($positiveIds))) {
            throw ValidationException::withMessages([
                'lines' => [__('user.receipts.duplicate_product_lines')],
            ]);
        }

        foreach ($lines as $i => $line) {
            $pid = (int) ($line['product_id'] ?? 0);
            $qty = (int) ($line['quantity'] ?? 0);

            if ($pid < 1) {
                throw ValidationException::withMessages([
                    "lines.{$i}.product_id" => [__('user.receipts.line_product_required')],
                ]);
            }

            if ($qty < 1) {
                throw ValidationException::withMessages([
                    "lines.{$i}.quantity" => [__('user.receipts.quantity_min_one')],
                ]);
            }

            if (! in_array($pid, $allowedIds, true)) {
                throw ValidationException::withMessages([
                    "lines.{$i}.product_id" => [__('user.receipts.product_not_on_promotion')],
                ]);
            }
        }
    }

    public function assertExpectedRefundIsPositive(Receipt $receipt): void
    {
        try {
            $amount = $this->refundCalculator->calculateTotalRefundAmountForReceipt($receipt);
            if (\function_exists('bccomp')) {
                if (bccomp($amount, '0', 4) <= 0) {
                    throw ZeroRefundCalculatedException::forReceipt($receipt->getKey());
                }
            } elseif ((float) $amount <= 0.0) {
                throw ZeroRefundCalculatedException::forReceipt($receipt->getKey());
            }
        } catch (ReceiptPromotionMissingException|ZeroRefundCalculatedException) {
            throw ValidationException::withMessages([
                'lines' => [__('user.receipts.expected_refund_must_be_positive')],
            ]);
        }
    }

    private function replaceReceiptLines(Receipt $receipt, array $lines): void
    {
        $receipt->receiptProducts()->delete();

        foreach ($lines as $line) {
            ReceiptProduct::query()->create([
                'receipt_id' => $receipt->getKey(),
                'product_id' => (int) ($line['product_id'] ?? 0),
                'quantity' => max(0, (int) ($line['quantity'] ?? 0)),
                'line_subtotal' => '0.0000',
            ]);
        }
    }
}
