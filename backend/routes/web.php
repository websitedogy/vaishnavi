<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::get('/', [App\Http\Controllers\Public\HomeController::class, 'index'])->name('home');
Route::post('/register', [App\Http\Controllers\Public\HomeController::class, 'register'])
    ->middleware('throttle:10,1')
    ->name('register');

// Tenant public news site (path-based: /site/{tenant-slug})
Route::prefix('site/{tenant:slug}')->name('tenant-site.')->group(function () {
    Route::get('/', [App\Http\Controllers\TenantSite\SiteController::class, 'home'])->name('home');
    Route::get('/search', [App\Http\Controllers\TenantSite\SiteController::class, 'search'])->name('search');
    Route::get('/category/{slug}', [App\Http\Controllers\TenantSite\SiteController::class, 'category'])->name('category');
    Route::get('/news/{slug}', [App\Http\Controllers\TenantSite\SiteController::class, 'article'])->name('article');
});

// Tenant Admin Routes (separate panel — uses users table + MySQL from .env)
Route::prefix('tenant-admin')->name('tenant-admin.')->group(function () {
    Route::middleware('guest:tenant_admin')->group(function () {
        Route::get('/login', [App\Http\Controllers\TenantAdmin\AuthController::class, 'showLoginForm'])->name('login');
        Route::post('/login', [App\Http\Controllers\TenantAdmin\AuthController::class, 'login'])->name('login.submit');
    });

    Route::post('/logout', [App\Http\Controllers\TenantAdmin\AuthController::class, 'logout'])
        ->middleware('auth:tenant_admin')
        ->name('logout');

    Route::middleware('auth:tenant_admin')->group(function () {
        Route::get('/dashboard', [App\Http\Controllers\TenantAdmin\DashboardController::class, 'index'])->name('dashboard');
        Route::get('/analytics/live', [App\Http\Controllers\TenantAdmin\DashboardController::class, 'analytics'])->name('analytics.live');

        Route::post('news/bulk', [App\Http\Controllers\TenantAdmin\NewsArticleController::class, 'bulk'])->name('news.bulk');
        Route::post('news/autosave/{news?}', [App\Http\Controllers\TenantAdmin\NewsArticleController::class, 'autosave'])->name('news.autosave');
        Route::post('news/{news}/approve', [App\Http\Controllers\TenantAdmin\NewsArticleController::class, 'approve'])->name('news.approve');
        Route::post('news/{news}/duplicate', [App\Http\Controllers\TenantAdmin\NewsArticleController::class, 'duplicate'])->name('news.duplicate');
        Route::get('news/{news}/preview', [App\Http\Controllers\TenantAdmin\NewsArticleController::class, 'preview'])->name('news.preview');
        Route::post('news/preview-draft/{news?}', [App\Http\Controllers\TenantAdmin\NewsArticleController::class, 'previewDraft'])->name('news.preview-draft');
        Route::get('news/preview-draft/show', [App\Http\Controllers\TenantAdmin\NewsArticleController::class, 'previewSession'])->name('news.preview-session');
        Route::resource('news', App\Http\Controllers\TenantAdmin\NewsArticleController::class);

        Route::get('categories/subcategories', [App\Http\Controllers\TenantAdmin\CategoryController::class, 'subcategoriesIndex'])->name('categories.subcategories.index');
        Route::get('categories/subcategories/create', [App\Http\Controllers\TenantAdmin\CategoryController::class, 'createSubcategory'])->name('categories.subcategories.create');
        Route::post('categories/quick-store', [App\Http\Controllers\TenantAdmin\CategoryController::class, 'quickStore'])->name('categories.quick-store');
        Route::post('categories/reorder', [App\Http\Controllers\TenantAdmin\CategoryController::class, 'reorder'])->name('categories.reorder');
        Route::post('categories/{category}/toggle-visibility', [App\Http\Controllers\TenantAdmin\CategoryController::class, 'toggleVisibility'])->name('categories.toggle-visibility');
        Route::resource('categories', App\Http\Controllers\TenantAdmin\CategoryController::class)->except(['show']);

        Route::get('media/browser', [App\Http\Controllers\TenantAdmin\MediaController::class, 'browser'])->name('media.browser');
        Route::post('media/upload-editor', [App\Http\Controllers\TenantAdmin\MediaController::class, 'uploadEditor'])->name('media.upload-editor');
        Route::post('media/{media}/reupload', [App\Http\Controllers\TenantAdmin\MediaController::class, 'reupload'])->name('media.reupload');
        Route::resource('media', App\Http\Controllers\TenantAdmin\MediaController::class)->only(['index', 'store', 'destroy']);

        Route::get('staff/logs', [App\Http\Controllers\TenantAdmin\StaffController::class, 'logs'])->name('staff.logs');
        Route::get('staff/{staff}/profile', [App\Http\Controllers\TenantAdmin\StaffController::class, 'profile'])->name('staff.profile');
        Route::post('comments/{comment}/approve', [App\Http\Controllers\TenantAdmin\CommentManagementController::class, 'approve'])->name('comments.approve');
        Route::delete('comments/{comment}', [App\Http\Controllers\TenantAdmin\CommentManagementController::class, 'destroy'])->name('comments.destroy');
        Route::get('comments', [App\Http\Controllers\TenantAdmin\CommentManagementController::class, 'index'])->name('comments.index');
        Route::post('comments/settings', [App\Http\Controllers\TenantAdmin\CommentManagementController::class, 'updateSettings'])->name('comments.settings');
        Route::post('comments/block', [App\Http\Controllers\TenantAdmin\CommentManagementController::class, 'blockUser'])->name('comments.block');

        Route::resource('staff', App\Http\Controllers\TenantAdmin\StaffController::class)->parameters(['staff' => 'staff']);

        Route::resource('ads', App\Http\Controllers\TenantAdmin\AdvertisementController::class)->parameters(['ads' => 'ad']);
        Route::post('ads/adsense', [App\Http\Controllers\TenantAdmin\AdvertisementController::class, 'updateAdsense'])->name('ads.adsense');

        Route::get('seo', [App\Http\Controllers\TenantAdmin\ModuleSettingsController::class, 'seo'])->name('seo.index');
        Route::put('seo', [App\Http\Controllers\TenantAdmin\ModuleSettingsController::class, 'updateSeo'])->name('seo.update');
        Route::get('sitemap.xml', [App\Http\Controllers\TenantAdmin\ModuleSettingsController::class, 'sitemap'])->name('sitemap');

        Route::get('theme', [App\Http\Controllers\TenantAdmin\ModuleSettingsController::class, 'theme'])->name('theme.index');
        Route::put('theme', [App\Http\Controllers\TenantAdmin\ModuleSettingsController::class, 'updateTheme'])->name('theme.update');

        Route::get('breaking-news', [App\Http\Controllers\TenantAdmin\ModuleSettingsController::class, 'breaking'])->name('breaking.index');
        Route::put('breaking-news', [App\Http\Controllers\TenantAdmin\ModuleSettingsController::class, 'updateBreaking'])->name('breaking.update');

        Route::get('push-notifications', [App\Http\Controllers\TenantAdmin\PushNotificationController::class, 'index'])->name('push.index');
        Route::post('push-notifications', [App\Http\Controllers\TenantAdmin\PushNotificationController::class, 'store'])->name('push.store');
        Route::put('push-notifications/config', [App\Http\Controllers\TenantAdmin\PushNotificationController::class, 'updateConfig'])->name('push.config');

        Route::get('newsletters', [App\Http\Controllers\TenantAdmin\NewsletterController::class, 'index'])->name('newsletters.index');
        Route::post('newsletters/campaigns', [App\Http\Controllers\TenantAdmin\NewsletterController::class, 'storeCampaign'])->name('newsletters.campaigns.store');
        Route::post('newsletters/campaigns/{campaign}/send', [App\Http\Controllers\TenantAdmin\NewsletterController::class, 'sendCampaign'])->name('newsletters.campaigns.send');
        Route::post('newsletters/subscribers', [App\Http\Controllers\TenantAdmin\NewsletterController::class, 'storeSubscriber'])->name('newsletters.subscribers.store');

        Route::get('analytics', [App\Http\Controllers\TenantAdmin\TenantAnalyticsController::class, 'index'])->name('analytics.index');

        Route::get('social', [App\Http\Controllers\TenantAdmin\ModuleSettingsController::class, 'social'])->name('social.index');
        Route::put('social', [App\Http\Controllers\TenantAdmin\ModuleSettingsController::class, 'updateSocial'])->name('social.update');

        Route::get('polls', [App\Http\Controllers\TenantAdmin\PollController::class, 'index'])->name('polls.index');
        Route::post('polls', [App\Http\Controllers\TenantAdmin\PollController::class, 'store'])->name('polls.store');
        Route::delete('polls/{poll}', [App\Http\Controllers\TenantAdmin\PollController::class, 'destroy'])->name('polls.destroy');

        Route::get('readers', [App\Http\Controllers\TenantAdmin\ReaderController::class, 'index'])->name('readers.index');
        Route::post('readers', [App\Http\Controllers\TenantAdmin\ReaderController::class, 'store'])->name('readers.store');
        Route::post('readers/{reader}/ban', [App\Http\Controllers\TenantAdmin\ReaderController::class, 'ban'])->name('readers.ban');

        Route::get('monetization', [App\Http\Controllers\TenantAdmin\MonetizationController::class, 'index'])->name('monetization.index');
        Route::post('monetization/plans', [App\Http\Controllers\TenantAdmin\MonetizationController::class, 'storePlan'])->name('monetization.plans.store');
        Route::post('monetization/coupons', [App\Http\Controllers\TenantAdmin\MonetizationController::class, 'storeCoupon'])->name('monetization.coupons.store');
        Route::get('monetization/settings', [App\Http\Controllers\TenantAdmin\ModuleSettingsController::class, 'monetizationSettings'])->name('monetization.settings');
        Route::put('monetization/settings', [App\Http\Controllers\TenantAdmin\ModuleSettingsController::class, 'updateMonetizationSettings'])->name('monetization.settings.update');

        Route::get('security', [App\Http\Controllers\TenantAdmin\ModuleSettingsController::class, 'security'])->name('security.index');
        Route::put('security', [App\Http\Controllers\TenantAdmin\ModuleSettingsController::class, 'updateSecurity'])->name('security.update');

        Route::get('integrations', [App\Http\Controllers\TenantAdmin\ModuleSettingsController::class, 'integrations'])->name('integrations.index');
        Route::put('integrations', [App\Http\Controllers\TenantAdmin\ModuleSettingsController::class, 'updateIntegrations'])->name('integrations.update');
        Route::post('integrations/api-keys', [App\Http\Controllers\TenantAdmin\IntegrationController::class, 'storeApiKey'])->name('integrations.api-keys.store');
        Route::delete('integrations/api-keys/{apiKey}', [App\Http\Controllers\TenantAdmin\IntegrationController::class, 'destroyApiKey'])->name('integrations.api-keys.destroy');

        Route::get('mobile-app', [App\Http\Controllers\TenantAdmin\ModuleSettingsController::class, 'mobile'])->name('mobile.index');
        Route::put('mobile-app', [App\Http\Controllers\TenantAdmin\ModuleSettingsController::class, 'updateMobile'])->name('mobile.update');
    });
});

