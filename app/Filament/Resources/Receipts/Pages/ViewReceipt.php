<?php

declare(strict_types=1);

namespace App\Filament\Resources\Receipts\Pages;

use App\Domain\Receipts\ReceiptSubmissionStatus;
use App\Filament\Resources\Receipts\Concerns\InteractsWithReceiptWorkflowActions;
use App\Filament\Resources\Receipts\ReceiptResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;
use Filament\Schemas\Components\Actions as SchemaActions;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Enums\Alignment;
use Illuminate\Database\Eloquent\Model;

class ViewReceipt extends ViewRecord
{
    use InteractsWithReceiptWorkflowActions;

    protected static string $resource = ReceiptResource::class;

    protected function resolveRecord(int|string $key): Model
    {
        $record = parent::resolveRecord($key);
        $record->loadMissing(['receiptProducts.product', 'promotion.products']);

        return $record;
    }

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make()
                ->color('warning')
                ->outlined(),
            ...$this->getReceiptWorkflowHeaderActions(),
        ];
    }

    public function content(Schema $schema): Schema
    {
        if ($this->hasCombinedRelationManagerTabsWithContent()) {
            return parent::content($schema);
        }

        return $schema
            ->components([
                $this->getInfolistContentComponent(),
                $this->getRelationManagersContentComponent(),
                Section::make(__('filament.receipts.workflow_footer.section_heading'))
                    ->description(__('filament.receipts.workflow_footer.section_description'))
                    ->visible(fn (): bool => $this->getRecord()->status === ReceiptSubmissionStatus::UnderReview)
                    ->schema([
                        SchemaActions::make($this->getReceiptWorkflowFooterActions())
                            ->alignment(Alignment::Center),
                    ]),
            ]);
    }
}
