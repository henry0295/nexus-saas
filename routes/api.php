<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\SmsController;
use App\Http\Controllers\Api\EmailController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// Public routes
Route::post('/auth/register', [AuthController::class, 'register']);
Route::post('/auth/login', [AuthController::class, 'login']);

// Protected routes (requieren autenticación)
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/auth/me', [AuthController::class, 'me']);
    Route::post('/auth/logout', [AuthController::class, 'logout']);

    // SMS routes
    Route::post('/sms/send', [SmsController::class, 'send']);
    Route::post('/sms/bulk', [SmsController::class, 'bulk']);
    Route::get('/sms/logs', [SmsController::class, 'logs']);

    // Email routes
    Route::post('/email/send', [EmailController::class, 'send']);
    Route::post('/email/bulk', [EmailController::class, 'bulk']);
    Route::get('/email/logs', [EmailController::class, 'logs']);

    // Credits
    Route::get('/credits/balance', function (Request $request) {
        return response()->json([
            'balance' => $request->user()->tenant->credits->balance,
            'tenant_id' => $request->user()->tenant_id,
        ]);
    });
});
