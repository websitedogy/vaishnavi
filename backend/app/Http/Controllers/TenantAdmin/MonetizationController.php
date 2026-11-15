<?php

namespace App\Http\Controllers\TenantAdmin;

use App\Http\Controllers\Controller;
use App\Http\Controllers\TenantAdmin\Concerns\ResolvesTenant;
use App\Models\Coupon;
use App\Models\SubscriptionPlan;
use Illuminate\Http\Request;

class MonetizationController extends Controller
{
    use ResolvesTenant;

    public function index()
    {
        $plans = SubscriptionPlan::where('tenant_id', $this->tenantId())->get();
        $coupons = Coupon::where('tenant_id', $this->tenantId())->latest()->get();

        return view('tenant-admin.monetization.index', compact('plans', 'coupons'));
    }

    public function storePlan(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'price' => 'required|numeric|min:0',
            'interval' => 'required|in:monthly,yearly',
            'features' => 'nullable|string',
        ]);
        $data['tenant_id'] = $this->tenantId();
        $data['features'] = array_filter(array_map('trim', explode("\n", $data['features'] ?? '')));
        SubscriptionPlan::create($data);

        return back()->with('success', 'Subscription plan added.');
    }

    public function storeCoupon(Request $request)
    {
        $data = $request->validate([
            'code' => 'required|string|max:50',
            'discount_percent' => 'required|integer|min:1|max:100',
            'expires_at' => 'nullable|date',
        ]);
        $data['tenant_id'] = $this->tenantId();
        $data['code'] = strtoupper($data['code']);
        Coupon::create($data);

        return back()->with('success', 'Coupon created.');
    }
}
