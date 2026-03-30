<?php

declare(strict_types=1);

namespace App\Filament\User\Pages;

use App\Filament\User\Schemas\UserProfileFormSchema;
use App\Models\User;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Pages\Concerns\CanUseDatabaseTransactions;
use Filament\Pages\Page;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\EmbeddedSchema;
use Filament\Schemas\Components\Form;
use Filament\Schemas\Schema;
use Filament\Support\Enums\Alignment;
use Filament\Support\Enums\Width;
use Filament\Support\Icons\Heroicon;
use Illuminate\Contracts\Support\Htmlable;
use Throwable;

/**
 * @property-read Schema $form
 */
final class UserProfilePage extends Page
{
    use CanUseDatabaseTransactions;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUserCircle;

    protected static ?string $navigationLabel = null;

    protected static ?int $navigationSort = -50;

    protected static ?string $slug = 'profile';

    protected string $view = 'filament.user.pages.user-profile';

    protected Width|string|null $maxContentWidth = Width::FourExtraLarge;

    /**
     * @var array<string, mixed>|null
     */
    public ?array $data = [];

    public static function getNavigationLabel(): string
    {
        return __('user.navigation.profile');
    }

    public function getTitle(): string|Htmlable
    {
        return __('user.profile.title');
    }

    public function mount(): void
    {
        $this->fillForm();
    }

    protected function fillForm(): void
    {
        /** @var User $user */
        $user = auth()->user();

        $this->form->fill([
            'name' => $user->name,
            'phone' => $user->phone ?? '',
            'bank_account' => $user->bank_account ?? '',
        ]);
    }

    public function defaultForm(Schema $schema): Schema
    {
        return $schema
            ->operation('edit')
            ->model(auth()->user())
            ->statePath('data');
    }

    public function form(Schema $schema): Schema
    {
        return UserProfileFormSchema::configure($schema);
    }

    public function save(): void
    {
        try {
            $this->beginDatabaseTransaction();

            $data = $this->form->getState();

            /** @var User $user */
            $user = auth()->user();

            $user->update([
                'name' => trim((string) $data['name']),
                'phone' => trim((string) $data['phone']),
                'bank_account' => trim((string) $data['bank_account']),
            ]);

            $this->commitDatabaseTransaction();
        } catch (Throwable $exception) {
            $this->rollBackDatabaseTransaction();

            throw $exception;
        }

        Notification::make()
            ->title(__('user.profile.saved'))
            ->success()
            ->send();

        $this->fillForm();
    }

    public function content(Schema $schema): Schema
    {
        return $schema
            ->components([
                $this->getFormContentComponent(),
            ]);
    }

    public function getFormContentComponent(): Component
    {
        return Form::make([EmbeddedSchema::make('form')])
            ->id('user-profile-form')
            ->livewireSubmitHandler('save')
            ->footer([
                Actions::make([
                    Action::make('save')
                        ->label(__('user.profile.save'))
                        ->submit('save')
                        ->keyBindings(['mod+s']),
                ])
                    ->alignment(Alignment::Start)
                    ->key('form-actions'),
            ]);
    }
}
