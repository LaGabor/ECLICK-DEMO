<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\RefundExport;
use App\Models\User;
use App\Policies\Concerns\GrantsAllAbilitiesToFilamentAdministrator;

class RefundExportPolicy
{
    use GrantsAllAbilitiesToFilamentAdministrator;

    public function viewAny(User $user): bool
    {
        return false;
    }

    public function view(User $user, RefundExport $refundExport): bool
    {
        return false;
    }

    public function create(User $user): bool
    {
        return false;
    }

    public function update(User $user, RefundExport $refundExport): bool
    {
        return false;
    }

    public function delete(User $user, RefundExport $refundExport): bool
    {
        return false;
    }

    public function restore(User $user, RefundExport $refundExport): bool
    {
        return false;
    }

    public function forceDelete(User $user, RefundExport $refundExport): bool
    {
        return false;
    }
}
