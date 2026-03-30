<?php

declare(strict_types=1);

namespace App\Filament\Resources\RefundExports\Pages;

use App\Domain\Refunds\RefundExportStatus;
use App\Filament\Resources\RefundExports\RefundExportResource;
use Filament\Actions\Action;
use Filament\Resources\Pages\ViewRecord;

class ViewRefundExport extends ViewRecord
{
    protected static string $resource = RefundExportResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('downloadRefundExportZipArchive')
                ->label(__('filament.refund_exports.download_zip'))
                ->url(fn (): string => route('filament.admin.downloads.refund-export-zip', ['refundExport' => $this->getRecord()]))
                ->openUrlInNewTab()
                ->visible(fn (): bool => $this->getRecord()->status === RefundExportStatus::Done
                    && filled($this->getRecord()->zip_path)),
        ];
    }
}
