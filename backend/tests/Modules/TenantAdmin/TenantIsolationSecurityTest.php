<?php

namespace Tests\Modules\TenantAdmin;

use Tests\TestCase;
use App\Models\User;
use App\Modules\SuperAdmin\Models\Tenant;
use App\Modules\TenantAdmin\Models\Edition;
use App\Modules\TenantAdmin\Models\EditionPage;
use App\Modules\TenantAdmin\Models\MediaFile;
use App\Modules\TenantAdmin\Models\SupportTicket;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;

class TenantIsolationSecurityTest extends TestCase
{
    use DatabaseTransactions;

    protected Tenant $tenant1;
    protected Tenant $tenant2;
    protected User $user1;
    protected User $user2;
    protected Edition $t1Edition;
    protected EditionPage $t1Page;
    protected MediaFile $t1Media;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
        Storage::fake('public');

        // Create or get Tenant 1 (Telangana Samacharam)
        $this->tenant1 = Tenant::firstOrCreate(
            ['slug' => 'telangana-samacharam'],
            [
                'name' => 'Telangana Samacharam',
                'email' => 'publisher@samacharam.com',
                'status' => 'active',
                'storage_limit_mb' => 51200,
                'storage_used_mb' => 100,
            ]
        );

        $this->user1 = User::firstOrCreate(
            ['email' => 'owner@samacharam.com'],
            [
                'tenant_id' => $this->tenant1->id,
                'name' => 'Owner Samacharam',
                'password' => bcrypt('TenantPassword123!'),
                'role' => 'admin',
                'is_active' => true,
            ]
        );

        // Create or get Tenant 2 (Andhra Prabha Digital)
        $this->tenant2 = Tenant::firstOrCreate(
            ['slug' => 'andhra-prabha-digital'],
            [
                'name' => 'Andhra Prabha Digital',
                'email' => 'editions@andhraprabha.com',
                'status' => 'active',
                'storage_limit_mb' => 51200,
                'storage_used_mb' => 50,
            ]
        );

        $this->user2 = User::firstOrCreate(
            ['email' => 'admin@andhraprabha.com'],
            [
                'tenant_id' => $this->tenant2->id,
                'name' => 'Admin Prabha',
                'password' => bcrypt('TenantPassword123!'),
                'role' => 'admin',
                'is_active' => true,
            ]
        );

        // Seed Tenant 1 resources
        $this->t1Edition = Edition::firstOrCreate(
            ['edition_code' => 'HYD-SEC-01'],
            [
                'tenant_id' => $this->tenant1->id,
                'edition_name' => 'Samacharam Morning Secret Edition',
                'edition_title' => 'Samacharam Morning Secret Edition',
                'edition_language' => 'te',
                'category' => 'Confidential',
                'publish_date' => Carbon::now()->toDateString(),
                'total_pages' => 1,
                'file_size_mb' => 5.0,
                'status' => 'ready_for_review',
                'original_pdf_path' => 'private/tenants/telangana-samacharam/editions/HYD-SEC-01/original/HYD-SEC-01.pdf',
            ]
        );
        $this->t1Edition->update(['status' => 'ready_for_review']);

        // Put dummy PDF in private storage
        Storage::disk('local')->put($this->t1Edition->original_pdf_path, '%PDF-1.4 mock confidential pdf bytes');

        $this->t1Page = EditionPage::firstOrCreate(
            [
                'edition_id' => $this->t1Edition->id,
                'page_number' => 1,
            ],
            [
                'tenant_id' => $this->tenant1->id,
                'page_title' => 'Page 1 Secret',
                'image_path' => '/storage/tenants/telangana-samacharam/editions/HYD-SEC-01/pages/page_1.png',
                'thumbnail_path' => '/storage/tenants/telangana-samacharam/editions/HYD-SEC-01/thumbnails/page_1.png',
                'width' => 1200,
                'height' => 1800,
                'rotation' => 0,
                'is_visible' => true,
                'sort_order' => 1,
            ]
        );

