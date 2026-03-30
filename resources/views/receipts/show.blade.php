<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('receipts.show.title') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900 dark:text-gray-100 space-y-4">
                    @if (session('status'))
                        <div class="rounded-md border border-emerald-200 dark:border-emerald-900/50 bg-emerald-50 dark:bg-emerald-950/40 p-4 text-sm text-emerald-900 dark:text-emerald-100">
                            {{ session('status') }}
                        </div>
                    @endif
                    <p class="text-sm text-gray-600 dark:text-gray-400">
                        {{ __('receipts.show.status_label') }}
                        <span class="font-medium">{{ $receipt->status->getLabel() }}</span>
                    </p>
                    @if(filled($receipt->receipt_image))
                        <div class="eclick-receipt-show">
                            <h3 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">{{ __('receipts.show.receipt_image_heading') }}</h3>
                            <img
                                src="{{ route('media.receipts.image', $receipt) }}"
                                alt=""
                                class="eclick-open-image-preview max-w-full cursor-pointer rounded-lg border border-gray-200 dark:border-gray-700 shadow-sm"
                            />
                        </div>
                    @endif
                    @if(filled($receipt->admin_note))
                        <div class="rounded-md border border-amber-200 dark:border-amber-900/50 bg-amber-50 dark:bg-amber-950/40 p-4 text-sm">
                            <p class="font-medium text-amber-900 dark:text-amber-100">{{ __('receipts.show.note_heading') }}</p>
                            <p class="mt-1 text-amber-900/90 dark:text-amber-100/90 whitespace-pre-wrap">{{ $receipt->admin_note }}</p>
                        </div>
                    @endif
                    @can('uploadReceiptImage', $receipt)
                        <div class="border-t border-gray-200 dark:border-gray-700 pt-6">
                            <h3 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">{{ __('receipts.show.upload_heading') }}</h3>
                            <p class="text-xs text-gray-500 dark:text-gray-400 mb-3">{{ __('receipts.show.upload_hint') }}</p>
                            <form
                                method="post"
                                action="{{ route('receipts.image.store', $receipt) }}"
                                enctype="multipart/form-data"
                                class="space-y-3"
                                id="receipt-image-upload-form"
                            >
                                @csrf
                                <input
                                    type="file"
                                    name="receipt_image"
                                    id="receipt_image"
                                    accept="image/jpeg,image/png,.jpg,.jpeg,.png"
                                    required
                                    class="block w-full text-sm text-gray-700 dark:text-gray-300 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-medium file:bg-indigo-50 file:text-indigo-700 dark:file:bg-indigo-950 dark:file:text-indigo-200"
                                />
                                @error('receipt_image')
                                    <p class="text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                                @enderror
                                <x-primary-button type="submit">{{ __('receipts.show.upload_submit') }}</x-primary-button>
                            </form>
                            <script>
                                (function () {
                                    const form = document.getElementById('receipt-image-upload-form');
                                    const input = document.getElementById('receipt_image');
                                    if (!form || !input) return;
                                    const maxBytes = 10 * 1024 * 1024;
                                    form.addEventListener('submit', function (e) {
                                        const file = input.files && input.files[0];
                                        if (!file) return;
                                        if (!file.type.startsWith('image/') || !/(jpe?g|png)$/i.test(file.name)) {
                                            e.preventDefault();
                                            alert(@json(__('receipts.show.upload_invalid_type')));
                                            return;
                                        }
                                        if (file.size > maxBytes) {
                                            e.preventDefault();
                                            alert(@json(__('receipts.show.upload_too_large')));
                                        }
                                    });
                                })();
                            </script>
                        </div>
                    @endcan
                    <p class="text-sm">
                        <a href="{{ route('profile.edit') }}" class="text-indigo-600 dark:text-indigo-400 underline">
                            {{ __('receipts.show.profile_link') }}
                        </a>
                    </p>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
