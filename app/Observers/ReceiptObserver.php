<?php

declare(strict_types=1);

namespace App\Observers;

use App\Domain\Receipts\ReceiptSubmissionStatus;
use App\Events\ReceiptSubmissionStatusChanged;
use App\Models\Receipt;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

/**
 * Dispatches {@see ReceiptSubmissionStatusChanged} when the receipt `status` attribute changes.
 * Registered for `updated` only (not `created`). Bulk `db:seed` runs under
 * {@see WithoutModelEvents}, so model events (and mail jobs) are not fired during seeding.
 */
final class ReceiptObserver
{
    public function updated(Receipt $receipt): void
    {
        if (! $receipt->wasChanged('status')) {
            return;
        }

        ReceiptSubmissionStatusChanged::dispatch(
            $receipt,
            self::normalizeStatus($receipt->getOriginal('status')),
            self::normalizeStatus($receipt->status),
        );
    }

    private static function normalizeStatus(mixed $value): ReceiptSubmissionStatus
    {
        return $value instanceof ReceiptSubmissionStatus
            ? $value
            : ReceiptSubmissionStatus::from((string) $value);
    }
}
