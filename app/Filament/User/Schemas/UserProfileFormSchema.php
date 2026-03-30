<?php

declare(strict_types=1);

namespace App\Filament\User\Schemas;

use App\Support\Validation\HungarianInternationalPhoneRules;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Html;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\HtmlString;
use Illuminate\Validation\Rule;

final class UserProfileFormSchema
{
    public static function configure(Schema $schema): Schema
    {
        $user = auth()->user();
        $userId = $user?->getKey();

        return $schema
            ->columns(1)
            ->components([
                Section::make(__('user.profile.section_account'))
                    ->description(__('user.profile.section_account_description'))
                    ->icon(Heroicon::OutlinedEnvelope)
                    ->schema([
                        Html::make(function (): HtmlString {
                            $email = e((string) auth()->user()?->email);

                            return new HtmlString(
                                '<div class="rounded-xl border border-gray-200/80 bg-gray-50/80 px-4 py-3 dark:border-white/10 dark:bg-white/[0.04]">'
                                .'<p class="text-xs font-medium uppercase tracking-wide text-gray-500 dark:text-gray-400">'
                                .e(__('user.profile.email_label'))
                                .'</p>'
                                .'<p class="mt-1 break-all text-sm font-semibold text-gray-950 dark:text-white">'.$email.'</p>'
                                .'<p class="mt-2 text-xs text-gray-500 dark:text-gray-400">'
                                .e(__('user.profile.email_cannot_change'))
                                .'</p>'
                                .'</div>'
                            );
                        }),
                    ])
                    ->columns(1)
                    ->columnSpanFull(),
                Section::make(__('user.profile.section_identity'))
                    ->description(__('user.profile.section_identity_description'))
                    ->icon(Heroicon::OutlinedUser)
                    ->schema([
                        TextInput::make('name')
                            ->label(__('user.profile.name_label'))
                            ->required()
                            ->maxLength(255)
                            ->trim()
                            ->autocomplete('name'),
                        TextInput::make('phone')
                            ->label(__('user.profile.phone_label'))
                            ->required()
                            ->maxLength(12)
                            ->extraInputAttributes([
                                'class' => 'font-mono',
                                'placeholder' => '+36300705352',
                                'pattern' => '\+36[0-9]{9}',
                                'inputmode' => 'numeric',
                            ])
                            ->tel()
                            ->trim()
                            ->autocomplete('tel')
                            ->helperText(__('user.profile.phone_helper'))
                            ->rules([
                                ...HungarianInternationalPhoneRules::requiredRules(),
                                Rule::unique('users', 'phone')
                                    ->ignore($userId)
                                    ->whereNull('deleted_at'),
                            ])
                            ->validationMessages([
                                'regex' => __('messages.phone_hu_e164_invalid'),
                                'unique' => __('user.profile.validation.phone_taken'),
                            ]),
                    ])
                    ->columns(1)
                    ->columnSpanFull(),
                Section::make(__('user.profile.section_payout'))
                    ->description(__('user.profile.section_payout_description'))
                    ->icon(Heroicon::OutlinedBuildingLibrary)
                    ->schema([
                        TextInput::make('bank_account')
                            ->label(__('user.profile.bank_account_label'))
                            ->required()
                            ->maxLength(255)
                            ->trim()
                            ->autocomplete('off')
                            ->helperText(__('user.profile.bank_account_helper'))
                            ->rules([
                                Rule::unique('users', 'bank_account')
                                    ->ignore($userId)
                                    ->whereNull('deleted_at'),
                            ])
                            ->validationMessages([
                                'unique' => __('user.profile.validation.bank_account_taken'),
                            ]),
                    ])
                    ->columns(1)
                    ->columnSpanFull(),
            ]);
    }
}
