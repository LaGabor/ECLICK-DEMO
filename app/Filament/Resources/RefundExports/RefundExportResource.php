<?php

declare(strict_types=1);

namespace App\Filament\Resources\RefundExports;

use App\Filament\Resources\RefundExports\Pages\ListRefundExports;
use App\Filament\Resources\RefundExports\Pages\ViewRefundExport;
use App\Filament\Resources\RefundExports\RelationManagers\ItemsRelationManager;
use App\Filament\Resources\RefundExports\Schemas\RefundExportForm;
use App\Filament\Resources\RefundExports\Schemas\RefundExportInfolist;
use App\Filament\Resources\RefundExports\Tables\RefundExportsTable;
use App\Models\RefundExport;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class RefundExportResource extends Resource
{
    protected static ?string $model = RefundExport::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedArrowDownTray;

    protected static string|UnitEnum|null $navigationGroup = null;

    public static function getNavigationGroup(): string|UnitEnum|null
    {
        return __('filament.navigation.refunds');
    }

    public static function getNavigationSort(): ?int
    {
        return 30;
    }

    public static function getModelLabel(): string
    {
        return __('filament.refund_exports.label');
    }

    public static function getPluralModelLabel(): string
    {
        return __('filament.refund_exports.plural');
    }

    public static function form(Schema $schema): Schema
    {
        return RefundExportForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return RefundExportInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return RefundExportsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            ItemsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListRefundExports::route('/'),
            'view' => ViewRefundExport::route('/{record}'),
        ];
    }
}
