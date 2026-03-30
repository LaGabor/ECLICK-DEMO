<x-mail::message>
{{ __('mail.refund_export_failed.intro', ['id' => $refundExport->getKey()]) }}

@if(filled($lastError))
**{{ __('mail.refund_export_failed.reason_heading') }}**  
{{ $lastError }}
@endif

@if(filled($technicalMessage) && config('app.debug'))
**{{ __('mail.refund_export_failed.technical_heading') }}**  
{{ $technicalMessage }}
@endif

{{ __('mail.refund_export_failed.outro') }}

{{ __('mail.refund_export_failed.salutation') }}<br>
{{ config('app.name') }}
</x-mail::message>
