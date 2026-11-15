<?php

use Illuminate\Support\Facades\Route;
use App\Modules\SuperAdmin\Controllers\AuthController;
use App\Modules\SuperAdmin\Controllers\DashboardController;
use App\Modules\SuperAdmin\Controllers\TenantController;
use App\Modules\SuperAdmin\Controllers\DomainController;
use App\Modules\SuperAdmin\Controllers\SubscriptionController;
use App\Modules\SuperAdmin\Controllers\PaymentController;
use App\Modules\SuperAdmin\Controllers\BrandingController;
use App\Modules\SuperAdmin\Controllers\OperationsController;
use App\Modules\SuperAdmin\Controllers\InfrastructureController;
use App\Modules\SuperAdmin\Controllers\SecurityController;
use App\Modules\SuperAdmin\Controllers\BackupController;
use App\Modules\SuperAdmin\Controllers\ReportController;
use App\Modules\SuperAdmin\Controllers\SupportController;
use App\Modules\SuperAdmin\Controllers\DeveloperController;
use App\Modules\SuperAdmin\Controllers\PlatformSettingsController;
use App\Modules\SuperAdmin\Controllers\ProfileController;

// Public Unauthenticated Routes
Route::prefix('auth')->group(function () {
    Route::post('login', [AuthController::class, 'login']);
});

Route::post('payments/webhook/{gateway}', [PaymentController::class, 'webhook']);

