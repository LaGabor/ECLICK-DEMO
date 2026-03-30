<x-mail::message>
{{ __('mail.receipt_bank_transfer_failed.greeting', ['name' => $receipt->user?->name ?? __('mail.receipt_bank_transfer_failed.participant')]) }}

{{ __('mail.receipt_bank_transfer_failed.intro') }}

@if(filled($bankAccount))
{{ __('mail.receipt_bank_transfer_failed.bank_line', ['account' => $bankAccount]) }}
@endif

@if(filled($promotionName))
{{ __('mail.receipt_bank_transfer_failed.campaign_line', ['campaign' => $promotionName]) }}
@endif

<x-mail::button :url="$receiptUrl">
{{ __('mail.receipt_bank_transfer_failed.button') }}
</x-mail::button>

{{ __('mail.receipt_bank_transfer_failed.outro') }}

{{ __('mail.receipt_bank_transfer_failed.salutation') }}<br>
{{ config('app.name') }}
</x-mail::message>
