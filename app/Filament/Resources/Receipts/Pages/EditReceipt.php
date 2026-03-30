<?php

declare(strict_types=1);

namespace App\Filament\Resources\Receipts\Pages;

use App\Filament\Resources\Receipts\ReceiptResource;
use App\Filament\Resources\Receipts\Schemas\ReceiptForm;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;

class EditReceipt extends EditRecord
{
    protected static string $resource = ReceiptResource::class;

    public function form(Schema $schema): Schema
    {
        return ReceiptForm::configureForProcessing($schema, $this->getRecord());
    }

    protected function resolveRecord(int|string $key): Model
    {
        $record = parent::resolveRecord($key);
        $record->loadMissing(['receiptProducts.product', 'promotion.products']);

        return $record;
    }

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make()
                ->color('gray')
                ->outlined(),
            DeleteAction::make()
                ->color('danger')
                ->outlined(),
        ];
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $data = parent::mutateFormDataBeforeFill($data);

        $record = $this->getRecord();
        $record->loadMissing(['user', 'promotion']);

        $data['context_participant'] = $record->user?->name;
        $data['context_email'] = $record->user?->email;
        $data['context_phone'] = $record->user?->phone;
        $data['context_bank'] = $record->user?->bank_account;
        $data['context_campaign'] = $record->promotion?->name;
        $data['context_ap_code'] = $record->ap_code;
        $data['context_purchase_date'] = $record->purchase_date?->toDateString() ?? '';

        return $data;
    }

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        foreach (array_keys($data) as $key) {
            if (str_starts_with((string) $key, 'context_')) {
                unset($data[$key]);
            }
        }

        $record->update(Arr::only($data, ['admin_note', 'status']));

        return $record->refresh();
    }

    protected function getRedirectUrl(): ?string
    {
        return static::getResource()::getUrl('index');
    }
}
