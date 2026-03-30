<?php

declare(strict_types=1);

namespace App\Services\Refunds;

use App\Contracts\Refunds\ReceiptRefundTotalCalculatorInterface;
use App\Domain\Receipts\ReceiptSubmissionStatus;
use App\Domain\Refunds\RefundExportItemPaymentStatus;
use App\Domain\Refunds\RefundExportStatus;
use App\DTO\Refunds\RefundExportRequestData;
use App\Exports\RefundBankCsvChunkExport;
use App\Jobs\Refunds\SendRefundExportReadyNotificationJob;
use App\Models\Receipt;
use App\Models\RefundExport;
use App\Models\RefundExportItem;
use Carbon\CarbonImmutable;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Excel as ExcelWriter;
use Maatwebsite\Excel\Facades\Excel;
use stdClass;
use ZipArchive;

final class RefundExportProcessor
{
    private const LOCK_PREFIX = 'refund-export-process:';

    private const REFUND_EXPORT_ITEM_INSERT_CHUNK = 500;

    public function __construct(
        private readonly ReceiptRefundTotalCalculatorInterface $receiptRefundTotalCalculator,
    ) {}

    public function process(RefundExport $export): void
    {
        $export->refresh();

        if ($this->isAlreadyCompleted($export)) {
            return;
        }

        $lock = Cache::lock(self::LOCK_PREFIX.$export->getKey(), 600);

        $lock->block(30, function () use ($export): void {
            $export->refresh();

            if ($this->isAlreadyCompleted($export)) {
                return;
            }

            if ($export->items()->doesntExist()) {
                $this->attachEligibleReceiptsOrMarkFailed($export);
                $export->refresh();
            }

            if ($export->status === RefundExportStatus::Failed) {
                return;
            }

            $this->buildZipFromItems($export);
        });
    }

    private function refundExportDiskName(): string
    {
        return (string) config('refund_exports.disk');
    }

    private function isAlreadyCompleted(RefundExport $export): bool
    {
        if ($export->status !== RefundExportStatus::Done) {
            return false;
        }

        if ($export->zip_path === null || $export->zip_path === '') {
            return false;
        }

        $disk = Storage::disk($this->refundExportDiskName());

        return $disk->exists($export->zip_path);
    }

