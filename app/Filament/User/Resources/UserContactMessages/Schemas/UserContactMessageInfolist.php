<?php

declare(strict_types=1);

namespace App\Filament\User\Resources\UserContactMessages\Schemas;

use App\Models\ContactMessage;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;

final class UserContactMessageInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                Section::make(__('user.contact.infolist.section_your_message'))
                    ->description(__('user.contact.infolist.section_your_message_description'))
                    ->icon(Heroicon::OutlinedPaperAirplane)
                    ->schema([
                        TextEntry::make('subject')
                            ->label(__('Subject'))
                            ->placeholder('—')
                            ->columnSpanFull(),
                        TextEntry::make('message')
                            ->label(__('Your message'))
                            ->columnSpanFull()
                            ->wrap(),
                        TextEntry::make('created_at')
                            ->label(__('user.contact.infolist.sent_at'))
                            ->dateTime(),
                    ])
                    ->columns(2)
                    ->columnSpanFull(),
                Section::make(__('user.contact.infolist.section_team_reply'))
                    ->description(__('user.contact.infolist.section_team_reply_description'))
                    ->icon(Heroicon::OutlinedChatBubbleLeftEllipsis)
                    ->schema([
                        TextEntry::make('admin_reply')
                            ->label(__('user.contact.infolist.team_reply_label'))
                            ->columnSpanFull()
                            ->wrap()
                            ->placeholder(__('user.contact.infolist.awaiting_reply'))
                            ->formatStateUsing(function (?string $state, ContactMessage $record): ?string {
                                return $record->isAnswered() ? $state : null;
                            }),
                        TextEntry::make('replied_at')
                            ->label(__('user.contact.infolist.answered_at'))
                            ->dateTime()
                            ->visible(fn (ContactMessage $record): bool => $record->isAnswered()),
                    ])
                    ->columnSpanFull(),
            ]);
    }
}
