<?php

use App\Http\Controllers\DashboardExcelValuesController;
use Illuminate\Support\Facades\Route;

Route::get('/dashboard/excel-values', DashboardExcelValuesController::class)
    ->middleware(['web', 'auth', 'throttle:60,1'])
    ->name('dashboard.excel-values');
