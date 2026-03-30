<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\RefundExport;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

final class DownloadRefundExportZipController extends Controller
{
    public function __invoke(Request $request, RefundExport $refundExport): BinaryFileResponse
    {
        Gate::authorize('view', $refundExport);

        $storageDiskName = (string) config('refund_exports.disk');
        $relativeZipPath = $refundExport->zip_path;

        if ($relativeZipPath === null || $relativeZipPath === '') {
            abort(404, 'Export archive is not available yet.');
        }

        $disk = Storage::disk($storageDiskName);

        if (! $disk->exists($relativeZipPath)) {
            Log::warning('Refund export ZIP missing on disk.', [
                'refund_export_id' => $refundExport->getKey(),
                'disk' => $storageDiskName,
                'zip_path' => $relativeZipPath,
                'resolved' => $disk->path($relativeZipPath),
            ]);
            abort(404, 'Export archive file is missing from storage.');
        }

        $absolutePath = $disk->path($relativeZipPath);

        if (! is_file($absolutePath) || ! is_readable($absolutePath)) {
            abort(404, 'Export archive file is missing from storage.');
        }

        return response()->download(
            $absolutePath,
            'refund_export_'.$refundExport->getKey().'.zip',
            ['Content-Type' => 'application/zip'],
        )->deleteFileAfterSend(false);
    }
}
