<?php

declare(strict_types=1);

namespace App\Filament\User\Resources\ParticipantReceipts;

use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Support\Icons\Heroicon;

final class RequestRefundReviewActionConfigurator
{
    public static function apply(Action $action): Action
    {
        return $action
            ->label(__('user.receipts.request_refund_action_label'))
            ->icon(Heroicon::OutlinedReceiptRefund)
            ->color('warning')
            ->modalHeading(__('user.receipts.request_refund_modal_heading'))
            ->modalDescription(__('user.receipts.request_refund_modal_warning'))
            ->modalIcon(Heroicon::OutlinedExclamationTriangle)
            ->modalIconColor('warning')
            ->modalWidth('lg')
            ->schema([
                Textarea::make('appeal_message')
                    ->label(__('user.receipts.request_refund_message_label'))
                    ->required()
                    ->rows(6)
                    ->columnSpanFull(),
            ])
            ->modalSubmitActionLabel(__('user.receipts.request_refund_submit'));
    }
}
