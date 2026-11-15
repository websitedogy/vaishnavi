<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\PlatformSetting;
use App\Services\AuditLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class SaasConfigurationController extends Controller
{
    public function index()
    {
        $settings = [
            'company_name' => PlatformSetting::getValue('saas.company_name', 'News SaaS'),
            'company_email' => PlatformSetting::getValue('saas.company_email', 'support@example.com'),
            'brand_primary' => PlatformSetting::getValue('saas.brand_primary', '#2563eb'),
            'brand_secondary' => PlatformSetting::getValue('saas.brand_secondary', '#1e293b'),
            'logo' => PlatformSetting::getValue('saas.logo', ''),
            'terms_url' => PlatformSetting::getValue('saas.terms_url', ''),
            'privacy_url' => PlatformSetting::getValue('saas.privacy_url', ''),
        ];

        return view('super-admin.saas.index', compact('settings'));
    }

    public function update(Request $request)
    {
        $data = $request->validate([
            'company_name' => ['required', 'string', 'max:255'],
            'company_email' => ['required', 'email', 'max:255'],
            'brand_primary' => ['required', 'string', 'max:20'],
            'brand_secondary' => ['required', 'string', 'max:20'],
            'terms_url' => ['nullable', 'url', 'max:255'],
            'privacy_url' => ['nullable', 'url', 'max:255'],
            'logo' => ['nullable', 'image', 'max:2048'],
        ]);

        $logoPath = PlatformSetting::getValue('saas.logo', '');
        if ($request->hasFile('logo')) {
            if ($logoPath) {
                Storage::disk('public')->delete($logoPath);
            }
            $logoPath = $request->file('logo')->store('platform', 'public');
        }

        PlatformSetting::setValue('saas', 'saas.company_name', $data['company_name']);
        PlatformSetting::setValue('saas', 'saas.company_email', $data['company_email']);
        PlatformSetting::setValue('saas', 'saas.brand_primary', $data['brand_primary']);
        PlatformSetting::setValue('saas', 'saas.brand_secondary', $data['brand_secondary']);
        PlatformSetting::setValue('saas', 'saas.logo', $logoPath);
        PlatformSetting::setValue('saas', 'saas.terms_url', $data['terms_url'] ?? '');
        PlatformSetting::setValue('saas', 'saas.privacy_url', $data['privacy_url'] ?? '');

        AuditLogger::log('saas_configuration_updated', 'platform_settings', null, 'SaaS platform settings updated.', $data, $request);

        return back()->with('success', 'SaaS configuration updated.');
    }
}
