<?php

declare(strict_types=1);

namespace App\Mail;

use App\Models\RefundExport;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\URL;

final class RefundExportReadyMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly RefundExport $refundExport,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: __('mail.refund_export_ready.subject'),
        );
    }

    public function content(): Content
    {
        $expiresAt = now()->addDays((int) config('refund_exports.signed_download_ttl_days'));

        $relativeSigned = URL::temporarySignedRoute(
            'filament.admin.refund-exports.download-signed',
            $expiresAt,
            ['refundExport' => $this->refundExport->getKey()],
            false,
        );

        $publicBase = rtrim((string) (config('app.filament_public_url') ?: config('app.url')), '/');
        $downloadUrl = $publicBase.$relativeSigned;

        return new Content(
            markdown: 'mail.refund-export-ready',
            with: [
                'refundExport' => $this->refundExport,
                'downloadUrl' => $downloadUrl,
                'expiresAt' => $expiresAt,
            ],
        );
    }
}