// Protected Super Admin Routes
Route::middleware(['superadmin.auth'])->group(function () {
    
    // Auth Session
    Route::prefix('auth')->group(function () {
        Route::get('me', [AuthController::class, 'me']);
        Route::post('logout', [AuthController::class, 'logout']);
    });

    // 1. Dashboard
    Route::prefix('dashboard')->group(function () {
        Route::get('stats', [DashboardController::class, 'stats']);
    });

    // 2. Tenant Management
    Route::prefix('tenants')->group(function () {
        Route::get('/', [TenantController::class, 'index']);
        Route::post('/', [TenantController::class, 'store']);
        Route::get('{id}', [TenantController::class, 'show']);
        Route::put('{id}', [TenantController::class, 'update']);
        Route::post('{id}/activate', [TenantController::class, 'activate']);
        Route::post('{id}/suspend', [TenantController::class, 'suspend']);
        Route::post('{id}/reactivate', [TenantController::class, 'reactivate']);
        Route::post('{id}/renew', [TenantController::class, 'renew']);
        Route::post('{id}/archive', [TenantController::class, 'archive']);
        Route::delete('{id}', [TenantController::class, 'destroy']);
    });

    // 3. Domain & SSL Management
    Route::prefix('domains')->group(function () {
        Route::get('/', [DomainController::class, 'index']);
        Route::post('/', [DomainController::class, 'store']);
        Route::get('{id}', [DomainController::class, 'show']);
        Route::post('{id}/verify', [DomainController::class, 'verify']);
        Route::post('{id}/provision-ssl', [DomainController::class, 'provisionSsl']);
        Route::delete('{id}', [DomainController::class, 'destroy']);
    });

    // 4. Subscription & Packages
    Route::prefix('subscriptions')->group(function () {
        Route::get('packages', [SubscriptionController::class, 'packages']);
        Route::post('packages', [SubscriptionController::class, 'storePackage']);
        Route::put('packages/{id}', [SubscriptionController::class, 'updatePackage']);
        Route::get('list', [SubscriptionController::class, 'subscriptions']);
        Route::post('tenant/{tenantId}/change-package', [SubscriptionController::class, 'changePackage']);
        Route::get('invoices', [SubscriptionController::class, 'invoices']);
    });

    // 5. Payments & Revenue
    Route::prefix('payments')->group(function () {
        Route::get('/', [PaymentController::class, 'index']);
        Route::get('revenue-summary', [PaymentController::class, 'revenueSummary']);
        Route::get('reconciliation', [PaymentController::class, 'reconciliation']);
        Route::get('{id}', [PaymentController::class, 'show']);
        Route::post('{id}/refund', [PaymentController::class, 'refund']);
    });

    // 6. White Label & Branding
    Route::prefix('branding')->group(function () {
        Route::get('platform', [BrandingController::class, 'getPlatformBranding']);
        Route::put('platform', [BrandingController::class, 'updatePlatformBranding']);
        Route::get('tenant/{tenantId}', [BrandingController::class, 'getTenantBranding']);
        Route::put('tenant/{tenantId}', [BrandingController::class, 'updateTenantBranding']);
        Route::post('tenant/{tenantId}/rollback/{version}', [BrandingController::class, 'rollbackTenantBranding']);
    });

    // 7. Newspaper Operations Monitoring
    Route::prefix('operations')->group(function () {
        Route::get('stats', [OperationsController::class, 'stats']);
        Route::get('editions', [OperationsController::class, 'editions']);
        Route::get('jobs', [OperationsController::class, 'jobs']);
        Route::post('jobs/{id}/retry', [OperationsController::class, 'retryJob']);
        Route::post('purge-dlq', [OperationsController::class, 'purgeDlq']);
        Route::get('storage-usage', [OperationsController::class, 'storageUsage']);
    });

    // 8. Infrastructure & Cloud Monitoring
    Route::prefix('infrastructure')->group(function () {
        Route::get('overview', [InfrastructureController::class, 'overview']);
        Route::get('services', [InfrastructureController::class, 'services']);
        Route::get('alerts', [InfrastructureController::class, 'alerts']);
        Route::post('alerts/{id}/acknowledge', [InfrastructureController::class, 'acknowledgeAlert']);
        Route::post('alerts/{id}/resolve', [InfrastructureController::class, 'resolveAlert']);
        Route::get('deployments', [InfrastructureController::class, 'deployments']);
    });

    // 9. Security & Access Management
    Route::prefix('security')->group(function () {
        Route::get('admins', [SecurityController::class, 'admins']);
        Route::post('admins', [SecurityController::class, 'storeAdmin']);
        Route::put('admins/{id}', [SecurityController::class, 'updateAdmin']);
        Route::get('roles', [SecurityController::class, 'roles']);
        Route::get('permissions', [SecurityController::class, 'permissions']);
        Route::get('sessions', [SecurityController::class, 'sessions']);
        Route::delete('sessions/{id}', [SecurityController::class, 'revokeSession']);
        Route::get('audit-logs', [SecurityController::class, 'auditLogs']);
        Route::get('login-logs', [SecurityController::class, 'loginLogs']);
    });

    // 10. Backup & Disaster Recovery
    Route::prefix('backups')->group(function () {
        Route::get('/', [BackupController::class, 'index']);
        Route::post('trigger', [BackupController::class, 'triggerBackup']);
        Route::post('{id}/test-restore', [BackupController::class, 'testRestore']);
    });

    // 11. Reports & Analytics
    Route::prefix('reports')->group(function () {
        Route::get('/', [ReportController::class, 'index']);
        Route::post('generate', [ReportController::class, 'generate']);
        Route::get('{id}/download', [ReportController::class, 'download']);
    });

    // 12. Support & Communication
    Route::prefix('support')->group(function () {
        Route::get('tickets', [SupportController::class, 'tickets']);
        Route::get('tickets/{id}', [SupportController::class, 'showTicket']);
        Route::post('tickets/{id}/reply', [SupportController::class, 'replyTicket']);
        Route::put('tickets/{id}/status', [SupportController::class, 'updateTicketStatus']);
        Route::get('templates', [SupportController::class, 'templates']);
        Route::post('announcements', [SupportController::class, 'sendAnnouncement']);
    });

    // 13. Developer & Integrations
    Route::prefix('developer')->group(function () {
        Route::get('api-keys', [DeveloperController::class, 'apiKeys']);
        Route::post('api-keys', [DeveloperController::class, 'storeApiKey']);
        Route::delete('api-keys/{id}', [DeveloperController::class, 'revokeApiKey']);
        Route::get('webhooks', [DeveloperController::class, 'webhooks']);
        Route::post('webhooks', [DeveloperController::class, 'storeWebhook']);
        Route::get('webhook-logs', [DeveloperController::class, 'webhookLogs']);
        Route::post('webhook-logs/{id}/retry', [DeveloperController::class, 'retryWebhook']);
    });

    // 14. Platform Settings
    Route::prefix('settings')->group(function () {
        Route::get('/', [PlatformSettingsController::class, 'index']);
        Route::post('/', [PlatformSettingsController::class, 'update']);
    });

    // 15. My Account
    Route::prefix('profile')->group(function () {
        Route::get('/', [ProfileController::class, 'getProfile']);
        Route::put('/', [ProfileController::class, 'updateProfile']);
        Route::post('change-password', [ProfileController::class, 'changePassword']);
    });
});
