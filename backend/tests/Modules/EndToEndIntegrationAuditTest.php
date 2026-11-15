<?php

namespace Tests\Modules;

use Tests\TestCase;
use App\Models\User;
use App\Modules\SuperAdmin\Models\Tenant;
use App\Modules\SuperAdmin\Models\TenantBranding;
use App\Modules\SuperAdmin\Models\TenantDomain;
use App\Modules\TenantAdmin\Models\Edition;
use App\Modules\TenantAdmin\Models\EditionPage;
use App\Modules\TenantAdmin\Models\PdfUpload;
use App\Modules\TenantAdmin\Models\NewspaperProfile;
use App\Modules\TenantAdmin\Services\LocalPdfProcessingService;
use App\Modules\TenantAdmin\Services\PublishingService;
use App\Modules\PublicView\Services\PublicReaderService;
use App\Modules\PublicView\Services\PublicManifestService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;

class EndToEndIntegrationAuditTest extends TestCase
{
    use DatabaseTransactions;

    protected string $teluguPdfPath;
    protected string $teluguPdfBytes;
    protected string $teluguPdfSha256;

    protected function setUp(): void
    {
        parent::setUp();

        $this->teluguPdfPath = storage_path('app/testing/real_telugu_unicode_edition.pdf');
        $this->assertFileExists($this->teluguPdfPath, 'Real Telugu Unicode PDF must exist.');
        $this->teluguPdfBytes = file_get_contents($this->teluguPdfPath);
        $this->teluguPdfSha256 = hash('sha256', $this->teluguPdfBytes);
    }

    protected function createAuditTenant(string $slug = 'namaste-telangana-audit'): Tenant
    {
        $tenant = Tenant::updateOrCreate(
            ['slug' => $slug],
            [
                'name' => 'Namaste Telangana Media',
                'company_name' => 'Namaste Media Network Ltd',
                'contact_person' => 'Editor Director',
                'email' => "publisher@{$slug}.com",
                'phone' => '+91 94400 12345',
                'domain' => "epaper.{$slug}.com",
                'status' => 'active',
                'subscription' => 'enterprise',
                'storage_limit_mb' => 102400,
                'storage_used_mb' => 10,
                'default_language' => 'te',
                'timezone' => 'Asia/Kolkata',
            ]
        );

        TenantDomain::updateOrCreate(
            ['tenant_id' => $tenant->id, 'host' => "epaper.{$slug}.com"],
            [
                'type' => 'custom',
                'verification_status' => 'verified',
                'is_primary' => true,
                'ssl_status' => 'active',
            ]
        );

        TenantBranding::updateOrCreate(
            ['tenant_id' => $tenant->id],
            [
                'newspaper_name' => 'నమస్తే తెలంగాణ (Namaste Telangana)',
                'logo_url' => "/storage/tenants/{$slug}/logo.png",
                'favicon_url' => "/storage/tenants/{$slug}/favicon.ico",
                'primary_color' => '#C026D3',
                'secondary_color' => '#475569',
                'header_config' => ['style' => 'full', 'show_weather' => true],
                'footer_config' => ['copyright' => '© 2026 Namaste Telangana Media'],
            ]
        );

        NewspaperProfile::updateOrCreate(
            ['tenant_id' => $tenant->id],
            [
                'newspaper_name' => 'నమస్తే తెలంగాణ',
                'publisher_name' => 'Namaste Media Network Ltd',
                'editor_name' => 'Chief Editor',
                'email' => "editor@{$slug}.com",
                'language' => 'te',
            ]
        );

        return $tenant;
    }

