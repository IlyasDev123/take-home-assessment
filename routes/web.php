<?php

declare(strict_types=1);

use App\Http\Controllers\Admin\ReportController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\RegisteredUserController;
use App\Http\Controllers\CreditsController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\GenerationController;
use App\Http\Controllers\WebhookController;
use App\Http\Middleware\EnsureUserIsAdmin;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route('login');
});

// Auth routes (Breeze-compatible naming)
Route::middleware('guest')->group(function (): void {
    Route::get('/register', [RegisteredUserController::class, 'create'])->name('register');
    Route::post('/register', [RegisteredUserController::class, 'store']);
    Route::get('/login', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('/login', [AuthenticatedSessionController::class, 'store']);
});

Route::middleware('auth')->group(function (): void {
    Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])->name('logout');
});

Route::middleware(['auth'])->group(function (): void {
    Route::get('/dashboard', DashboardController::class)->name('dashboard');

    Route::prefix('generations')->name('generations.')->group(function (): void {
        Route::get('/', [GenerationController::class, 'index'])->name('index');
        Route::get('/create', [GenerationController::class, 'create'])->name('create');
        // estimate-cost MUST be before /{generation} to avoid route collision
        Route::get('/estimate-cost', [GenerationController::class, 'estimateCost'])->name('estimate-cost');
        Route::post('/', [GenerationController::class, 'store'])->name('store');
        Route::get('/{generation}', [GenerationController::class, 'show'])->name('show');
    });

    // Credits
    Route::prefix('credits')->name('credits.')->group(function (): void {
        Route::get('/', [CreditsController::class, 'index'])->name('index');
        Route::get('/buy', [CreditsController::class, 'buy'])->name('buy');
        Route::post('/purchase', [CreditsController::class, 'purchase'])->name('purchase');
    });

    // Admin (is_admin gate)
    Route::middleware(EnsureUserIsAdmin::class)
        ->prefix('admin')
        ->name('admin.')
        ->group(function (): void {
            Route::get('/report', ReportController::class)->name('report');
        });
});

// Webhooks bypass CSRF — HMAC signature verification replaces it
Route::post('/webhooks/pixelforge', [WebhookController::class, 'pixelforge'])
    ->name('webhooks.pixelforge')
    ->withoutMiddleware([
        \Illuminate\Foundation\Http\Middleware\PreventRequestForgery::class,
    ]);
