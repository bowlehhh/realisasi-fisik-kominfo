<?php

use App\Http\Controllers\AuthenticatedSessionController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\MicrosoftAuthorizationController;
use App\Http\Middleware\EnsureAdministrator;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->controller(AuthenticatedSessionController::class)->group(function (): void {
    Route::get('/login', 'create')->name('login');
    Route::post('/login', 'store')->middleware('throttle:login')->name('login.store');
});

Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])
    ->middleware('auth')
    ->name('logout');

Route::middleware('auth')->controller(DashboardController::class)->group(function (): void {
    Route::get('/', 'index')->name('dashboard.index');
    Route::get('/dashboard-apbd', 'apbd')->name('dashboard.apbd');
});

Route::middleware(['auth', EnsureAdministrator::class])->controller(MicrosoftAuthorizationController::class)->group(function (): void {
    Route::get('/auth/microsoft/redirect', 'redirect')->name('microsoft.redirect');
    Route::get('/auth/microsoft/callback', 'callback')->name('microsoft.callback');
    Route::post('/auth/microsoft/disconnect', 'disconnect')->name('microsoft.disconnect');
});
