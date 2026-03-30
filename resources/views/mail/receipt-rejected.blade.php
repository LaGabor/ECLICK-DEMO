<x-mail::message>
{{ __('mail.receipt_rejected.greeting', ['name' => $receipt->user?->name ?? __('mail.receipt_rejected.participant')]) }}

{{ __('mail.receipt_rejected.intro') }}

@if(filled($promotionName))
{{ __('mail.receipt_rejected.campaign_line', ['campaign' => $promotionName]) }}
@endif

@if(filled($adminNote))
{{ __('mail.receipt_rejected.note_heading') }}

{{ $adminNote }}
@endif

<x-mail::button :url="$receiptUrl">
{{ __('mail.receipt_rejected.button') }}
</x-mail::button>

{{ __('mail.receipt_rejected.outro') }}

{{ __('mail.receipt_rejected.salutation') }}<br>
{{ config('app.name') }}
</x-mail::message>
