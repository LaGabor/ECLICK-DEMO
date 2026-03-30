<?php

declare(strict_types=1);

namespace App\Services\Refunds;

use App\Contracts\Refunds\RefundExportGeneratorInterface;
use App\Domain\Refunds\RefundExportStatus;
use App\Domain\Refunds\RefundExportType;
use App\DTO\Refunds\RefundExportRequestData;
use App\Jobs\Refunds\GenerateRefundExportJob;
use App\Models\RefundExport;
use App\Models\User;

final class RefundExportGenerator implements RefundExportGeneratorInterface
{
    public function queueRefundExport(RefundExportRequestData $request, User $actingAdmin): RefundExport
    {
        $refundExport = RefundExport::query()->create([
            'created_by' => $actingAdmin->getKey(),
            'type' => RefundExportType::Refund,
            'status' => RefundExportStatus::Pending,
            'exported_at' => null,
            'period_start' => $request->purchasePeriodStartsOn->toDateString(),
            'period_end' => $request->purchasePeriodEndsOn->toDateString(),
            'total_rows' => 0,
            'zip_path' => null,
            'last_error' => null,
        ]);

        GenerateRefundExportJob::dispatch($refundExport->getKey());

        return $refundExport->fresh();
    }
}
