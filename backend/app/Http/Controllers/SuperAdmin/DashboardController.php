<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Services\DashboardStatsService;

class DashboardController extends Controller
{
    public function index(DashboardStatsService $statsService)
    {
        return view('super-admin.dashboard', $statsService->getStats());
    }
}
