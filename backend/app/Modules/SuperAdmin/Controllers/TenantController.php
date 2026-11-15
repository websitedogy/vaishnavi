<?php

namespace App\Modules\SuperAdmin\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\SuperAdmin\Models\Tenant;
use App\Modules\SuperAdmin\Models\TenantDomain;
use App\Modules\SuperAdmin\Models\TenantSubscription;
use App\Modules\SuperAdmin\Models\Payment;
use App\Modules\SuperAdmin\Models\TenantBranding;
use App\Modules\SuperAdmin\Models\SupportTicket;
use App\Modules\SuperAdmin\Services\TenantProvisioningService;
use App\Shared\Traits\ApiResponseTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TenantController extends Controller
{
    use ApiResponseTrait;

    protected TenantProvisioningService $provisioningService;

    public function __construct(TenantProvisioningService $provisioningService)
    {
        $this->provisioningService = $provisioningService;
    }

    public function index(Request $request)
    {
        $query = Tenant::with(['primaryDomain', 'package']);

        // Filter by Status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Filter by State / District
        if ($request->filled('state')) {
            $query->where('state', $request->state);
        }
        if ($request->filled('district')) {
            $query->where('district', $request->district);
        }

        // Search Filter
        if ($request->filled('search')) {
            $s = trim($request->search);
            $query->where(function ($q) use ($s) {
                $q->where('name', 'like', "%{$s}%")
                  ->orWhere('company_name', 'like', "%{$s}%")
                  ->orWhere('email', 'like', "%{$s}%")
                  ->orWhere('phone', 'like', "%{$s}%")
                  ->orWhere('domain', 'like', "%{$s}%");
            });
        }

        $tenants = $query->orderByDesc('id')->paginate($request->integer('per_page', 15));

        return $this->successResponse($tenants);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'company_name' => 'nullable|string|max:255',
            'contact_person' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'phone' => 'nullable|string|max:30',
            'state' => 'nullable|string|max:100',
            'district' => 'nullable|string|max:100',
            'mandal' => 'nullable|string|max:100',
            'address' => 'nullable|string|max:500',
            'slug' => 'nullable|string|max:100',
            'primary_domain' => 'nullable|string|max:255',
            'package_id' => 'nullable|integer|exists:subscription_packages,id',
            'billing_cycle' => 'nullable|string|in:monthly,quarterly,half_yearly,yearly',
            'admin_name' => 'nullable|string|max:255',
            'admin_email' => 'nullable|email|max:255',
            'admin_phone' => 'nullable|string|max:30',
            'primary_color' => 'nullable|string|max:20',
            'secondary_color' => 'nullable|string|max:20',
            'status' => 'nullable|string|in:active,trial,pending',
        ]);

        $adminId = $request->user() ? $request->user()->id : null;
        $tenant = $this->provisioningService->createTenantFromWizard($validated, $adminId);

        return $this->successResponse($tenant, 'Tenant publisher registered and provisioned successfully.', 201);
    }

    public function show(int $id)
    {
        $tenant = Tenant::with([
            'primaryDomain',
            'domains',
            'contacts',
            'activeSubscription.package.entitlements',
            'branding',
            'invoices' => fn($q) => $q->orderByDesc('id')->limit(10),
            'payments' => fn($q) => $q->orderByDesc('id')->limit(10),
            'editions' => fn($q) => $q->orderByDesc('id')->limit(10),
        ])->findOrFail($id);

        // Fetch audit logs and status history
        $activityLogs = DB::table('audit_logs')
            ->where('entity_type', 'Tenant')
            ->where('entity_id', $id)
            ->orderByDesc('performed_at')
            ->limit(15)
            ->get();

        $provisioningLogs = DB::table('tenant_provisioning_logs')
            ->where('tenant_id', $id)
            ->orderByDesc('id')
            ->get();

        $supportTickets = SupportTicket::where('tenant_id', $id)->orderByDesc('id')->limit(5)->get();

        return $this->successResponse([
            'tenant' => $tenant,
            'activity_logs' => $activityLogs,
            'provisioning_logs' => $provisioningLogs,
            'support_tickets' => $supportTickets,
        ]);
    }

    public function update(Request $request, int $id)
    {
        $tenant = Tenant::findOrFail($id);

        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'company_name' => 'nullable|string|max:255',
            'contact_person' => 'nullable|string|max:255',
            'email' => 'sometimes|required|email|max:255',
            'phone' => 'nullable|string|max:30',
            'state' => 'nullable|string|max:100',
            'district' => 'nullable|string|max:100',
            'mandal' => 'nullable|string|max:100',
            'address' => 'nullable|string|max:500',
            'default_language' => 'nullable|string|max:10',
            'timezone' => 'nullable|string|max:50',
        ]);

        $tenant->update($validated);

        return $this->successResponse($tenant, 'Tenant updated successfully.');
    }

    public function activate(Request $request, int $id)
    {
        $adminId = $request->user() ? $request->user()->id : null;
        $tenant = $this->provisioningService->activate($id, $adminId);

        return $this->successResponse($tenant, "Tenant '{$tenant->name}' has been activated.");
    }

    public function suspend(Request $request, int $id)
    {
        $request->validate([
            'reason' => 'required|string|max:500',
        ]);

        $adminId = $request->user() ? $request->user()->id : null;
        $tenant = $this->provisioningService->suspend($id, $request->reason, $adminId);

        return $this->successResponse($tenant, "Tenant '{$tenant->name}' has been suspended.");
    }

    public function reactivate(Request $request, int $id)
    {
        $adminId = $request->user() ? $request->user()->id : null;
        $tenant = $this->provisioningService->reactivate($id, $adminId);

        return $this->successResponse($tenant, "Tenant '{$tenant->name}' reactivated.");
    }

    public function renew(Request $request, int $id)
    {
        $request->validate([
            'months' => 'nullable|integer|min:1|max:36',
        ]);

        $adminId = $request->user() ? $request->user()->id : null;
        $tenant = $this->provisioningService->renew($id, $request->integer('months', 1), $adminId);

        return $this->successResponse($tenant, "Tenant subscription renewed.");
    }

    public function archive(Request $request, int $id)
    {
        $adminId = $request->user() ? $request->user()->id : null;
        $tenant = $this->provisioningService->archive($id, $adminId);

        return $this->successResponse($tenant, "Tenant archived.");
    }

    public function destroy(Request $request, int $id)
    {
        $request->validate([
            'confirmation_code' => 'required|string',
        ]);

        $adminId = $request->user() ? $request->user()->id : null;
        $this->provisioningService->controlledDelete($id, $request->confirmation_code, $adminId);

        return $this->successResponse(null, 'Tenant has been permanently deleted in accordance with data retention policies.');
    }
}
