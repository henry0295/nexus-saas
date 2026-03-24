<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\SmsController;
use App\Http\Controllers\Api\EmailController;
use App\Http\Controllers\Api\AudioController;
use App\Http\Controllers\Api\CreditsController;
use App\Http\Controllers\Api\TenantController;
use App\Http\Controllers\Api\AdminController;
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

    // Audio routes (360nrs - IVR calls)
    Route::post('/audio/call', [AudioController::class, 'call']);
    Route::post('/audio/bulk', [AudioController::class, 'bulk']);
    Route::get('/audio/logs', [AudioController::class, 'logs']);
    Route::get('/audio/logs/{audioLog}', [AudioController::class, 'show']);

    // Credits routes
    Route::get('/credits/balance', function (Request $request) {
        return response()->json([
            'balance' => $request->user()->tenant->credits->balance,
            'tenant_id' => $request->user()->tenant_id,
        ]);
    });
    Route::post('/credits/purchase', [CreditsController::class, 'purchase']);
    Route::get('/credits/packages', [CreditsController::class, 'packages']);
    Route::get('/credits/transactions', [CreditsController::class, 'transactions']);

    // Tenant routes
    Route::get('/tenant', [TenantController::class, 'show']);
    Route::put('/tenant', [TenantController::class, 'update']);
    Route::get('/tenant/users', [TenantController::class, 'users']);
    Route::post('/tenant/users', [TenantController::class, 'addUser']);
    Route::put('/tenant/users/{user}', [TenantController::class, 'updateUser']);
    Route::delete('/tenant/users/{user}', [TenantController::class, 'removeUser']);

    // Admin routes (solo superadmin)
    Route::middleware('admin')->group(function () {
        Route::get('/admin/tenants', [AdminController::class, 'tenants']);
        Route::get('/admin/tenants/{tenant}', [AdminController::class, 'tenantDetail']);
        Route::post('/admin/tenants/{tenant}/suspend', [AdminController::class, 'suspendTenant']);
        Route::post('/admin/tenants/{tenant}/activate', [AdminController::class, 'activateTenant']);
        Route::post('/admin/pricing-rules', [AdminController::class, 'createPricingRule']);
        Route::put('/admin/pricing-rules/{pricingRule}', [AdminController::class, 'updatePricingRule']);
        Route::get('/admin/pricing-rules', [AdminController::class, 'listPricingRules']);
        Route::post('/admin/tenants/{tenant}/pricing-override', [AdminController::class, 'setPricingOverride']);
        Route::delete('/admin/tenants/{tenant}/pricing-override', [AdminController::class, 'deletePricingOverride']);
        Route::get('/admin/audit-logs', [AdminController::class, 'auditLogs']);
        Route::get('/admin/analytics', [AdminController::class, 'analytics']);
    });
});
