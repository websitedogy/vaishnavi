<?php

namespace App\Http\Controllers\TenantAdmin;

use App\Http\Controllers\Controller;
use App\Http\Controllers\TenantAdmin\Concerns\ResolvesTenant;
use App\Models\NewsPost;
use App\Services\TenantSettingsService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ModuleSettingsController extends Controller
{
    use ResolvesTenant;

    public function __construct(protected TenantSettingsService $settings) {}

    public function seo()
    {
        return view('tenant-admin.modules.seo', ['settings' => $this->settings->get($this->tenantId(), 'seo')]);
    }

    public function updateSeo(Request $request)
    {
        $this->settings->save($this->tenantId(), 'seo', $request->except('_token'));

        return back()->with('success', 'SEO settings saved.');
    }

    public function theme()
    {
        return view('tenant-admin.modules.theme', ['settings' => $this->settings->get($this->tenantId(), 'theme')]);
    }

    public function updateTheme(Request $request)
    {
        $data = $request->except('_token', 'logo', 'favicon');
        $dir = 'tenants/'.$this->tenantId().'/branding';
        if ($request->hasFile('logo')) {
            $data['logo'] = $request->file('logo')->store($dir, 'public');
        }
        if ($request->hasFile('favicon')) {
            $data['favicon'] = $request->file('favicon')->store($dir, 'public');
        }
        $this->settings->save($this->tenantId(), 'theme', $data);

        return back()->with('success', 'Theme customization saved.');
    }

    public function breaking()
    {
        $articles = NewsPost::where('tenant_id', $this->tenantId())->where('status', 'published')->latest()->limit(20)->get(['id', 'title']);

        return view('tenant-admin.modules.breaking', [
            'settings' => $this->settings->get($this->tenantId(), 'breaking'),
            'articles' => $articles,
        ]);
    }

    public function updateBreaking(Request $request)
    {
        $this->settings->save($this->tenantId(), 'breaking', $request->except('_token'));

        return back()->with('success', 'Breaking news settings saved.');
    }

    public function social()
    {
        return view('tenant-admin.modules.social', ['settings' => $this->settings->get($this->tenantId(), 'social')]);
    }

    public function updateSocial(Request $request)
    {
        $this->settings->save($this->tenantId(), 'social', $request->except('_token'));

        return back()->with('success', 'Social media settings saved.');
    }

    public function security()
    {
        $logins = \App\Models\LoginLog::where('guard', 'tenant_admin')
            ->whereIn('email', $this->tenant()->users()->pluck('email'))
            ->latest()->limit(20)->get();

        return view('tenant-admin.modules.security', [
            'settings' => $this->settings->get($this->tenantId(), 'security'),
            'logins' => $logins,
        ]);
    }

    public function updateSecurity(Request $request)
    {
        $this->settings->save($this->tenantId(), 'security', $request->except('_token'));

        return back()->with('success', 'Security settings saved.');
    }

    public function integrations()
    {
        $apiKeys = \App\Models\TenantApiKey::where('tenant_id', $this->tenantId())->get();

        return view('tenant-admin.modules.integrations', [
            'settings' => $this->settings->get($this->tenantId(), 'integrations'),
            'apiKeys' => $apiKeys,
        ]);
    }

    public function updateIntegrations(Request $request)
    {
        $this->settings->save($this->tenantId(), 'integrations', $request->except('_token'));

        return back()->with('success', 'Integration settings saved.');
    }

    public function mobile()
    {
        return view('tenant-admin.modules.mobile', ['settings' => $this->settings->get($this->tenantId(), 'mobile')]);
    }

    public function updateMobile(Request $request)
    {
        $this->settings->save($this->tenantId(), 'mobile', $request->except('_token'));

        return back()->with('success', 'Mobile app settings saved.');
    }

    public function monetizationSettings()
    {
        return view('tenant-admin.modules.monetization-settings', [
            'settings' => $this->settings->get($this->tenantId(), 'monetization'),
        ]);
    }

    public function updateMonetizationSettings(Request $request)
    {
        $this->settings->save($this->tenantId(), 'monetization', $request->except('_token'));

        return back()->with('success', 'Monetization settings saved.');
    }

    public function sitemap()
    {
        $posts = NewsPost::where('tenant_id', $this->tenantId())->where('status', 'published')->get(['slug', 'updated_at']);

        return response()->view('tenant-admin.modules.sitemap-xml', compact('posts'))
            ->header('Content-Type', 'application/xml');
    }
}
