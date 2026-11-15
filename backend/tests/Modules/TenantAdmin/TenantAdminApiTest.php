<?php

namespace Tests\Modules\TenantAdmin;

use Tests\TestCase;
use App\Models\User;
use App\Modules\SuperAdmin\Models\Tenant;
use App\Modules\TenantAdmin\Models\Edition;
use App\Modules\TenantAdmin\Models\EditionPage;
use App\Modules\TenantAdmin\Models\NewspaperProfile;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Hash;

class TenantAdminApiTest extends TestCase
{
    use DatabaseTransactions;

    protected Tenant $tenant1;
    protected Tenant $tenant2;
    protected User $tenant1Admin;
    protected User $tenant1Editor;
    protected User $tenant2Admin;
    protected string $tenant1Token = 'test-tenant-admin-token';
    protected string $tenant2Token = 'test-tenant-2-token';

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');
        Storage::fake('public');

        // Setup test Tenant 1 (Telangana Samacharam)
        $this->tenant1 = Tenant::firstOrCreate(
            ['slug' => 'telangana-samacharam'],
            [
                'name' => 'Telangana Samacharam',
                'company_name' => 'Samacharam Media Pvt Ltd',
                'contact_person' => 'K. R. Rao',
                'email' => 'publisher@samacharam.com',
                'domain' => 'epaper.samacharam.com',
                'status' => 'active',
                'storage_limit_mb' => 51200,
                'storage_used_mb' => 100,
            ]
        );

        // Setup test Tenant 2 (Andhra Prabha)
        $this->tenant2 = Tenant::firstOrCreate(
            ['slug' => 'andhra-prabha-digital'],
            [
                'name' => 'Andhra Prabha Digital',
                'company_name' => 'Prabha Publications Ltd',
                'contact_person' => 'S. V. Murthy',
                'email' => 'editions@andhraprabha.com',
                'domain' => 'epaper.andhraprabha.com',
                'status' => 'active',
                'storage_limit_mb' => 51200,
                'storage_used_mb' => 200,
            ]
        );

        // Users for Tenant 1
        $this->tenant1Admin = User::firstOrCreate(
            ['email' => 'owner@samacharam.com'],
            [
                'tenant_id' => $this->tenant1->id,
                'name' => 'K. R. Rao (Owner)',
                'password' => Hash::make('TenantPassword123!'),
                'role' => 'admin',
                'is_active' => true,
                'permissions' => ['*'],
            ]
        );

        $this->tenant1Editor = User::firstOrCreate(
            ['email' => 'editor@samacharam.com'],
            [
                'tenant_id' => $this->tenant1->id,
                'name' => 'M. Venkat (Editor)',
                'password' => Hash::make('TenantPassword123!'),
                'role' => 'editor',
                'is_active' => true,
                'permissions' => ['editions.*', 'publishing.*', 'pages.*'],
            ]
        );

