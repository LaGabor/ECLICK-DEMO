<?php

declare(strict_types=1);

namespace App\Filament\User\Resources\UserContactMessages\Pages;

use App\Filament\User\Resources\UserContactMessages\UserContactMessageResource;
use App\Models\ContactMessage;
use Filament\Actions\Action;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

final class CreateUserContactMessage extends CreateRecord
{
    protected static string $resource = UserContactMessageResource::class;

    protected function getCreateFormAction(): Action
    {
        return parent::getCreateFormAction()
            ->requiresConfirmation()
            ->modalHeading(__('user.contact.confirm_modal_heading'))
            ->modalDescription(__('user.contact.confirm_modal_description'));
    }

    protected function getRedirectUrl(): string
    {
        return self::getResource()::getUrl('index');
    }

    /**
     * @param  array<string, mixed>  $data
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $user = auth()->user();

        $data['user_id'] = $user->getKey();
        $data['name'] = $user->name;
        $data['email'] = $user->email;

        return $data;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    protected function handleRecordCreation(array $data): Model
    {
        return ContactMessage::query()->create($data);
    }
}
