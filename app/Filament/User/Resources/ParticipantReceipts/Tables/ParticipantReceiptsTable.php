<?php

declare(strict_types=1);

namespace App\Filament\User\Resources\ParticipantReceipts\Tables;

use App\Domain\Receipts\ReceiptSubmissionStatus;
use App\Filament\User\Resources\ParticipantReceipts\RequestRefundReviewActionConfigurator;
use App\Models\Receipt;
use App\Services\Receipts\UserReceiptParticipantService;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

final class ParticipantReceiptsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('status')
                    ->label(__('Status'))
                    ->badge()
                    ->formatStateUsing(fn (ReceiptSubmissionStatus $state): string => $state->getLabel())
                    ->color(fn (ReceiptSubmissionStatus $state): string => $state->getBadgeColor()),
                TextColumn::make('promotion.name')
                    ->label(__('user.receipts.promotion'))
                    ->searchable(),
                TextColumn::make('purchase_date')
                    ->label(__('Purchase date'))
                    ->date()
                    ->sortable(),
                TextColumn::make('updated_at')
                    ->label(__('Updated'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->recordActions([
                ViewAction::make(),
                RequestRefundReviewActionConfigurator::apply(Action::make('requestRefundReview'))
                    ->visible(fn (Receipt $record): bool => auth()->user()?->can('appeal', $record) ?? false)
                    ->action(function (array $data, Action $action): void {
                        $record = $action->getRecord();
                        if (! $record instanceof Receipt) {
                            return;
                        }

                        app(UserReceiptParticipantService::class)->submitAppeal(
                            auth()->user(),
                            $record,
                            (string) $data['appeal_message'],
                        );

                        Notification::make()
                            ->title(__('user.receipts.request_refund_sent'))
                            ->success()
                            ->send();
                    }),
                EditAction::make()
                    ->visible(fn (Receipt $record): bool => auth()->user()?->can('update', $record) ?? false),
            ])
            ->defaultSort('created_at', 'desc');
    }
}
