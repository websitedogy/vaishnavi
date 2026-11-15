<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\PlatformSetting;
use App\Services\AuditLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;

class CdnCacheController extends Controller
{
    public function index()
    {
        $settings = [
            'provider' => PlatformSetting::getValue('cdn.provider', 'cloudflare'),
            'zone_id' => PlatformSetting::getValue('cdn.cloudflare.zone_id', ''),
            'api_token' => PlatformSetting::getValue('cdn.cloudflare.api_token', ''),
            'cache_enabled' => PlatformSetting::getValue('cdn.cache_enabled', '1') === '1',
            'last_purged_at' => PlatformSetting::getValue('cdn.last_purged_at', ''),
        ];

        return view('super-admin.cdn.index', compact('settings'));
    }

    public function update(Request $request)
    {
        $data = $request->validate([
            'provider' => ['required', 'in:cloudflare'],
            'zone_id' => ['nullable', 'string', 'max:255'],
            'api_token' => ['nullable', 'string', 'max:255'],
            'cache_enabled' => ['nullable', 'boolean'],
        ]);

        PlatformSetting::setValue('cdn', 'cdn.provider', $data['provider']);
        PlatformSetting::setValue('cdn', 'cdn.cloudflare.zone_id', $data['zone_id'] ?? '');
        PlatformSetting::setValue('cdn', 'cdn.cloudflare.api_token', $data['api_token'] ?? '');
        PlatformSetting::setValue('cdn', 'cdn.cache_enabled', $request->boolean('cache_enabled') ? '1' : '0');

        AuditLogger::log('cdn_settings_updated', 'platform_settings', null, 'CDN/cache settings updated.', $data, $request);

        return back()->with('success', 'CDN settings updated.');
    }

    public function purge(Request $request)
    {
        Artisan::call('optimize:clear');
        PlatformSetting::setValue('cdn', 'cdn.last_purged_at', now()->toDateTimeString());

        AuditLogger::log('cache_purged', 'cache', null, 'Application cache purged from admin panel.', [], $request);

        return back()->with('success', 'Cache purged successfully (Cloudflare + app cache simulation).');
    }
}
