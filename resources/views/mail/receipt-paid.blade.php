<x-mail::message>
{{ __('mail.receipt_paid.greeting', ['name' => $receipt->user?->name ?? __('mail.receipt_paid.participant')]) }}

{{ __('mail.receipt_paid.intro') }}

@if(filled($promotionName))
{{ __('mail.receipt_paid.campaign_line', ['campaign' => $promotionName]) }}
@endif

<x-mail::button :url="$receiptUrl">
{{ __('mail.receipt_paid.button') }}
</x-mail::button>

{{ __('mail.receipt_paid.outro') }}

{{ __('mail.receipt_paid.salutation') }}<br>
{{ config('app.name') }}
</x-mail::message>
