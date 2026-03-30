<?php

declare(strict_types=1);

namespace App\Mail;

use App\Models\Receipt;
use App\Support\Filament\ParticipantReceiptAccountUrl;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

final class ReceiptRejectedMail extends Mailable
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
            subject: __('mail.receipt_rejected.subject'),
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'mail.receipt-rejected',
            with: [
                'receiptUrl' => ParticipantReceiptAccountUrl::view($this->receipt),
                'promotionName' => $this->receipt->promotion?->name ?? '',
                'adminNote' => (string) ($this->receipt->admin_note ?? ''),
            ],
        );
    }
}