    private function attachEligibleReceiptsOrMarkFailed(RefundExport $export): void
    {
        $request = new RefundExportRequestData(
            CarbonImmutable::parse((string) $export->period_start),
            CarbonImmutable::parse((string) $export->period_end),
        );

        try {
            DB::transaction(function () use ($export, $request): void {
                $eligibleReceipts = $this->lockAndLoadEligibleApprovedReceipts($request);

                if ($eligibleReceipts->isEmpty()) {
                    $export->update([
                        'status' => RefundExportStatus::Failed,
                        'last_error' => __('filament.refund_exports.generator.no_eligible_receipts'),
                    ]);

                    return;
                }

                $now = now();
                $insertRows = [];
                $receiptIds = [];

                foreach ($eligibleReceipts as $receipt) {
                    if ($receipt->user === null || $receipt->user->bank_account === null || trim((string) $receipt->user->bank_account) === '') {
                        throw new \RuntimeException(
                            "Receipt {$receipt->getKey()} is missing a bank account on the participant profile.",
                        );
                    }

                    $refundAmount = $this->receiptRefundTotalCalculator->calculateTotalRefundAmountForReceipt($receipt);

                    $insertRows[] = [
                        'refund_export_id' => $export->getKey(),
                        'receipt_id' => $receipt->getKey(),
                        'refund_amount' => $refundAmount,
                        'payment_status' => RefundExportItemPaymentStatus::Pending->value,
                        'payment_error' => null,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];
                    $receiptIds[] = $receipt->getKey();
                }

                $processedCount = count($insertRows);

                foreach (array_chunk($insertRows, self::REFUND_EXPORT_ITEM_INSERT_CHUNK) as $chunk) {
                    RefundExportItem::query()->insert($chunk);
                }

                Receipt::query()->whereIn('id', $receiptIds)->update([
                    'status' => ReceiptSubmissionStatus::PaymentPending->value,
                    'updated_at' => $now,
                ]);

                $export->update([
                    'status' => RefundExportStatus::Processing,
                    'total_rows' => $processedCount,
                    'last_error' => null,
                ]);
            });
        } catch (\Throwable $e) {
            Log::error('Refund export attach phase failed.', [
                'refund_export_id' => $export->getKey(),
                'exception_class' => $e::class,
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            $export->update([
                'status' => RefundExportStatus::Failed,
                'last_error' => __('filament.refund_exports.generator.attach_failed'),
            ]);
        }
    }

    private function lockAndLoadEligibleApprovedReceipts(RefundExportRequestData $request): Collection
    {
        return RefundExportReceiptQuery::eligibleApprovedForRange($request)
            ->with(['user'])
            ->orderBy('id')
            ->lockForUpdate()
            ->get();
    }

    private function buildZipFromItems(RefundExport $export): void
    {
        $maximumRowsPerBankCsvFile = (int) config('refund_exports.max_csv_rows_per_bank_batch_file');
        $storageDiskName = $this->refundExportDiskName();
        $storageBaseDirectory = trim((string) config('refund_exports.directory'), '/');

        if ($maximumRowsPerBankCsvFile < 1) {
            throw new \RuntimeException('refund_exports.max_csv_rows_per_bank_batch_file must be at least 1.');
        }

        $dataRowsPerCsvFile = max(1, $maximumRowsPerBankCsvFile - 1);

        $disk = Storage::disk($storageDiskName);

        if (! $disk instanceof FilesystemAdapter) {
            throw new \RuntimeException('Refund export generation requires a local filesystem disk (FilesystemAdapter).');
        }

        $aggregatedRows = $this->aggregatedBankTransferRowsForExport($export);
        $transferRowCount = $aggregatedRows->count();

        if ($transferRowCount === 0) {
            throw new \RuntimeException('Refund export has no items to serialize.');
        }

        $rowChunks = $aggregatedRows->chunk($dataRowsPerCsvFile);
        $totalChunkCount = $rowChunks->count();

        $exportDirectoryRelativePath = $storageBaseDirectory.'/'.$export->getKey();

        if ($disk->exists($exportDirectoryRelativePath)) {
            $disk->deleteDirectory($exportDirectoryRelativePath);
        }

        $disk->makeDirectory($exportDirectoryRelativePath);

        $writtenCsvRelativePaths = [];
        $chunkSequenceNumber = 0;

        foreach ($rowChunks as $chunk) {
            $chunkSequenceNumber++;
            $writtenCsvRelativePaths[] = $this->storeRefundCsvChunk(
                $chunk->values(),
                $chunkSequenceNumber,
                $totalChunkCount,
                $exportDirectoryRelativePath,
                $storageDiskName,
            );
        }

        if ($chunkSequenceNumber !== $totalChunkCount) {
            throw new \RuntimeException(
                "Refund export CSV chunk count mismatch (expected {$totalChunkCount}, wrote {$chunkSequenceNumber}).",
            );
        }

        $zipRelativePath = $exportDirectoryRelativePath.'/refund_export.zip';
        $this->zipCsvFilesOnSameDisk($disk, $writtenCsvRelativePaths, $zipRelativePath);

        $this->ensureReadablePermissions(
            $disk,
            $storageBaseDirectory,
            $exportDirectoryRelativePath,
            $zipRelativePath,
        );

        foreach ($writtenCsvRelativePaths as $csvRelativePathToRemove) {
            $disk->delete($csvRelativePathToRemove);
        }

        $export->update([
            'zip_path' => $zipRelativePath,
            'status' => RefundExportStatus::Done,
            'exported_at' => now(),
            'total_rows' => $transferRowCount,
            'last_error' => null,
        ]);

        SendRefundExportReadyNotificationJob::dispatch($export->getKey())
            ->onQueue((string) config('refund_exports.queue'));
    }

    private function aggregatedBankTransferRowsForExport(RefundExport $export): Collection
    {
        $rows = DB::table('refund_export_items as rei')
            ->join('receipts as r', 'r.id', '=', 'rei.receipt_id')
            ->join('users as u', 'u.id', '=', 'r.user_id')
            ->where('rei.refund_export_id', $export->getKey())
            ->selectRaw('u.name as recipient_name')
            ->selectRaw('u.bank_account as bank_account_number')
            ->selectRaw('SUM(rei.refund_amount) as refund_amount_sum')
            ->groupBy('u.name', 'u.bank_account')
            ->orderByRaw('MIN(rei.id)')
            ->get();

        return $rows->map(function (stdClass $row): array {
            return [
                'recipient_name' => (string) $row->recipient_name,
                'bank_account_number' => (string) $row->bank_account_number,
                'refund_amount' => $this->formatRefundAmountSumForBankCsv($row->refund_amount_sum),
            ];
        });
    }

    private function formatRefundAmountSumForBankCsv(mixed $sum): string
    {
        return number_format((float) $sum, 2, '.', '');
    }

    private function storeRefundCsvChunk(
        Collection $rowChunk,
        int $chunkSequenceNumber,
        int $totalChunkCount,
        string $exportDirectoryRelativePath,
        string $storageDiskName,
    ): string {
        $csvFileName = sprintf(
            'refund_batch_%s_of_%s.csv',
            str_pad((string) $chunkSequenceNumber, 2, '0', STR_PAD_LEFT),
            str_pad((string) $totalChunkCount, 2, '0', STR_PAD_LEFT),
        );
        $csvRelativePath = $exportDirectoryRelativePath.'/'.$csvFileName;

        Excel::store(
            new RefundBankCsvChunkExport($rowChunk),
            $csvRelativePath,
            $storageDiskName,
            ExcelWriter::CSV,
        );

        return $csvRelativePath;
    }

    private function zipCsvFilesOnSameDisk(FilesystemAdapter $disk, array $csvRelativePathsOnDisk, string $zipRelativePath): void
    {
        $absoluteZipPath = $disk->path($zipRelativePath);

        $zipArchive = new ZipArchive;
        $openResult = $zipArchive->open($absoluteZipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE);

        if ($openResult !== true) {
            throw new \RuntimeException("Unable to open ZIP archive for writing: {$absoluteZipPath} (code {$openResult})");
        }

        try {
            foreach ($csvRelativePathsOnDisk as $csvRelativePath) {
                $absoluteCsvPath = $disk->path($csvRelativePath);
                $entryName = basename($csvRelativePath);

                if (! $zipArchive->addFile($absoluteCsvPath, $entryName)) {
                    throw new \RuntimeException("Unable to add CSV file to ZIP: {$absoluteCsvPath}");
                }
            }
        } finally {
            if (! $zipArchive->close()) {
                throw new \RuntimeException("Failed to finalize ZIP archive: {$absoluteZipPath}");
            }
        }
    }

    private function ensureReadablePermissions(
        FilesystemAdapter $disk,
        string $storageBaseDirectory,
        string $exportDirectoryRelativePath,
        string $zipRelativePath,
    ): void {
        foreach ([$storageBaseDirectory, $exportDirectoryRelativePath] as $dir) {
            $absolute = $disk->path($dir);
            if (is_dir($absolute) && ! chmod($absolute, 0755)) {
                Log::warning('chmod failed for refund export directory.', [
                    'path' => $absolute,
                ]);
            }
        }

        $zipAbsolute = $disk->path($zipRelativePath);
        if (is_file($zipAbsolute) && ! chmod($zipAbsolute, 0644)) {
            Log::warning('chmod failed for refund export ZIP file.', [
                'path' => $zipAbsolute,
            ]);
        }
    }
}
