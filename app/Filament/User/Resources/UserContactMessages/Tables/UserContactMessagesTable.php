<?php

declare(strict_types=1);

namespace App\Filament\User\Resources\UserContactMessages\Tables;

use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

final class UserContactMessagesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('subject')
                    ->label(__('Subject'))
                    ->searchable()
                    ->placeholder('—'),
                TextColumn::make('created_at')
                    ->label(__('Sent'))
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('replied_at')
                    ->label(__('Answered'))
                    ->dateTime()
                    ->sortable()
                    ->placeholder('—'),
            ])
            ->recordActions([
                ViewAction::make(),
            ])
            ->defaultSort('created_at', 'desc');
    }
}
