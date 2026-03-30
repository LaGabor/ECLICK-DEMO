<?php

declare(strict_types=1);

namespace App\Filament\Resources\ContactMessages\Pages;

use App\Filament\Resources\ContactMessages\ContactMessageResource;
use App\Models\ContactMessage;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\EmbeddedSchema;
use Filament\Schemas\Components\Form;
use Filament\Support\Facades\FilamentView;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Js;

class EditContactMessage extends EditRecord
{
    protected static string $resource = ContactMessageResource::class;

    public function mount(int|string $record): void
    {
        $this->record = $this->resolveRecord($record);

        /** @var ContactMessage $contact */
        $contact = $this->getRecord();

        if ($contact->isAnswered()) {
            $this->redirect(static::getResource()::getUrl('view', ['record' => $contact]));
        }

        $this->authorizeAccess();

        $this->fillForm();

        $this->previousUrl = url()->previous();
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $data['participant_phone'] = $this->getRecord()->user?->phone;

        return $data;
    }

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make()
                ->color('danger')
                ->outlined(),
        ];
    }

    public function requestSaveWithConfirmation(): void
    {
        $this->mountAction('save');
    }

    public function getFormContentComponent(): Component
    {
        return Form::make([EmbeddedSchema::make('form')])
            ->id('form')
            ->livewireSubmitHandler('requestSaveWithConfirmation')
            ->footer([
                $this->getFormActionsContentComponent(),
            ]);
    }

    protected function getSaveFormAction(): Action
    {
        return Action::make('save')
            ->label(__('filament.contact_messages.actions.answer_question'))
            ->color('success')
            ->requiresConfirmation()
            ->modalHeading(__('filament.contact_messages.confirm_send.heading'))
            ->modalDescription(__('filament.contact_messages.confirm_send.description'))
            ->modalSubmitActionLabel(__('filament.contact_messages.confirm_send.submit'))
            ->modalIcon(Heroicon::OutlinedPaperAirplane)
            ->modalIconColor('success')
            ->action(function (): void {
                $this->save();
            })
            ->keyBindings(['mod+s']);
    }

    protected function getCancelFormAction(): Action
    {
        $url = static::getResource()::getUrl('index');

        return Action::make('cancel')
            ->label(__('filament.contact_messages.actions.back'))
            ->alpineClickHandler(
                FilamentView::hasSpaMode($url)
                    ? 'document.referrer ? window.history.back() : Livewire.navigate('.Js::from($url).')'
                    : 'document.referrer ? window.history.back() : (window.location.href = '.Js::from($url).')',
            )
            ->color('gray');
    }

    protected function getRedirectUrl(): ?string
    {
        return static::getResource()::getUrl('index');
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        if (filled(trim((string) ($data['admin_reply'] ?? '')))) {
            $data['replied_at'] = now();
            $data['replied_by'] = Auth::id();
        }

        return $data;
    }
}
