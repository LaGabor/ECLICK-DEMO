<x-mail::message>
{{ __('mail.refund_export_ready.intro') }}

{{ __('mail.refund_export_ready.period_line', [
    'from' => $refundExport->period_start?->toDateString() ?? '—',
    'to' => $refundExport->period_end?->toDateString() ?? '—',
]) }}

{{ __('mail.refund_export_ready.rows_line', ['count' => $refundExport->total_rows]) }}

<x-mail::button :url="$downloadUrl">
{{ __('mail.refund_export_ready.button') }}
</x-mail::button>

{{ __('mail.refund_export_ready.expires_line', ['date' => $expiresAt->toDayDateTimeString()]) }}

{{ __('mail.refund_export_ready.outro') }}

{{ __('mail.refund_export_ready.salutation') }}<br>
{{ config('app.name') }}
</x-mail::message>
