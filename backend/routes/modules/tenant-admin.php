<?php

use Illuminate\Support\Facades\Route;
use App\Modules\TenantAdmin\Controllers\AuthController;
use App\Modules\TenantAdmin\Controllers\DashboardController;
use App\Modules\TenantAdmin\Controllers\NewspaperProfileController;
use App\Modules\TenantAdmin\Controllers\EditionController;
use App\Modules\TenantAdmin\Controllers\PdfUploadController;
use App\Modules\TenantAdmin\Controllers\PageController;
use App\Modules\TenantAdmin\Controllers\PublishingController;
use App\Modules\TenantAdmin\Controllers\MediaLibraryController;
use App\Modules\TenantAdmin\Controllers\BrandingController;
use App\Modules\TenantAdmin\Controllers\TemplateController;
use App\Modules\TenantAdmin\Controllers\StaffController;
use App\Modules\TenantAdmin\Controllers\RoleController;
use App\Modules\TenantAdmin\Controllers\ReportController;
use App\Modules\TenantAdmin\Controllers\SubscriptionController;
use App\Modules\TenantAdmin\Controllers\NotificationController;
use App\Modules\TenantAdmin\Controllers\SupportController;
use App\Modules\TenantAdmin\Controllers\SettingsController;
use App\Modules\TenantAdmin\Middleware\TenantAdminMiddleware;

/*
|--------------------------------------------------------------------------
| Tenant Admin Control Center Routes (/api/v1/tenant-admin)
|--------------------------------------------------------------------------
*/

// Public Authentication Endpoints
Route::prefix('auth')->group(function () {
    Route::post('/login', [AuthController::class, 'login']);
});

// Protected Tenant Admin Routes (Enforced by TenantAdminMiddleware)
Route::middleware([TenantAdminMiddleware::class])->group(function () {
    
    // Auth Profile
    Route::prefix('auth')->group(function () {
        Route::get('/me', [AuthController::class, 'me']);
        Route::post('/logout', [AuthController::class, 'logout']);
        Route::post('/change-password', [AuthController::class, 'changePassword']);
    });

    // 1. Dashboard
    Route::get('/dashboard', [DashboardController::class, 'index']);

    // 2. Newspaper Profile
    Route::prefix('newspaper-profile')->group(function () {
        Route::get('/', [NewspaperProfileController::class, 'show']);
        Route::put('/', [NewspaperProfileController::class, 'update']);
        Route::post('/logo', [NewspaperProfileController::class, 'uploadLogo']);
        Route::post('/favicon', [NewspaperProfileController::class, 'uploadFavicon']);
    });

    // 3. Editions Management
    Route::prefix('editions')->group(function () {
        Route::get('/', [EditionController::class, 'index']);
        Route::post('/', [EditionController::class, 'store']);
        Route::get('/{id}', [EditionController::class, 'show']);
        Route::put('/{id}', [EditionController::class, 'update']);
        Route::delete('/{id}', [EditionController::class, 'destroy']);
        Route::post('/{id}/duplicate', [EditionController::class, 'duplicate']);
        Route::post('/{id}/archive', [EditionController::class, 'archive']);
        Route::get('/{id}/download-pdf', [EditionController::class, 'downloadOriginalPdf']);

        // 4. PDF Upload & Processing
        Route::post('/{id}/upload', [PdfUploadController::class, 'upload']);
        Route::get('/{id}/processing-status', [PdfUploadController::class, 'processingStatus']);
        Route::post('/{id}/retry-processing', [PdfUploadController::class, 'retryProcessing']);

        // 5. Page Management
        Route::get('/{id}/pages', [PageController::class, 'index']);
        Route::put('/{id}/pages/reorder', [PageController::class, 'reorder']);
        Route::post('/{id}/pages/{pageId}/rotate', [PageController::class, 'rotate']);
        Route::post('/{id}/pages/{pageId}/visibility', [PageController::class, 'toggleVisibility']);
        Route::post('/{id}/pages/{pageId}/replace', [PageController::class, 'replace']);
        Route::delete('/{id}/pages/{pageId}', [PageController::class, 'destroy']);

        // 6. Publishing & Scheduling
        Route::post('/{id}/schedule', [PublishingController::class, 'schedule']);
        Route::post('/{id}/publish', [PublishingController::class, 'publishNow']);
        Route::post('/{id}/unpublish', [PublishingController::class, 'unpublish']);
        Route::post('/{id}/cancel-schedule', [PublishingController::class, 'cancelSchedule']);
    });

    // Publishing Calendar & History
    Route::prefix('publishing')->group(function () {
        Route::get('/calendar', [PublishingController::class, 'calendar']);
        Route::get('/history', [PublishingController::class, 'history']);
    });

    // 7. Media Library
    Route::prefix('media')->group(function () {
        Route::get('/', [MediaLibraryController::class, 'index']);
        Route::post('/', [MediaLibraryController::class, 'store']);
        Route::put('/{id}', [MediaLibraryController::class, 'update']);
        Route::delete('/{id}', [MediaLibraryController::class, 'destroy']);
    });

    // 8. Branding & Appearance
    Route::prefix('branding')->group(function () {
        Route::get('/', [BrandingController::class, 'show']);
        Route::put('/', [BrandingController::class, 'update']);
        Route::post('/reset', [BrandingController::class, 'reset']);
    });

    // 9. Template Management
    Route::prefix('templates')->group(function () {
        Route::get('/', [TemplateController::class, 'index']);
        Route::post('/{id}/apply', [TemplateController::class, 'apply']);
    });

    // 10. Staff & User Management
    Route::prefix('staff')->group(function () {
        Route::get('/', [StaffController::class, 'index']);
        Route::post('/', [StaffController::class, 'store']);
        Route::put('/{id}', [StaffController::class, 'update']);
        Route::delete('/{id}', [StaffController::class, 'destroy']);
    });

    Route::get('/roles', [RoleController::class, 'index']);

    // 11. Reports & Analytics
    Route::prefix('reports')->group(function () {
        Route::get('/editions', [ReportController::class, 'editions']);
        Route::get('/storage', [ReportController::class, 'storage']);
        Route::get('/activity', [ReportController::class, 'activity']);
        Route::get('/export/csv', [ReportController::class, 'exportCsv']);
    });

    // 12. Subscription & Usage
    Route::prefix('subscription')->group(function () {
        Route::get('/', [SubscriptionController::class, 'index']);
        Route::post('/upgrade-request', [SubscriptionController::class, 'upgradeRequest']);
    });

    // 13. Notifications
    Route::prefix('notifications')->group(function () {
        Route::get('/', [NotificationController::class, 'index']);
        Route::post('/{id}/read', [NotificationController::class, 'markAsRead']);
        Route::post('/read-all', [NotificationController::class, 'markAllAsRead']);
        Route::delete('/{id}', [NotificationController::class, 'destroy']);
    });

    // 14. Support & Helpdesk
    Route::prefix('support')->group(function () {
        Route::get('/tickets', [SupportController::class, 'index']);
        Route::post('/tickets', [SupportController::class, 'store']);
        Route::get('/tickets/{id}', [SupportController::class, 'show']);
        Route::post('/tickets/{id}/reply', [SupportController::class, 'reply']);
        Route::post('/tickets/{id}/close', [SupportController::class, 'close']);
    });

    // 15. Tenant Settings
    Route::prefix('settings')->group(function () {
        Route::get('/', [SettingsController::class, 'index']);
        Route::post('/', [SettingsController::class, 'update']);
    });
});
