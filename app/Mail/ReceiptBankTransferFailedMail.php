<?php

declare(strict_types=1);

namespace App\Mail;

use App\Models\Receipt;
use App\Support\Filament\ParticipantReceiptAccountUrl;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

final class ReceiptBankTransferFailedMail extends Mailable
{
    use SerializesModels;

    public function __construct(
        public Receipt $receipt,
    ) {
        $this->receipt->loadMissing(['user', 'promotion']);
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: __('mail.receipt_bank_transfer_failed.subject'),
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'mail.receipt-bank-transfer-failed',
            with: [
                'receiptUrl' => ParticipantReceiptAccountUrl::edit($this->receipt),
                'bankAccount' => $this->receipt->user?->bank_account ?? '',
                'promotionName' => $this->receipt->promotion?->name ?? '',
            ],
        );
    }
}
