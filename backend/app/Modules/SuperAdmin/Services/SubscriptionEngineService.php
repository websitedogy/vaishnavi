<?php

namespace App\Modules\SuperAdmin\Services;

use App\Modules\SuperAdmin\Models\SubscriptionPackage;
use App\Modules\SuperAdmin\Models\PackageEntitlement;
use App\Modules\SuperAdmin\Models\TenantSubscription;
use App\Modules\SuperAdmin\Models\SubscriptionInvoice;
use App\Modules\SuperAdmin\Models\Tenant;
use App\Shared\Helpers\AuditHelper;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Carbon\Carbon;

class SubscriptionEngineService
{
    public function createPackage(array $data, ?int $adminId = null): SubscriptionPackage
    {
        return DB::transaction(function () use ($data, $adminId) {
            $package = SubscriptionPackage::create([
                'name' => $data['name'],
                'slug' => Str::slug($data['slug'] ?? $data['name']),
                'description' => $data['description'] ?? null,
                'billing_cycle' => $data['billing_cycle'] ?? 'monthly',
                'price' => $data['price'] ?? 0,
                'currency' => $data['currency'] ?? 'INR',
                'trial_days' => $data['trial_days'] ?? 14,
                'is_active' => $data['is_active'] ?? true,
                'is_popular' => $data['is_popular'] ?? false,
                'is_custom' => $data['is_custom'] ?? false,
                'version' => 1,
            ]);

            PackageEntitlement::create([
                'package_id' => $package->id,
                'max_domains' => $data['max_domains'] ?? 1,
                'storage_limit_gb' => $data['storage_limit_gb'] ?? 10,
                'pdf_upload_limit_mb' => $data['pdf_upload_limit_mb'] ?? 100,
                'monthly_edition_limit' => $data['monthly_edition_limit'] ?? 31,
                'admin_user_limit' => $data['admin_user_limit'] ?? 3,
                'custom_branding_enabled' => $data['custom_branding_enabled'] ?? true,
                'priority_support_enabled' => $data['priority_support_enabled'] ?? false,
                'analytics_enabled' => $data['analytics_enabled'] ?? true,
                'custom_entitlements' => $data['custom_entitlements'] ?? null,
            ]);

            AuditHelper::log($adminId, 'PACKAGE_CREATED', 'SubscriptionPackage', $package->id, "Package '{$package->name}' created.");

            return $package->load('entitlements');
        });
    }

    public function updatePackage(int $packageId, array $data, ?int $adminId = null): SubscriptionPackage
    {
        return DB::transaction(function () use ($packageId, $data, $adminId) {
            $package = SubscriptionPackage::findOrFail($packageId);
            $package->increment('version');

            $package->update([
                'name' => $data['name'] ?? $package->name,
                'description' => $data['description'] ?? $package->description,
                'price' => $data['price'] ?? $package->price,
                'currency' => $data['currency'] ?? $package->currency,
                'billing_cycle' => $data['billing_cycle'] ?? $package->billing_cycle,
                'trial_days' => $data['trial_days'] ?? $package->trial_days,
                'is_active' => $data['is_active'] ?? $package->is_active,
                'is_popular' => $data['is_popular'] ?? $package->is_popular,
            ]);

            if ($package->entitlements) {
                $package->entitlements->update([
                    'max_domains' => $data['max_domains'] ?? $package->entitlements->max_domains,
                    'storage_limit_gb' => $data['storage_limit_gb'] ?? $package->entitlements->storage_limit_gb,
                    'pdf_upload_limit_mb' => $data['pdf_upload_limit_mb'] ?? $package->entitlements->pdf_upload_limit_mb,
                    'monthly_edition_limit' => $data['monthly_edition_limit'] ?? $package->entitlements->monthly_edition_limit,
                    'admin_user_limit' => $data['admin_user_limit'] ?? $package->entitlements->admin_user_limit,
                    'custom_branding_enabled' => $data['custom_branding_enabled'] ?? $package->entitlements->custom_branding_enabled,
                    'priority_support_enabled' => $data['priority_support_enabled'] ?? $package->entitlements->priority_support_enabled,
                    'analytics_enabled' => $data['analytics_enabled'] ?? $package->entitlements->analytics_enabled,
                ]);
            }

            AuditHelper::log($adminId, 'PACKAGE_UPDATED', 'SubscriptionPackage', $package->id, "Package '{$package->name}' updated to v{$package->version}.");

            return $package->fresh(['entitlements']);
        });
    }

    public function changeTenantPackage(int $tenantId, int $newPackageId, ?int $adminId = null): TenantSubscription
    {
        return DB::transaction(function () use ($tenantId, $newPackageId, $adminId) {
            $tenant = Tenant::findOrFail($tenantId);
            $newPackage = SubscriptionPackage::with('entitlements')->findOrFail($newPackageId);
            $now = Carbon::now();

            $oldPackageId = $tenant->package_id;

            // Update Tenant record
            $tenant->package_id = $newPackage->id;
            $tenant->subscription = $newPackage->name;
            $tenant->subscription_amount = $newPackage->price;
            $tenant->storage_limit_mb = $newPackage->entitlements ? ($newPackage->entitlements->storage_limit_gb * 1024) : 10240;
            $tenant->subscription_ends_at = $now->copy()->addMonths($newPackage->billing_cycle === 'yearly' ? 12 : 1);
            $tenant->save();

            // Create new Subscription record
            $sub = TenantSubscription::create([
                'tenant_id' => $tenant->id,
                'package_id' => $newPackage->id,
                'status' => 'active',
                'billing_cycle' => $newPackage->billing_cycle,
                'amount' => $newPackage->price,
                'currency' => $newPackage->currency,
                'current_period_start' => $now,
                'current_period_end' => $tenant->subscription_ends_at,
                'auto_renew' => true,
            ]);

            // Subscription History
            DB::table('subscription_history')->insert([
                'tenant_id' => $tenant->id,
                'package_id' => $newPackage->id,
                'event' => ($newPackage->price >= $tenant->subscription_amount) ? 'upgraded' : 'downgraded',
                'old_package_id' => $oldPackageId,
                'new_package_id' => $newPackage->id,
                'notes' => "Changed to package {$newPackage->name} by Super Admin.",
                'actor_id' => $adminId,
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            // Generate Invoice
            $this->generateInvoice($tenant->id, $sub->id, $newPackage->price, $newPackage->currency);

            AuditHelper::log($adminId, 'SUBSCRIPTION_CHANGED', 'Tenant', $tenant->id, "Tenant '{$tenant->name}' package switched to '{$newPackage->name}'.");

            return $sub->load('package.entitlements');
        });
    }

    public function generateInvoice(int $tenantId, ?int $subscriptionId, float $amount, string $currency = 'INR'): SubscriptionInvoice
    {
        $now = Carbon::now();
        $tax = $amount * 0.18; // 18% GST

        return SubscriptionInvoice::create([
            'tenant_id' => $tenantId,
            'subscription_id' => $subscriptionId,
            'invoice_number' => 'INV-' . strtoupper(Str::random(4)) . '-' . date('Ymd') . '-' . rand(100, 999),
            'amount' => $amount,
            'tax_amount' => $tax,
            'currency' => $currency,
            'status' => 'pending',
            'due_date' => $now->copy()->addDays(7),
        ]);
    }
}
