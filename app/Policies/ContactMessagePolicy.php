<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\ContactMessage;
use App\Models\User;
use App\Policies\Concerns\GrantsAllAbilitiesToFilamentAdministrator;
use App\Support\UserRole;

class ContactMessagePolicy
{
    use GrantsAllAbilitiesToFilamentAdministrator;

    public function viewAny(User $user): bool
    {
        return $user->hasRole(UserRole::User);
    }

    public function view(User $user, ContactMessage $contactMessage): bool
    {
        return $this->participantOwnsMessage($user, $contactMessage);
    }

    public function create(User $user): bool
    {
        return $user->hasRole(UserRole::User);
    }

    public function update(User $user, ContactMessage $contactMessage): bool
    {
        return false;
    }

    public function delete(User $user, ContactMessage $contactMessage): bool
    {
        return false;
    }

    public function restore(User $user, ContactMessage $contactMessage): bool
    {
        return false;
    }

    public function forceDelete(User $user, ContactMessage $contactMessage): bool
    {
        return false;
    }

    private function participantOwnsMessage(User $user, ContactMessage $contactMessage): bool
    {
        if (! $user->hasRole(UserRole::User)) {
            return false;
        }

        return (string) $contactMessage->user_id === (string) $user->getKey();
    }
}
