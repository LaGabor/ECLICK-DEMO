<?php

declare(strict_types=1);

namespace App\Support\Filament;

use App\Filament\User\Resources\ParticipantReceipts\ParticipantReceiptResource;
use App\Models\Receipt;

/**
 * Absolute URLs for participant receipt pages in the Filament "account" panel (emails, notifications).
 */
final class ParticipantReceiptAccountUrl
{
    public static function view(Receipt $receipt): string
    {
        return ParticipantReceiptResource::getUrl(
            name: 'view',
            parameters: ['record' => $receipt],
            panel: 'account',
        );
    }

    public static function edit(Receipt $receipt): string
    {
        return ParticipantReceiptResource::getUrl(
            name: 'edit',
            parameters: ['record' => $receipt],
            panel: 'account',
        );
    }
}
