<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Routing\Exceptions\InvalidSignatureException;
use Symfony\Component\HttpFoundation\Response;

/**
 * Accepts signed URLs generated with either relative or absolute signing so older
 * emails still work after switching middleware, and Docker port/host mismatches are tolerated.
 */
final class ValidateRefundExportSignedDownload
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->hasValidRelativeSignatureWhileIgnoring([]) || $request->hasValidSignatureWhileIgnoring([], true)) {
            return $next($request);
        }

        throw new InvalidSignatureException;
    }
}
