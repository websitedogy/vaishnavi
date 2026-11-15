<?php

namespace App\Modules\SuperAdmin\Services;

use App\Modules\SuperAdmin\Models\Tenant;
use App\Modules\SuperAdmin\Models\TenantContact;
use App\Modules\SuperAdmin\Models\TenantDomain;
use App\Modules\SuperAdmin\Models\TenantBranding;
use App\Modules\SuperAdmin\Models\TenantSubscription;
use App\Modules\SuperAdmin\Models\TenantAdminInvitation;
use App\Modules\SuperAdmin\Models\SubscriptionPackage;
use App\Shared\Helpers\AuditHelper;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Carbon\Carbon;

class TenantProvisioningService
{
    /**
     * Executes the complete 7-step Tenant Creation Wizard in a single atomic database transaction.
     */
    public function createTenantFromWizard(array $data, ?int $adminId = null): Tenant
    {
        return DB::transaction(function () use ($data, $adminId) {
            $now = Carbon::now();

            // 1. Publisher Information & Configuration
            $slug = Str::slug($data['slug'] ?? $data['name']);
            $uniqueSlug = $this->ensureUniqueSlug($slug);

            $packageId = $data['package_id'] ?? null;
            $package = $packageId ? SubscriptionPackage::with('entitlements')->find($packageId) : null;
            $storageLimitMb = $package && $package->entitlements ? ($package->entitlements->storage_limit_gb * 1024) : 10240;

            $initialStatus = $data['status'] ?? 'active';
            $trialDays = $package ? $package->trial_days : 14;
            $trialEndsAt = ($initialStatus === 'trial') ? $now->copy()->addDays($trialDays) : null;
            $subscriptionEndsAt = ($initialStatus === 'active') ? $now->copy()->addMonths($data['billing_cycle'] === 'yearly' ? 12 : 1) : null;

            $tenant = Tenant::create([
                'name' => $data['name'],
                'company_name' => $data['company_name'] ?? $data['name'],
                'contact_person' => $data['contact_person'] ?? null,
                'slug' => $uniqueSlug,
                'email' => $data['email'],
                'phone' => $data['phone'] ?? null,
                'address' => $data['address'] ?? null,
                'state' => $data['state'] ?? null,
                'district' => $data['district'] ?? null,
                'mandal' => $data['mandal'] ?? null,
                'domain' => $data['primary_domain'] ?? null,
                'subscription' => $package ? $package->name : 'Custom',
                'package_id' => $packageId,
                'subscription_amount' => $package ? $package->price : 0,
                'billing_cycle' => $data['billing_cycle'] ?? 'monthly',
                'storage_limit_mb' => $storageLimitMb,
                'storage_used_mb' => 0,
                'default_language' => $data['default_language'] ?? 'en',
                'timezone' => $data['timezone'] ?? 'Asia/Kolkata',
                'database_name' => 'tenant_' . str_replace('-', '_', $uniqueSlug),
                'storage_bucket' => config('filesystems.disks.s3.bucket', 'dogy-epaper-storage'),
                'storage_prefix' => 'tenants/' . $uniqueSlug . '/',
                'status' => $initialStatus,
                'trial_ends_at' => $trialEndsAt,
                'subscription_ends_at' => $subscriptionEndsAt,
            ]);

            // Provisioning Log: Step 1 & 2
            $this->logProvisioningStep($tenant->id, 'tenant_record_created', 'completed', 'Tenant publisher record created with unique slug and storage configuration.');

            // 2. Primary Contact
            TenantContact::create([
                'tenant_id' => $tenant->id,
                'name' => $data['contact_person'] ?? $data['name'],
                'designation' => $data['designation'] ?? 'Publisher / Managing Director',
                'email' => $data['email'],
                'phone' => $data['phone'] ?? null,
                'is_primary' => true,
            ]);

            // 3. Domain Configuration
            if (!empty($data['primary_domain'])) {
                $domainHost = strtolower(trim($data['primary_domain']));
                $token = 'dogy-vfy-' . Str::random(24);
                
                TenantDomain::create([
                    'tenant_id' => $tenant->id,
                    'type' => 'custom',
                    'host' => $domainHost,
                    'is_primary' => true,
                    'verification_status' => 'pending',
                    'verification_token' => $token,
                    'txt_record_host' => '_dogy-challenge.' . $domainHost,
                    'txt_record_value' => 'dogy-token=' . $token,
                    'cname_host' => $domainHost,
                    'cname_target' => 'cname.dogyepaper.com',
                    'ssl_status' => 'pending',
                    'ssl_auto' => true,
                    'health_status' => 'pending_dns',
                ]);

                $this->logProvisioningStep($tenant->id, 'domain_dns_setup', 'pending', "DNS challenge generated for {$domainHost}. Verification required.");
            }

            // 4. Subscription & Entitlements
            if ($package) {
                TenantSubscription::create([
                    'tenant_id' => $tenant->id,
                    'package_id' => $package->id,
                    'status' => $initialStatus,
                    'billing_cycle' => $data['billing_cycle'] ?? 'monthly',
                    'amount' => $package->price,
                    'currency' => $package->currency,
                    'current_period_start' => $now,
                    'current_period_end' => $subscriptionEndsAt,
                    'trial_ends_at' => $trialEndsAt,
                    'auto_renew' => true,
                ]);

                $this->logProvisioningStep($tenant->id, 'subscription_provisioned', 'completed', "Subscribed to package {$package->name}.");
            }

            // 5. Tenant Admin Invitation
            $invitationToken = Str::random(48);
            TenantAdminInvitation::create([
                'tenant_id' => $tenant->id,
                'name' => $data['admin_name'] ?? $data['contact_person'] ?? $data['name'],
                'email' => $data['admin_email'] ?? $data['email'],
                'phone' => $data['admin_phone'] ?? $data['phone'] ?? null,
                'token' => $invitationToken,
                'status' => 'pending',
                'expires_at' => $now->copy()->addDays(7),
            ]);

            $this->logProvisioningStep($tenant->id, 'admin_invitation_dispatched', 'completed', 'Tenant administrator secure onboarding invitation generated.');

            // 6. Branding Setup
            TenantBranding::create([
                'tenant_id' => $tenant->id,
                'newspaper_name' => $data['name'],
                'logo_url' => $data['logo_url'] ?? null,
                'favicon_url' => $data['favicon_url'] ?? null,
                'primary_color' => $data['primary_color'] ?? '#2563EB',
                'secondary_color' => $data['secondary_color'] ?? '#0EA5E9',
                'typography' => $data['typography'] ?? 'Inter, sans-serif',
                'header_config' => ['sticky' => true, 'show_date' => true, 'show_edition_picker' => true],
                'footer_config' => ['show_copyright' => true, 'copyright_text' => '© ' . date('Y') . ' ' . $data['name']],
                'is_published' => true,
                'version' => 1,
            ]);

            $this->logProvisioningStep($tenant->id, 'branding_initialized', 'completed', 'Default tenant white-label branding configured.');

            // Audit Log
            AuditHelper::log(
                $adminId,
                'TENANT_CREATED',
                'Tenant',
                $tenant->id,
                "New tenant '{$tenant->name}' created via Super Admin Wizard.",
                ['slug' => $tenant->slug, 'package' => $package ? $package->name : 'None', 'status' => $tenant->status]
            );

            return $tenant->load(['primaryDomain', 'contacts', 'activeSubscription.package', 'branding']);
        });
    }

