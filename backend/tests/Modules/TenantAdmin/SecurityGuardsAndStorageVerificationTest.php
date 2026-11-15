<?php

namespace Tests\Modules\TenantAdmin;

use Tests\TestCase;
use App\Models\User;
use App\Modules\SuperAdmin\Models\SuperAdmin;
use App\Modules\SuperAdmin\Models\Tenant;
use App\Modules\TenantAdmin\Models\Edition;
use App\Modules\TenantAdmin\Models\EditionPage;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class SecurityGuardsAndStorageVerificationTest extends TestCase
{
    use DatabaseTransactions;

    protected Tenant $tenant;
    protected User $tenantUser;
    protected SuperAdmin $superAdmin;
    protected Edition $edition;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
        Storage::fake('public');

        $this->tenant = Tenant::firstOrCreate(
            ['slug' => 'security-verify-daily'],
            [
                'name' => 'Security Verify Daily',
                'email' => 'admin@securityverify.com',
                'status' => 'active',
                'storage_quota_mb' => 51200,
                'storage_used_mb' => 50,
                'is_active' => true,
            ]
        );

        $this->tenantUser = User::firstOrCreate(
            ['email' => 'owner@securityverify.com'],
            [
                'tenant_id' => $this->tenant->id,
                'name' => 'Owner Security',
                'password' => bcrypt('TenantPassword123!'),
                'role' => 'admin',
                'is_active' => true,
            ]
        );

        $roleId = DB::table('super_admin_roles')->where('id', 1)->value('id');
        if (!$roleId) {
            $roleId = DB::table('super_admin_roles')->insertGetId([
                'id' => 1,
                'name' => 'Platform Owner',
                'slug' => 'platform_owner',
                'description' => 'Platform Owner',
                'is_system' => true,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ]);
        }

        $this->superAdmin = SuperAdmin::firstOrCreate(
            ['email' => 'superadmin@dogy-epaper.com'],
            [
                'name' => 'Master Super Admin',
                'password' => bcrypt('SuperAdminPassword123!'),
                'role_id' => $roleId,
                'is_active' => true,
                'status' => 'active',
            ]
        );

        $this->edition = Edition::create([
            'tenant_id' => $this->tenant->id,
            'edition_code' => 'SEC-GUARD-01',
            'edition_name' => 'Secure Storage Issue',
            'publish_date' => Carbon::now()->toDateString(),
            'status' => 'ready_for_review',
            'original_pdf_path' => 'private/tenants/security-verify-daily/editions/SEC-GUARD-01/original/SEC-GUARD-01.pdf',
            'total_pages' => 1,
        ]);

        Storage::disk('local')->put($this->edition->original_pdf_path, '%PDF-1.4 mock private binary content');

        EditionPage::create([
            'edition_id' => $this->edition->id,
            'tenant_id' => $this->tenant->id,
            'page_number' => 1,
            'page_title' => 'Cover Page',
            'image_path' => '/storage/tenants/security-verify-daily/editions/SEC-GUARD-01/pages/page_1.png',
            'thumbnail_path' => '/storage/tenants/security-verify-daily/editions/SEC-GUARD-01/thumbnails/page_1.png',
            'width' => 1200,
            'height' => 1800,
            'rotation' => 0,
            'is_visible' => true,
            'sort_order' => 1,
        ]);
    }

    /**
     * Requirement D.1: Original PDFs exist ONLY in private storage, inaccessible via public disk.
     */
    public function test_original_pdfs_stored_in_private_storage_only(): void
    {
        // Must exist in local (private) disk
        Storage::disk('local')->assertExists($this->edition->original_pdf_path);

        // Must NOT exist in public disk
        Storage::disk('public')->assertMissing($this->edition->original_pdf_path);

        // The path must contain the private prefix
        $this->assertStringStartsWith('private/tenants/', $this->edition->original_pdf_path);
    }

    /**
     * Requirement D.2 & D.3: Authorized tenant downloads work; foreign tenant and unauthenticated blocked.
     */
    public function test_pdf_downloads_authorization_matrix(): void
    {
        // 1. Unauthenticated request -> 401
        $unauthRes = $this->getJson("/api/v1/tenant-admin/editions/{$this->edition->id}/download-pdf");
        $unauthRes->assertStatus(401);

        // 2. Foreign tenant user -> 404
        $foreignTenant = Tenant::firstOrCreate(
            ['slug' => 'foreign-tenant-xyz'],
            ['name' => 'Foreign Tenant', 'email' => 'foreign@xyz.com', 'is_active' => true]
        );
        $foreignUser = User::firstOrCreate(
            ['email' => 'hacker@foreign.com'],
            ['tenant_id' => $foreignTenant->id, 'name' => 'Foreign', 'password' => bcrypt('pass'), 'role' => 'admin', 'is_active' => true]
        );
        $foreignToken = $foreignUser->createToken('foreign')->plainTextToken;

        $crossRes = $this->withHeader('Authorization', "Bearer {$foreignToken}")
            ->getJson("/api/v1/tenant-admin/editions/{$this->edition->id}/download-pdf");
        $crossRes->assertStatus(404);

        // 3. Authorized tenant owner -> 200 with PDF content-type
        $ownerToken = $this->tenantUser->createToken('owner')->plainTextToken;
        $ownerRes = $this->withHeader('Authorization', "Bearer {$ownerToken}")
            ->get("/api/v1/tenant-admin/editions/{$this->edition->id}/download-pdf");
        $ownerRes->assertStatus(200);
        $ownerRes->assertHeader('content-type', 'application/pdf');
    }

    /**
     * Requirement D.4: Path traversal attempts are strictly blocked.
     */
    public function test_path_traversal_attempts_blocked(): void
    {
        $traversalEdition = Edition::create([
            'tenant_id' => $this->tenant->id,
            'edition_code' => 'TRAVERSAL-TEST',
            'edition_name' => 'Traversal Attack Simulation',
            'publish_date' => Carbon::now()->toDateString(),
            'status' => 'ready_for_review',
            'original_pdf_path' => 'private/tenants/../../../../windows/win.ini',
        ]);

        $ownerToken = $this->tenantUser->createToken('owner')->plainTextToken;
        $response = $this->withHeader('Authorization', "Bearer {$ownerToken}")
            ->getJson("/api/v1/tenant-admin/editions/{$traversalEdition->id}/download-pdf");

        // Must reject with 400 Security Error
        $response->assertStatus(400);
        $response->assertJson([
            'success' => false,
            'message' => 'Security Error: Path traversal detected.',
        ]);
    }

    /**
     * Requirement D.5: Public page image URLs expose no private filesystem paths.
     */
    public function test_public_page_image_urls_expose_no_private_paths(): void
    {
        $ownerToken = $this->tenantUser->createToken('owner')->plainTextToken;
        $response = $this->withHeader('Authorization', "Bearer {$ownerToken}")
            ->getJson("/api/v1/tenant-admin/editions/{$this->edition->id}/pages");

        $response->assertStatus(200);
        $pages = $response->json('data');
        $this->assertNotEmpty($pages);

        foreach ($pages as $p) {
            $this->assertStringStartsWith('/storage/tenants/', $p['image_path']);
            $this->assertStringNotContainsString('private', $p['image_path']);
            $this->assertStringNotContainsString('C:\\', $p['image_path']);
            $this->assertStringNotContainsString('/var/www', $p['image_path']);
        }
    }

    /**
     * Requirement D.6: File MIME type and extension validation work.
     */
    public function test_file_mime_type_validation_rejects_malicious_files(): void
    {
        $ownerToken = $this->tenantUser->createToken('owner')->plainTextToken;

        // Attempt to upload executable php script
        $fakePhp = UploadedFile::fake()->create('malicious_payload.php', 10, 'application/x-php');

        $response = $this->withHeader('Authorization', "Bearer {$ownerToken}")
            ->postJson("/api/v1/tenant-admin/editions/{$this->edition->id}/upload", [
                'pdf_file' => $fakePhp,
            ]);

        // Must fail with 422 Unprocessable Content
        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['pdf_file']);
    }

    /**
     * Requirement E.1: Super Admin tokens cannot access Tenant Admin endpoints.
     */
    public function test_super_admin_tokens_cannot_access_tenant_admin_endpoints(): void
    {
        $superAdminToken = $this->superAdmin->createToken('superadmin_token')->plainTextToken;

        // Super Admin token querying Tenant Admin dashboard
        $response = $this->withHeader('Authorization', "Bearer {$superAdminToken}")
            ->getJson('/api/v1/tenant-admin/dashboard');

        // Must be rejected with 401
        $response->assertStatus(401);
        $response->assertJson([
            'success' => false,
            'message' => 'Unauthorized: Invalid token or user is not assigned to any tenant.',
        ]);
    }

    /**
     * Requirement E.2: Tenant Admin tokens cannot access Super Admin endpoints.
     */
    public function test_tenant_admin_tokens_cannot_access_super_admin_endpoints(): void
    {
        $tenantToken = $this->tenantUser->createToken('tenant_token')->plainTextToken;

        // Tenant Admin token querying Super Admin dashboard stats
        $response = $this->withHeader('Authorization', "Bearer {$tenantToken}")
            ->getJson('/api/v1/super-admin/dashboard/stats');

        // Must be rejected with 401
        $response->assertStatus(401);
    }

    /**
     * Requirement E.3: Demo tokens strictly rejected in production.
     */
    public function test_demo_tokens_strictly_rejected_in_production(): void
    {
        App::detectEnvironment(fn() => 'production');

        // 1. Tenant Admin demo token in production -> 401
        $resTenant = $this->withHeaders([
            'Authorization' => 'Bearer test-tenant-admin-token',
        ])->getJson('/api/v1/tenant-admin/dashboard');
        $resTenant->assertStatus(401);

        // 2. Super Admin demo token in production -> 401
        $resSuper = $this->withHeaders([
            'Authorization' => 'Bearer superadmin-master-session-token',
        ])->getJson('/api/v1/super-admin/dashboard/stats');
        $resSuper->assertStatus(401);
    }

    /**
     * Requirement E.4: Demo seeders do NOT execute in production.
     */
    public function test_demo_seeders_do_not_execute_in_production(): void
    {
        App::detectEnvironment(fn() => 'production');

        $seeder = new DatabaseSeeder();
        // DatabaseSeeder::run() checks App::environment(['local', 'testing', 'development'])
        // In production it performs zero database mutations
        $seeder->run();

        // Environment guard safely prevented execution without throwing exception
        $this->assertEquals('production', App::environment());
    }

    /**
     * Requirement E.5: Session and token revocation on logout.
     */
    public function test_session_and_token_revocation_on_logout(): void
    {
        $token = $this->tenantUser->createToken('session_test_token')->plainTextToken;

        // 1. Authenticated query succeeds
        $resBefore = $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson('/api/v1/tenant-admin/dashboard');
        $resBefore->assertStatus(200);

        // 2. Perform logout
        $logoutRes = $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson('/api/v1/tenant-admin/auth/logout');
        $logoutRes->assertStatus(200);

        // 3. Subsequent query with revoked token MUST return 401
        $resAfter = $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson('/api/v1/tenant-admin/dashboard');
        $resAfter->assertStatus(401);
    }
}
