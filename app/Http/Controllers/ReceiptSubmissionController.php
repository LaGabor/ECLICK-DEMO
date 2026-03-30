<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Receipt;
use Illuminate\Http\Request;
use Illuminate\View\View;

final class ReceiptSubmissionController extends Controller
{
    public function show(Request $request, Receipt $receipt): View
    {
        $this->authorize('view', $receipt);

        $receipt->load(['promotion', 'receiptProducts.product']);

        return view('receipts.show', [
            'receipt' => $receipt,
        ]);
    }
}
