<?php

declare(strict_types=1);

namespace App\Filament\Resources\Promotions\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class PromotionForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->label(__('Campaign name'))
                    ->required()
                    ->maxLength(255),
                DatePicker::make('purchase_start')
                    ->label(__('Purchase period start'))
                    ->required()
                    ->native(false),
                DatePicker::make('purchase_end')
                    ->label(__('Purchase period end'))
                    ->required()
                    ->native(false)
                    ->afterOrEqual('purchase_start')
                    ->validationMessages([
                        'after_or_equal' => __('filament.promotions.validation.purchase_end_after_or_equal'),
                    ]),
                DatePicker::make('upload_start')
                    ->label(__('Upload period start'))
                    ->native(false)
                    ->default(fn (callable $get) => $get('purchase_start'))
                    ->required()
                    ->afterOrEqual('purchase_start')
                    ->validationMessages([
                        'after_or_equal' => __('filament.promotions.validation.upload_start_after_or_equal'),
                    ]),
                DatePicker::make('upload_end')
                    ->label(__('Upload period end'))
                    ->required()
                    ->native(false)
                    ->afterOrEqual('upload_start')
                    ->afterOrEqual('purchase_end')
                    ->validationMessages([
                        'after_or_equal' => __('filament.promotions.validation.upload_end_after_or_equal'),
                    ]),
            ]);
    }
}
