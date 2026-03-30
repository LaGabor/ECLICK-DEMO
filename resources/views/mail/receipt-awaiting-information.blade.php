<x-mail::message>
{{ __('mail.receipt_awaiting_information.greeting', ['name' => $receipt->user?->name ?? __('mail.receipt_awaiting_information.participant')]) }}

{{ __('mail.receipt_awaiting_information.intro') }}

@if(filled($promotionName))
{{ __('mail.receipt_awaiting_information.campaign_line', ['campaign' => $promotionName]) }}
@endif

@if(filled($instruction))
{{ __('mail.receipt_awaiting_information.instruction_heading') }}

{{ $instruction }}
@endif

<x-mail::button :url="$receiptUrl">
{{ __('mail.receipt_awaiting_information.button') }}
</x-mail::button>

{{ __('mail.receipt_awaiting_information.outro') }}

{{ __('mail.receipt_awaiting_information.salutation') }}<br>
{{ config('app.name') }}
</x-mail::message>