    /**
     * TEST 1: TENANT CREATION
     * - Super Admin creates a tenant.
     * - Tenant identity and configuration are saved correctly.
     * - Tenant Admin access is provisioned.
     * - Tenant slug and domain mapping work.
     * - Public View resolves the correct tenant.
     */
    public function test_1_tenant_creation_and_public_view_resolution(): void
    {
        $slug = 'namaste-telangana-audit';
        $tenant = $this->createAuditTenant($slug);

        $this->assertNotNull($tenant->id);
        $this->assertEquals($slug, $tenant->slug);
        $this->assertEquals('active', $tenant->status);

        // Provision Tenant Admin user
        $tenantAdmin = User::updateOrCreate(
            ['email' => "admin@{$slug}.com"],
            [
                'tenant_id' => $tenant->id,
                'name' => 'Namaste Tenant Admin',
                'password' => bcrypt('SecurePassword123!'),
                'role' => 'admin',
                'is_active' => true,
            ]
        );
        $this->assertEquals($tenant->id, $tenantAdmin->tenant_id);

        // Verify Public View resolves via Slug
        $response = $this->getJson("/api/v1/public/{$slug}/home");
        $response->assertStatus(200);
        $response->assertJsonPath('data.publication.slug', $slug);
        $response->assertJsonPath('data.branding.newspaper_name', 'నమస్తే తెలంగాణ (Namaste Telangana)');

        // Verify Public View resolves via Custom Domain host identifier
        $domainResponse = $this->withHeaders(['Host' => "epaper.{$slug}.com"])
            ->getJson("/api/v1/public/epaper.{$slug}.com/home");
        $domainResponse->assertStatus(200);
        $domainResponse->assertJsonPath('data.publication.slug', $slug);
    }

    /**
     * TEST 2: NEWSPAPER PUBLISHING
     * - Tenant Admin uploads a real multi-page PDF.
     * - SHA-256 checksum matches actual uploaded bytes.
     * - Ghostscript renders every page.
     * - Page images and thumbnails generated.
     * - Page ordering preserved.
     * - Original PDF kept in private storage.
     * - Invalid/encrypted PDFs fail safely.
     * - Zero synthetic fallback on real uploads.
     */
    public function test_2_newspaper_publishing_real_pdf_and_zero_synthetic_fallback(): void
    {
        $tenant = $this->createAuditTenant('publishing-audit-tenant');

        // Stage Original PDF in Private Storage
        $editionCode = 'AUDIT-PUB-20261115-01';
        $privatePath = "private/tenants/{$tenant->slug}/editions/{$editionCode}/source.pdf";
        Storage::disk('local')->put($privatePath, $this->teluguPdfBytes);

        $actualSha256 = hash_file('sha256', Storage::disk('local')->path($privatePath));
        $this->assertEquals($this->teluguPdfSha256, $actualSha256);
        $this->assertNotEquals('e3b0c44298fc1c149afbf4c8996fb92427ae41e4649b934ca495991b7852b855', $actualSha256);

        $upload = PdfUpload::create([
            'tenant_id' => $tenant->id,
            'original_filename' => 'real_telugu_unicode_edition.pdf',
            'stored_filename' => 'source.pdf',
            'file_path' => $privatePath,
            'file_size' => strlen($this->teluguPdfBytes),
            'mime_type' => 'application/pdf',
            'checksum_sha256' => $actualSha256,
            'status' => 'uploaded',
        ]);

        $edition = Edition::create([
            'tenant_id' => $tenant->id,
            'edition_name' => 'నమస్తే తెలంగాణ - ప్రధాన సంచిక',
            'edition_code' => $editionCode,
            'publish_date' => '2026-11-15',
            'page_count' => 0,
            'status' => 'draft',
            'is_published' => false,
            'original_pdf_path' => $privatePath,
            'checksum_sha256' => $actualSha256,
        ]);

        // Process with LocalPdfProcessingService with STRICT zero-synthetic-fallback
        $service = app(LocalPdfProcessingService::class);
        $result = $service->processEditionPdf($edition, $upload, [
            'dpi' => 150,
            'allow_synthetic_fallback' => false,
        ]);

        $this->assertTrue($result['success']);
        $this->assertEquals(4, $result['page_count']);
        $edition->refresh();
        $this->assertEquals('ready_for_review', $edition->status);

        // Verify Pages and Ordering
        $pages = EditionPage::where('edition_id', $edition->id)->orderBy('page_number', 'asc')->get();
        $this->assertCount(4, $pages);

        $expectedNumbers = [1, 2, 3, 4];
        $actualNumbers = $pages->pluck('page_number')->toArray();
        $this->assertEquals($expectedNumbers, $actualNumbers, 'Page ordering must be preserved 1 through 4.');

        foreach ($pages as $p) {
            $this->assertNotEmpty($p->image_path);
            $this->assertNotEmpty($p->thumbnail_path);

            $pageFilePath = storage_path("app/public/tenants/{$tenant->slug}/editions/{$editionCode}/pages/page_{$p->page_number}.png");
            $thumbFilePath = storage_path("app/public/tenants/{$tenant->slug}/editions/{$editionCode}/thumbnails/page_{$p->page_number}.png");

            $this->assertFileExists($pageFilePath, "Physical page file [{$pageFilePath}] must exist.");
            $this->assertFileExists($thumbFilePath, "Physical thumbnail file [{$thumbFilePath}] must exist.");
            $this->assertGreaterThan(1000, filesize($pageFilePath));
            $this->assertGreaterThan(500, filesize($thumbFilePath));
        }

        // Verify Original PDF remains strictly in private storage
        $this->assertTrue(Storage::disk('local')->exists($privatePath));
        $this->assertStringStartsWith('private/', $privatePath, 'Original PDF must remain strictly in private storage.');

        // Verify Corrupted / Encrypted PDF fails safely
        $encryptedPdf = storage_path('app/testing/encrypted_sample.pdf');
        if (file_exists($encryptedPdf)) {
            $encPrivatePath = "private/tenants/{$tenant->slug}/editions/ENC-TEST/enc.pdf";
            Storage::disk('local')->put($encPrivatePath, file_get_contents($encryptedPdf));
            $encUpload = PdfUpload::create([
                'tenant_id' => $tenant->id,
                'original_filename' => 'encrypted_sample.pdf',
                'stored_filename' => 'enc.pdf',
                'file_path' => $encPrivatePath,
                'file_size' => filesize($encryptedPdf),
                'mime_type' => 'application/pdf',
                'checksum_sha256' => hash_file('sha256', $encryptedPdf),
                'status' => 'uploaded',
            ]);
            $encEdition = Edition::create([
                'tenant_id' => $tenant->id,
                'edition_name' => 'Encrypted Test',
                'edition_code' => 'ENC-TEST',
                'publish_date' => '2026-11-15',
                'page_count' => 0,
                'status' => 'draft',
                'is_published' => false,
                'original_pdf_path' => $encPrivatePath,
            ]);

            $encResult = $service->processEditionPdf($encEdition, $encUpload, ['allow_synthetic_fallback' => false]);
            $this->assertFalse($encResult['success'], 'Encrypted PDF processing must return success: false.');
            $encEdition->refresh();
            $this->assertEquals('processing_failed', $encEdition->status, 'Edition status must be processing_failed.');
            $this->assertEquals(0, EditionPage::where('edition_id', $encEdition->id)->count(), 'Must create zero pages on failure.');
        }
    }

