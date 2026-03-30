<?php

declare(strict_types=1);

namespace App\Policies\Concerns;

use App\Models\User;
use App\Support\UserRole;

trait GrantsAllAbilitiesToFilamentAdministrator
{
    public function before(?User $user, string $ability): ?bool
    {
        if ($user !== null && $user->hasRole(UserRole::Admin)) {
            return true;
        }

        return null;
    }
}
