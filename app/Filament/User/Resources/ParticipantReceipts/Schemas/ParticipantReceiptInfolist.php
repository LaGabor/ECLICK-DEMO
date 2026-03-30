<?php

declare(strict_types=1);

namespace App\Filament\User\Resources\ParticipantReceipts\Schemas;

use App\Contracts\Refunds\ReceiptRefundTotalCalculatorInterface;
use App\Domain\Receipts\ReceiptSubmissionStatus;
use App\Models\Receipt;
use Filament\Infolists\Components\ImageEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Enums\Alignment;

final class ParticipantReceiptInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                Section::make(__('user.receipts.infolist.submission_section'))
                    ->schema([
                        TextEntry::make('status')
                            ->label(__('Status'))
                            ->badge()
                            ->formatStateUsing(fn (ReceiptSubmissionStatus $state): string => $state->getLabel())
                            ->color(fn (ReceiptSubmissionStatus $state): string => $state->getBadgeColor()),
                        TextEntry::make('promotion.name')
                            ->label(__('user.receipts.promotion')),
                        TextEntry::make('purchase_date')
                            ->label(__('user.receipts.purchase_date'))
                            ->date(),
                        TextEntry::make('created_at')
                            ->label(__('user.receipts.infolist.uploaded_at'))
                            ->dateTime(),
                        TextEntry::make('admin_note')
                            ->label(__('Message from the team'))
                            ->placeholder('—')
                            ->columnSpanFull()
                            ->visible(fn (Receipt $record): bool => filled($record->admin_note)),
                        TextEntry::make('appeal_message')
                            ->label(__('Your appeal'))
                            ->placeholder('—')
                            ->columnSpanFull()
                            ->visible(fn (Receipt $record): bool => filled($record->appeal_message)),
                        TextEntry::make('appeal_submitted_at')
                            ->label(__('Appeal submitted at'))
                            ->dateTime()
                            ->visible(fn (Receipt $record): bool => $record->appeal_submitted_at !== null),
                    ])
                    ->columns(2)
                    ->columnSpanFull(),
                Section::make(__('filament.receipts.infolist.purchase_section'))
                    ->description(__('filament.receipts.infolist.purchase_section_description'))
                    ->schema([
                        TextEntry::make('ap_code')
                            ->label(__('user.receipts.ap_code'))
                            ->copyable(),
                        TextEntry::make('id')
                            ->label(__('filament.receipts.infolist.purchased_products'))
                            ->html()
                            ->formatStateUsing(function (mixed $state, Receipt $record): string {
                                $summary = app(ReceiptRefundTotalCalculatorInterface::class)
                                    ->summarizePurchaseAndRefundByLine($record);

                                return view('filament.infolists.receipt-purchase-summary', [
                                    'summary' => $summary,
                                ])->render();
                            })
                            ->columnSpanFull(),
                    ])
                    ->columns(1)
                    ->columnSpanFull(),
                Section::make(__('filament.receipts.workflow_form.section_receipt_image'))
                    ->schema([
                        ImageEntry::make('receipt_image')
                            ->label('')
                            ->height(320)
                            ->checkFileExistence(false)
                            ->url(null)
                            ->extraImgAttributes([
                                'class' => 'cursor-pointer eclick-open-image-preview',
                            ])
                            ->alignment(Alignment::Center)
                            ->formatStateUsing(function (?string $state, Receipt $record): ?string {
                                if (blank($state)) {
                                    return null;
                                }

                                return route('media.receipts.image', $record, absolute: true);
                            })
                            ->placeholder(__('filament.receipts.infolist.no_receipt_image'))
                            ->defaultImageUrl(function (): string {
                                $text = htmlspecialchars(
                                    __('filament.receipts.infolist.image_placeholder'),
                                    ENT_QUOTES | ENT_XML1,
                                    'UTF-8',
                                );

                                $svg = '<svg xmlns="http://www.w3.org/2000/svg" width="640" height="360" viewBox="0 0 640 360"><rect width="100%" height="100%" fill="#e5e7eb"/><text x="320" y="180" dominant-baseline="middle" text-anchor="middle" fill="#4b5563" font-size="16" font-family="ui-sans-serif,system-ui,sans-serif">'.$text.'</text></svg>';

                                return 'data:image/svg+xml;charset=utf-8,'.rawurlencode($svg);
                            }),
                    ])
                    ->columnSpanFull(),
            ]);
    }
}
