<?php

use App\Filament\User\Pages\UserDashboard;
use App\Http\Controllers\Media\ReceiptImageUploadController;
use App\Http\Controllers\Media\StreamProductImageController;
use App\Http\Controllers\Media\StreamReceiptImageController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ReceiptSubmissionController;
use App\Support\UserRole;
use Illuminate\Support\Facades\Route;

$registerPublicWebRoutes = function (): void {
    Route::get('/', function () {
        return view('welcome');
    });

    Route::get('/dashboard', function () {
        if (auth()->user()?->hasRole(UserRole::User)) {
            return redirect()->to(UserDashboard::getUrl(panel: 'account'));
        }

        return view('dashboard');
    })->middleware(['auth', 'verified'])->name('dashboard');

    Route::middleware(['auth', 'verified'])->group(function () {
        Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
        Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
        Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

        Route::get('/receipts/{receipt}', [ReceiptSubmissionController::class, 'show'])
            ->name('receipts.show');

        Route::get('/media/receipts/{receipt}/image', StreamReceiptImageController::class)
            ->middleware('throttle:120,1')
            ->name('media.receipts.image');

        Route::get('/media/products/{product}/image', StreamProductImageController::class)
            ->middleware('throttle:120,1')
            ->name('media.products.image');

        Route::post('/receipts/{receipt}/image', ReceiptImageUploadController::class)
            ->middleware('throttle:10,1')
            ->name('receipts.image.store');
    });

    require __DIR__.'/auth.php';
};

$mainDomain = config('app.main_domain');

if (filled($mainDomain)) {
    Route::domain($mainDomain)->group($registerPublicWebRoutes);
} else {
    $registerPublicWebRoutes();
}
