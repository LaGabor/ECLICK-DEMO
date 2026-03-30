<?php

declare(strict_types=1);

namespace App\Http\Controllers\Media;

use App\Domain\Media\StoredImageKind;
use App\Http\Controllers\Controller;
use App\Http\Requests\Media\StoreReceiptImageRequest;
use App\Jobs\Media\ProcessStoredImageJob;
use App\Models\Receipt;
use App\Services\Media\ImageStorageService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;

final class ReceiptImageUploadController extends Controller
{
    public function __invoke(
        StoreReceiptImageRequest $request,
        Receipt $receipt,
        ImageStorageService $images,
    ): RedirectResponse {
        $this->authorize('uploadReceiptImage', $receipt);

        $uploaded = $request->file('receipt_image');
        $images->assertHttpUploadIsSafeRaster($uploaded);

        $previous = (string) $receipt->receipt_image;

        $stagingPath = $images->storeReceiptUploadToStaging($uploaded);

        DB::transaction(function () use ($receipt, $stagingPath): void {
            $receipt->update([
                'receipt_image' => $stagingPath,
            ]);
        });

        ProcessStoredImageJob::dispatch(
            StoredImageKind::Receipt,
            (int) $receipt->getKey(),
            $stagingPath,
            $previous !== '' ? $previous : null,
        )->afterCommit();

        return redirect()
            ->route('receipts.show', $receipt)
            ->with('status', __('receipts.upload.image_queued'));
    }
}
