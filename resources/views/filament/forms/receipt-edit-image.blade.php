@php
    use Illuminate\Support\Facades\Storage;
@endphp

<div class="fi-receipt-edit-image-ctn flex flex-col items-center justify-center py-4">
    @if (filled($receipt->receipt_image) && Storage::disk((string) config('image_upload.disk'))->exists((string) $receipt->receipt_image))
        <img
            src="{{ route('filament.admin.media.receipts.image', $receipt, absolute: true) }}"
            alt=""
            class="eclick-open-image-preview max-h-80 cursor-pointer rounded-lg shadow-sm ring-1 ring-gray-950/5 dark:ring-white/10"
        />
    @else
        <p class="text-center text-sm text-gray-500 dark:text-gray-400">
            {{ __('filament.receipts.infolist.no_receipt_image') }}
        </p>
    @endif
</div>
