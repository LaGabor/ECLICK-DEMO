<?php

declare(strict_types=1);

namespace App\Filament\User\Resources\ParticipantReceipts;

use App\Filament\User\Resources\ParticipantReceipts\Pages\CreateParticipantReceipt;
use App\Filament\User\Resources\ParticipantReceipts\Pages\EditParticipantReceipt;
use App\Filament\User\Resources\ParticipantReceipts\Pages\ListParticipantReceipts;
use App\Filament\User\Resources\ParticipantReceipts\Pages\ViewParticipantReceipt;
use App\Filament\User\Resources\ParticipantReceipts\Schemas\ParticipantReceiptForm;
use App\Filament\User\Resources\ParticipantReceipts\Schemas\ParticipantReceiptInfolist;
use App\Filament\User\Resources\ParticipantReceipts\Tables\ParticipantReceiptsTable;
use App\Models\Receipt;
use App\Support\UserRole;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

final class ParticipantReceiptResource extends Resource
{
    protected static ?string $model = Receipt::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentText;

    protected static ?string $recordTitleAttribute = 'ap_code';

    public static function getNavigationLabel(): string
    {
        return __('user.navigation.receipts');
    }

    public static function getModelLabel(): string
    {
        return __('user.receipts.label');
    }

    public static function getPluralModelLabel(): string
    {
        return __('user.receipts.plural');
    }

    public static function getNavigationSort(): ?int
    {
        return 10;
    }

    public static function form(Schema $schema): Schema
    {
        return ParticipantReceiptForm::configure($schema, null);
    }

    public static function infolist(Schema $schema): Schema
    {
        return ParticipantReceiptInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ParticipantReceiptsTable::configure($table);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->where('user_id', auth()->id());
    }

    public static function canAccess(): bool
    {
        $user = auth()->user();

        return $user !== null && $user->hasRole(UserRole::User);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListParticipantReceipts::route('/'),
            'create' => CreateParticipantReceipt::route('/create'),
            'view' => ViewParticipantReceipt::route('/{record}'),
            'edit' => EditParticipantReceipt::route('/{record}/edit'),
        ];
    }

    public static function getRecordRouteBindingEloquentQuery(): Builder
    {
        return self::getEloquentQuery();
    }
}
