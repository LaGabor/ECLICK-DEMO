<?php

declare(strict_types=1);

namespace App\Contracts\Refunds;

use App\DTO\Refunds\RefundExportRequestData;
use App\Models\RefundExport;
use App\Models\User;

interface RefundExportGeneratorInterface
{
    public function queueRefundExport(RefundExportRequestData $request, User $actingAdmin): RefundExport;
}
