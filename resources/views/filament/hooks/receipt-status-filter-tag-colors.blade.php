@php
    use App\Domain\Receipts\ReceiptSubmissionStatus;

    $statusesByBadgeColor = collect(ReceiptSubmissionStatus::cases())->groupBy(
        fn (ReceiptSubmissionStatus $status): string => $status->getBadgeColor()
    );
    $colorSteps = [50, 100, 200, 300, 400, 500, 600, 700, 800, 900, 950];
@endphp
<style>
@foreach ($statusesByBadgeColor as $filamentColor => $statuses)
@php
    $selectorList = $statuses->map(function (ReceiptSubmissionStatus $status): string {
        return '.fi-fo-select.fi-receipt-status-filter-multiselect .fi-select-input-value-badges-ctn .fi-badge[data-value="'.e($status->value).'"]';
    })->implode(",\n");
@endphp
{!! $selectorList !!} {
@foreach ($colorSteps as $step)
    --color-{{ $step }}: var(--{{ $filamentColor }}-{{ $step }});
@endforeach
}

@endforeach
</style>
