<?php

declare(strict_types=1);

namespace App\Domain\Receipts;

enum ReceiptStatusNotificationKind: string
{
    case Paid = 'paid';
    case Rejected = 'rejected';
    case AwaitingUserInformation = 'awaiting_user_information';
    case PaymentFailed = 'payment_failed';

    public static function tryFromSubmissionStatus(ReceiptSubmissionStatus $status): ?self
    {
        return match ($status) {
            ReceiptSubmissionStatus::Paid => self::Paid,
            ReceiptSubmissionStatus::Rejected => self::Rejected,
            ReceiptSubmissionStatus::AwaitingUserInformation => self::AwaitingUserInformation,
            ReceiptSubmissionStatus::PaymentFailed => self::PaymentFailed,
            default => null,
        };
    }
}
