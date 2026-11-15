<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\PlatformSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;

class MaintenanceController extends Controller
{
    public function index()
    {
        $enabled = app()->isDownForMaintenance();
        $message = PlatformSetting::getValue('maintenance.message', 'Platform is under maintenance. Please try again later.');

        return view('super-admin.maintenance.index', compact('enabled', 'message'));
    }

    public function update(Request $request)
    {
        $data = $request->validate([
            'message' => ['nullable', 'string', 'max:500'],
        ]);

        PlatformSetting::setValue('maintenance', 'maintenance.message', $data['message'] ?? 'Platform is under maintenance. Please try again later.');

        if ($request->boolean('enabled')) {
            Artisan::call('down', ['--render' => 'errors::503']);
            return back()->with('success', 'Maintenance mode enabled.');
        }

        Artisan::call('up');
        return back()->with('success', 'Maintenance mode disabled.');
    }
}