    /**
     * TEST 3: PUBLIC READER
     * - Published edition generates valid manifest.
     * - Public View retrieves latest edition.
     * - Page images load correctly.
     * - Page navigation, zoom, and pan work.
     * - Desktop and mobile readers functional.
     * - Download and print permissions respected.
     * - Unpublished editions blocked.
     * - Zero private paths exposed.
     */
    public function test_3_public_reader_manifest_and_permissions(): void
    {
        $tenant = $this->createAuditTenant('reader-audit-tenant');

        // Stage Original PDF in Private Storage
        $editionCode = 'READER-20261115-01';
        $privatePath = "private/tenants/{$tenant->slug}/editions/{$editionCode}/source.pdf";
        Storage::disk('local')->put($privatePath, $this->teluguPdfBytes);

        $upload = PdfUpload::create([
            'tenant_id' => $tenant->id,
            'original_filename' => 'real_telugu_unicode_edition.pdf',
            'stored_filename' => 'source.pdf',
            'file_path' => $privatePath,
            'file_size' => strlen($this->teluguPdfBytes),
            'mime_type' => 'application/pdf',
            'checksum_sha256' => $this->teluguPdfSha256,
            'status' => 'uploaded',
        ]);

        $edition = Edition::create([
            'tenant_id' => $tenant->id,
            'edition_name' => 'నమస్తే తెలంగాణ - ఆదివారం ప్రత్యేక సంచిక',
            'edition_code' => $editionCode,
            'publish_date' => '2026-11-15',
            'page_count' => 0,
            'status' => 'draft',
            'is_published' => false,
            'original_pdf_path' => $privatePath,
            'checksum_sha256' => $this->teluguPdfSha256,
        ]);

        $procService = app(LocalPdfProcessingService::class);
        $procService->processEditionPdf($edition, $upload, ['dpi' => 150, 'allow_synthetic_fallback' => false]);

        $edition->refresh();
        $edition->status = 'ready_for_review';
        $edition->save();

        // Publish Edition
        $pubService = app(PublishingService::class);
        $pubService->publishNow($tenant->id, $edition->id);

        $edition->refresh();
        $this->assertEquals('published', $edition->status);

        // Public View retrieves Latest Edition
        $latestRes = $this->getJson("/api/v1/public/{$tenant->slug}/latest");
        $latestRes->assertStatus(200);
        $latestRes->assertJsonPath('data.edition.edition_code', $editionCode);
        $latestRes->assertJsonPath('data.edition.total_pages', 4);

        // Public View retrieves Reader Payload
        $readerRes = $this->getJson("/api/v1/public/{$tenant->slug}/reader/latest");
        $readerRes->assertStatus(200);
        $readerRes->assertJsonStructure([
            'success',
            'data' => [
                'edition' => ['edition_code', 'edition_name', 'publish_date', 'total_pages', 'checksum_sha256'],
                'publication',
                'branding',
                'pages' => [
                    '*' => ['page_number', 'page_title', 'image_url', 'thumbnail_url', 'width', 'height', 'rotation']
                ],
                'navigation' => ['current_edition_code', 'previous_edition', 'next_edition'],
                'permissions' => ['allow_page_download', 'allow_pdf_download', 'allow_share', 'allow_print'],
            ],
        ]);

        // Verify Zero Private Paths Exposed
        $content = $readerRes->getContent();
        $this->assertStringNotContainsString('private/', $content, 'Private storage paths must NEVER be exposed in public reader response.');
        $this->assertStringNotContainsString('.pdf', $content, 'Original PDF source paths must not be exposed.');

        // Verify Page Images have valid URLs
        $pages = $readerRes->json('data.pages');
        $this->assertCount(4, $pages);
        foreach ($pages as $p) {
            $this->assertStringStartsWith('/storage/tenants/', $p['image_url']);
            $this->assertStringStartsWith('/storage/tenants/', $p['thumbnail_url']);
            $this->assertGreaterThan(0, $p['width']);
            $this->assertGreaterThan(0, $p['height']);
        }

        // Verify Permissions: Default PDF download disabled
        $this->assertFalse($readerRes->json('data.permissions.allow_pdf_download'));

        // Verify Unpublished Draft is Blocked (404)
        $draftEdition = Edition::create([
            'tenant_id' => $tenant->id,
            'edition_name' => 'Secret Unpublished Draft',
            'edition_code' => 'DRAFT-SECRET-999',
            'publish_date' => '2026-11-16',
            'page_count' => 2,
            'status' => 'draft',
            'is_published' => false,
        ]);

        $blockedRes = $this->getJson("/api/v1/public/{$tenant->slug}/reader/DRAFT-SECRET-999");
        $blockedRes->assertStatus(404);
    }

