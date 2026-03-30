@php
    /** @var array{lines: list<array<string, mixed>>, purchase_total_display: string, refund_total_display: string} $summary */
@endphp
<div class="fi-section-content-ctn overflow-hidden rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
    <div class="mb-8 overflow-x-auto">
        <table class="fi-ta-table">
            <thead>
                <tr>
                    <th class="fi-ta-header-cell w-16 min-w-16 px-3 py-3 text-center sm:px-3" scope="col" title="{{ __('filament.receipts.infolist.col_on_promotion_hint') }}">
                        {{ __('filament.receipts.infolist.col_on_promotion') }}
                    </th>
                    <th class="fi-ta-header-cell px-3 py-3 text-center sm:px-3" scope="col">
                        {{ __('filament.receipts.infolist.col_product_code') }}
                    </th>
                    <th class="fi-ta-header-cell px-3 py-3 text-center sm:px-3" scope="col">
                        {{ __('filament.receipts.infolist.col_quantity') }}
                    </th>
                    <th class="fi-ta-header-cell px-3 py-3 text-center sm:px-3" scope="col">
                        {{ __('filament.receipts.infolist.col_product_price') }}
                    </th>
                    <th class="fi-ta-header-cell px-3 py-3 text-center sm:px-3" scope="col">
                        {{ __('filament.receipts.infolist.col_line_subtotal') }}
                    </th>
                    <th class="fi-ta-header-cell px-3 py-3 text-center sm:px-3" scope="col">
                        {{ __('filament.receipts.infolist.col_refund_per_unit') }}
                    </th>
                    <th class="fi-ta-header-cell px-3 py-3 text-center sm:px-3" scope="col">
                        {{ __('filament.receipts.infolist.col_expected_refund') }}
                    </th>
                </tr>
            </thead>
            <tbody>
                @forelse ($summary['lines'] as $line)
                    <tr class="bg-white dark:bg-gray-900">
                        <td class="fi-ta-cell px-3 py-3 text-center align-middle sm:px-3">
                            @if ($line['on_promotion'] === true)
                                <span
                                    style="display:inline-block;font-size:2.25rem;line-height:1;font-weight:800;color:#16a34a;"
                                    title="{{ __('filament.receipts.infolist.on_promotion_yes') }}"
                                >✓</span>
                            @else
                                <span
                                    style="display:inline-block;font-size:2.25rem;line-height:1;font-weight:800;color:#dc2626;"
                                    title="{{ __('filament.receipts.infolist.on_promotion_no') }}"
                                >✗</span>
                            @endif
                        </td>
                        <td class="fi-ta-cell px-3 py-3 text-center font-mono text-sm text-gray-950 sm:px-3 dark:text-white">
                            {{ $line['product_code'] }}
                        </td>
                        <td class="fi-ta-cell px-3 py-3 text-center text-sm text-gray-950 sm:px-3 dark:text-white font-variant-numeric tabular-nums">
                            {{ $line['quantity_display'] ?? '—' }}
                        </td>
                        <td class="fi-ta-cell px-3 py-3 text-center text-sm text-gray-950 sm:px-3 dark:text-white">
                            {{ $line['product_price_display'] }}
                        </td>
                        <td class="fi-ta-cell px-3 py-3 text-center text-sm text-gray-950 sm:px-3 dark:text-white font-variant-numeric tabular-nums">
                            {{ $line['line_subtotal_display'] ?? '—' }}
                        </td>
                        <td class="fi-ta-cell px-3 py-3 text-center text-sm text-gray-950 sm:px-3 dark:text-white">
                            {{ $line['refund_per_unit_display'] }}
                        </td>
                        <td class="fi-ta-cell px-3 py-3 text-center text-sm sm:px-3">
                            @if ($line['expected_refund_display'] !== '—')
                                <span class="font-semibold text-green-700 dark:text-green-400">{{ $line['expected_refund_display'] }}</span>
                            @else
                                <span class="text-gray-500 dark:text-gray-400">—</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td class="fi-ta-cell px-3 py-6 text-center text-sm text-gray-500 sm:px-3 dark:text-gray-400" colspan="7">
                            {{ __('filament.receipts.infolist.no_lines') }}
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if (count($summary['lines']) > 0)
        <div
            class="border-t border-gray-200 bg-gray-50 dark:border-white/10 dark:bg-white/5"
            style="margin-top:2.5rem;padding:2.5rem 1.5rem 3rem;text-align:center;box-sizing:border-box;"
        >
            <div style="margin-bottom:2rem;">
                <div style="font-size:1.25rem;font-weight:800;line-height:1.4;color:#0a0a0a;margin-bottom:0.75rem;">
                    {{ __('filament.receipts.infolist.footer_purchase_total') }}
                </div>
                <div style="font-size:2rem;font-weight:900;line-height:1.2;color:#0a0a0a;font-variant-numeric:tabular-nums;">
                    {{ $summary['purchase_total_display'] }}
                </div>
            </div>
            <div style="margin-top:2.5rem;padding-top:2rem;border-top:1px solid #d1d5db;">
                <div style="font-size:1.25rem;font-weight:800;line-height:1.4;color:#0a0a0a;margin-bottom:0.75rem;">
                    {{ __('filament.receipts.infolist.footer_refund_total') }}
                </div>
                <div style="font-size:2rem;font-weight:900;line-height:1.2;color:#15803d;font-variant-numeric:tabular-nums;">
                    {{ $summary['refund_total_display'] }}
                </div>
            </div>
        </div>
    @endif
</div>
