<x-filament-panels::page>
    @php
        $userName = auth()->user()?->name;
    @endphp

    <div
        class="fi-user-dashboard mx-auto w-full max-w-6xl space-y-12 px-2 pb-8 sm:px-4 lg:px-6"
    >
        {{-- Welcome --}}
        <section
            class="relative overflow-hidden rounded-2xl border border-gray-200/80 bg-gradient-to-br from-sky-50 via-white to-sky-50/50 shadow-sm ring-1 ring-sky-500/10 dark:border-white/10 dark:from-gray-900 dark:via-gray-950 dark:to-sky-950/30 dark:ring-sky-400/20"
        >
            <div
                class="pointer-events-none absolute -right-16 -top-16 h-48 w-48 rounded-full bg-sky-400/20 blur-3xl dark:bg-sky-500/10"
                aria-hidden="true"
            ></div>
            <div
                class="pointer-events-none absolute -bottom-20 -left-10 h-40 w-40 rounded-full bg-sky-300/15 blur-2xl dark:bg-sky-600/10"
                aria-hidden="true"
            ></div>

            <div class="relative flex flex-col gap-6 p-6 sm:flex-row sm:items-center sm:justify-between sm:p-8 lg:p-10">
                <div class="max-w-xl space-y-3">
                    <p class="text-xs font-semibold uppercase tracking-wider text-sky-700 dark:text-sky-400">
                        {{ config('app.name') }}
                    </p>
                    <h1 class="text-2xl font-bold tracking-tight text-gray-950 dark:text-white sm:text-3xl">
                        {{ __('user.dashboard.welcome_title') }}
                        @if (filled($userName))
                            <span class="text-sky-700 dark:text-sky-400">, {{ $userName }}</span>
                        @endif
                    </h1>
                    <p class="text-sm leading-relaxed text-gray-600 dark:text-gray-400 sm:text-base">
                        {{ __('user.dashboard.welcome_subtitle') }}
                    </p>
                    <p class="text-sm">
                        <a
                            href="{{ \App\Filament\User\Pages\UserProfilePage::getUrl(panel: 'account') }}"
                            class="font-semibold text-sky-700 underline decoration-sky-400/50 underline-offset-2 transition hover:text-sky-600 dark:text-sky-400 dark:hover:text-sky-300"
                        >
                            {{ __('user.dashboard.profile_link') }}
                        </a>
                    </p>
                </div>

                <div class="flex shrink-0 flex-wrap gap-3 sm:flex-col sm:items-end">
                    <a
                        href="{{ \App\Filament\User\Resources\ParticipantReceipts\ParticipantReceiptResource::getUrl(name: 'create', panel: 'account') }}"
                        class="inline-flex items-center justify-center gap-2 rounded-xl bg-sky-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-sky-500 focus:outline-none focus-visible:ring-2 focus-visible:ring-sky-500 focus-visible:ring-offset-2 dark:focus-visible:ring-offset-gray-950"
                    >
                        <svg class="h-5 w-5 shrink-0 opacity-90" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                        </svg>
                        {{ __('user.receipts.create') }}
                    </a>
                    <a
                        href="{{ \App\Filament\User\Resources\UserContactMessages\UserContactMessageResource::getUrl(name: 'create', panel: 'account') }}"
                        class="inline-flex items-center justify-center gap-2 rounded-xl border border-gray-300 bg-white/80 px-4 py-2.5 text-sm font-semibold text-gray-800 shadow-sm transition hover:bg-white dark:border-white/15 dark:bg-white/5 dark:text-gray-100 dark:hover:bg-white/10"
                    >
                        <svg class="h-5 w-5 shrink-0 text-gray-500 dark:text-gray-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 0 1-2.25 2.25h-15a2.25 2.25 0 0 1-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0 0 19.5 4.5h-15a2.25 2.25 0 0 0-2.25 2.25m19.5 0v.243a2.25 2.25 0 0 1-1.07 1.916l-7.5 4.615a2.25 2.25 0 0 1-2.36 0L3.32 8.91a2.25 2.25 0 0 1-1.07-1.916V6.75" />
                        </svg>
                        {{ __('user.contact.create') }}
                    </a>
                </div>
            </div>
        </section>

        {{-- FAQ --}}
        <section class="space-y-8">
            <header class="mx-auto max-w-2xl text-center sm:mx-0 sm:max-w-none sm:text-left">
                <div class="inline-flex items-center gap-2 text-sky-700 dark:text-sky-400">
                    <span class="flex h-9 w-9 items-center justify-center rounded-lg bg-sky-100 dark:bg-sky-500/15">
                        <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9.879 7.519c1.171-1.025 3.071-1.025 4.242 0 1.172 1.025 1.172 2.687 0 3.712-.203.179-.43.326-.67.442-.745.361-1.45.999-1.45 1.827v.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9 5.25h.008v.008H12v-.008Z" />
                        </svg>
                    </span>
                    <span class="text-xs font-semibold uppercase tracking-wider">{{ __('user.dashboard.help_badge') }}</span>
                </div>
                <h2 class="mt-3 text-2xl font-bold tracking-tight text-gray-950 dark:text-white sm:text-3xl">
                    {{ __('user.dashboard.faq_heading') }}
                </h2>
                <p class="mt-2 text-sm text-gray-600 dark:text-gray-400 sm:text-base">
                    {{ __('user.dashboard.faq_intro') }}
                </p>
            </header>

            @forelse ($this->faqs as $faq)
                @if ($loop->first)
                    <div
                        class="grid gap-5 sm:grid-cols-1 lg:grid-cols-2 lg:gap-6"
                    >
                @endif

                <article
                    class="group flex h-full flex-col rounded-2xl border border-gray-200/90 bg-white p-6 shadow-sm transition duration-200 hover:border-sky-200/80 hover:shadow-md dark:border-white/10 dark:bg-gray-950/80 dark:hover:border-sky-500/30"
                >
                    <div class="flex gap-4">
                        <span
                            class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-gradient-to-br from-sky-100 to-sky-50 text-sm font-bold text-sky-800 dark:from-sky-500/20 dark:to-sky-600/10 dark:text-sky-300"
                            aria-hidden="true"
                        >
                            {{ str_pad((string) $loop->iteration, 2, '0', STR_PAD_LEFT) }}
                        </span>
                        <div class="min-w-0 flex-1 space-y-3">
                            <h3 class="text-base font-semibold leading-snug text-gray-950 dark:text-white">
                                {{ $faq->question }}
                            </h3>
                            <div
                                class="border-t border-gray-100 pt-3 text-sm leading-relaxed text-gray-600 dark:border-white/10 dark:text-gray-400"
                            >
                                {!! nl2br(e($faq->answer)) !!}
                            </div>
                        </div>
                    </div>
                </article>

                @if ($loop->last)
                    </div>
                @endif
            @empty
                <div
                    class="mx-auto flex max-w-lg flex-col items-center rounded-2xl border border-dashed border-gray-300 bg-gray-50/80 px-8 py-14 text-center dark:border-white/15 dark:bg-white/[0.03]"
                >
                    <span class="flex h-14 w-14 items-center justify-center rounded-2xl bg-gray-200/80 text-gray-500 dark:bg-white/10 dark:text-gray-400">
                        <svg class="h-7 w-7" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 6.042A8.967 8.967 0 0 0 6 3.75c-1.052 0-2.062.18-3 .512v14.25A8.987 8.987 0 0 1 6 18c2.305 0 4.408.867 6 2.292m0-14.25a8.966 8.966 0 0 0-6-2.292c-1.052 0-2.062.18-3 .512v14.25A8.987 8.987 0 0 1 6 18c2.305 0 4.408.867 6 2.292m0-14.25v14.25" />
                        </svg>
                    </span>
                    <p class="mt-5 text-sm font-medium text-gray-700 dark:text-gray-300">
                        {{ __('user.dashboard.empty_faqs') }}
                    </p>
                </div>
            @endforelse
        </section>
    </div>
</x-filament-panels::page>