    public function activate(int $tenantId, ?int $adminId = null): Tenant
    {
        $tenant = Tenant::findOrFail($tenantId);
        $prevStatus = $tenant->status;
        $tenant->status = 'active';
        $tenant->suspended_at = null;
        if (!$tenant->subscription_ends_at || $tenant->subscription_ends_at->isPast()) {
            $tenant->subscription_ends_at = Carbon::now()->addMonth();
        }
        $tenant->save();

        $this->recordStatusHistory($tenant->id, $prevStatus, 'active', 'Activated by Super Admin', $adminId);
        AuditHelper::log($adminId, 'TENANT_ACTIVATED', 'Tenant', $tenant->id, "Tenant '{$tenant->name}' activated.");

        return $tenant;
    }

    public function suspend(int $tenantId, string $reason, ?int $adminId = null): Tenant
    {
        $tenant = Tenant::findOrFail($tenantId);
        $prevStatus = $tenant->status;
        $tenant->status = 'suspended';
        $tenant->suspended_at = Carbon::now();
        $tenant->save();

        $this->recordStatusHistory($tenant->id, $prevStatus, 'suspended', $reason, $adminId);
        AuditHelper::log($adminId, 'TENANT_SUSPENDED', 'Tenant', $tenant->id, "Tenant '{$tenant->name}' suspended: {$reason}");

        return $tenant;
    }

