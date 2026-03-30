<?php

declare(strict_types=1);

namespace App\Filament\User\Resources\ParticipantReceipts\Pages;

use App\Domain\Media\StoredImageKind;
use App\Domain\Receipts\ReceiptSubmissionStatus;
use App\Filament\User\Resources\ParticipantReceipts\ParticipantReceiptResource;
use App\Filament\User\Resources\ParticipantReceipts\Schemas\ParticipantReceiptForm;
use App\Jobs\Media\ProcessStoredImageJob;
use App\Models\Promotion;
use App\Models\Receipt;
use App\Services\Receipts\UserReceiptParticipantService;
use Filament\Actions\Action;
use Filament\Resources\Pages\EditRecord;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\EmbeddedSchema;
use Filament\Schemas\Components\Form;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

final class EditParticipantReceipt extends EditRecord
{
    protected static string $resource = ParticipantReceiptResource::class;

    protected function resolveRecord(int|string $key): Model
    {
        $record = parent::resolveRecord($key);
        $record->loadMissing('promotion', 'receiptProducts');

        return $record;
    }

    public function form(Schema $schema): Schema
    {
        return ParticipantReceiptForm::configure($schema, $this->getRecord());
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $data = parent::mutateFormDataBeforeFill($data);

        $record = $this->getRecord();
        $record->loadMissing('receiptProducts');

        $data['lines'] = $record->receiptProducts->map(static fn ($rp): array => [
            'product_id' => $rp->product_id,
            'quantity' => $rp->quantity,
        ])->all();

        return $data;
    }

    protected function getRedirectUrl(): ?string
    {
        return self::getResource()::getUrl('index');
    }

    /**
     * Form uses {@see wire:submit} → this method so Enter does not bypass the save confirmation modal
     * (unlike {@see Action::submit}('save'), which calls Livewire save directly).
     */
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
        $record = $this->getRecord();

        return Action::make('save')
            ->label(__('filament-panels::resources/pages/edit-record.form.actions.save.label'))
            ->action(function (): void {
                $this->save();
            })
            ->keyBindings(['mod+s'])
            ->requiresConfirmation()
            ->modalHeading($record instanceof Receipt ? $this->participantReceiptSaveConfirmHeading($record) : __('user.receipts.confirm_save_generic_heading'))
            ->modalDescription($record instanceof Receipt ? $this->participantReceiptSaveConfirmDescription($record) : __('user.receipts.confirm_save_generic_description'))
            ->modalSubmitActionLabel(__('user.receipts.confirm_save_submit'))
            ->modalIcon(Heroicon::OutlinedExclamationTriangle)
            ->modalIconColor('warning');
    }

    private function participantReceiptSaveConfirmHeading(Receipt $record): string
    {
        return match ($record->status) {
            ReceiptSubmissionStatus::PaymentFailed => __('user.receipts.confirm_save_payment_failed_heading'),
            ReceiptSubmissionStatus::AwaitingUserInformation => __('user.receipts.confirm_save_awaiting_info_heading'),
            default => __('user.receipts.confirm_save_generic_heading'),
        };
    }

    private function participantReceiptSaveConfirmDescription(Receipt $record): string
    {
        return match ($record->status) {
            ReceiptSubmissionStatus::PaymentFailed => __('user.receipts.confirm_save_payment_failed_description'),
            ReceiptSubmissionStatus::AwaitingUserInformation => __('user.receipts.confirm_save_awaiting_info_description'),
            default => __('user.receipts.confirm_save_generic_description'),
        };
    }

    /**
     * @param  array<string, mixed>  $data
     */
    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        if (! $record instanceof Receipt) {
            return $record;
        }

        $previousImage = (string) $record->receipt_image;

        $newPath = $this->normalizeStoredPath($data['receipt_image'] ?? null);
        $pathToUse = ($newPath !== null && $newPath !== $previousImage) ? $newPath : null;

        if ($pathToUse !== null) {
            $disk = Storage::disk((string) config('image_upload.disk'));
            if (! $disk->exists($pathToUse)) {
                throw ValidationException::withMessages([
                    'receipt_image' => [__('The uploaded file could not be read. Please try again.')],
                ]);
            }
        }

        $promotion = Promotion::query()->findOrFail((int) $record->promotion_id);
        $lines = $this->normalizeLines($data['lines'] ?? []);

        app(UserReceiptParticipantService::class)->updateParticipantReceipt(
            $record,
            auth()->user(),
            $promotion,
            trim((string) $data['ap_code']),
            (string) $data['purchase_date'],
            $pathToUse,
            $lines,
        );

        $record->refresh();

        if ($pathToUse !== null) {
            ProcessStoredImageJob::dispatch(
                StoredImageKind::Receipt,
                (int) $record->getKey(),
                $pathToUse,
                $previousImage !== '' ? $previousImage : null,
            )->afterCommit();
        }

        return $record;
    }

    private function normalizeStoredPath(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

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
