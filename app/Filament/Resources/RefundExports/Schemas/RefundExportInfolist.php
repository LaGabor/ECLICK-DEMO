<?php

declare(strict_types=1);

namespace App\Filament\Resources\RefundExports\Schemas;

use App\Domain\Refunds\RefundExportStatus;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class RefundExportInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(__('Batch metadata'))
                    ->extraAttributes([
                        'style' => 'max-width: 42rem;',
                    ])
                    ->schema([
                        TextEntry::make('id')
                            ->label('ID'),
                        TextEntry::make('status')
                            ->label(__('filament.refund_exports.status.label'))
                            ->badge()
                            ->formatStateUsing(fn (?RefundExportStatus $state): string => $state?->getLabel() ?? '')
                            ->color(fn (?RefundExportStatus $state): string => match ($state) {
                                RefundExportStatus::Done => 'success',
                                RefundExportStatus::Failed => 'danger',
                                RefundExportStatus::Pending, RefundExportStatus::Processing => 'warning',
                                default => 'gray',
                            }),
                        TextEntry::make('exported_at')
                            ->label(__('Exported at'))
                            ->dateTime()
                            ->placeholder('—'),
                        TextEntry::make('period_start')
                            ->label(__('Purchase period start'))
                            ->date(),
                        TextEntry::make('period_end')
                            ->label(__('Purchase period end'))
                            ->date(),
                        TextEntry::make('total_rows')
                            ->label(__('Transfer rows')),
                        TextEntry::make('creator.name')
                            ->label(__('Created by'))
                            ->placeholder('—'),
                        TextEntry::make('last_error')
                            ->label(__('filament.refund_exports.last_error'))
                            ->placeholder('—')
                            ->columnSpanFull()
                            ->visible(fn ($record): bool => filled($record->last_error)),
                        TextEntry::make('zip_path')
                            ->label(__('Stored archive path'))
                            ->placeholder('—')
                            ->columnSpanFull(),
                    ])
                    ->columns(2),
            ]);
    }
}
