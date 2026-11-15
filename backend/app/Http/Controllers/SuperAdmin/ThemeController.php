<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use App\Models\TenantTheme;
use App\Services\ActivityLogger;
use Illuminate\Http\Request;

class ThemeController extends Controller
{
    public function index(Request $request)
    {
        $tenants = Tenant::with('theme')
            ->when($request->filled('search'), fn ($q) => $q->where('name', 'like', '%'.$request->search.'%'))
            ->orderBy('name')
            ->paginate(12)
            ->withQueryString();

        $presets = config('themes.presets');
        $globalStats = [
            'themed' => TenantTheme::count(),
            'live_tv' => TenantTheme::get()->filter(fn ($t) => $t->featureEnabled('live_tv'))->count(),
            'polls' => TenantTheme::get()->filter(fn ($t) => $t->featureEnabled('polls'))->count(),
        ];

        return view('super-admin.themes.index', compact('tenants', 'presets', 'globalStats'));
    }

    public function edit(Tenant $tenant)
    {
        $theme = $tenant->theme ?? new TenantTheme([
            'tenant_id' => $tenant->id,
            'features' => TenantTheme::defaultFeatures(),
        ]);

        return view('super-admin.themes.edit', [
            'tenant' => $tenant,
            'theme' => $theme,
            'presets' => config('themes.presets'),
            'layouts' => config('themes.layouts'),
            'fonts' => config('themes.fonts'),
            'features' => config('themes.features'),
        ]);
    }

    public function update(Request $request, Tenant $tenant)
    {
        $validated = $request->validate([
            'theme_preset' => ['required', 'string'],
            'layout' => ['required', 'string'],
            'font_family' => ['required', 'string'],
            'primary_color' => ['required', 'string', 'max:7'],
            'secondary_color' => ['required', 'string', 'max:7'],
            'accent_color' => ['required', 'string', 'max:7'],
            'feature_live_tv' => ['boolean'],
            'feature_video_section' => ['boolean'],
            'feature_notifications' => ['boolean'],
            'feature_polls' => ['boolean'],
        ]);

        $features = [
            'live_tv' => $request->boolean('feature_live_tv'),
            'video_section' => $request->boolean('feature_video_section'),
            'notifications' => $request->boolean('feature_notifications'),
            'polls' => $request->boolean('feature_polls'),
        ];

        $tenant->theme()->updateOrCreate(
            ['tenant_id' => $tenant->id],
            [
                'theme_preset' => $validated['theme_preset'],
                'layout' => $validated['layout'],
                'font_family' => $validated['font_family'],
                'primary_color' => $validated['primary_color'],
                'secondary_color' => $validated['secondary_color'],
                'accent_color' => $validated['accent_color'],
                'features' => $features,
            ]
        );

        ActivityLogger::log('theme_updated', 'Theme updated', "Theme settings updated for {$tenant->name}.", $tenant);

        return redirect()->route('super-admin.themes.index')
            ->with('success', "Theme updated for {$tenant->name}.");
    }
}
