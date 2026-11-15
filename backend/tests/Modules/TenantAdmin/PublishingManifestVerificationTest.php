<?php

namespace Tests\Modules\TenantAdmin;

use Tests\TestCase;
use App\Models\User;
use App\Modules\SuperAdmin\Models\Tenant;
use App\Modules\SuperAdmin\Models\TenantBranding;
use App\Modules\TenantAdmin\Models\Edition;
use App\Modules\TenantAdmin\Models\EditionPage;
use App\Modules\TenantAdmin\Models\NewspaperProfile;
use App\Modules\TenantAdmin\Services\PublishingService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;

class PublishingManifestVerificationTest extends TestCase
{
    use DatabaseTransactions;

    protected Tenant $tenant;
    protected User $user;
    protected PublishingService $publishingService;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
        Storage::fake('public');

        $this->tenant = Tenant::firstOrCreate(
            ['slug' => 'manifest-test-daily'],
            [
                'name' => 'Manifest Test Daily',
                'email' => 'editor@manifestdaily.com',
                'domain' => 'manifestdaily.com',
                'status' => 'active',
                'storage_quota_mb' => 51200,
                'storage_used_mb' => 50,
                'is_active' => true,
            ]
        );

        $this->user = User::firstOrCreate(
            ['email' => 'publisher@manifestdaily.com'],
            [
                'tenant_id' => $this->tenant->id,
                'name' => 'Publisher Manifest',
                'password' => bcrypt('TenantPassword123!'),
                'role' => 'admin',
                'is_active' => true,
            ]
        );

        NewspaperProfile::updateOrCreate(
            ['tenant_id' => $this->tenant->id],
            [
                'newspaper_name' => 'Manifest Grand Broadsheet',
                'about_newspaper' => 'Leading Telugu and English digital publication.',
                'publisher_name' => 'Grand Media Corporation',
                'editor_name' => 'Chief Editor Rao',
                'email' => 'contact@manifestdaily.com',
                'phone' => '+91-40-12345678',
                'logo_url' => '/storage/branding/manifest_logo.png',
                'favicon_url' => '/storage/branding/manifest_favicon.ico',
            ]
        );

        TenantBranding::updateOrCreate(
            ['tenant_id' => $this->tenant->id],
            [
                'newspaper_name' => 'Manifest Grand Broadsheet',
                'primary_color' => '#1D4ED8',
                'secondary_color' => '#3B82F6',
                'typography' => 'Inter, sans-serif',
                'header_style' => 'centered-masthead',
                'custom_css' => '.newspaper-header { font-weight: 700; }',
            ]
        );

