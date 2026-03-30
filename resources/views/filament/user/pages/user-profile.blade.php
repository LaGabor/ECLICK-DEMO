<x-filament-panels::page>
    <div class="fi-user-profile mx-auto w-full max-w-4xl space-y-8 px-1 pb-8 sm:px-2">
        <header
            class="relative overflow-hidden rounded-2xl border border-gray-200/80 bg-gradient-to-br from-sky-50 via-white to-white shadow-sm ring-1 ring-sky-500/10 dark:border-white/10 dark:from-gray-900 dark:via-gray-950 dark:to-sky-950/20 dark:ring-sky-400/15"
        >
            <div
                class="pointer-events-none absolute -right-12 -top-12 h-40 w-40 rounded-full bg-sky-400/15 blur-3xl dark:bg-sky-500/10"
                aria-hidden="true"
            ></div>
            <div class="relative space-y-2 p-6 sm:p-8">
                <p class="text-xs font-semibold uppercase tracking-wider text-sky-700 dark:text-sky-400">
                    {{ __('user.profile.hero_kicker') }}
                </p>
                <h2 class="text-xl font-bold tracking-tight text-gray-950 dark:text-white sm:text-2xl">
                    {{ __('user.profile.hero_title') }}
                </h2>
                <p class="max-w-2xl text-sm leading-relaxed text-gray-600 dark:text-gray-400">
                    {{ __('user.profile.hero_description') }}
                </p>
            </div>
        </header>

        <div
            class="rounded-2xl border border-gray-200/90 bg-white/90 p-1 shadow-sm ring-1 ring-gray-950/5 dark:border-white/10 dark:bg-gray-950/60 dark:ring-white/10 sm:p-2"
        >
            {{ $this->content }}
        </div>
    </div>
</x-filament-panels::page>