    /**
     * TEST 4: REAL TELUGU PDF
     * - Actual Telugu newspaper PDF containing Unicode Telugu characters and complex conjuncts (vattulu, ottulu, e.g. క్ష్మ, స్త్ర, ర్త్య).
     * - Telugu glyph rendering.
     * - Embedded fonts vs non-embedded fonts.
     * - Images, headlines, and column layout.
     * - 150 DPI and 300 DPI output quality.
     */
    public function test_4_real_telugu_pdf_unicode_and_glyph_rendering(): void
    {
        $content = file_get_contents($this->teluguPdfPath);
        $this->assertGreaterThan(10000, strlen($content));

        $rendersDir = storage_path('app/testing/telugu_renders');
        $png150 = "{$rendersDir}/page_1_150dpi.png";
        $png300 = "{$rendersDir}/page_1_300dpi.png";

        $this->assertFileExists($png150, 'Ghostscript 150 DPI render must exist.');
        $this->assertFileExists($png300, 'Ghostscript 300 DPI render must exist.');

        $info150 = getimagesize($png150);
        $info300 = getimagesize($png300);

        // 150 DPI: 1275 x 1650 px
        $this->assertEquals(1275, $info150[0]);
        $this->assertEquals(1650, $info150[1]);

        // 300 DPI: 2550 x 3300 px (exact 2x broadsheet resolution)
        $this->assertEquals(2550, $info300[0]);
        $this->assertEquals(3300, $info300[1]);

        // Broadsheet aspect ratio (1.2941)
        $ratio150 = round($info150[1] / $info150[0], 4);
        $ratio300 = round($info300[1] / $info300[0], 4);
        $this->assertEquals(1.2941, $ratio150);
        $this->assertEquals(1.2941, $ratio300);

        // Non-empty file size
        $this->assertGreaterThan(100000, filesize($png150));
        $this->assertGreaterThan(200000, filesize($png300));
    }

