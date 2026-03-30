<?php

declare(strict_types=1);

namespace App\Policies;

use App\Domain\Receipts\ReceiptSubmissionStatus;
use App\Models\Receipt;
use App\Models\User;
use App\Policies\Concerns\GrantsAllAbilitiesToFilamentAdministrator;
use App\Support\UserRole;

class ReceiptPolicy
{
    use GrantsAllAbilitiesToFilamentAdministrator;

    public function viewAny(User $user): bool
    {
        return $user->hasRole(UserRole::User);
    }

    public function view(User $user, Receipt $receipt): bool
    {
        return (string) $user->id === (string) $receipt->user_id;
    }

    public function create(User $user): bool
    {
        return $user->hasRole(UserRole::User);
    }

    public function update(User $user, Receipt $receipt): bool
    {
        if ((string) $user->id !== (string) $receipt->user_id) {
            return false;
        }

        return in_array($receipt->status, [
            ReceiptSubmissionStatus::AwaitingUserInformation,
            ReceiptSubmissionStatus::PaymentFailed,
        ], true);
    }

    public function delete(User $user, Receipt $receipt): bool
    {
        return false;
    }

    public function restore(User $user, Receipt $receipt): bool
    {
        return false;
    }

    public function forceDelete(User $user, Receipt $receipt): bool
    {
        return false;
    }

    public function uploadReceiptImage(User $user, Receipt $receipt): bool
    {
        if ((string) $user->id !== (string) $receipt->user_id) {
            return false;
        }

        return in_array($receipt->status, [
            ReceiptSubmissionStatus::AwaitingUserInformation,
            ReceiptSubmissionStatus::PaymentFailed,
        ], true);
    }

    public function appeal(User $user, Receipt $receipt): bool
    {
        if ((string) $user->id !== (string) $receipt->user_id) {
            return false;
        }

        return $receipt->status === ReceiptSubmissionStatus::Rejected
            && $receipt->appeal_submitted_at === null;
    }
}
