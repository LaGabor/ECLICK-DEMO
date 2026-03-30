<?php

declare(strict_types=1);

namespace App\Contracts\Receipts;

use App\Domain\Receipts\ReceiptSubmissionStatus;
use App\Models\Receipt;

interface ReceiptWorkflowServiceInterface
{
    public function moveToUnderReview(Receipt $receipt): void;

    public function approve(Receipt $receipt): void;

    public function reject(Receipt $receipt, string $adminVisibleReason): void;

    public function markAwaitingUserInformation(Receipt $receipt, string $instructionForUser): void;

    public function markPaymentFailed(Receipt $receipt, string $bankOrProcessorMessage): void;

    public function markPaidManually(Receipt $receipt): void;

    public function applyAdminStatusOverride(Receipt $receipt, ReceiptSubmissionStatus $targetStatus, ?string $adminNote): void;
}