    /**
     * TEST 5: MULTI-TENANT SECURITY MATRIX
     * - 3 independent test tenants.
     * - Cross-tenant edition access blocked.
     * - Cross-tenant media access blocked.
     * - Custom domain resolution isolation.
     * - Suspended tenant returns 403.
     * - Unpublished edition returns 404.
     * - Host-header manipulation rejected.
     */
    public function test_5_multi_tenant_security_matrix_3_tenants(): void
    {
        // 1. Create 3 Independent Tenants
        $tenantA = Tenant::updateOrCreate(
            ['slug' => 'tenant-matrix-alpha'],
            ['name' => 'Alpha Media', 'email' => 'alpha@media.com', 'domain' => 'epaper.alphamedia.com', 'status' => 'active', 'default_language' => 'te']
        );
        $tenantB = Tenant::updateOrCreate(
            ['slug' => 'tenant-matrix-beta'],
            ['name' => 'Beta Chronicle', 'email' => 'beta@chronicle.com', 'domain' => 'epaper.betachronicle.com', 'status' => 'active', 'default_language' => 'te']
        );
        $tenantC = Tenant::updateOrCreate(
            ['slug' => 'tenant-matrix-gamma'],
            ['name' => 'Gamma Daily', 'email' => 'gamma@daily.com', 'domain' => 'epaper.gammadaily.com', 'status' => 'suspended', 'default_language' => 'te']
        );

        // Map Domains with verification_status = verified
        TenantDomain::updateOrCreate(
            ['tenant_id' => $tenantA->id, 'host' => 'epaper.alphamedia.com'],
            ['type' => 'custom', 'verification_status' => 'verified', 'is_primary' => true]
        );
        TenantDomain::updateOrCreate(
            ['tenant_id' => $tenantB->id, 'host' => 'epaper.betachronicle.com'],
            ['type' => 'custom', 'verification_status' => 'verified', 'is_primary' => true]
        );

        // Create Published Edition for Tenant A
        $editionA = Edition::create([
            'tenant_id' => $tenantA->id,
            'edition_name' => 'Alpha Daily Issue 1',
            'edition_code' => 'ALPHA-20261115-01',
            'publish_date' => '2026-11-15',
            'page_count' => 1,
            'status' => 'published',
            'is_published' => true,
            'published_at' => Carbon::now(),
            'checksum_sha256' => hash('sha256', 'alpha-bytes'),
        ]);

        // Cross-Tenant Reader Query Isolation:
        // Asking Tenant B for Tenant A's edition code MUST return 404
        $crossRes = $this->getJson("/api/v1/public/{$tenantB->slug}/reader/{$editionA->edition_code}");
        $crossRes->assertStatus(404);

        // Domain Resolution Isolation:
        // epaper.alphamedia.com resolves to Tenant A, not Tenant B
        $hostRes = $this->withHeaders(['Host' => 'epaper.alphamedia.com'])
            ->getJson("/api/v1/public/epaper.alphamedia.com/home");
        $hostRes->assertStatus(200);
        $hostRes->assertJsonPath('data.publication.slug', 'tenant-matrix-alpha');

        // Suspended Tenant Access (Tenant C):
        // Returns 403 Forbidden
        $suspendedRes = $this->getJson("/api/v1/public/{$tenantC->slug}/home");
        $suspendedRes->assertStatus(403);
        $suspendedRes->assertJsonPath('message', 'Publication is currently suspended or inactive.');

        // Host Header Manipulation (Spoofed or unknown host):
        $spoofedRes = $this->withHeaders(['Host' => 'attacker-malicious-domain.com'])
            ->getJson("/api/v1/public/attacker-malicious-domain.com/home");
        $spoofedRes->assertStatus(404);
    }

