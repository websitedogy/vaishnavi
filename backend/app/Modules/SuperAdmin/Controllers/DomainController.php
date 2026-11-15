<?php

namespace App\Modules\SuperAdmin\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\SuperAdmin\Models\TenantDomain;
use App\Modules\SuperAdmin\Services\DomainSslManagerService;
use App\Shared\Traits\ApiResponseTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DomainController extends Controller
{
    use ApiResponseTrait;

    protected DomainSslManagerService $domainService;

    public function __construct(DomainSslManagerService $domainService)
    {
        $this->domainService = $domainService;
    }

    public function index(Request $request)
    {
        $query = TenantDomain::with(['tenant', 'dnsRecords', 'sslCertificates']);

        if ($request->filled('verification_status')) {
            $query->where('verification_status', $request->verification_status);
        }

        if ($request->filled('ssl_status')) {
            $query->where('ssl_status', $request->ssl_status);
        }

        if ($request->filled('search')) {
            $s = trim($request->search);
            $query->where('host', 'like', "%{$s}%")
                  ->orWhereHas('tenant', fn($q) => $q->where('name', 'like', "%{$s}%"));
        }

        $domains = $query->orderByDesc('id')->paginate($request->integer('per_page', 15));

        return $this->successResponse($domains);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'tenant_id' => 'required|integer|exists:tenants,id',
            'host' => 'required|string|max:255',
            'type' => 'nullable|string|in:custom,subdomain',
            'is_primary' => 'nullable|boolean',
        ]);

        $adminId = $request->user() ? $request->user()->id : null;
        $domain = $this->domainService->registerDomain(
            $validated['tenant_id'],
            $validated['host'],
            $validated['type'] ?? 'custom',
            $validated['is_primary'] ?? false,
            $adminId
        );

        return $this->successResponse($domain, 'Domain registered with DNS verification instructions.', 201);
    }

    public function show(int $id)
    {
        $domain = TenantDomain::with(['tenant', 'dnsRecords', 'sslCertificates'])->findOrFail($id);
        $activityLogs = DB::table('domain_activity_logs')
            ->where('domain_id', $id)
            ->orderByDesc('created_at')
            ->limit(20)
            ->get();

        return $this->successResponse([
            'domain' => $domain,
            'activity_logs' => $activityLogs,
        ]);
    }

    public function verify(Request $request, int $id)
    {
        $adminId = $request->user() ? $request->user()->id : null;
        $result = $this->domainService->verifyDomainDns($id, $adminId);

        if (!$result['verified']) {
            return $this->errorResponse($result['message'], 422, $result);
        }

        return $this->successResponse($result, $result['message']);
    }

    public function provisionSsl(Request $request, int $id)
    {
        $adminId = $request->user() ? $request->user()->id : null;
        $domain = $this->domainService->provisionAcmCertificate($id, $adminId);

        return $this->successResponse($domain, 'SSL Certificate provisioning completed and assigned to CloudFront.');
    }

    public function destroy(Request $request, int $id)
    {
        $adminId = $request->user() ? $request->user()->id : null;
        $this->domainService->disconnectDomain($id, $adminId);

        return $this->successResponse(null, 'Domain disconnected successfully.');
    }
}