// Super Admin Routes
Route::prefix('super-admin')->name('super-admin.')->group(function () {
    Route::middleware('guest:super_admin')->group(function () {
        Route::get('/login', [App\Http\Controllers\SuperAdmin\AuthController::class, 'showLoginForm'])->name('login');
        Route::post('/login', [App\Http\Controllers\SuperAdmin\AuthController::class, 'login'])->name('login.submit');
    });
    Route::post('/logout', [App\Http\Controllers\SuperAdmin\AuthController::class, 'logout'])->middleware('auth:super_admin')->name('logout');
    
    Route::middleware(['auth:super_admin'])->group(function () {
        Route::get('/dashboard', [App\Http\Controllers\SuperAdmin\DashboardController::class, 'index'])->name('dashboard');

        Route::post('tenants/{tenant}/suspend', [App\Http\Controllers\SuperAdmin\TenantController::class, 'suspend'])->name('tenants.suspend');
        Route::post('tenants/{tenant}/activate', [App\Http\Controllers\SuperAdmin\TenantController::class, 'activate'])->name('tenants.activate');
        Route::resource('tenants', App\Http\Controllers\SuperAdmin\TenantController::class)->except(['show']);

        Route::post('domains/{domain}/ssl', [App\Http\Controllers\SuperAdmin\DomainController::class, 'provisionSsl'])->name('domains.ssl');
        Route::resource('domains', App\Http\Controllers\SuperAdmin\DomainController::class)->except(['show']);

        Route::resource('users', App\Http\Controllers\SuperAdmin\TenantUserController::class)->except(['show']);

        Route::get('themes', [App\Http\Controllers\SuperAdmin\ThemeController::class, 'index'])->name('themes.index');
        Route::get('themes/{tenant}/edit', [App\Http\Controllers\SuperAdmin\ThemeController::class, 'edit'])->name('themes.edit');
        Route::put('themes/{tenant}', [App\Http\Controllers\SuperAdmin\ThemeController::class, 'update'])->name('themes.update');

        Route::resource('advertisements', App\Http\Controllers\SuperAdmin\AdvertisementController::class)->except(['show']);
        Route::get('analytics', [App\Http\Controllers\SuperAdmin\AnalyticsController::class, 'index'])->name('analytics.index');

        Route::get('security', [App\Http\Controllers\SuperAdmin\SecurityController::class, 'index'])->name('security.index');
        Route::post('security/ip-block', [App\Http\Controllers\SuperAdmin\SecurityController::class, 'blockIp'])->name('security.block-ip');
        Route::delete('security/ip-block/{blockedIp}', [App\Http\Controllers\SuperAdmin\SecurityController::class, 'unblockIp'])->name('security.unblock-ip');
        Route::post('security/sessions/{session}/revoke', [App\Http\Controllers\SuperAdmin\SecurityController::class, 'revokeSession'])->name('security.revoke-session');
        Route::post('security/tenants/{tenant}/lock', [App\Http\Controllers\SuperAdmin\SecurityController::class, 'lockTenant'])->name('security.lock-tenant');

        Route::get('backups', [App\Http\Controllers\SuperAdmin\BackupController::class, 'index'])->name('backups.index');
        Route::post('backups', [App\Http\Controllers\SuperAdmin\BackupController::class, 'store'])->name('backups.store');
        Route::post('backups/{backup}/restore', [App\Http\Controllers\SuperAdmin\BackupController::class, 'restore'])->name('backups.restore');

        Route::get('notifications', [App\Http\Controllers\SuperAdmin\NotificationController::class, 'index'])->name('notifications.index');
        Route::post('notifications', [App\Http\Controllers\SuperAdmin\NotificationController::class, 'store'])->name('notifications.store');

        Route::get('storage-management', [App\Http\Controllers\SuperAdmin\StorageManagementController::class, 'index'])->name('storage.index');
        Route::put('storage-management', [App\Http\Controllers\SuperAdmin\StorageManagementController::class, 'update'])->name('storage.update');

        Route::get('api-management', [App\Http\Controllers\SuperAdmin\ApiManagementController::class, 'index'])->name('api.index');
        Route::post('api-management/keys', [App\Http\Controllers\SuperAdmin\ApiManagementController::class, 'store'])->name('api.keys.store');
        Route::post('api-management/keys/{apiKey}/toggle', [App\Http\Controllers\SuperAdmin\ApiManagementController::class, 'toggle'])->name('api.keys.toggle');

        Route::get('payment-gateways', [App\Http\Controllers\SuperAdmin\PaymentGatewayController::class, 'index'])->name('payments.index');
        Route::put('payment-gateways/{gateway}', [App\Http\Controllers\SuperAdmin\PaymentGatewayController::class, 'update'])->name('payments.update');

        Route::get('seo-settings', [App\Http\Controllers\SuperAdmin\SeoSettingsController::class, 'index'])->name('seo.index');
        Route::put('seo-settings', [App\Http\Controllers\SuperAdmin\SeoSettingsController::class, 'update'])->name('seo.update');

        Route::get('maintenance', [App\Http\Controllers\SuperAdmin\MaintenanceController::class, 'index'])->name('maintenance.index');
        Route::put('maintenance', [App\Http\Controllers\SuperAdmin\MaintenanceController::class, 'update'])->name('maintenance.update');

        Route::get('activity-logs', [App\Http\Controllers\SuperAdmin\ActivityLogController::class, 'index'])->name('activity-logs.index');

        Route::get('support', [App\Http\Controllers\SuperAdmin\SupportController::class, 'index'])->name('support.index');
        Route::get('support/{ticket}', [App\Http\Controllers\SuperAdmin\SupportController::class, 'show'])->name('support.show');
        Route::put('support/{ticket}', [App\Http\Controllers\SuperAdmin\SupportController::class, 'update'])->name('support.update');
        Route::post('support/{ticket}/reply', [App\Http\Controllers\SuperAdmin\SupportController::class, 'reply'])->name('support.reply');

        Route::get('server-management', [App\Http\Controllers\SuperAdmin\ServerManagementController::class, 'index'])->name('server.index');

        Route::get('languages', [App\Http\Controllers\SuperAdmin\LanguageManagementController::class, 'index'])->name('languages.index');
        Route::put('languages', [App\Http\Controllers\SuperAdmin\LanguageManagementController::class, 'update'])->name('languages.update');

        Route::get('cdn-cache', [App\Http\Controllers\SuperAdmin\CdnCacheController::class, 'index'])->name('cdn.index');
        Route::put('cdn-cache', [App\Http\Controllers\SuperAdmin\CdnCacheController::class, 'update'])->name('cdn.update');
        Route::post('cdn-cache/purge', [App\Http\Controllers\SuperAdmin\CdnCacheController::class, 'purge'])->name('cdn.purge');

        Route::get('audit-system', [App\Http\Controllers\SuperAdmin\AuditSystemController::class, 'index'])->name('audit.index');

        Route::get('database-management', [App\Http\Controllers\SuperAdmin\DatabaseManagementController::class, 'index'])->name('database.index');
        Route::post('database-management/cleanup', [App\Http\Controllers\SuperAdmin\DatabaseManagementController::class, 'cleanup'])->name('database.cleanup');
        Route::post('database-management/optimize', [App\Http\Controllers\SuperAdmin\DatabaseManagementController::class, 'optimize'])->name('database.optimize');

        Route::get('saas-configuration', [App\Http\Controllers\SuperAdmin\SaasConfigurationController::class, 'index'])->name('saas.index');
        Route::put('saas-configuration', [App\Http\Controllers\SuperAdmin\SaasConfigurationController::class, 'update'])->name('saas.update');
    });
});