    public function reactivate(int $tenantId, ?int $adminId = null): Tenant
    {
        return $this->activate($tenantId, $adminId);
    }

    public function renew(int $tenantId, int $months = 1, ?int $adminId = null): Tenant
    {
        $tenant = Tenant::findOrFail($tenantId);
        $now = Carbon::now();
        $currentEnd = ($tenant->subscription_ends_at && $tenant->subscription_ends_at->isFuture()) 
            ? $tenant->subscription_ends_at 
            : $now;

        $tenant->subscription_ends_at = $currentEnd->copy()->addMonths($months);
        $tenant->status = 'active';
        $tenant->save();

        AuditHelper::log($adminId, 'TENANT_RENEWED', 'Tenant', $tenant->id, "Tenant '{$tenant->name}' renewed for {$months} months.");
        return $tenant;
    }

    public function archive(int $tenantId, ?int $adminId = null): Tenant
    {
        $tenant = Tenant::findOrFail($tenantId);
        $prevStatus = $tenant->status;
        $tenant->status = 'archived';
        $tenant->save();

        $this->recordStatusHistory($tenant->id, $prevStatus, 'archived', 'Tenant archived by Super Admin', $adminId);
        AuditHelper::log($adminId, 'TENANT_ARCHIVED', 'Tenant', $tenant->id, "Tenant '{$tenant->name}' moved to archive.");

        return $tenant;
    }

    public function controlledDelete(int $tenantId, string $confirmationCode, ?int $adminId = null): bool
    {
        $tenant = Tenant::findOrFail($tenantId);

        if ($confirmationCode !== 'DELETE-' . strtoupper($tenant->slug)) {
            throw new \InvalidArgumentException("Invalid deletion confirmation code. Expected 'DELETE-" . strtoupper($tenant->slug) . "'.");
        }

        AuditHelper::log($adminId, 'TENANT_PERMANENTLY_DELETED', 'Tenant', $tenant->id, "Tenant '{$tenant->name}' [slug: {$tenant->slug}] permanently deleted.");

        $tenant->delete();
        return true;
    }

    protected function ensureUniqueSlug(string $slug): string
    {
        $base = $slug;
        $count = 1;
        while (Tenant::where('slug', $slug)->exists()) {
            $slug = $base . '-' . (++$count);
        }
        return $slug;
    }

    protected function logProvisioningStep(int $tenantId, string $step, string $status, ?string $message = null): void
    {
        DB::table('tenant_provisioning_logs')->insert([
            'tenant_id' => $tenantId,
            'step' => $step,
            'status' => $status,
            'message' => $message,
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ]);
    }

    protected function recordStatusHistory(int $tenantId, ?string $prev, string $new, ?string $reason, ?int $adminId): void
    {
        DB::table('tenant_status_history')->insert([
            'tenant_id' => $tenantId,
            'previous_status' => $prev,
            'new_status' => $new,
            'reason' => $reason,
            'changed_by_admin_id' => $adminId,
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ]);
    }
}