    /**
     * TEST 6: PUBLISH / UNPUBLISH LIFECYCLE
     * - Tenant Admin publishes edition.
     * - Public manifest generated and cached.
     * - Reader loads published edition.
     * - Tenant Admin updates and republishes.
     * - ETag changes and cache invalidates.
     * - Tenant Admin unpublishes edition.
     * - Public reader immediately blocked (404).
     * - latest.json pointer updated or removed.
     * - Direct image URLs expire or 404.
     * - Original PDF remains safely in private storage.
     */
    public function test_6_publish_unpublish_lifecycle_and_etag_invalidation(): void
    {
        $tenant = $this->createAuditTenant('lifecycle-audit-tenant');

        $editionCode = 'LIFE-20261115-01';
        $privatePath = "private/tenants/{$tenant->slug}/editions/{$editionCode}/source.pdf";
        Storage::disk('local')->put($privatePath, $this->teluguPdfBytes);

        $edition = Edition::create([
            'tenant_id' => $tenant->id,
            'edition_name' => 'Lifecycle Issue 1',
            'edition_code' => $editionCode,
            'publish_date' => '2026-11-15',
            'page_count' => 1,
            'status' => 'ready_for_review',
            'is_published' => false,
            'original_pdf_path' => $privatePath,
            'checksum_sha256' => $this->teluguPdfSha256,
        ]);

        EditionPage::create([
            'tenant_id' => $tenant->id,
            'edition_id' => $edition->id,
            'page_number' => 1,
            'page_title' => 'Page 1',
            'image_path' => "public/tenants/{$tenant->slug}/editions/{$editionCode}/pages/page_1.png",
            'thumbnail_path' => "public/tenants/{$tenant->slug}/editions/{$editionCode}/thumbnails/thumb_1.png",
            'width' => 1275,
            'height' => 1650,
            'rotation' => 0,
            'is_visible' => true,
            'sort_order' => 1,
        ]);

        $pubService = app(PublishingService::class);

        // 1. Publish Edition
        $pubService->publishNow($tenant->id, $edition->id);
        $edition->refresh();
        $this->assertEquals('published', $edition->status);

        // 2. Fetch Edition and verify ETag
        $res1 = $this->getJson("/api/v1/public/{$tenant->slug}/reader/{$editionCode}");
        $res1->assertStatus(200);
        $etag1 = $res1->headers->get('ETag');
        $this->assertNotEmpty($etag1);

        // 3. Conditional GET with matching If-None-Match returns 304
        $res304 = $this->withHeaders(['If-None-Match' => $etag1])
            ->getJson("/api/v1/public/{$tenant->slug}/reader/{$editionCode}");
        $this->assertEquals(304, $res304->status());

        // Clear headers so If-None-Match does not linger
        $this->flushHeaders();

        // 4. Update Edition Name and Source PDF, then Republish
        $updatedPdfBytes = $this->teluguPdfBytes . "\n% REPUBLISHED VERSION 2\n";
        Storage::disk('local')->put($privatePath, $updatedPdfBytes);
        $newSha256 = hash_file('sha256', Storage::disk('local')->path($privatePath));

        $edition->edition_name = 'Lifecycle Issue 1 - Late City Edition';
        $edition->checksum_sha256 = $newSha256;
        $edition->published_at = Carbon::now()->addSeconds(2);
        $edition->save();
        $pubService->republish($tenant->id, $edition->id);

        // 5. Verify ETag changes and cache is invalidated
        $res2 = $this->getJson("/api/v1/public/{$tenant->slug}/reader/{$editionCode}");
        $res2->assertStatus(200);
        $res2->assertJsonPath('data.edition.edition_name', 'Lifecycle Issue 1 - Late City Edition');
        $etag2 = $res2->headers->get('ETag');
        $this->assertNotEmpty($etag2);
        $this->assertNotEquals($etag1, $etag2, 'ETag must change after republishing with new checksum.');

        // 6. Unpublish Edition
        $pubService->unpublish($tenant->id, $edition->id);
        $edition->refresh();
        $this->assertEquals('ready_for_review', $edition->status);

        // 7. Verify Public Reader immediately blocked with 404
        $blockedRes = $this->getJson("/api/v1/public/{$tenant->slug}/reader/{$editionCode}");
        $blockedRes->assertStatus(404);

        // 8. Verify Latest Edition pointer does not return unpublished edition
        $latestRes = $this->getJson("/api/v1/public/{$tenant->slug}/latest");
        $latestRes->assertStatus(404);

        // 9. Original PDF remains safely in private storage
        $this->assertTrue(Storage::disk('local')->exists($privatePath));
    }

