<?php

declare(strict_types=1);

namespace App\Filament\Resources\Receipts;

use App\Filament\Resources\Receipts\Pages\EditReceipt;
use App\Filament\Resources\Receipts\Pages\ListReceipts;
use App\Filament\Resources\Receipts\Pages\ViewReceipt;
use App\Filament\Resources\Receipts\Schemas\ReceiptForm;
use App\Filament\Resources\Receipts\Schemas\ReceiptInfolist;
use App\Filament\Resources\Receipts\Tables\ReceiptsTable;
use App\Models\Receipt;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class ReceiptResource extends Resource
{
    protected static ?string $model = Receipt::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentText;

    protected static string|UnitEnum|null $navigationGroup = null;

    public static function getNavigationGroup(): string|UnitEnum|null
    {
        return __('filament.navigation.operations');
    }

    public static function getNavigationSort(): ?int
    {
        return 20;
    }

    public static function getModelLabel(): string
    {
        return __('filament.receipts.label');
    }

    public static function getPluralModelLabel(): string
    {
        return __('filament.receipts.plural');
    }

    public static function form(Schema $schema): Schema
    {
        return ReceiptForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return ReceiptInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ReceiptsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListReceipts::route('/'),
            'view' => ViewReceipt::route('/{record}'),
            'edit' => EditReceipt::route('/{record}/edit'),
        ];
    }
}
