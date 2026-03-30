<?php

declare(strict_types=1);

namespace App\Filament\User\Resources\UserContactMessages\Schemas;

use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

final class UserContactMessageForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                Section::make()
                    ->schema([
                        TextInput::make('subject')
                            ->label(__('Subject'))
                            ->maxLength(255),
                        Textarea::make('message')
                            ->label(__('Message'))
                            ->required()
                            ->rows(8)
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}
