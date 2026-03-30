<?php

declare(strict_types=1);

namespace App\Filament\User\Resources\ParticipantReceipts\Pages;

use App\Domain\Media\StoredImageKind;
use App\Filament\User\Resources\ParticipantReceipts\ParticipantReceiptResource;
use App\Jobs\Media\ProcessStoredImageJob;
use App\Models\Promotion;
use App\Services\Receipts\UserReceiptParticipantService;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

final class CreateParticipantReceipt extends CreateRecord
{
    protected static string $resource = ParticipantReceiptResource::class;

    protected function getRedirectUrl(): string
    {
        return self::getResource()::getUrl('index');
    }

    protected function handleRecordCreation(array $data): Model
    {
        $path = $this->normalizeStoredPath($data['receipt_image'] ?? null);

        if ($path === null) {
            throw ValidationException::withMessages([
                'receipt_image' => [__('validation.required', ['attribute' => __('Receipt image')])],
            ]);
        }

        $disk = Storage::disk((string) config('image_upload.disk'));

        if (! $disk->exists($path)) {
            throw ValidationException::withMessages([
                'receipt_image' => [__('The uploaded file could not be read. Please try again.')],
            ]);
        }

        $promotion = Promotion::query()->findOrFail((int) $data['promotion_id']);
        $lines = $this->normalizeLines($data['lines'] ?? []);

        $receipt = app(UserReceiptParticipantService::class)->createForParticipant(
            auth()->user(),
            $promotion,
            trim((string) $data['ap_code']),
            (string) $data['purchase_date'],
            $path,
            $lines,
        );

        ProcessStoredImageJob::dispatch(
            StoredImageKind::Receipt,
            (int) $receipt->getKey(),
            $path,
            null,
        )->afterCommit();

        return $receipt;
    }

    private function normalizeStoredPath(mixed $value): ?string
    {
        if (is_array($value)) {
            $value = $value[array_key_first($value)] ?? null;
        }

        return is_string($value) && $value !== '' ? $value : null;
    }

    private function normalizeLines(array $lines): array
    {
        $out = [];

        foreach ($lines as $line) {
            if (! is_array($line)) {
                throw ValidationException::withMessages([
                    'lines' => [__('user.receipts.invalid_line_data')],
                ]);
            }

            $out[] = [
                'product_id' => (int) ($line['product_id'] ?? 0),
                'quantity' => (int) ($line['quantity'] ?? 0),
            ];
        }

        return $out;
    }
}
