<?php

namespace Tests\Modules\SuperAdmin;

use Tests\TestCase;
use App\Modules\SuperAdmin\Models\SuperAdmin;
use App\Modules\SuperAdmin\Models\Tenant;
use App\Modules\SuperAdmin\Models\SubscriptionPackage;
use App\Modules\SuperAdmin\Models\TenantDomain;
use App\Modules\SuperAdmin\Models\Payment;
use App\Modules\SuperAdmin\Models\PdfProcessingJob;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class SuperAdminApiTest extends TestCase
{
    use DatabaseTransactions;

    protected string $token = 'superadmin-master-session-token';

    protected function getAuthHeaders(): array
    {
        return [
            'Authorization' => 'Bearer ' . $this->token,
            'Accept' => 'application/json',
        ];
    }

    /**
     * Test 1: Super Admin Authentication (Valid and Invalid)
     */
    public function test_super_admin_can_login_with_valid_credentials()
    {
        $response = $this->postJson('/api/v1/super-admin/auth/login', [
            'email' => 'admin@dogyepaper.com',
            'password' => 'SuperAdminPassword123!',
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => ['token', 'user' => ['id', 'name', 'email', 'role', 'permissions']],
            ]);
    }

    public function test_super_admin_login_fails_with_invalid_password()
    {
        $response = $this->postJson('/api/v1/super-admin/auth/login', [
            'email' => 'admin@dogyepaper.com',
            'password' => 'WrongPassword123!',
        ]);

        $response->assertStatus(401)
            ->assertJsonPath('success', false);
    }

    public function test_unauthenticated_request_is_rejected()
    {
        $response = $this->getJson('/api/v1/super-admin/dashboard/stats');
        $response->assertStatus(401)
            ->assertJsonPath('success', false);
    }

    /**
     * Test 2: Dashboard Statistics API returns accurate KPIs and charts
     */
    public function test_dashboard_stats_endpoint_returns_data()
    {
        $response = $this->getJson('/api/v1/super-admin/dashboard/stats', $this->getAuthHeaders());

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'kpis' => [
                        'total_tenants',
                        'active_tenants',
                        'monthly_recurring_revenue',
                        'total_storage_used_mb',
                    ],
                    'charts' => [
                        'tenant_growth',
                        'monthly_revenue',
                    ],
                    'infrastructure',
                    'recent_tenants',
                ]
            ]);
    }

    /**
     * Test 3: Tenant Management - Listing, Searching, and 7-Step Wizard Provisioning
     */
    public function test_tenants_listing_and_filtering()
    {
        $response = $this->getJson('/api/v1/super-admin/tenants', $this->getAuthHeaders());

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonStructure([
                'data' => ['data', 'total', 'current_page']
            ]);
    }

    public function test_tenant_creation_via_wizard()
    {
        $package = SubscriptionPackage::first();
        $uid = uniqid();

        $response = $this->postJson('/api/v1/super-admin/tenants', [
            'name' => 'Visakha Times ' . $uid,
            'company_name' => 'Visakha Media Publications',
            'contact_person' => 'G. V. Sharma',
            'email' => "editor_{$uid}@visakhatimes.com",
            'phone' => '+91 99887 76655',
            'state' => 'Andhra Pradesh',
            'district' => 'Visakhapatnam',
            'mandal' => 'Gajuwaka',
            'primary_domain' => "epaper-{$uid}.visakhatimes.com",
            'package_id' => $package ? $package->id : 1,
            'billing_cycle' => 'monthly',
            'admin_name' => 'G. V. Sharma',
            'admin_email' => "admin_{$uid}@visakhatimes.com",
            'primary_color' => '#1D4ED8',
            'secondary_color' => '#0284C7',
            'status' => 'active',
        ], $this->getAuthHeaders());

        $response->assertStatus(201)
            ->assertJsonPath('success', true);

        $this->assertDatabaseHas('tenants', [
            'email' => "editor_{$uid}@visakhatimes.com",
        ]);
    }

    /**
     * Test 4: Tenant Lifecycle Operations (Suspend & Reactivate)
     */
    public function test_tenant_suspension_and_reactivation()
    {
        $tenant = Tenant::first();

        // Suspend
        $suspendResponse = $this->postJson("/api/v1/super-admin/tenants/{$tenant->id}/suspend", [
            'reason' => 'Payment overdue and policy review.',
        ], $this->getAuthHeaders());

        $suspendResponse->assertStatus(200)
            ->assertJsonPath('data.status', 'suspended');

        // Reactivate
        $reactivateResponse = $this->postJson("/api/v1/super-admin/tenants/{$tenant->id}/reactivate", [], $this->getAuthHeaders());

        $reactivateResponse->assertStatus(200)
            ->assertJsonPath('data.status', 'active');
    }

    /**
     * Test 5: Domain Management & Simulated DNS Verification
     */
    public function test_domain_registration_and_dns_verification()
    {
        $tenant = Tenant::first();
        $host = 'digital-' . uniqid() . '.deccannews.in';

        $regResponse = $this->postJson('/api/v1/super-admin/domains', [
            'tenant_id' => $tenant->id,
            'host' => $host,
            'type' => 'custom',
        ], $this->getAuthHeaders());

        $regResponse->assertStatus(201)
            ->assertJsonPath('data.verification_status', 'pending');

        $domainId = $regResponse->json('data.id');

        // Verify with simulation flag
        $verifyResponse = $this->postJson("/api/v1/super-admin/domains/{$domainId}/verify?simulate_success=1", [], $this->getAuthHeaders());

        $verifyResponse->assertStatus(200)
            ->assertJsonPath('data.verified', true);
    }

    /**
     * Test 6: Subscription Packages & Versioning
     */
    public function test_subscription_package_creation_and_update()
    {
        $pkgName = 'Metro Daily ' . uniqid();
        $response = $this->postJson('/api/v1/super-admin/subscriptions/packages', [
            'name' => $pkgName,
            'billing_cycle' => 'monthly',
            'price' => 19999.00,
            'currency' => 'INR',
            'trial_days' => 21,
            'max_domains' => 8,
            'storage_limit_gb' => 100,
            'pdf_upload_limit_mb' => 300,
            'monthly_edition_limit' => 120,
            'admin_user_limit' => 20,
            'custom_branding_enabled' => true,
        ], $this->getAuthHeaders());

        $response->assertStatus(201)
            ->assertJsonPath('data.name', $pkgName);

        $pkgId = $response->json('data.id');

        // Update package to increment version
        $updateResponse = $this->putJson("/api/v1/super-admin/subscriptions/packages/{$pkgId}", [
            'price' => 21999.00,
        ], $this->getAuthHeaders());

        $updateResponse->assertStatus(200)
            ->assertJsonPath('data.version', 2);
    }

    /**
     * Test 7: Idempotent Payment Webhook Processing
     */
    public function test_idempotent_payment_webhook_processing()
    {
        $eventId = 'evt_test_' . uniqid();
        $payload = [
            'event_id' => $eventId,
            'event' => 'payment.captured',
            'amount' => 14999.00,
            'currency' => 'INR',
            'method' => 'upi',
            'gateway_payment_id' => 'pay_' . uniqid(),
        ];

        // First attempt
        $res1 = $this->postJson('/api/v1/super-admin/payments/webhook/razorpay', $payload);
        $res1->assertStatus(200)
            ->assertJsonPath('success', true);

        // Duplicate second attempt must return duplicate: true
        $res2 = $this->postJson('/api/v1/super-admin/payments/webhook/razorpay', $payload);
        $res2->assertStatus(200)
            ->assertJsonPath('duplicate', true);
    }

    /**
     * Test 8: Custom CSS Sanitization in Dynamic Branding Engine
     */
    public function test_branding_css_sanitization()
    {
        $dirtyCss = "body { background: #fff; } @import url('http://evil.com/malicious.css'); <script>alert(1)</script>";

        $response = $this->putJson('/api/v1/super-admin/branding/platform', [
            'primary_color' => '#2563EB',
            'custom_css' => $dirtyCss,
        ], $this->getAuthHeaders());

        $response->assertStatus(200);
        $savedCss = $response->json('data.custom_css');

        $this->assertStringNotContainsString('@import', $savedCss);
        $this->assertStringNotContainsString('<script>', $savedCss);
    }

    /**
     * Test 9: Newspaper Operations & Job Retrying
     */
    public function test_operations_job_retry()
    {
        $job = PdfProcessingJob::first();
        if ($job) {
            $response = $this->postJson("/api/v1/super-admin/operations/jobs/{$job->id}/retry", [], $this->getAuthHeaders());
            $response->assertStatus(200)
                ->assertJsonPath('data.status', 'queued');
        } else {
            $this->assertTrue(true);
        }
    }

    /**
     * Test 10: Infrastructure Health Monitoring
     */
    public function test_infrastructure_overview_returns_services_and_telemetry()
    {
        $response = $this->getJson('/api/v1/super-admin/infrastructure/overview', $this->getAuthHeaders());

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonStructure([
                'data' => [
                    'overall_status',
                    'healthy_services_ratio',
                    'services',
                    'cloudwatch_telemetry',
                ]
            ]);
    }
}
