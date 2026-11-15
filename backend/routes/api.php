<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes - DOGY ePAPER SaaS
|--------------------------------------------------------------------------
*/

Route::prefix('v1/super-admin')->group(function () {
    require __DIR__ . '/modules/super-admin.php';
});

Route::prefix('v1/tenant-admin')->group(function () {
    require __DIR__ . '/modules/tenant-admin.php';
});

// Public View Module API Endpoints
Route::prefix('v1/public')->group(function () {
    require app_path('Modules/PublicView/Routes/api.php');
});
