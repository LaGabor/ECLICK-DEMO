<?php

declare(strict_types=1);

namespace App\Filament\Resources\Receipts\Tables;

use App\Domain\Receipts\ReceiptSubmissionStatus;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Select;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ReceiptsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('user.name')
                    ->label(__('Participant'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('promotion.name')
                    ->label(__('Campaign'))
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('purchase_date')
                    ->label(__('Purchase date'))
                    ->date()
                    ->sortable(),
                TextColumn::make('created_at')
                    ->label(__('filament.receipts.columns.uploaded_at'))
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('status')
                    ->label(__('Status'))
                    ->badge()
                    ->formatStateUsing(fn (?ReceiptSubmissionStatus $state): string => $state?->getLabel() ?? '')
                    ->color(fn (?ReceiptSubmissionStatus $state): string => $state?->getBadgeColor() ?? 'gray'),
            ])
            ->defaultSort('created_at', direction: 'desc')
            ->filters([
                SelectFilter::make('status')
                    ->label(__('Status'))
                    ->multiple()
                    ->native(false)
                    ->default(ReceiptSubmissionStatus::adminListDefaultFilterValues())
                    ->options(collect(ReceiptSubmissionStatus::cases())->mapWithKeys(
                        fn (ReceiptSubmissionStatus $status): array => [$status->value => $status->getLabel()],
                    ))
                    ->modifyFormFieldUsing(fn (Select $field): Select => $field->extraAttributes([
                        'class' => 'fi-receipt-status-filter-multiselect',
                    ], merge: true)),
                SelectFilter::make('campaign_scope')
                    ->label(__('filament.receipts.filters.campaign_scope'))
                    ->options([
                        'all' => __('filament.receipts.filters.campaign_all'),
                        'active_window' => __('filament.receipts.filters.campaign_active_window'),
                    ])
                    ->default('active_window')
                    ->selectablePlaceholder(false)
                    ->native(false)
                    ->query(function (Builder $query, array $data): void {
                        if (($data['value'] ?? 'all') !== 'active_window') {
                            return;
                        }

                        $query->whereHas('promotion', function (Builder $q): void {
                            $q->whereDate(
                                'upload_end',
                                '>=',
                                now()->subDays(7)->toDateString(),
                            );
                        });
                    }),
            ])
            ->recordActions([
                ViewAction::make()
                    ->label(__('filament.receipts.actions.manage'))
                    ->color('success')
                    ->outlined(),
                EditAction::make()
                    ->color('warning')
                    ->outlined(),
                DeleteAction::make()
                    ->color('danger')
                    ->outlined(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