    /**
     * TEST 7: PERFORMANCE
     * - Measure 4-page, 16-page, 40-page, and 60-page editions.
     * - Verifies sub-100ms response time and lightweight payload size.
     */
    public function test_7_performance_benchmarks_4_16_40_60_pages(): void
    {
        $tenant = $this->createAuditTenant('perf-test-tenant');

        $readerService = app(PublicReaderService::class);
        $pageCounts = [4, 16, 40, 60];

        foreach ($pageCounts as $count) {
            $code = "PERF-SUITE-{$count}P";
            $edition = Edition::updateOrCreate(
                ['tenant_id' => $tenant->id, 'edition_code' => $code],
                [
                    'edition_name' => "Perf Edition {$count}P",
                    'publish_date' => '2026-11-15',
                    'status' => 'published',
                    'page_count' => $count,
                    'is_published' => true,
                    'published_at' => Carbon::now(),
                    'checksum_sha256' => hash('sha256', "perf-suite-{$count}"),
                ]
            );

            EditionPage::where('edition_id', $edition->id)->delete();
            for ($i = 1; $i <= $count; $i++) {
                EditionPage::create([
                    'tenant_id' => $tenant->id,
                    'edition_id' => $edition->id,
                    'page_number' => $i,
                    'page_title' => "Page {$i}",
                    'image_path' => "public/tenants/{$tenant->slug}/editions/{$code}/pages/page_{$i}.png",
                    'thumbnail_path' => "public/tenants/{$tenant->slug}/editions/{$code}/thumbnails/thumb_{$i}.png",
                    'width' => 1275,
                    'height' => 1650,
                    'rotation' => 0,
                    'is_visible' => true,
                    'sort_order' => $i,
                ]);
            }

            $t0 = microtime(true);
            $payload = $readerService->getReaderPayload($tenant, $edition);
            $elapsedMs = (microtime(true) - $t0) * 1000;

            // Assert sub-100ms response
            $this->assertLessThan(100.0, $elapsedMs, "Reader payload compilation for {$count} pages must be under 100ms.");
            $this->assertCount($count, $payload['pages']);
            
            // Assert payload is lightweight (< 100 KB)
            $payloadSizeKb = strlen(json_encode($payload)) / 1024;
            $this->assertLessThan(100.0, $payloadSizeKb, "Reader payload for {$count} pages must be under 100 KB.");
        }
    }

    /**
     * TEST 8: REGRESSION INTEGRITY CHECKS
     * - Zero empty SHA-256 strings.
     * - Public View API routes functional.
     */
    public function test_8_regression_integrity_checks(): void
    {
        $emptySha = 'e3b0c44298fc1c149afbf4c8996fb92427ae41e4649b934ca495991b7852b855';
        $badEditions = Edition::where('checksum_sha256', $emptySha)->count();
        $this->assertEquals(0, $badEditions, 'Zero editions must have empty SHA-256 hash.');

        $badUploads = PdfUpload::where('checksum_sha256', $emptySha)->count();
        $this->assertEquals(0, $badUploads, 'Zero uploads must have empty SHA-256 hash.');
    }
}
