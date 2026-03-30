<?php

declare(strict_types=1);

namespace App\Domain\Receipts;

enum ReceiptSubmissionStatus: string
{
    case Pending = 'pending';
    case UnderReview = 'under_review';
    case Approved = 'approved';
    case Rejected = 'rejected';
    case Appealed = 'appealed';
    case AwaitingUserInformation = 'awaiting_user_information';
    case PaymentPending = 'payment_pending';
    case Paid = 'paid';
    case PaymentFailed = 'payment_failed';

    public function getLabel(): string
    {
        return match ($this) {
            self::Pending => __('filament.receipts.status.pending'),
            self::UnderReview => __('filament.receipts.status.under_review'),
            self::Approved => __('filament.receipts.status.approved'),
            self::Rejected => __('filament.receipts.status.rejected'),
            self::Appealed => __('filament.receipts.status.appealed'),
            self::AwaitingUserInformation => __('filament.receipts.status.awaiting_user_information'),
            self::PaymentPending => __('filament.receipts.status.payment_pending'),
            self::Paid => __('filament.receipts.status.paid'),
            self::PaymentFailed => __('filament.receipts.status.payment_failed'),
        };
    }

    public function getBadgeColor(): string
    {
        return match ($this) {
            self::Approved, self::Paid => 'success',
            self::Rejected, self::PaymentFailed => 'danger',
            self::Appealed, self::AwaitingUserInformation, self::PaymentPending => 'warning',
            self::Pending, self::UnderReview => 'info',
        };
    }

    /**
     * Default “active work” subset for the receipt submissions table filter.
     *
     * @return list<string>
     */
    public static function adminListDefaultFilterValues(): array
    {
        return [
            self::Pending->value,
            self::UnderReview->value,
            self::Appealed->value,
            self::PaymentPending->value,
        ];
    }
}
