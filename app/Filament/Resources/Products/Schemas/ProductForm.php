<?php

declare(strict_types=1);

namespace App\Filament\Resources\Products\Schemas;

use App\Support\Validation\ProductListPriceRules;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class ProductForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->required()
                    ->maxLength(255),
                FileUpload::make('product_image')
                    ->label(__('Product image'))
                    ->image()
                    ->disk((string) config('image_upload.disk'))
                    ->directory((string) config('image_upload.path.product_staging'))
                    ->visibility('private')
                    ->maxSize((int) config('image_upload.max_upload_kb'))
                    ->acceptedFileTypes(['image/jpeg', 'image/png'])
                    ->editableSvgs(false)
                    ->required(),
                TextInput::make('sku')
                    ->label('SKU')
                    ->required()
                    ->unique(ignoreRecord: true)
                    ->maxLength(64),
                TextInput::make('price')
                    ->label(__('List price'))
                    ->required()
                    ->numeric()
                    ->minValue(0)
                    ->step(0.01)
                    ->rules(ProductListPriceRules::rulesWithoutRequired())
                    ->dehydrateStateUsing(
                        fn (mixed $state): mixed => $state === null || $state === ''
                            ? $state
                            : ProductListPriceRules::normalizeTwoDecimalString($state),
                    )
                    ->suffix('USD'),
                Toggle::make('active')
                    ->label(__('Active in catalog'))
                    ->default(true),
            ]);
    }
}
