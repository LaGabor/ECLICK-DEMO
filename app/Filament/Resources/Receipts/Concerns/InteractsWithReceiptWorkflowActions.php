<?php

declare(strict_types=1);

namespace App\Filament\Resources\Receipts\Concerns;

use App\Contracts\Receipts\ReceiptWorkflowServiceInterface;
use App\Domain\Receipts\ReceiptSubmissionStatus;
use App\Models\Receipt;
use App\Services\Receipts\ReceiptApprovalEligibilityService;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Log;

trait InteractsWithReceiptWorkflowActions
{
    protected function getReceiptWorkflowHeaderActions(): array
    {
        return [
            Action::make('header_moveSubmissionUnderReview')
                ->label(__('filament.receipts.actions.under_review'))
                ->visible(fn (): bool => in_array($this->getRecord()->status, [
                    ReceiptSubmissionStatus::Pending,
                    ReceiptSubmissionStatus::Appealed,
                ], true))
                ->action(function (): void {
                    $this->runReceiptWorkflow(
                        function (ReceiptWorkflowServiceInterface $workflow, Receipt $receipt): void {
                            $workflow->moveToUnderReview($receipt);
                        },
                        static::getResource()::getUrl('view', ['record' => $this->getRecord()]),
                    );
                }),
            Action::make('header_confirmTransferCompleted')
                ->label(__('filament.receipts.actions.mark_paid'))
                ->color('success')
                ->outlined()
                ->visible(fn (): bool => $this->getRecord()->status === ReceiptSubmissionStatus::PaymentPending)
                ->requiresConfirmation()
                ->modalHeading(__('filament.receipts.actions.confirm_mark_paid_heading'))
                ->modalDescription(__('filament.receipts.actions.confirm_mark_paid_description'))
                ->action(function (): void {
                    $this->runReceiptWorkflow(
                        function (ReceiptWorkflowServiceInterface $workflow, Receipt $receipt): void {
                            $workflow->markPaidManually($receipt);
                        },
                    );
                }),
            Action::make('header_recordPaymentFailed')
                ->label(__('filament.receipts.actions.payment_failed'))
                ->color('danger')
                ->outlined()
                ->visible(fn (): bool => $this->getRecord()->status === ReceiptSubmissionStatus::PaymentPending)
                ->schema([
                    Textarea::make('bankOrProcessorMessage')
                        ->label(__('filament.receipts.workflow_header.payment_failed_message_label'))
                        ->required()
                        ->rows(4),
                ])
                ->action(function (array $data): void {
                    $this->runReceiptWorkflow(
                        function (ReceiptWorkflowServiceInterface $workflow, Receipt $receipt) use ($data): void {
                            $workflow->markPaymentFailed($receipt, (string) $data['bankOrProcessorMessage']);
                        },
                    );
                }),
        ];
    }

    protected function getReceiptWorkflowFooterActions(): array
    {
        $eligibility = app(ReceiptApprovalEligibilityService::class);

        return [
            Action::make('footer_approveReceiptSubmission')
                ->label(__('filament.receipts.actions.approve'))
                ->color('success')
                ->visible(function () use ($eligibility): bool {
                    if ($this->getRecord()->status !== ReceiptSubmissionStatus::UnderReview) {
                        return false;
                    }

                    return $eligibility->canApprove($this->getRecord());
                })
                ->action(function (): void {
                    $this->runReceiptWorkflow(
                        function (ReceiptWorkflowServiceInterface $workflow, Receipt $receipt): void {
                            $workflow->approve($receipt);
                        },
                    );
                }),
            Action::make('footer_rejectReceiptSubmission')
                ->label(__('filament.receipts.actions.reject'))
                ->color('danger')
                ->visible(fn (): bool => $this->getRecord()->status === ReceiptSubmissionStatus::UnderReview)
                ->schema([
                    Textarea::make('adminVisibleReason')
                        ->label(__('filament.receipts.workflow_footer.rejection_reason_label'))
                        ->required()
                        ->rows(4),
                ])
                ->action(function (array $data): void {
                    $this->runReceiptWorkflow(
                        function (ReceiptWorkflowServiceInterface $workflow, Receipt $receipt) use ($data): void {
                            $workflow->reject($receipt, (string) $data['adminVisibleReason']);
                        },
                    );
                }),
            Action::make('footer_markAwaitingUserInformation')
                ->label(__('filament.receipts.actions.awaiting_user'))
                ->color('warning')
                ->visible(fn (): bool => $this->getRecord()->status === ReceiptSubmissionStatus::UnderReview)
                ->schema([
                    Textarea::make('instructionForUser')
                        ->label(__('filament.receipts.workflow_footer.awaiting_user_message_label'))
                        ->required()
                        ->rows(4),
                ])
                ->action(function (array $data): void {
                    $this->runReceiptWorkflow(
                        function (ReceiptWorkflowServiceInterface $workflow, Receipt $receipt) use ($data): void {
                            $workflow->markAwaitingUserInformation($receipt, (string) $data['instructionForUser']);
                        },
                    );
                }),
        ];
    }

    /**
     * @param  callable(ReceiptWorkflowServiceInterface, Receipt): void  $callback
     */
    protected function runReceiptWorkflow(callable $callback, ?string $redirectAfterSuccessUrl = null): void
    {
        try {
            $record = $this->getRecord();
            $callback(app(ReceiptWorkflowServiceInterface::class), $record);

            Notification::make()
                ->title(__('Saved'))
                ->success()
                ->send();

            $this->redirect($redirectAfterSuccessUrl ?? static::getResource()::getUrl('index'));
        } catch (\Throwable $exception) {
            Log::error('Receipt workflow Filament action failed.', [
                'receipt_id' => $this->getRecord()->getKey(),
                'exception_class' => $exception::class,
                'message' => $exception->getMessage(),
                'trace' => $exception->getTraceAsString(),
            ]);

            $body = config('app.debug')
                ? $exception->getMessage()
                : __('filament.receipts.workflow_error.body');

            Notification::make()
                ->title(__('filament.receipts.workflow_error.title'))
                ->body($body)
                ->danger()
                ->send();
        }
    }
}
