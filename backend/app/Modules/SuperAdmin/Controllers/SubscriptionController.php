<?php

namespace App\Modules\SuperAdmin\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\SuperAdmin\Models\SubscriptionPackage;
use App\Modules\SuperAdmin\Models\TenantSubscription;
use App\Modules\SuperAdmin\Models\SubscriptionInvoice;
use App\Modules\SuperAdmin\Services\SubscriptionEngineService;
use App\Shared\Traits\ApiResponseTrait;
use Illuminate\Http\Request;

class SubscriptionController extends Controller
{
    use ApiResponseTrait;

    protected SubscriptionEngineService $subscriptionService;

    public function __construct(SubscriptionEngineService $subscriptionService)
    {
        $this->subscriptionService = $subscriptionService;
    }

    public function packages(Request $request)
    {
        $packages = SubscriptionPackage::with('entitlements')
            ->orderBy('price')
            ->get();

        return $this->successResponse($packages);
    }

    public function storePackage(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'billing_cycle' => 'required|string|in:monthly,quarterly,half_yearly,yearly,custom',
            'price' => 'required|numeric|min:0',
            'currency' => 'nullable|string|max:10',
            'trial_days' => 'nullable|integer|min:0',
            'is_active' => 'nullable|boolean',
            'is_popular' => 'nullable|boolean',
            'max_domains' => 'nullable|integer|min:1',
            'storage_limit_gb' => 'nullable|integer|min:1',
            'pdf_upload_limit_mb' => 'nullable|integer|min:10',
            'monthly_edition_limit' => 'nullable|integer|min:1',
            'admin_user_limit' => 'nullable|integer|min:1',
            'custom_branding_enabled' => 'nullable|boolean',
            'priority_support_enabled' => 'nullable|boolean',
            'analytics_enabled' => 'nullable|boolean',
        ]);

        $adminId = $request->user() ? $request->user()->id : null;
        $package = $this->subscriptionService->createPackage($validated, $adminId);

        return $this->successResponse($package, 'Package created successfully.', 201);
    }

    public function updatePackage(Request $request, int $id)
    {
        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'description' => 'nullable|string',
            'billing_cycle' => 'nullable|string|in:monthly,quarterly,half_yearly,yearly,custom',
            'price' => 'nullable|numeric|min:0',
            'currency' => 'nullable|string|max:10',
            'trial_days' => 'nullable|integer|min:0',
            'is_active' => 'nullable|boolean',
            'is_popular' => 'nullable|boolean',
            'max_domains' => 'nullable|integer|min:1',
            'storage_limit_gb' => 'nullable|integer|min:1',
            'pdf_upload_limit_mb' => 'nullable|integer|min:10',
            'monthly_edition_limit' => 'nullable|integer|min:1',
            'admin_user_limit' => 'nullable|integer|min:1',
            'custom_branding_enabled' => 'nullable|boolean',
            'priority_support_enabled' => 'nullable|boolean',
            'analytics_enabled' => 'nullable|boolean',
        ]);

        $adminId = $request->user() ? $request->user()->id : null;
        $package = $this->subscriptionService->updatePackage($id, $validated, $adminId);

        return $this->successResponse($package, 'Package updated to new version.');
    }

    public function subscriptions(Request $request)
    {
        $query = TenantSubscription::with(['tenant', 'package.entitlements']);

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('billing_cycle')) {
            $query->where('billing_cycle', $request->billing_cycle);
        }

        $subscriptions = $query->orderByDesc('id')->paginate($request->integer('per_page', 15));

        return $this->successResponse($subscriptions);
    }

    public function changePackage(Request $request, int $tenantId)
    {
        $request->validate([
            'new_package_id' => 'required|integer|exists:subscription_packages,id',
        ]);

        $adminId = $request->user() ? $request->user()->id : null;
        $subscription = $this->subscriptionService->changeTenantPackage($tenantId, $request->new_package_id, $adminId);

        return $this->successResponse($subscription, 'Tenant package switched and invoice generated.');
    }

    public function invoices(Request $request)
    {
        $query = SubscriptionInvoice::with(['tenant', 'subscription.package']);

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $invoices = $query->orderByDesc('id')->paginate($request->integer('per_page', 15));

        return $this->successResponse($invoices);
    }
}
