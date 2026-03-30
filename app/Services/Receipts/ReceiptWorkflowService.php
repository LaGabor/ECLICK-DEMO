<?php

declare(strict_types=1);

namespace App\Services\Receipts;

use App\Contracts\Receipts\ReceiptWorkflowServiceInterface;
use App\Domain\Receipts\ReceiptSubmissionStatus;
use App\Domain\Refunds\RefundExportItemPaymentStatus;
use App\Models\Receipt;
use Illuminate\Support\Facades\DB;

final class ReceiptWorkflowService implements ReceiptWorkflowServiceInterface
{
    public function moveToUnderReview(Receipt $receipt): void
    {
        $allowedSourceStatuses = [
            ReceiptSubmissionStatus::Pending,
            ReceiptSubmissionStatus::Appealed,
            ReceiptSubmissionStatus::AwaitingUserInformation,
        ];

        $this->assertCurrentStatusIsOneOf($receipt, $allowedSourceStatuses);

        $receipt->update([
            'status' => ReceiptSubmissionStatus::UnderReview,
            'reviewed_at' => now(),
        ]);
    }

    public function approve(Receipt $receipt): void
    {
        $allowedSourceStatuses = [
            ReceiptSubmissionStatus::UnderReview,
        ];

        $this->assertCurrentStatusIsOneOf($receipt, $allowedSourceStatuses);

        $receipt->update([
            'status' => ReceiptSubmissionStatus::Approved,
            'reviewed_at' => now(),
        ]);
    }

    public function reject(Receipt $receipt, string $adminVisibleReason): void
    {
        $this->assertNonEmptyNote($adminVisibleReason);

        $allowedSourceStatuses = [
            ReceiptSubmissionStatus::UnderReview,
        ];

        $this->assertCurrentStatusIsOneOf($receipt, $allowedSourceStatuses);

        $receipt->update([
            'status' => ReceiptSubmissionStatus::Rejected,
            'admin_note' => $adminVisibleReason,
            'reviewed_at' => now(),
        ]);
    }

    public function markAwaitingUserInformation(Receipt $receipt, string $instructionForUser): void
    {
        $this->assertNonEmptyNote($instructionForUser);

        $allowedSourceStatuses = [
            ReceiptSubmissionStatus::UnderReview,
            ReceiptSubmissionStatus::PaymentFailed,
        ];

        $this->assertCurrentStatusIsOneOf($receipt, $allowedSourceStatuses);

        $receipt->update([
            'status' => ReceiptSubmissionStatus::AwaitingUserInformation,
            'admin_note' => $instructionForUser,
        ]);
    }

    public function markPaymentFailed(Receipt $receipt, string $bankOrProcessorMessage): void
    {
        $this->assertNonEmptyNote($bankOrProcessorMessage);

        $allowedSourceStatuses = [
            ReceiptSubmissionStatus::Approved,
            ReceiptSubmissionStatus::PaymentPending,
        ];

        $this->assertCurrentStatusIsOneOf($receipt, $allowedSourceStatuses);

        $wasPaymentPending = $receipt->status === ReceiptSubmissionStatus::PaymentPending;

        DB::transaction(function () use ($receipt, $bankOrProcessorMessage, $wasPaymentPending): void {
            if ($wasPaymentPending) {
                $receipt->refundExportItems()
                    ->where('payment_status', RefundExportItemPaymentStatus::Pending)
                    ->update([
                        'payment_status' => RefundExportItemPaymentStatus::Failed,
                        'payment_error' => $bankOrProcessorMessage,
                    ]);
            }

            $receipt->update([
                'status' => ReceiptSubmissionStatus::PaymentFailed,
                'admin_note' => $bankOrProcessorMessage,
            ]);
        });
    }

    public function markPaidManually(Receipt $receipt): void
    {
        if ($receipt->status !== ReceiptSubmissionStatus::PaymentPending) {
            throw new \InvalidArgumentException(__('filament.receipts.workflow.paid_only_from_payment_pending'));
        }

        DB::transaction(function () use ($receipt): void {
            $receipt->refundExportItems()
                ->where('payment_status', RefundExportItemPaymentStatus::Pending)
                ->update(['payment_status' => RefundExportItemPaymentStatus::Paid]);

            $receipt->update([
                'status' => ReceiptSubmissionStatus::Paid,
                'paid_at' => now(),
            ]);
        });
    }

    public function applyAdminStatusOverride(Receipt $receipt, ReceiptSubmissionStatus $targetStatus, ?string $adminNote): void
    {
        if ($receipt->status === $targetStatus) {
            return;
        }

        match ($targetStatus) {
            ReceiptSubmissionStatus::UnderReview => $this->moveToUnderReview($receipt),
            ReceiptSubmissionStatus::Approved => $this->approve($receipt),
            ReceiptSubmissionStatus::Rejected => $this->reject($receipt, $adminNote ?? ''),
            ReceiptSubmissionStatus::AwaitingUserInformation => $this->markAwaitingUserInformation($receipt, $adminNote ?? ''),
            ReceiptSubmissionStatus::PaymentFailed => $this->markPaymentFailed($receipt, $adminNote ?? ''),
            ReceiptSubmissionStatus::Paid => $this->markPaidManually($receipt),
            default => throw new \InvalidArgumentException(__('filament.receipts.workflow.unsupported_admin_transition')),
        };
    }

    private function assertCurrentStatusIsOneOf(Receipt $receipt, array $allowedStatuses): void
    {
        if (! in_array($receipt->status, $allowedStatuses, true)) {
            throw new \InvalidArgumentException(__('filament.receipts.workflow.invalid_source_status'));
        }
    }

    private function assertNonEmptyNote(string $note): void
    {
        if (trim($note) === '') {
            throw new \InvalidArgumentException(__('filament.receipts.workflow.note_required'));
        }
    }
}