        $this->publishingService = app(PublishingService::class);
    }

    protected function authHeader(): array
    {
        $token = $this->user->createToken('manifest_test_token')->plainTextToken;
        return ['Authorization' => "Bearer {$token}"];
    }

    /**
     * Requirement C: Comprehensive verification of manifest fields and sensitive data omission.
     */
    public function test_public_manifest_structure_and_sensitive_data_protection(): void
    {
        $samplePdfPath = storage_path('app/testing/telugu_sample_edition.pdf');
        $this->assertFileExists($samplePdfPath);
        $samplePdfBytes = file_get_contents($samplePdfPath);
        $expectedSha256 = hash('sha256', $samplePdfBytes);

        $storedPdfPath = 'private/tenants/manifest-test-daily/editions/MNF-CHECK-01/original/source.pdf';
        Storage::disk('local')->put($storedPdfPath, $samplePdfBytes);

        $edition = Edition::create([
            'tenant_id' => $this->tenant->id,
            'edition_code' => 'MNF-CHECK-01',
            'edition_name' => 'Special Investigation Issue',
            'edition_title' => 'Special Investigation Issue - City Edition',
            'edition_language' => 'te',
            'category' => 'Special Edition',
            'description' => 'In-depth regional investigative reportage.',
            'publish_date' => Carbon::parse('2026-11-15'),
            'status' => 'ready_for_review',
            'original_pdf_path' => $storedPdfPath,
            'checksum_sha256' => $expectedSha256,
            'total_pages' => 2,
        ]);

        // Create 2 pages
        EditionPage::create([
            'edition_id' => $edition->id,
            'tenant_id' => $this->tenant->id,
            'page_number' => 1,
            'page_title' => 'Page 1: Frontline Report',
            'image_path' => '/storage/tenants/manifest-test-daily/editions/MNF-CHECK-01/pages/page_1.png',
            'thumbnail_path' => '/storage/tenants/manifest-test-daily/editions/MNF-CHECK-01/thumbnails/page_1.png',
            'width' => 1240,
            'height' => 1754,
            'rotation' => 0,
            'is_visible' => true,
            'sort_order' => 1,
        ]);

        EditionPage::create([
            'edition_id' => $edition->id,
            'tenant_id' => $this->tenant->id,
            'page_number' => 2,
            'page_title' => 'Page 2: Regional Digest',
            'image_path' => '/storage/tenants/manifest-test-daily/editions/MNF-CHECK-01/pages/page_2.png',
            'thumbnail_path' => '/storage/tenants/manifest-test-daily/editions/MNF-CHECK-01/thumbnails/page_2.png',
            'width' => 1240,
            'height' => 1754,
            'rotation' => 0,
            'is_visible' => true,
            'sort_order' => 2,
        ]);

        $publishedEdition = $this->publishingService->publishNow($this->tenant->id, $edition->id, $this->user->id);

        $manifest = $publishedEdition->public_manifest;
        $this->assertIsArray($manifest);

        // 1. Check Schema & Metadata
        $this->assertEquals('2.0', $manifest['schema_version']);
        $this->assertEquals('MNF-CHECK-01', $manifest['edition']['edition_code']);
        $this->assertEquals('Special Investigation Issue - City Edition', $manifest['edition']['edition_title']);
        $this->assertEquals('te', $manifest['edition']['edition_language']);
        $this->assertEquals('Special Edition', $manifest['edition']['category']);
        $this->assertEquals(2, $manifest['edition']['total_pages']);
        $this->assertEquals($expectedSha256, $manifest['edition']['checksum_sha256']);
        $this->assertNotEquals('e3b0c44298fc1c149afbf4c8996fb92427ae41e4649b934ca495991b7852b855', $manifest['edition']['checksum_sha256']);
        $this->assertNotEmpty($manifest['edition']['published_at']);

        // 2. Check Newspaper Identity & Branding
        $this->assertEquals('Manifest Grand Broadsheet', $manifest['publication']['name']);
        $this->assertEquals('manifest-test-daily', $manifest['publication']['slug']);
        $this->assertEquals('Grand Media Corporation', $manifest['publication']['publisher_name']);
        $this->assertEquals('#1D4ED8', $manifest['branding']['primary_color']);
        $this->assertEquals('Inter, sans-serif', $manifest['branding']['font_family']);

        // 3. Check Pages Array Ordering, Dimensions, and URLs
        $this->assertCount(2, $manifest['pages']);
        $p1 = $manifest['pages'][0];
        $this->assertEquals(1, $p1['page_number']);
        $this->assertEquals('Page 1: Frontline Report', $p1['page_title']);
        $this->assertEquals(1240, $p1['width']);
        $this->assertEquals(1754, $p1['height']);
        $this->assertEquals(0, $p1['rotation']);
        $this->assertTrue($p1['is_visible']);
        $this->assertEquals('/storage/tenants/manifest-test-daily/editions/MNF-CHECK-01/pages/page_1.png', $p1['image_url']);
        $this->assertEquals('/storage/tenants/manifest-test-daily/editions/MNF-CHECK-01/thumbnails/page_1.png', $p1['thumbnail_url']);

        // 4. Strict Security Verification: Ensure NO sensitive or private paths exist in JSON
        $manifestJson = json_encode($manifest);

        // Must NOT contain private storage paths
        $this->assertStringNotContainsString('private/tenants', $manifestJson, 'Manifest must not expose private storage paths.');
        $this->assertStringNotContainsString('source.pdf', $manifestJson, 'Manifest must not expose original PDF file names.');
        $this->assertStringNotContainsString('download-pdf', $manifestJson, 'Manifest must not expose private download endpoints.');

        // Must NOT contain user/staff credentials or passwords
        $this->assertStringNotContainsString('password', $manifestJson);
        $this->assertStringNotContainsString('TenantPassword123!', $manifestJson);
        $this->assertStringNotContainsString('remember_token', $manifestJson);
        $this->assertStringNotContainsString('api_token', $manifestJson);

        // Must NOT contain database filesystem paths (e.g. C:\ or /var/www)
        $this->assertStringNotContainsString('C:\\', $manifestJson);
        $this->assertStringNotContainsString('/var/www', $manifestJson);
    }

    /**
     * Requirement C.1 - C.9: Full Publishing Lifecycle Test:
     * Publish -> Generate manifest -> Read -> Update -> Republish -> Unpublish -> latest.json updates -> Stale cache prevention.
     */
    public function test_full_publishing_lifecycle_and_latest_pointer_invalidation(): void
    {
        $headers = $this->authHeader();

        $pdfBytes = "%PDF-1.4\n%DOGY-VALID-SOURCE\n1 0 obj<</Type/Catalog/Pages 2 0 R>>endobj\n2 0 obj<</Type/Pages/Kids[3 0 R]/Count 1>>endobj\n3 0 obj<</Type/Page/MediaBox[0 0 595 842]>>endobj\ntrailer<</Root 1 0 R>>\n%%EOF\n";
        $pdfPathA = "private/tenants/{$this->tenant->slug}/editions/MNF-LIFECYCLE-A/original/sourceA.pdf";
        Storage::disk('local')->put($pdfPathA, $pdfBytes);
        $checksumA = hash('sha256', $pdfBytes);

        // 1. Setup Edition A (Morning Edition, published first)
        $editionA = Edition::create([
            'tenant_id' => $this->tenant->id,
            'edition_code' => 'MNF-LIFECYCLE-A',
            'edition_name' => 'Edition A Morning',
            'publish_date' => Carbon::parse('2026-11-14'),
            'status' => 'ready_for_review',
            'original_pdf_path' => $pdfPathA,
            'checksum_sha256' => $checksumA,
            'total_pages' => 1,
        ]);
        EditionPage::create([
            'edition_id' => $editionA->id,
            'tenant_id' => $this->tenant->id,
            'page_number' => 1,
            'page_title' => 'Page 1 Edition A',
            'image_path' => '/storage/tenants/manifest-test-daily/editions/MNF-LIFECYCLE-A/pages/page_1.png',
            'thumbnail_path' => '/storage/tenants/manifest-test-daily/editions/MNF-LIFECYCLE-A/thumbnails/page_1.png',
            'width' => 1200,
            'height' => 1800,
            'rotation' => 0,
            'is_visible' => true,
            'sort_order' => 1,
        ]);

        // Publish Edition A
        $this->withHeaders($headers)
            ->postJson("/api/v1/tenant-admin/editions/{$editionA->id}/publish")
            ->assertStatus(200);

        // Verify Edition A manifest and latest pointer exist
        $manifestAPath = "tenants/{$this->tenant->slug}/editions/MNF-LIFECYCLE-A/manifest.json";
        $latestPointerPath = "tenants/{$this->tenant->slug}/latest.json";

        Storage::disk('public')->assertExists($manifestAPath);
        Storage::disk('public')->assertExists($latestPointerPath);

        // Verify Public View endpoint returns Edition A as latest
        $pubLatest = $this->getJson("/api/v1/public/{$this->tenant->slug}/latest");
        $pubLatest->assertStatus(200);
        $this->assertEquals('MNF-LIFECYCLE-A', $pubLatest->json('data.edition.edition_code'));

        // 2. Setup Edition B (Evening Edition, published later with newer publish_date)
        $pdfBytesB = "%PDF-1.4\n%DOGY-VALID-SOURCE-B\n1 0 obj<</Type/Catalog/Pages 2 0 R>>endobj\n2 0 obj<</Type/Pages/Kids[3 0 R]/Count 1>>endobj\n3 0 obj<</Type/Page/MediaBox[0 0 595 842]>>endobj\ntrailer<</Root 1 0 R>>\n%%EOF\n";
        $pdfPathB = "private/tenants/{$this->tenant->slug}/editions/MNF-LIFECYCLE-B/original/sourceB.pdf";
        Storage::disk('local')->put($pdfPathB, $pdfBytesB);
        $checksumB = hash('sha256', $pdfBytesB);

        $editionB = Edition::create([
            'tenant_id' => $this->tenant->id,
            'edition_code' => 'MNF-LIFECYCLE-B',
            'edition_name' => 'Edition B Evening Prime',
            'publish_date' => Carbon::parse('2026-11-15'),
            'status' => 'ready_for_review',
            'original_pdf_path' => $pdfPathB,
            'checksum_sha256' => $checksumB,
            'total_pages' => 1,
        ]);
        EditionPage::create([
            'edition_id' => $editionB->id,
            'tenant_id' => $this->tenant->id,
            'page_number' => 1,
            'page_title' => 'Page 1 Edition B',
            'image_path' => '/storage/tenants/manifest-test-daily/editions/MNF-LIFECYCLE-B/pages/page_1.png',
            'thumbnail_path' => '/storage/tenants/manifest-test-daily/editions/MNF-LIFECYCLE-B/thumbnails/page_1.png',
            'width' => 1200,
            'height' => 1800,
            'rotation' => 0,
            'is_visible' => true,
            'sort_order' => 1,
        ]);

        // Publish Edition B
        $this->withHeaders($headers)
            ->postJson("/api/v1/tenant-admin/editions/{$editionB->id}/publish")
            ->assertStatus(200);

        // Verify latest.json pointer has now switched to Edition B
        $pubLatestB = $this->getJson("/api/v1/public/{$this->tenant->slug}/latest");
        $pubLatestB->assertStatus(200);
        $this->assertEquals('MNF-LIFECYCLE-B', $pubLatestB->json('data.edition.edition_code'));

        // 3. Update Edition B details & Republish
        $this->withHeaders($headers)
            ->putJson("/api/v1/tenant-admin/editions/{$editionB->id}", [
                'edition_name' => 'Edition B Evening Prime - Updated Breaking Edition',
            ])
            ->assertStatus(200);

        // Republish Edition B
        $this->withHeaders($headers)
            ->postJson("/api/v1/tenant-admin/editions/{$editionB->id}/publish")
            ->assertStatus(200);

        // Verify latest.json reflects updated title
        $pubLatestUpdated = $this->getJson("/api/v1/public/{$this->tenant->slug}/latest");
        $pubLatestUpdated->assertStatus(200);
        $this->assertEquals('Edition B Evening Prime - Updated Breaking Edition', $pubLatestUpdated->json('data.edition.edition_name'));

        // 4. Unpublish Edition B
        $this->withHeaders($headers)
            ->postJson("/api/v1/tenant-admin/editions/{$editionB->id}/unpublish")
            ->assertStatus(200);

        // Assert Edition B manifest file was deleted from disk and DB
        Storage::disk('public')->assertMissing("tenants/{$this->tenant->slug}/editions/MNF-LIFECYCLE-B/manifest.json");
        $this->assertNull($editionB->fresh()->public_manifest);
        $this->assertEquals('ready_for_review', $editionB->fresh()->status);

        // 5. Verify latest pointer reverted back to Edition A (prevents stale access)
        $pubLatestReverted = $this->getJson("/api/v1/public/{$this->tenant->slug}/latest");
        $pubLatestReverted->assertStatus(200);
        $this->assertEquals('MNF-LIFECYCLE-A', $pubLatestReverted->json('data.edition.edition_code'));

        // Verify direct request to unpublished Edition B returns 404
        $pubEditionB = $this->getJson("/api/v1/public/{$this->tenant->slug}/editions/MNF-LIFECYCLE-B");
        $pubEditionB->assertStatus(404);

        // 6. Unpublish Edition A (now zero published editions remain)
        $this->withHeaders($headers)
            ->postJson("/api/v1/tenant-admin/editions/{$editionA->id}/unpublish")
            ->assertStatus(200);

        // Assert latest.json pointer was completely deleted when no published editions exist
        Storage::disk('public')->assertMissing($latestPointerPath);

        // Public latest endpoint now returns 404 cleanly
        $pubLatestNone = $this->getJson("/api/v1/public/{$this->tenant->slug}/latest");
        $pubLatestNone->assertStatus(404);
    }
}
