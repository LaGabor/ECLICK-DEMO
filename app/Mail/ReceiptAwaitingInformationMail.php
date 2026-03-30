<?php

declare(strict_types=1);

namespace App\Mail;

use App\Models\Receipt;
use App\Support\Filament\ParticipantReceiptAccountUrl;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

final class ReceiptAwaitingInformationMail extends Mailable
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
            subject: __('mail.receipt_awaiting_information.subject'),
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'mail.receipt-awaiting-information',
            with: [
                'receiptUrl' => ParticipantReceiptAccountUrl::edit($this->receipt),
                'promotionName' => $this->receipt->promotion?->name ?? '',
                'instruction' => (string) ($this->receipt->admin_note ?? ''),
            ],
        );
    }
}
