@php
    $imageUrl = $imageUrl ?? null;
@endphp
<div
    class="fi-participant-line-thumb flex h-20 w-20 shrink-0 items-center justify-center overflow-hidden rounded-lg border border-gray-200 bg-gray-50 ring-1 ring-gray-950/5 dark:border-white/10 dark:bg-white/[0.04] dark:ring-white/10"
>
    @if (filled($imageUrl))
        <img
            src="{{ $imageUrl }}"
            alt=""
            class="eclick-open-image-preview h-full w-full cursor-pointer object-cover"
        />
    @endif
</div>
