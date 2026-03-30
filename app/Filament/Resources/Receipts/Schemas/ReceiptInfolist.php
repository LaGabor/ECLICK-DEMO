<?php

declare(strict_types=1);

namespace App\Filament\Resources\Receipts\Schemas;

use App\Contracts\Refunds\ReceiptRefundTotalCalculatorInterface;
use App\Domain\Receipts\ReceiptSubmissionStatus;
use App\Models\Receipt;
use Filament\Infolists\Components\ImageEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Enums\Alignment;

class ReceiptInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                Section::make(__('Participant'))
                    ->schema([
                        TextEntry::make('user.name')
                            ->label(__('Name')),
                        TextEntry::make('user.email')
                            ->label(__('Email')),
                        TextEntry::make('user.phone')
                            ->label(__('Phone')),
                        TextEntry::make('user.bank_account')
                            ->label(__('Bank account')),
                    ])
                    ->columns(2)
                    ->columnSpanFull(),
                Section::make(__('Submission'))
                    ->schema([
                        TextEntry::make('promotion.name')
                            ->label(__('Campaign')),
                        TextEntry::make('purchase_date')
                            ->label(__('Purchase date'))
                            ->date(),
                        TextEntry::make('created_at')
                            ->label(__('filament.receipts.columns.uploaded_at'))
                            ->dateTime(),
                        TextEntry::make('status')
                            ->label(__('Status'))
                            ->badge()
                            ->formatStateUsing(fn ($state): string => $state?->getLabel() ?? '')
                            ->color(fn ($state): string => $state instanceof ReceiptSubmissionStatus ? $state->getBadgeColor() : 'gray'),
                        TextEntry::make('admin_note')
                            ->label(__('Administrator note'))
                            ->placeholder('—')
                            ->columnSpanFull(),
                        TextEntry::make('reviewed_at')
                            ->label(__('Reviewed at'))
                            ->dateTime()
                            ->placeholder('—'),
                        TextEntry::make('paid_at')
                            ->label(__('Paid at'))
                            ->dateTime()
                            ->placeholder('—'),
                        TextEntry::make('appeal_submitted_at')
                            ->label(__('Appeal submitted at'))
                            ->dateTime()
                            ->placeholder('—'),
                    ])
                    ->columns(2)
                    ->columnSpanFull(),
                Section::make(__('filament.receipts.infolist.purchase_section'))
                    ->description(__('filament.receipts.infolist.purchase_section_description'))
                    ->schema([
                        TextEntry::make('ap_code')
                            ->label(__('AP code'))
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
                Section::make(null)
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

                                return route('filament.admin.media.receipts.image', $record, absolute: true);
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
