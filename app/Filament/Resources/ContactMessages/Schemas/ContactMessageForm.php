<?php

declare(strict_types=1);

namespace App\Filament\Resources\ContactMessages\Schemas;

use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class ContactMessageForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->disabled()
                    ->dehydrated(true),
                TextInput::make('email')
                    ->label(__('Email address'))
                    ->email()
                    ->disabled()
                    ->dehydrated(true),
                TextInput::make('participant_phone')
                    ->label(__('filament.contact_messages.columns.user_phone'))
                    ->disabled()
                    ->dehydrated(false),
                TextInput::make('subject')
                    ->disabled()
                    ->dehydrated(true),
                Textarea::make('message')
                    ->label(__('Message'))
                    ->required()
                    ->disabled()
                    ->dehydrated(true)
                    ->rows(6)
                    ->columnSpanFull(),
                Textarea::make('admin_reply')
                    ->label(__('Administrator reply'))
                    ->required()
                    ->rows(8)
                    ->columnSpanFull(),
            ]);
    }
}