        $this->t1Media = MediaFile::firstOrCreate(
            [
                'tenant_id' => $this->tenant1->id,
                'name' => 'confidential_sponsor_logo.png',
            ],
            [
                'path' => '/storage/tenants/telangana-samacharam/media/logo.png',
                'disk' => 'public',
                'type' => 'image',
                'mime_type' => 'image/png',
                'size' => 10240,
            ]
        );
    }

    protected function authHeaderFor(User $user): array
    {
        $token = $user->createToken('tenant_auth_token')->plainTextToken;
        return ['Authorization' => "Bearer {$token}"];
    }

    public function test_tenant_cannot_list_another_tenants_editions()
    {
        $headers = $this->authHeaderFor($this->user2);
        $response = $this->withHeaders($headers)->getJson('/api/v1/tenant-admin/editions');

        $response->assertStatus(200);
        $data = $response->json('data');

        // Assert that none of Tenant 1's editions appear in Tenant 2's list
        $editionCodes = collect($data)->pluck('edition_code')->toArray();
        $this->assertNotContains('HYD-SEC-01', $editionCodes);
    }

    public function test_tenant_cannot_view_another_tenants_edition_details()
    {
        $headers = $this->authHeaderFor($this->user2);
        $response = $this->withHeaders($headers)->getJson("/api/v1/tenant-admin/editions/{$this->t1Edition->id}");

        $response->assertStatus(404);
    }

    public function test_tenant_cannot_update_another_tenants_edition()
    {
        $headers = $this->authHeaderFor($this->user2);
        $response = $this->withHeaders($headers)->putJson("/api/v1/tenant-admin/editions/{$this->t1Edition->id}", [
            'edition_name' => 'Hacked Edition Title',
        ]);

        $response->assertStatus(404);
        $this->assertDatabaseMissing('newspaper_editions', [
            'id' => $this->t1Edition->id,
            'edition_name' => 'Hacked Edition Title',
        ]);
    }

    public function test_tenant_cannot_delete_another_tenants_edition()
    {
        $headers = $this->authHeaderFor($this->user2);
        $response = $this->withHeaders($headers)->deleteJson("/api/v1/tenant-admin/editions/{$this->t1Edition->id}");

        $response->assertStatus(404);
        $this->assertDatabaseHas('newspaper_editions', [
            'id' => $this->t1Edition->id,
        ]);
    }

    public function test_tenant_cannot_view_or_manipulate_another_tenants_pages()
    {
        $headers = $this->authHeaderFor($this->user2);

        // View pages
        $getRes = $this->withHeaders($headers)->getJson("/api/v1/tenant-admin/editions/{$this->t1Edition->id}/pages");
        $getRes->assertStatus(404);

        // Reorder pages
        $reorderRes = $this->withHeaders($headers)->putJson("/api/v1/tenant-admin/editions/{$this->t1Edition->id}/pages/reorder", [
            'page_ids' => [$this->t1Page->id],
        ]);
        $reorderRes->assertStatus(404);

        // Rotate page
        $rotateRes = $this->withHeaders($headers)->postJson("/api/v1/tenant-admin/editions/{$this->t1Edition->id}/pages/{$this->t1Page->id}/rotate", [
            'angle' => 90,
        ]);
        $rotateRes->assertStatus(404);
    }

    public function test_tenant_cannot_publish_another_tenants_edition()
    {
        $headers = $this->authHeaderFor($this->user2);
        $response = $this->withHeaders($headers)->postJson("/api/v1/tenant-admin/editions/{$this->t1Edition->id}/publish");

        $response->assertStatus(404);
        $this->assertEquals('ready_for_review', $this->t1Edition->fresh()->status);
    }

    public function test_tenant_cannot_download_another_tenants_original_pdf()
    {
        $headers = $this->authHeaderFor($this->user2);
        $response = $this->withHeaders($headers)->getJson("/api/v1/tenant-admin/editions/{$this->t1Edition->id}/download-pdf");

        $response->assertStatus(404);
    }

    public function test_unauthenticated_request_cannot_download_original_pdf()
    {
        // No Authorization header
        $response = $this->getJson("/api/v1/tenant-admin/editions/{$this->t1Edition->id}/download-pdf");
        $response->assertStatus(401);
    }

    public function test_tenant_can_download_own_original_pdf()
    {
        $headers = $this->authHeaderFor($this->user1);
        $response = $this->withHeaders($headers)->get("/api/v1/tenant-admin/editions/{$this->t1Edition->id}/download-pdf");

        $response->assertStatus(200);
        $response->assertHeader('content-type', 'application/pdf');
    }

    public function test_tenant_cannot_delete_another_tenants_media()
    {
        $headers = $this->authHeaderFor($this->user2);
        $response = $this->withHeaders($headers)->deleteJson("/api/v1/tenant-admin/media/{$this->t1Media->id}");

        $response->assertStatus(404);
        $this->assertDatabaseHas('media_files', [
            'id' => $this->t1Media->id,
        ]);
    }

    public function test_publishing_generates_public_reader_manifest_and_latest_pointer()
    {
        $headers = $this->authHeaderFor($this->user1);

        // Publish Tenant 1's edition
        $response = $this->withHeaders($headers)->postJson("/api/v1/tenant-admin/editions/{$this->t1Edition->id}/publish");
        $response->assertStatus(200);

        $fresh = $this->t1Edition->fresh();
        $this->assertEquals('published', $fresh->status);
        $this->assertNotNull($fresh->published_at);
        $this->assertNotNull($fresh->public_manifest);

        // Check manifest structure
        $manifest = $fresh->public_manifest;
        $this->assertEquals('2.0', $manifest['schema_version']);
        $this->assertEquals('HYD-SEC-01', $manifest['edition']['edition_code']);
        $this->assertEquals(1, $manifest['edition']['total_pages']);
        $this->assertCount(1, $manifest['pages']);
        $this->assertEquals(1, $manifest['pages'][0]['page_number']);

        // Check static manifest file in public storage
        $manifestPath = "tenants/{$this->tenant1->slug}/editions/HYD-SEC-01/manifest.json";
        Storage::disk('public')->assertExists($manifestPath);

        // Check latest.json pointer in public storage
        Storage::disk('public')->assertExists("tenants/{$this->tenant1->slug}/latest.json");

        // Verify Public View endpoint serves this manifest without authentication
        $publicRes = $this->getJson("/api/v1/public/{$this->tenant1->slug}/latest");
        $publicRes->assertStatus(200);
        $publicRes->assertJsonPath('data.edition.edition_code', 'HYD-SEC-01');

        // Test Unpublishing revokes the manifest
        $unpublishRes = $this->withHeaders($headers)->postJson("/api/v1/tenant-admin/editions/{$this->t1Edition->id}/unpublish");
        $unpublishRes->assertStatus(200);

        $this->assertEquals('ready_for_review', $this->t1Edition->fresh()->status);
        $this->assertNull($this->t1Edition->fresh()->public_manifest);
        Storage::disk('public')->assertMissing($manifestPath);
    }

    public function test_production_environment_strictly_rejects_test_tokens()
    {
        // Mock production environment
        App::detectEnvironment(fn() => 'production');

        // Request with hardcoded test token in production MUST be rejected with 401
        $response = $this->withHeaders([
            'Authorization' => 'Bearer test-tenant-admin-token',
        ])->getJson('/api/v1/tenant-admin/dashboard');

        $response->assertStatus(401);
    }
}
