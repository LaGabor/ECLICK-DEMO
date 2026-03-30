<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Product;
use App\Models\User;
use App\Policies\Concerns\GrantsAllAbilitiesToFilamentAdministrator;

class ProductPolicy
{
    use GrantsAllAbilitiesToFilamentAdministrator;

    public function viewAny(User $user): bool
    {
        return false;
    }

    public function view(User $user, Product $product): bool
    {
        return false;
    }

    public function create(User $user): bool
    {
        return false;
    }

    public function update(User $user, Product $product): bool
    {
        return false;
    }

    public function delete(User $user, Product $product): bool
    {
        return false;
    }

    public function restore(User $user, Product $product): bool
    {
        return false;
    }

    public function forceDelete(User $user, Product $product): bool
    {
        return false;
    }

    /**
     * Any authenticated participant or admin may load catalog product images (never a public filesystem URL).
     */
    public function viewCatalogImage(User $user, Product $product): bool
    {
        return true;
    }
}
