<?php

declare(strict_types=1);

namespace App\Filament\User\Resources\ParticipantReceipts\Pages;

use App\Filament\User\Resources\ParticipantReceipts\ParticipantReceiptResource;
use App\Filament\User\Resources\ParticipantReceipts\RequestRefundReviewActionConfigurator;
use App\Services\Receipts\UserReceiptParticipantService;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Database\Eloquent\Model;

final class ViewParticipantReceipt extends ViewRecord
{
    protected static string $resource = ParticipantReceiptResource::class;

    protected function resolveRecord(int|string $key): Model
    {
        $record = parent::resolveRecord($key);
        $record->loadMissing(['receiptProducts.product', 'promotion.products']);

        return $record;
    }

    protected function getHeaderActions(): array
    {
        return [
            RequestRefundReviewActionConfigurator::apply(Action::make('requestRefundReview'))
                ->visible(fn (): bool => auth()->user()?->can('appeal', $this->getRecord()) ?? false)
                ->action(function (array $data): void {
                    app(UserReceiptParticipantService::class)->submitAppeal(
                        auth()->user(),
                        $this->getRecord(),
                        (string) $data['appeal_message'],
                    );

                    Notification::make()
                        ->title(__('user.receipts.request_refund_sent'))
                        ->success()
                        ->send();

                    $this->redirect(ParticipantReceiptResource::getUrl('view', [
                        'record' => $this->getRecord(),
                    ]));
                }),
            EditAction::make()
                ->visible(fn (): bool => auth()->user()?->can('update', $this->getRecord()) ?? false),
        ];
    }
}
