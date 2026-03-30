<?php

declare(strict_types=1);

namespace App\Filament\Resources\ContactMessages\Schemas;

use App\Models\ContactMessage;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;

class ContactMessageInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                Section::make(__('filament.contact_messages.infolist.section_from'))
                    ->description(__('filament.contact_messages.infolist.section_from_description'))
                    ->icon(Heroicon::OutlinedUser)
                    ->schema([
                        TextEntry::make('name')
                            ->label(__('Name')),
                        TextEntry::make('email')
                            ->label(__('Email address')),
                        TextEntry::make('user.phone')
                            ->label(__('filament.contact_messages.columns.user_phone'))
                            ->placeholder('—'),
                        TextEntry::make('created_at')
                            ->label(__('filament.contact_messages.columns.asked_at'))
                            ->dateTime(),
                    ])
                    ->columns(2)
                    ->columnSpanFull(),
                Section::make(__('filament.contact_messages.infolist.section_message'))
                    ->description(__('filament.contact_messages.infolist.section_message_description'))
                    ->icon(Heroicon::OutlinedChatBubbleLeftRight)
                    ->schema([
                        TextEntry::make('subject')
                            ->label(__('Subject'))
                            ->placeholder('—')
                            ->columnSpanFull(),
                        TextEntry::make('message')
                            ->label(__('Message'))
                            ->columnSpanFull()
                            ->wrap(),
                    ])
                    ->columnSpanFull(),
                Section::make(__('filament.contact_messages.infolist.section_reply'))
                    ->description(__('filament.contact_messages.infolist.section_reply_description'))
                    ->icon(Heroicon::OutlinedCheckCircle)
                    ->schema([
                        TextEntry::make('admin_reply')
                            ->label(__('Administrator reply'))
                            ->placeholder('—')
                            ->columnSpanFull()
                            ->wrap(),
                        TextEntry::make('replier.name')
                            ->label(__('filament.contact_messages.columns.replied_by'))
                            ->placeholder('—'),
                        TextEntry::make('replied_at')
                            ->label(__('filament.contact_messages.columns.answered_at'))
                            ->dateTime()
                            ->placeholder('—'),
                    ])
                    ->columns(2)
                    ->columnSpanFull()
                    ->visible(fn (ContactMessage $record): bool => $record->isAnswered()),
            ]);
    }
}
