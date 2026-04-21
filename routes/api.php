<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\SmsController;
use App\Http\Controllers\Api\EmailController;
use App\Http\Controllers\Api\AudioController;
use App\Http\Controllers\Api\CreditsController;
use App\Http\Controllers\Api\TenantController;
use App\Http\Controllers\Api\AdminController;
use App\Http\Controllers\Api\InvoiceController;
use App\Http\Controllers\Api\EmailTemplateController;
use App\Http\Controllers\Api\SmsTemplateController;
use App\Http\Controllers\Api\EmailSenderController;
use App\Http\Controllers\Api\EmailDomainController;
use App\Http\Controllers\Api\DashboardStatsController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// Public routes
Route::post('/auth/register', [AuthController::class, 'register']);
Route::post('/auth/login', [AuthController::class, 'login']);
Route::get('/auth/verify-sender/{token}', [EmailSenderController::class, 'verifySender']);

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

    // Email Templates routes
    Route::get('/email-templates', [EmailTemplateController::class, 'index']);
    Route::post('/email-templates', [EmailTemplateController::class, 'store']);
    Route::put('/email-templates/{emailTemplate}', [EmailTemplateController::class, 'update']);
    Route::delete('/email-templates/{emailTemplate}', [EmailTemplateController::class, 'destroy']);

    // SMS Templates routes
    Route::get('/sms-templates', [SmsTemplateController::class, 'index']);
    Route::post('/sms-templates', [SmsTemplateController::class, 'store']);
    Route::put('/sms-templates/{smsTemplate}', [SmsTemplateController::class, 'update']);
    Route::delete('/sms-templates/{smsTemplate}', [SmsTemplateController::class, 'destroy']);

    // Email Senders routes
    Route::get('/email-senders', [EmailSenderController::class, 'index']);
    Route::post('/email-senders', [EmailSenderController::class, 'store']);
    Route::post('/email-senders/{emailSender}/resend-verification', [EmailSenderController::class, 'resendVerification']);
    Route::delete('/email-senders/{emailSender}', [EmailSenderController::class, 'destroy']);

    // Email Domains routes
    Route::get('/email-domains', [EmailDomainController::class, 'index']);
    Route::post('/email-domains', [EmailDomainController::class, 'store']);
    Route::post('/email-domains/{emailDomain}/verify', [EmailDomainController::class, 'verifyDomain']);
    Route::delete('/email-domains/{emailDomain}', [EmailDomainController::class, 'destroy']);

    // Dashboard Stats routes
    Route::get('/dashboard/stats', [DashboardStatsController::class, 'index']);

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

    // Invoice routes
    Route::get('/invoices', [InvoiceController::class, 'index']);
    Route::get('/invoices/{invoice}', [InvoiceController::class, 'show']);
    Route::post('/invoices', [InvoiceController::class, 'store']);
    Route::post('/invoices/{invoice}/email', [InvoiceController::class, 'sendEmail']);
    Route::get('/invoices/{invoice}/pdf', [InvoiceController::class, 'downloadPdf']);
    Route::post('/invoices/{invoice}/mark-paid', [InvoiceController::class, 'markPaid']);

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

