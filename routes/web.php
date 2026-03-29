<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

$registerPublicWebRoutes = function (): void {
    Route::get('/', function () {
        return view('welcome');
    });

    Route::get('/dashboard', function () {
        return view('dashboard');
    })->middleware(['auth', 'verified'])->name('dashboard');

    Route::middleware('auth')->group(function () {
        Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
        Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
        Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
    });

    require __DIR__.'/auth.php';
};

$mainDomain = config('app.main_domain');

if (filled($mainDomain)) {
    Route::domain($mainDomain)->group($registerPublicWebRoutes);
} else {
    $registerPublicWebRoutes();
}
