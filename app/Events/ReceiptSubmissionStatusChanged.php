<?php

declare(strict_types=1);

namespace App\Events;

use App\Domain\Receipts\ReceiptSubmissionStatus;
use App\Models\Receipt;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

final class ReceiptSubmissionStatusChanged
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public Receipt $receipt,
        public ReceiptSubmissionStatus $previousStatus,
        public ReceiptSubmissionStatus $newStatus,
    ) {}
}
