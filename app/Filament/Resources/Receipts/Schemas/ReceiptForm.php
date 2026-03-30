<?php

declare(strict_types=1);

namespace App\Filament\Resources\Receipts\Schemas;

use App\Contracts\Refunds\ReceiptRefundTotalCalculatorInterface;
use App\Domain\Receipts\ReceiptSubmissionStatus;
use App\Models\Receipt;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\View as SchemaView;
use Filament\Schemas\Schema;
use Illuminate\Validation\Rules\Enum;

class ReceiptForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Textarea::make('admin_note')
                    ->label(__('Administrator note'))
                    ->rows(4)
                    ->columnSpanFull(),
            ]);
    }

    public static function configureForProcessing(Schema $schema, Receipt $record): Schema
    {
        $record->loadMissing(['receiptProducts.product', 'promotion.products', 'user', 'promotion']);

        return $schema
            ->columns(1)
            ->components([
                Section::make(__('filament.receipts.workflow_form.section_context'))
                    ->description(__('filament.receipts.workflow_form.section_context_description_edit'))
                    ->schema([
                        TextInput::make('context_participant')
                            ->label(__('Participant'))
                            ->disabled()
                            ->dehydrated(false),
                        TextInput::make('context_email')
                            ->label(__('Email address'))
                            ->disabled()
                            ->dehydrated(false),
                        TextInput::make('context_phone')
                            ->label(__('Phone'))
                            ->disabled()
                            ->dehydrated(false),
                        TextInput::make('context_bank')
                            ->label(__('Bank account'))
                            ->disabled()
                            ->dehydrated(false),
                        TextInput::make('context_campaign')
                            ->label(__('Campaign'))
                            ->disabled()
                            ->dehydrated(false),
                        TextInput::make('context_ap_code')
                            ->label(__('AP code'))
                            ->disabled()
                            ->dehydrated(false),
                        TextInput::make('context_purchase_date')
                            ->label(__('Purchase date'))
                            ->disabled()
                            ->dehydrated(false),
                    ])
                    ->columns(2)
                    ->columnSpanFull(),
                Section::make(__('filament.receipts.infolist.purchase_section'))
                    ->description(__('filament.receipts.infolist.purchase_section_description'))
                    ->schema([
                        SchemaView::make('filament.forms.receipt-edit-purchase')
                            ->viewData([
                                'summary' => app(ReceiptRefundTotalCalculatorInterface::class)
                                    ->summarizePurchaseAndRefundByLine($record),
                            ])
                            ->columnSpanFull(),
                    ])
                    ->columnSpanFull(),
                Section::make(__('filament.receipts.workflow_form.section_receipt_image'))
                    ->schema([
                        SchemaView::make('filament.forms.receipt-edit-image')
                            ->viewData([
                                'receipt' => $record,
                            ])
                            ->columnSpanFull(),
                    ])
                    ->columnSpanFull(),
                Section::make(__('filament.receipts.workflow_form.section_correction'))
                    ->description(__('filament.receipts.workflow_form.section_correction_description'))
                    ->schema([
                        Select::make('status')
                            ->label(__('Status'))
                            ->options(collect(ReceiptSubmissionStatus::cases())->mapWithKeys(
                                fn (ReceiptSubmissionStatus $s): array => [$s->value => $s->getLabel()],
                            ))
                            ->required()
                            ->native(false)
                            ->rules([new Enum(ReceiptSubmissionStatus::class)]),
                        Textarea::make('admin_note')
                            ->label(__('Administrator note'))
                            ->helperText(__('filament.receipts.workflow_form.admin_note_helper'))
                            ->rows(5)
                            ->columnSpanFull(),
                    ])
                    ->columns(1)
                    ->columnSpanFull(),
            ]);
    }
}
