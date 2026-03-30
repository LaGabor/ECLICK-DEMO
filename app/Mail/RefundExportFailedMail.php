<?php

declare(strict_types=1);

namespace App\Mail;

use App\Models\RefundExport;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Throwable;

final class RefundExportFailedMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly RefundExport $refundExport,
        public readonly ?Throwable $exception = null,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: __('mail.refund_export_failed.subject'),
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'mail.refund-export-failed',
            with: [
                'refundExport' => $this->refundExport,
                'lastError' => $this->refundExport->last_error,
                'technicalMessage' => $this->exception?->getMessage(),
            ],
        );
    }
}
