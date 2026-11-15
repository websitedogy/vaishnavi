<?php

namespace App\Http\Controllers\TenantAdmin;

use App\Enums\AdPlacement;
use App\Http\Controllers\Controller;
use App\Http\Controllers\TenantAdmin\Concerns\ResolvesTenant;
use App\Models\Advertisement;
use App\Services\StaffActivityLogger;
use App\Services\TenantSettingsService;
use Illuminate\Http\Request;

class AdvertisementController extends Controller
{
    use ResolvesTenant;

    public function __construct(protected TenantSettingsService $settings) {}

    public function index()
    {
        $this->authorizePermission('manage_ads');
        $ads = Advertisement::where('tenant_id', $this->tenantId())->latest()->paginate(12);
        $adsConfig = $this->settings->get($this->tenantId(), 'ads');
        $totalClicks = Advertisement::where('tenant_id', $this->tenantId())->sum('clicks');
        $totalImpressions = Advertisement::where('tenant_id', $this->tenantId())->sum('impressions');

        return view('tenant-admin.ads.index', compact('ads', 'adsConfig', 'totalClicks', 'totalImpressions'));
    }

    public function create()
    {
        $this->authorizePermission('manage_ads');

        return view('tenant-admin.ads.form', [
            'ad' => new Advertisement(['is_active' => true, 'ad_type' => 'banner', 'placement' => AdPlacement::Banner]),
            'placements' => AdPlacement::options(),
        ]);
    }

    public function store(Request $request)
    {
        $this->authorizePermission('manage_ads');
        $data = $this->validated($request);
        $data['tenant_id'] = $this->tenantId();
        $ad = Advertisement::create($data);
        StaffActivityLogger::log('ad_created', "Ad: {$ad->name}", $ad);

        return redirect()->route('tenant-admin.ads.index')->with('success', 'Advertisement created.');
    }

    public function edit(Advertisement $ad)
    {
        $this->ensureTenant($ad);
        $this->authorizePermission('manage_ads');

        return view('tenant-admin.ads.form', ['ad' => $ad, 'placements' => AdPlacement::options()]);
    }

    public function update(Request $request, Advertisement $ad)
    {
        $this->ensureTenant($ad);
        $this->authorizePermission('manage_ads');
        $ad->update($this->validated($request));

        return redirect()->route('tenant-admin.ads.index')->with('success', 'Advertisement updated.');
    }

    public function destroy(Advertisement $ad)
    {
        $this->ensureTenant($ad);
        $this->authorizePermission('manage_ads');
        $ad->delete();

        return back()->with('success', 'Advertisement deleted.');
    }

    public function updateAdsense(Request $request)
    {
        $this->authorizePermission('manage_ads');
        $this->settings->save($this->tenantId(), 'ads', $request->only(['adsense_publisher_id', 'adsense_auto_ads']));

        return back()->with('success', 'Google AdSense settings saved.');
    }

    protected function validated(Request $request): array
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'placement' => 'required|string',
            'ad_type' => 'required|in:banner,sidebar,header,footer,popup,video',
            'advertiser' => 'nullable|string|max:255',
            'content' => 'nullable|string',
            'image_url' => 'nullable|string|max:500',
            'video_url' => 'nullable|string|max:500',
            'link_url' => 'nullable|string|max:500',
            'adsense_slot' => 'nullable|string|max:100',
            'is_active' => 'boolean',
            'starts_at' => 'nullable|date',
            'ends_at' => 'nullable|date|after_or_equal:starts_at',
        ]);
        $data['is_active'] = $request->boolean('is_active', true);

        return $data;
    }

    protected function ensureTenant(Advertisement $ad): void
    {
        abort_unless($ad->tenant_id === $this->tenantId(), 404);
    }
}
