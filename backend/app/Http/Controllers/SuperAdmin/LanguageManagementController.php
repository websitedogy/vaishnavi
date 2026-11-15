<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\PlatformSetting;
use App\Services\AuditLogger;
use Illuminate\Http\Request;

class LanguageManagementController extends Controller
{
    public function index()
    {
        $available = [
            ['code' => 'te', 'label' => 'Telugu'],
            ['code' => 'en', 'label' => 'English'],
            ['code' => 'hi', 'label' => 'Hindi'],
        ];

        $enabled = explode(',', PlatformSetting::getValue('language.enabled', 'te,en,hi'));
        $default = PlatformSetting::getValue('language.default', 'en');

        return view('super-admin.languages.index', compact('available', 'enabled', 'default'));
    }

    public function update(Request $request)
    {
        $data = $request->validate([
            'enabled' => ['nullable', 'array'],
            'enabled.*' => ['in:te,en,hi'],
            'default' => ['required', 'in:te,en,hi'],
        ]);

        $enabled = $data['enabled'] ?? ['en'];
        if (! in_array($data['default'], $enabled, true)) {
            $enabled[] = $data['default'];
        }

        PlatformSetting::setValue('language', 'language.enabled', implode(',', array_unique($enabled)));
        PlatformSetting::setValue('language', 'language.default', $data['default']);

        AuditLogger::log('language_settings_updated', 'platform_settings', null, 'Language configuration updated.', $data, $request);

        return back()->with('success', 'Language settings updated.');
    }
}
