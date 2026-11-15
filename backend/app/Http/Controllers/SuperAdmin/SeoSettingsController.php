<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\PlatformSetting;
use Illuminate\Http\Request;

class SeoSettingsController extends Controller
{
    public function index()
    {
        $settings = [
            'sitemap_url' => PlatformSetting::getValue('seo.sitemap_url', url('/sitemap.xml')),
            'robots_txt' => PlatformSetting::getValue('seo.robots_txt', "User-agent: *\nAllow: /\nSitemap: ".url('/sitemap.xml')),
            'meta_title_default' => PlatformSetting::getValue('seo.meta_title_default', 'News SaaS Platform'),
            'meta_description_default' => PlatformSetting::getValue('seo.meta_description_default', 'Multi-tenant news platform for digital publishers.'),
        ];

        return view('super-admin.seo.index', compact('settings'));
    }

    public function update(Request $request)
    {
        $data = $request->validate([
            'sitemap_url' => ['required', 'url', 'max:255'],
            'robots_txt' => ['required', 'string'],
            'meta_title_default' => ['required', 'string', 'max:255'],
            'meta_description_default' => ['required', 'string', 'max:500'],
        ]);

        PlatformSetting::setValue('seo', 'seo.sitemap_url', $data['sitemap_url']);
        PlatformSetting::setValue('seo', 'seo.robots_txt', $data['robots_txt']);
        PlatformSetting::setValue('seo', 'seo.meta_title_default', $data['meta_title_default']);
        PlatformSetting::setValue('seo', 'seo.meta_description_default', $data['meta_description_default']);

        return back()->with('success', 'SEO settings updated.');
    }
}
