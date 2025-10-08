<?php

use App\Http\Controllers\ReportController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::middleware(['auth:sanctum', 'throttle:60,1'])->prefix('v1')->group(function () {
    Route::get('/reports/tasks', [ReportController::class, 'taskReport']);
});
