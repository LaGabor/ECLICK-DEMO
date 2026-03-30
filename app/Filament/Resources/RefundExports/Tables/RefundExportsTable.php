<?php

declare(strict_types=1);

namespace App\Filament\Resources\RefundExports\Tables;

use App\Domain\Refunds\RefundExportStatus;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class RefundExportsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')
                    ->label('ID')
                    ->sortable(),
                TextColumn::make('status')
                    ->label(__('filament.refund_exports.status.label'))
                    ->badge()
                    ->formatStateUsing(fn (?RefundExportStatus $state): string => $state?->getLabel() ?? '')
                    ->color(fn (?RefundExportStatus $state): string => match ($state) {
                        RefundExportStatus::Done => 'success',
                        RefundExportStatus::Failed => 'danger',
                        RefundExportStatus::Pending, RefundExportStatus::Processing => 'warning',
                        default => 'gray',
                    })
                    ->sortable(),
                TextColumn::make('exported_at')
                    ->label(__('Exported at'))
                    ->dateTime()
                    ->sortable()
                    ->placeholder('—'),
                TextColumn::make('period_start')
                    ->label(__('Purchase from'))
                    ->date(),
                TextColumn::make('period_end')
                    ->label(__('Purchase to'))
                    ->date(),
                TextColumn::make('total_rows')
                    ->label(__('Rows'))
                    ->sortable(),
                TextColumn::make('creator.name')
                    ->label(__('Created by'))
                    ->placeholder('—'),
            ])
            ->defaultSort('created_at', direction: 'desc')
            ->recordActions([
                ViewAction::make()
                    ->color('gray')
                    ->outlined(),
                Action::make('downloadRefundExportZipArchive')
                    ->label(__('filament.refund_exports.download_zip'))
                    ->icon('heroicon-o-arrow-down-tray')
                    ->url(fn ($record): string => route('filament.admin.downloads.refund-export-zip', ['refundExport' => $record]))
                    ->openUrlInNewTab()
                    ->visible(fn ($record): bool => $record->status === RefundExportStatus::Done && filled($record->zip_path)),
                DeleteAction::make()
                    ->color('danger')
                    ->outlined()
                    ->visible(fn ($record): bool => ! in_array($record->status, [
                        RefundExportStatus::Pending,
                        RefundExportStatus::Processing,
                    ], true)),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