        // User for Tenant 2
        $this->tenant2Admin = User::firstOrCreate(
            ['email' => 'admin@andhraprabha.com'],
            [
                'tenant_id' => $this->tenant2->id,
                'name' => 'S. V. Murthy (Admin)',
                'password' => Hash::make('TenantPassword123!'),
                'role' => 'admin',
                'is_active' => true,
                'permissions' => ['*'],
            ]
        );
    }

    public function test_tenant_staff_can_login_with_valid_credentials(): void
    {
        $response = $this->postJson('/api/v1/tenant-admin/auth/login', [
            'email' => 'owner@samacharam.com',
            'password' => 'TenantPassword123!',
        ]);

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'success',
            'data' => [
                'token',
                'user' => ['id', 'tenant_id', 'name', 'email', 'role'],
                'tenant' => ['id', 'name', 'slug', 'status'],
            ],
        ]);
        $this->assertEquals($this->tenant1->id, $response->json('data.tenant.id'));
    }

    public function test_login_fails_with_invalid_credentials(): void
    {
        $response = $this->postJson('/api/v1/tenant-admin/auth/login', [
            'email' => 'owner@samacharam.com',
            'password' => 'WrongPassword!',
        ]);

        $response->assertStatus(422);
    }

    public function test_unauthenticated_request_is_rejected(): void
    {
        $response = $this->getJson('/api/v1/tenant-admin/dashboard');

        $response->assertStatus(401);
        $response->assertJson([
            'success' => false,
        ]);
    }

    public function test_suspended_tenant_is_blocked_from_accessing_api(): void
    {
        $suspendedTenant = Tenant::create([
            'name' => 'Suspended Gazette',
            'slug' => 'suspended-gazette-' . uniqid(),
            'email' => 'contact@suspended.com',
            'domain' => 'epaper.suspended.com',
            'status' => 'suspended',
        ]);

        $suspendedUser = User::create([
            'tenant_id' => $suspendedTenant->id,
            'name' => 'Suspended Staff',
            'email' => 'staff@suspended.com',
            'password' => Hash::make('password'),
            'role' => 'admin',
            'is_active' => true,
        ]);

        $token = $suspendedUser->createToken('test-token')->plainTextToken;

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson('/api/v1/tenant-admin/dashboard');

        $response->assertStatus(403);
        $this->assertStringContainsString('suspended', strtolower($response->json('message')));
    }

    public function test_strict_tenant_isolation_cannot_access_other_tenant_editions(): void
    {
        // Create an edition owned by Tenant 2
        $tenant2Edition = Edition::create([
            'tenant_id' => $this->tenant2->id,
            'edition_code' => 'T2-ED-' . uniqid(),
            'edition_name' => 'Andhra Prabha Exclusive',
            'publish_date' => date('Y-m-d'),
            'status' => 'published',
        ]);

        // Tenant 1 requests Tenant 2's edition
        $response = $this->withHeader('Authorization', "Bearer {$this->tenant1Token}")
            ->getJson("/api/v1/tenant-admin/editions/{$tenant2Edition->id}");

        // Must fail with 404 Not Found (scoped out)
        $response->assertStatus(404);
    }

    public function test_strict_tenant_isolation_cannot_delete_other_tenant_editions(): void
    {
        $tenant2Edition = Edition::create([
            'tenant_id' => $this->tenant2->id,
            'edition_code' => 'T2-DRAFT-' . uniqid(),
            'edition_name' => 'Tenant 2 Draft Issue',
            'publish_date' => date('Y-m-d'),
            'status' => 'draft',
        ]);

        // Tenant 1 attempts to delete Tenant 2's edition
        $response = $this->withHeader('Authorization', "Bearer {$this->tenant1Token}")
            ->deleteJson("/api/v1/tenant-admin/editions/{$tenant2Edition->id}");

        $response->assertStatus(404);
        $this->assertDatabaseHas('newspaper_editions', ['id' => $tenant2Edition->id]);
    }

    public function test_dashboard_returns_real_tenant_scoped_kpis(): void
    {
        $response = $this->withHeader('Authorization', "Bearer {$this->tenant1Token}")
            ->getJson('/api/v1/tenant-admin/dashboard');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'success',
            'data' => [
                'kpis' => [
                    'total_editions',
                    'published_editions',
                    'draft_editions',
                    'storage',
                    'subscription',
                ],
                'recent_editions',
                'publishing_trajectory',
            ],
        ]);
    }

    public function test_newspaper_profile_retrieval_and_update(): void
    {
        // Get Profile
        $getResponse = $this->withHeader('Authorization', "Bearer {$this->tenant1Token}")
            ->getJson('/api/v1/tenant-admin/newspaper-profile');
        $getResponse->assertStatus(200);

        // Update Profile
        $updateResponse = $this->withHeader('Authorization', "Bearer {$this->tenant1Token}")
            ->putJson('/api/v1/tenant-admin/newspaper-profile', [
                'newspaper_name' => 'Telangana Samacharam Daily Updated',
                'editor_name' => 'Senior Editor Test',
                'whatsapp' => '+91 99999 11111',
            ]);

        $updateResponse->assertStatus(200);
        $this->assertEquals('Telangana Samacharam Daily Updated', $updateResponse->json('data.newspaper_name'));
        $this->assertEquals('Senior Editor Test', $updateResponse->json('data.editor_name'));
    }

    public function test_edition_crud_lifecycle(): void
    {
        // 1. Create Edition Draft
        $createResponse = $this->withHeader('Authorization', "Bearer {$this->tenant1Token}")
            ->postJson('/api/v1/tenant-admin/editions', [
                'edition_name' => 'Hanamkonda District Morning',
                'edition_title' => 'Hanamkonda District Tabloid',
                'category' => 'District Tabloid',
                'publish_date' => date('Y-m-d'),
            ]);

        $createResponse->assertStatus(201);
        $editionId = $createResponse->json('data.id');
        $this->assertNotNull($editionId);

        // 2. Update Edition
        $updateResponse = $this->withHeader('Authorization', "Bearer {$this->tenant1Token}")
            ->putJson("/api/v1/tenant-admin/editions/{$editionId}", [
                'edition_name' => 'Hanamkonda District Morning Edition (Revised)',
            ]);
        $updateResponse->assertStatus(200);
        $this->assertEquals('Hanamkonda District Morning Edition (Revised)', $updateResponse->json('data.edition_name'));

        // 3. Duplicate Edition
        $duplicateResponse = $this->withHeader('Authorization', "Bearer {$this->tenant1Token}")
            ->postJson("/api/v1/tenant-admin/editions/{$editionId}/duplicate");
        $duplicateResponse->assertStatus(201);
        $dupId = $duplicateResponse->json('data.id');

        // 4. Delete Duplicate Draft
        $deleteResponse = $this->withHeader('Authorization', "Bearer {$this->tenant1Token}")
            ->deleteJson("/api/v1/tenant-admin/editions/{$dupId}");
        $deleteResponse->assertStatus(200);
        $this->assertDatabaseMissing('newspaper_editions', ['id' => $dupId]);
    }

    public function test_local_pdf_upload_and_page_generation(): void
    {
        // Create an edition to attach PDF to
        $edition = Edition::create([
            'tenant_id' => $this->tenant1->id,
            'edition_code' => 'TEST-UPLOAD-' . uniqid(),
            'edition_name' => 'Upload Test Edition',
            'publish_date' => date('Y-m-d'),
            'status' => 'draft',
        ]);

        $fakePdf = UploadedFile::fake()->createWithContent(
            'sample_newspaper.pdf',
            "%PDF-1.4\n%DOGY-EPAPER-TEST-PDF\n1 0 obj<</Type/Catalog/Pages 2 0 R>>endobj\n2 0 obj<</Type/Pages/Kids[3 0 R]/Count 1>>endobj\n3 0 obj<</Type/Page/MediaBox[0 0 595 842]>>endobj\ntrailer<</Root 1 0 R>>\n%%EOF\n"
        );

        $response = $this->withHeader('Authorization', "Bearer {$this->tenant1Token}")
            ->postJson("/api/v1/tenant-admin/editions/{$edition->id}/upload", [
                'pdf_file' => $fakePdf,
            ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
        ]);

        $freshEdition = $edition->fresh();
        $this->assertEquals('ready_for_review', $freshEdition->status);
        $this->assertGreaterThan(0, $freshEdition->total_pages);
        $this->assertNotNull($freshEdition->thumbnail_path);

        // Assert edition_pages were generated
        $pages = EditionPage::where('edition_id', $edition->id)->get();
        $this->assertNotEmpty($pages);
    }

    public function test_page_reordering_and_rotation(): void
    {
        $edition = Edition::create([
            'tenant_id' => $this->tenant1->id,
            'edition_code' => 'PAGE-TEST-' . uniqid(),
            'edition_name' => 'Page Reorder Test',
            'publish_date' => date('Y-m-d'),
            'status' => 'ready_for_review',
        ]);

        $p1 = EditionPage::create([
            'edition_id' => $edition->id,
            'tenant_id' => $this->tenant1->id,
            'page_number' => 1,
            'image_path' => '/storage/page1.png',
            'sort_order' => 1,
        ]);

        $p2 = EditionPage::create([
            'edition_id' => $edition->id,
            'tenant_id' => $this->tenant1->id,
            'page_number' => 2,
            'image_path' => '/storage/page2.png',
            'sort_order' => 2,
        ]);

        // Rotate Page 1 by 90°
        $rotateResponse = $this->withHeader('Authorization', "Bearer {$this->tenant1Token}")
            ->postJson("/api/v1/tenant-admin/editions/{$edition->id}/pages/{$p1->id}/rotate", [
                'angle' => 90,
            ]);
        $rotateResponse->assertStatus(200);
        $this->assertEquals(90, $rotateResponse->json('data.rotation'));

        // Reorder Pages: [p2, p1]
        $reorderResponse = $this->withHeader('Authorization', "Bearer {$this->tenant1Token}")
            ->putJson("/api/v1/tenant-admin/editions/{$edition->id}/pages/reorder", [
                'page_ids' => [$p2->id, $p1->id],
            ]);
        $reorderResponse->assertStatus(200);

        $this->assertEquals($p2->id, $reorderResponse->json('data.0.id'));
        $this->assertEquals(1, $reorderResponse->json('data.0.sort_order'));
    }

    public function test_publishing_workflow(): void
    {
        $code = 'PUB-TEST-' . uniqid();
        $pdfBytes = "%PDF-1.4\n%DOGY-PUB-WORKFLOW-PDF\n1 0 obj<</Type/Catalog/Pages 2 0 R>>endobj\n2 0 obj<</Type/Pages/Kids[3 0 R]/Count 1>>endobj\n3 0 obj<</Type/Page/MediaBox[0 0 595 842]>>endobj\ntrailer<</Root 1 0 R>>\n%%EOF\n";
        $pdfRelPath = "private/tenants/{$this->tenant1->slug}/editions/{$code}/original/source.pdf";
        Storage::disk('local')->put($pdfRelPath, $pdfBytes);
        $checksum = hash('sha256', $pdfBytes);

        $edition = Edition::create([
            'tenant_id' => $this->tenant1->id,
            'edition_code' => $code,
            'edition_name' => 'Publishing Workflow Test',
            'publish_date' => date('Y-m-d'),
            'status' => 'ready_for_review',
            'original_pdf_path' => $pdfRelPath,
            'checksum_sha256' => $checksum,
        ]);

        EditionPage::create([
            'edition_id' => $edition->id,
            'tenant_id' => $this->tenant1->id,
            'page_number' => 1,
            'image_path' => '/storage/page1.png',
            'sort_order' => 1,
            'metadata' => ['engine' => 'ghostscript', 'dpi' => 150],
        ]);

        // 1. Schedule Edition
        $futureDate = date('Y-m-d H:i:s', strtotime('+2 days'));
        $scheduleResponse = $this->withHeader('Authorization', "Bearer {$this->tenant1Token}")
            ->postJson("/api/v1/tenant-admin/editions/{$edition->id}/schedule", [
                'scheduled_at' => $futureDate,
            ]);
        $scheduleResponse->assertStatus(200);
        $this->assertEquals('scheduled', $scheduleResponse->json('data.status'));

        // 2. Publish Now
        $publishResponse = $this->withHeader('Authorization', "Bearer {$this->tenant1Token}")
            ->postJson("/api/v1/tenant-admin/editions/{$edition->id}/publish");
        $publishResponse->assertStatus(200);
        $this->assertEquals('published', $publishResponse->json('data.status'));

        // 3. Unpublish Edition
        $unpublishResponse = $this->withHeader('Authorization', "Bearer {$this->tenant1Token}")
            ->postJson("/api/v1/tenant-admin/editions/{$edition->id}/unpublish");
        $unpublishResponse->assertStatus(200);
        $this->assertEquals('ready_for_review', $unpublishResponse->json('data.status'));
    }

    public function test_media_library_upload_and_listing(): void
    {
        $fakeImg = UploadedFile::fake()->image('banner_ad.png', 800, 250);

        $uploadResponse = $this->withHeader('Authorization', "Bearer {$this->tenant1Token}")
            ->postJson('/api/v1/tenant-admin/media', [
                'file' => $fakeImg,
            ]);

        $uploadResponse->assertStatus(201);
        $mediaId = $uploadResponse->json('data.id');

        $listResponse = $this->withHeader('Authorization', "Bearer {$this->tenant1Token}")
            ->getJson('/api/v1/tenant-admin/media');

        $listResponse->assertStatus(200);
        $this->assertNotEmpty($listResponse->json('data'));
    }

    public function test_branding_css_sanitization(): void
    {
        $maliciousCss = "<script>alert('hack')</script> @import url('http://evil.com/hack.css'); body { color: #000; }";

        $response = $this->withHeader('Authorization', "Bearer {$this->tenant1Token}")
            ->putJson('/api/v1/tenant-admin/branding', [
                'custom_css' => $maliciousCss,
                'primary_color' => '#1E3A8A',
            ]);

        $response->assertStatus(200);
        $savedCss = $response->json('data.custom_css');

        $this->assertStringNotContainsString('<script>', $savedCss);
        $this->assertStringNotContainsString('@import', $savedCss);
        $this->assertStringContainsString('body { color: #000; }', $savedCss);
    }

    public function test_staff_management_and_rbac(): void
    {
        // 1. Add new staff user
        $createResponse = $this->withHeader('Authorization', "Bearer {$this->tenant1Token}")
            ->postJson('/api/v1/tenant-admin/staff', [
                'name' => 'Sub Editor New',
                'email' => 'subeditor_' . uniqid() . '@samacharam.com',
                'password' => 'StaffPass123!',
                'role' => 'editor',
            ]);

        $createResponse->assertStatus(201);
        $newStaffId = $createResponse->json('data.id');

        // 2. List staff
        $listResponse = $this->withHeader('Authorization', "Bearer {$this->tenant1Token}")
            ->getJson('/api/v1/tenant-admin/staff');

        $listResponse->assertStatus(200);
        $this->assertGreaterThanOrEqual(2, count($listResponse->json('data')));

        // 3. Delete staff
        $deleteResponse = $this->withHeader('Authorization', "Bearer {$this->tenant1Token}")
            ->deleteJson("/api/v1/tenant-admin/staff/{$newStaffId}");

        $deleteResponse->assertStatus(200);
    }
}
