<?php

declare(strict_types=1);

namespace App\Filament\Resources\RefundExports\Pages;

use App\Contracts\Refunds\RefundExportGeneratorInterface;
use App\Domain\Refunds\RefundExportStatus;
use App\DTO\Refunds\RefundExportRequestData;
use App\Filament\Resources\RefundExports\RefundExportResource;
use App\Models\RefundExport;
use App\Models\User;
use App\Services\Refunds\RefundExportReceiptQuery;
use Carbon\CarbonImmutable;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Log;

class ListRefundExports extends ListRecords
{
    protected static string $resource = RefundExportResource::class;

    private const int POLL_EVERY_SECONDS = 5;

    private const int POLL_MAX_DURATION_SECONDS = 300;

    public ?int $refundExportsListPollingStartedAt = null;

    public function table(Table $table): Table
    {
        return $table->poll(fn (): ?string => $this->resolveRefundExportsListPollingInterval());
    }

    protected function resolveRefundExportsListPollingInterval(): ?string
    {
        $hasPendingOrProcessing = RefundExport::query()
            ->whereIn('status', [
                RefundExportStatus::Pending,
                RefundExportStatus::Processing,
            ])
            ->exists();

        if (! $hasPendingOrProcessing) {
            $this->refundExportsListPollingStartedAt = null;

            return null;
        }

        if ($this->refundExportsListPollingStartedAt === null) {
            $this->refundExportsListPollingStartedAt = now()->unix();
        }

        if ((now()->unix() - $this->refundExportsListPollingStartedAt) >= self::POLL_MAX_DURATION_SECONDS) {
            return null;
        }

        return self::POLL_EVERY_SECONDS.'s';
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('generateBankRefundBatchZipArchive')
                ->label(__('filament.refund_exports.generate'))
                ->schema([
                    DatePicker::make('purchasePeriodStartsOn')
                        ->label(__('filament.refund_exports.form.period_start'))
                        ->required()
                        ->native(false),
                    DatePicker::make('purchasePeriodEndsOn')
                        ->label(__('filament.refund_exports.form.period_end'))
                        ->required()
                        ->native(false)
                        ->afterOrEqual('purchasePeriodStartsOn'),
                ])
                ->action(function (array $data): void {
                    /** @var User|null $actingAdministrator */
                    $actingAdministrator = auth()->user();

                    if ($actingAdministrator === null) {
                        Notification::make()
                            ->title(__('Authentication required'))
                            ->danger()
                            ->send();

                        return;
                    }

                    try {
                        $exportRequest = new RefundExportRequestData(
                            CarbonImmutable::parse($data['purchasePeriodStartsOn']),
                            CarbonImmutable::parse($data['purchasePeriodEndsOn']),
                        );

                        if (! RefundExportReceiptQuery::hasEligibleApprovedInRange($exportRequest)) {
                            Notification::make()
                                ->title(__('filament.refund_exports.no_eligible_before_queue.title'))
                                ->body(__('filament.refund_exports.generator.no_eligible_receipts'))
                                ->warning()
                                ->persistent()
                                ->send();

                            return;
                        }

                        app(RefundExportGeneratorInterface::class)
                            ->queueRefundExport($exportRequest, $actingAdministrator);

                        $this->refundExportsListPollingStartedAt = null;

                        Notification::make()
                            ->title(__('filament.refund_exports.queued.title'))
                            ->body(__('filament.refund_exports.queued.body'))
                            ->success()
                            ->persistent()
                            ->send();

                        $this->resetTable();
                    } catch (\Throwable $exception) {
                        Log::error('Refund export queue request failed.', [
                            'admin_id' => $actingAdministrator->getKey(),
                            'exception_class' => $exception::class,
                            'message' => $exception->getMessage(),
                            'trace' => $exception->getTraceAsString(),
                        ]);

                        $body = config('app.debug')
                            ? $exception->getMessage()
                            : __('filament.refund_exports.generate_failed.body');

                        Notification::make()
                            ->title(__('filament.refund_exports.generate_failed.title'))
                            ->body($body)
                            ->danger()
                            ->send();
                    }
                }),
        ];
    }
}
