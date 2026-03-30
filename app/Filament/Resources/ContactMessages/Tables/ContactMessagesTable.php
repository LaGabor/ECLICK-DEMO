<?php

declare(strict_types=1);

namespace App\Filament\Resources\ContactMessages\Tables;

use App\Models\ContactMessage;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ContactMessagesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->searchable(),
                TextColumn::make('email')
                    ->label(__('Email address'))
                    ->searchable(),
                TextColumn::make('user.phone')
                    ->label(__('filament.contact_messages.columns.user_phone'))
                    ->placeholder('—')
                    ->toggleable(),
                TextColumn::make('subject')
                    ->searchable()
                    ->placeholder('—'),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('created_at')
                    ->label(__('filament.contact_messages.columns.asked_at'))
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('replied_at')
                    ->label(__('filament.contact_messages.columns.answered_at'))
                    ->dateTime()
                    ->sortable()
                    ->placeholder('—'),
                TextColumn::make('replier.name')
                    ->label(__('filament.contact_messages.columns.replied_by'))
                    ->placeholder('—')
                    ->toggleable(),
            ])
            ->defaultSort('created_at', direction: 'desc')
            ->filters([
                SelectFilter::make('reply_status')
                    ->label(__('filament.contact_messages.filter.reply_status'))
                    ->placeholder(__('filament.contact_messages.filter.all'))
                    ->options([
                        'unanswered' => __('filament.contact_messages.filter.unanswered'),
                        'answered' => __('filament.contact_messages.filter.answered'),
                    ])
                    ->query(function (Builder $query, array $data): void {
                        $value = $data['value'] ?? null;
                        if (blank($value)) {
                            return;
                        }

                        if ($value === 'unanswered') {
                            $query->where(function (Builder $q): void {
                                $q->whereNull('replied_at')
                                    ->where(function (Builder $q2): void {
                                        $q2->whereNull('admin_reply')
                                            ->orWhere('admin_reply', '');
                                    });
                            });

                            return;
                        }

                        if ($value === 'answered') {
                            $query->where(function (Builder $q): void {
                                $q->whereNotNull('replied_at')
                                    ->orWhereRaw('LENGTH(TRIM(COALESCE(admin_reply, ?))) > 0', ['']);
                            });
                        }
                    }),
            ])
            ->recordActions([
                ViewAction::make()
                    ->label(__('filament.contact_messages.actions.view'))
                    ->visible(fn (ContactMessage $record): bool => $record->isAnswered())
                    ->color('gray')
                    ->outlined(),
                EditAction::make()
                    ->label(__('filament.contact_messages.actions.answer'))
                    ->visible(fn (ContactMessage $record): bool => ! $record->isAnswered())
                    ->color('success')
                    ->outlined(),
                DeleteAction::make()
                    ->color('danger')
                    ->outlined(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
