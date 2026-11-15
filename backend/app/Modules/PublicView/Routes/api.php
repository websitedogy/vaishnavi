<?php

use App\Modules\PublicView\Controllers\ArchiveController;
use App\Modules\PublicView\Controllers\BrandingController;
use App\Modules\PublicView\Controllers\CategoryController;
use App\Modules\PublicView\Controllers\EditionController;
use App\Modules\PublicView\Controllers\HomeController;
use App\Modules\PublicView\Controllers\ReaderController;
use App\Modules\PublicView\Controllers\SearchController;
use App\Modules\PublicView\Controllers\SitemapController;
use App\Modules\PublicView\Middleware\EnsurePublicTenantActive;
use App\Modules\PublicView\Middleware\ResolvePublicTenant;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Public View API Routes
|--------------------------------------------------------------------------
|
| All public newspaper reader and portal endpoints for DOGY ePAPER SaaS.
| Protected by ResolvePublicTenant and EnsurePublicTenantActive middleware.
|
*/

Route::middleware([ResolvePublicTenant::class, EnsurePublicTenantActive::class])->group(function () {
    // 1. Homepage Payload (Masthead, latest edition, recents, categories, branding)
    Route::get('/{tenantSlug}/home', [HomeController::class, 'index']);

    // 2. Branding & Publication Profile
    Route::get('/{tenantSlug}/branding', [BrandingController::class, 'show']);

    // 3. Latest Published Edition Manifest (Cached with ETag)
    Route::get('/{tenantSlug}/latest', [EditionController::class, 'latest']);

    // 4. Editions List (Paginated & Filterable by category, date range)
    Route::get('/{tenantSlug}/editions', [EditionController::class, 'index']);

    // 5. Specific Edition Manifest (by date YYYY-MM-DD or edition_code)
    Route::get('/{tenantSlug}/editions/{dateOrCode}', [EditionController::class, 'show']);

    // 6. Dedicated Digital Reader Payload (Latest or specific date/code)
    Route::get('/{tenantSlug}/reader/{dateOrCode?}', [ReaderController::class, 'show']);

    // 7. Monthly Archive Calendar & Edition List
    Route::get('/{tenantSlug}/archive', [ArchiveController::class, 'index']);

    // 8. Public Metadata Search (Safe, parameterized edition search)
    Route::get('/{tenantSlug}/search', [SearchController::class, 'search']);

    // 9. Categories
    Route::get('/{tenantSlug}/categories', [CategoryController::class, 'index']);
    Route::get('/{tenantSlug}/categories/{category}', [CategoryController::class, 'show']);

    // 10. SEO: Sitemap XML & Robots.txt
    Route::get('/{tenantSlug}/sitemap.xml', [SitemapController::class, 'sitemap']);
    Route::get('/{tenantSlug}/robots.txt', [SitemapController::class, 'robots']);

    // Direct Host Header / Custom Domain Root Endpoints
    Route::get('/home', [HomeController::class, 'index']);
    Route::get('/branding', [BrandingController::class, 'show']);
    Route::get('/latest', [EditionController::class, 'latest']);
    Route::get('/editions', [EditionController::class, 'index']);
    Route::get('/editions/{dateOrCode}', [EditionController::class, 'show']);
    Route::get('/reader/{dateOrCode?}', [ReaderController::class, 'show']);
    Route::get('/archive', [ArchiveController::class, 'index']);
    Route::get('/search', [SearchController::class, 'search']);
    Route::get('/categories', [CategoryController::class, 'index']);
    Route::get('/sitemap.xml', [SitemapController::class, 'sitemap']);
    Route::get('/robots.txt', [SitemapController::class, 'robots']);
});
