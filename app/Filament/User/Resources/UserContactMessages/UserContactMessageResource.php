<?php

declare(strict_types=1);

namespace App\Filament\User\Resources\UserContactMessages;

use App\Filament\User\Resources\UserContactMessages\Pages\CreateUserContactMessage;
use App\Filament\User\Resources\UserContactMessages\Pages\ListUserContactMessages;
use App\Filament\User\Resources\UserContactMessages\Pages\ViewUserContactMessage;
use App\Filament\User\Resources\UserContactMessages\Schemas\UserContactMessageForm;
use App\Filament\User\Resources\UserContactMessages\Schemas\UserContactMessageInfolist;
use App\Filament\User\Resources\UserContactMessages\Tables\UserContactMessagesTable;
use App\Models\ContactMessage;
use App\Support\UserRole;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

final class UserContactMessageResource extends Resource
{
    protected static ?string $model = ContactMessage::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedChatBubbleLeftRight;

    public static function getNavigationLabel(): string
    {
        return __('user.navigation.contact');
    }

    public static function getModelLabel(): string
    {
        return __('user.contact.label');
    }

    public static function getPluralModelLabel(): string
    {
        return __('user.contact.plural');
    }

    public static function getNavigationSort(): ?int
    {
        return 20;
    }

    public static function form(Schema $schema): Schema
    {
        return UserContactMessageForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return UserContactMessageInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return UserContactMessagesTable::configure($table);
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
            'index' => ListUserContactMessages::route('/'),
            'create' => CreateUserContactMessage::route('/create'),
            'view' => ViewUserContactMessage::route('/{record}'),
        ];
    }

    public static function getRecordRouteBindingEloquentQuery(): Builder
    {
        return self::getEloquentQuery();
    }
}
