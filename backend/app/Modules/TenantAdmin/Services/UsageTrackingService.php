<?php

namespace App\Modules\TenantAdmin\Services;

use App\Modules\SuperAdmin\Models\Tenant;
use App\Modules\SuperAdmin\Models\SubscriptionPackage;
use App\Modules\TenantAdmin\Models\Edition;
use App\Modules\TenantAdmin\Models\TenantUpgradeRequest;
use App\Models\User;
use Carbon\Carbon;

class UsageTrackingService
{
    public function getSubscriptionUsage(int $tenantId): array
    {
        $tenant = Tenant::findOrFail($tenantId);
        $package = null;

        if ($tenant->package_id) {
            $package = SubscriptionPackage::with('entitlements')->find($tenant->package_id);
        }

        $now = Carbon::now();
        $startOfMonth = $now->copy()->startOfMonth();

        // Calculate usage metrics
        $storageUsedMb = (float)($tenant->storage_used_mb ?? 0);
        $storageLimitMb = (int)($tenant->storage_limit_mb ?? ($package?->entitlements?->storage_limit_gb ? $package->entitlements->storage_limit_gb * 1024 : 51200));

        $monthlyEditionsCount = Edition::where('tenant_id', $tenantId)->where('created_at', '>=', $startOfMonth)->count();
        $monthlyEditionsLimit = $package?->entitlements?->monthly_edition_limit ?? 31;

        $staffCount = User::where('tenant_id', $tenantId)->where('is_active', true)->count();
        $staffLimit = $package?->entitlements?->admin_user_limit ?? 5;

        $domainsCount = \App\Modules\SuperAdmin\Models\TenantDomain::where('tenant_id', $tenantId)->count();
        $domainsLimit = $package?->entitlements?->max_domains ?? 1;

        $pdfUploadLimitMb = $package?->entitlements?->pdf_upload_limit_mb ?? 250;

        return [
            'subscription' => [
                'package_name' => $package ? $package->name : ($tenant->subscription ?? 'Professional Publisher'),
                'package_slug' => $package?->slug ?? 'professional-publisher',
                'status' => $tenant->status,
                'billing_cycle' => $tenant->billing_cycle ?? 'monthly',
                'amount' => $tenant->subscription_amount ?? 14999.00,
                'starts_at' => $tenant->created_at ? Carbon::parse($tenant->created_at)->format('Y-m-d') : '2026-01-01',
                'expires_at' => $tenant->subscription_ends_at ? Carbon::parse($tenant->subscription_ends_at)->format('Y-m-d') : '2026-12-31',
            ],
            'quotas' => [
                'storage' => [
                    'used_mb' => $storageUsedMb,
                    'limit_mb' => $storageLimitMb,
                    'used_gb' => round($storageUsedMb / 1024, 2),
                    'limit_gb' => round($storageLimitMb / 1024, 1),
                    'percent' => $storageLimitMb > 0 ? min(round(($storageUsedMb / $storageLimitMb) * 100, 1), 100) : 0,
                ],
                'monthly_editions' => [
                    'used' => $monthlyEditionsCount,
                    'limit' => $monthlyEditionsLimit,
                    'percent' => $monthlyEditionsLimit > 0 ? min(round(($monthlyEditionsCount / $monthlyEditionsLimit) * 100, 1), 100) : 0,
                ],
                'staff_users' => [
                    'used' => $staffCount,
                    'limit' => $staffLimit,
                    'percent' => $staffLimit > 0 ? min(round(($staffCount / $staffLimit) * 100, 1), 100) : 0,
                ],
                'custom_domains' => [
                    'used' => $domainsCount,
                    'limit' => $domainsLimit,
                ],
                'pdf_upload_limit_mb' => $pdfUploadLimitMb,
            ],
            'available_packages' => SubscriptionPackage::where('is_active', true)->get(['id', 'name', 'slug', 'price', 'billing_cycle', 'description']),
        ];
    }

    public function submitUpgradeRequest(int $tenantId, int $packageId, ?string $notes = null, ?int $userId = null): TenantUpgradeRequest
    {
        SubscriptionPackage::findOrFail($packageId);

        $request = TenantUpgradeRequest::create([
            'tenant_id' => $tenantId,
            'requested_by_user_id' => $userId,
            'target_package_id' => $packageId,
            'notes' => $notes,
            'status' => 'pending',
        ]);

        TenantAuditHelper::log($tenantId, $userId, 'upgrade_requested', 'subscription', "Submitted upgrade request to package #{$packageId}.");

        return $request;
    }
}
