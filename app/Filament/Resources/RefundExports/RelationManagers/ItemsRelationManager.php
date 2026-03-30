<?php

declare(strict_types=1);

namespace App\Filament\Resources\RefundExports\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ItemsRelationManager extends RelationManager
{
    protected static string $relationship = 'items';

    public function isReadOnly(): bool
    {
        return true;
    }

    public function form(Schema $schema): Schema
    {
        return $schema->components([]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('receipt.user.name')
            ->columns([
                TextColumn::make('receipt.user.name')
                    ->label(__('filament.refund_exports.items.recipient_name'))
                    ->searchable(),
                TextColumn::make('receipt.user.bank_account')
                    ->label(__('filament.refund_exports.items.bank_account'))
                    ->copyable(),
                TextColumn::make('refund_amount')
                    ->label(__('filament.refund_exports.items.refund_amount'))
                    ->numeric(decimalPlaces: 4),
            ]);
    }
}
