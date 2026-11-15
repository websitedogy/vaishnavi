<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Enums\AdPlacement;
use App\Http\Controllers\Controller;
use App\Models\Advertisement;
use App\Models\Tenant;
use App\Services\ActivityLogger;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class AdvertisementController extends Controller
{
    public function index(Request $request)
    {
        $query = Advertisement::with('tenant')->latest();

        if ($placement = $request->get('placement')) {
            $query->where('placement', $placement);
        }

        if ($request->get('scope') === 'global') {
            $query->whereNull('tenant_id');
        } elseif ($tenantId = $request->get('tenant_id')) {
            $query->where('tenant_id', $tenantId);
        }

        $ads = $query->paginate(12)->withQueryString();

        $revenueStats = [
            'total_revenue' => Advertisement::sum('revenue'),
            'total_impressions' => Advertisement::sum('impressions'),
            'total_clicks' => Advertisement::sum('clicks'),
            'by_placement' => collect(AdPlacement::cases())->map(fn (AdPlacement $p) => [
                'placement' => $p->value,
                'label' => $p->label(),
                'revenue' => Advertisement::where('placement', $p)->sum('revenue'),
                'count' => Advertisement::where('placement', $p)->count(),
            ]),
        ];

        $tenants = Tenant::orderBy('name')->get();

        return view('super-admin.advertisements.index', compact('ads', 'revenueStats', 'tenants'));
    }

    public function create()
    {
        return view('super-admin.advertisements.create', [
            'ad' => new Advertisement(['is_active' => true]),
            'tenants' => Tenant::orderBy('name')->get(),
            'placements' => AdPlacement::cases(),
        ]);
    }

    public function store(Request $request)
    {
        $ad = Advertisement::create($this->validateAd($request));

        ActivityLogger::log('ad_created', 'Advertisement created', "{$ad->name} ({$ad->placement->label()}) was added.", $ad->tenant);

        return redirect()->route('super-admin.advertisements.index')
            ->with('success', 'Advertisement created.');
    }

    public function edit(Advertisement $advertisement)
    {
        return view('super-admin.advertisements.edit', [
            'ad' => $advertisement,
            'tenants' => Tenant::orderBy('name')->get(),
            'placements' => AdPlacement::cases(),
        ]);
    }

    public function update(Request $request, Advertisement $advertisement)
    {
        $advertisement->update($this->validateAd($request));

        ActivityLogger::log('ad_updated', 'Advertisement updated', "{$advertisement->name} was updated.", $advertisement->tenant);

        return redirect()->route('super-admin.advertisements.index')
            ->with('success', 'Advertisement updated.');
    }

    public function destroy(Advertisement $advertisement)
    {
        $name = $advertisement->name;
        $tenant = $advertisement->tenant;
        $advertisement->delete();

        ActivityLogger::log('ad_deleted', 'Advertisement removed', "{$name} was deleted.", $tenant);

        return back()->with('success', 'Advertisement deleted.');
    }

    protected function validateAd(Request $request): array
    {
        $data = $request->validate([
            'tenant_id' => ['nullable', 'exists:tenants,id'],
            'placement' => ['required', Rule::enum(AdPlacement::class)],
            'name' => ['required', 'string', 'max:255'],
            'advertiser' => ['nullable', 'string', 'max:255'],
            'content' => ['nullable', 'string'],
            'image_url' => ['nullable', 'url', 'max:500'],
            'link_url' => ['nullable', 'url', 'max:500'],
            'is_active' => ['boolean'],
            'revenue' => ['nullable', 'numeric', 'min:0'],
            'impressions' => ['nullable', 'integer', 'min:0'],
            'clicks' => ['nullable', 'integer', 'min:0'],
            'starts_at' => ['nullable', 'date'],
            'ends_at' => ['nullable', 'date', 'after_or_equal:starts_at'],
        ]);

        $data['is_active'] = $request->boolean('is_active', true);
        $data['tenant_id'] = $data['tenant_id'] ?: null;

        return $data;
    }
}
