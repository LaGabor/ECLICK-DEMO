<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Promotion;
use App\Models\User;
use App\Policies\Concerns\GrantsAllAbilitiesToFilamentAdministrator;

class PromotionPolicy
{
    use GrantsAllAbilitiesToFilamentAdministrator;

    public function viewAny(User $user): bool
    {
        return false;
    }

    public function view(User $user, Promotion $promotion): bool
    {
        return false;
    }

    public function create(User $user): bool
    {
        return false;
    }

    public function update(User $user, Promotion $promotion): bool
    {
        return false;
    }

    public function delete(User $user, Promotion $promotion): bool
    {
        return false;
    }

    public function restore(User $user, Promotion $promotion): bool
    {
        return false;
    }

    public function forceDelete(User $user, Promotion $promotion): bool
    {
        return false;
    }
}
