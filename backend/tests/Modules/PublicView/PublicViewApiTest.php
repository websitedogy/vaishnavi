<?php

namespace Tests\Modules\PublicView;

use App\Models\User;
use App\Modules\SuperAdmin\Models\Tenant;
use App\Modules\SuperAdmin\Models\TenantBranding;
use App\Modules\SuperAdmin\Models\TenantDomain;
use App\Modules\TenantAdmin\Models\Edition;
use App\Modules\TenantAdmin\Models\EditionPage;
use App\Modules\TenantAdmin\Models\NewspaperProfile;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class PublicViewApiTest extends TestCase
{
    use DatabaseTransactions;

    protected Tenant $tenantActive;
    protected Tenant $tenantSuspended;
    protected Edition $editionPublishedA;
    protected Edition $editionPublishedB;
    protected Edition $editionDraft;
    protected Edition $editionScheduled;

    protected function setUp(): void
    {
        parent::setUp();

        $now = Carbon::now();

        // 1. Create Active Tenant
        $this->tenantActive = Tenant::firstOrCreate(
            ['slug' => 'test-public-daily'],
            [
                'name' => 'Test Public Daily',
                'company_name' => 'Public Daily Media Inc',
                'contact_person' => 'Editor in Chief',
                'email' => 'editor@publicdaily.com',
                'phone' => '+91 99999 11111',
                'domain' => 'epaper.testpublicdaily.com',
                'status' => 'active',
                'default_language' => 'te',
            ]
        );

        // Active Branding
        TenantBranding::updateOrInsert(
            ['tenant_id' => $this->tenantActive->id],
            [
                'newspaper_name' => 'Test Public Daily Newspaper',
                'logo_url' => '/storage/tenants/test-public-daily/logo.png',
                'favicon_url' => '/storage/tenants/test-public-daily/favicon.ico',
                'primary_color' => '#1E3A8A',
                'secondary_color' => '#0EA5E9',
                'typography' => 'Inter',
                'header_config' => json_encode(['style' => 'standard', 'show_date' => true, 'show_search' => true]),
                'footer_config' => json_encode(['copyright' => '© 2026 Test Public Daily. All rights reserved.']),
                'social_links' => json_encode(['twitter' => 'https://twitter.com/publicdaily', 'facebook' => 'https://facebook.com/publicdaily']),
                'custom_css' => 'body { font-family: Inter, sans-serif; }',
                'is_published' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ]
        );

        // Verified Custom Domain
        TenantDomain::updateOrInsert(
            ['host' => 'epaper.testpublicdaily.com'],
            [
                'tenant_id' => $this->tenantActive->id,
                'type' => 'custom',
                'is_primary' => true,
                'verification_status' => 'verified',
                'ssl_status' => 'active',
                'created_at' => $now,
                'updated_at' => $now,
            ]
        );

        // 2. Create Suspended Tenant
        $this->tenantSuspended = Tenant::firstOrCreate(
            ['slug' => 'test-suspended-daily'],
            [
                'name' => 'Suspended Daily News',
                'email' => 'editor@suspended.com',
                'status' => 'suspended',
            ]
        );

        // 3. Create Published Editions for Active Tenant
        $this->editionPublishedA = Edition::create([
            'tenant_id' => $this->tenantActive->id,
            'edition_code' => 'PUB-ED-001',
            'edition_name' => 'Morning Main Edition',
            'edition_title' => 'Morning Main Edition - Telugu Daily',
            'edition_language' => 'te',
            'category' => 'Main Edition',
            'description' => 'Comprehensive morning broadsheet covering state news.',
            'publish_date' => '2026-11-14',
            'status' => 'published',
            'total_pages' => 2,
            'checksum_sha256' => 'aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa',
            'published_at' => $now->copy()->subDay(),
            'original_pdf_path' => 'private/tenants/test-public-daily/editions/PUB-ED-001/original/PUB-ED-001.pdf',
            'thumbnail_path' => '/storage/tenants/test-public-daily/editions/PUB-ED-001/thumbnails/page_1.png',
        ]);

        // Seed 2 pages for Edition A
        EditionPage::create([
            'edition_id' => $this->editionPublishedA->id,
            'tenant_id' => $this->tenantActive->id,
            'page_number' => 1,
            'page_title' => 'Main Front Page',
            'image_path' => '/storage/tenants/test-public-daily/editions/PUB-ED-001/pages/page_1.png',
            'thumbnail_path' => '/storage/tenants/test-public-daily/editions/PUB-ED-001/thumbnails/page_1.png',
            'width' => 1400,
            'height' => 2100,
            'rotation' => 0,
            'is_visible' => true,
            'sort_order' => 1,
        ]);
        EditionPage::create([
            'edition_id' => $this->editionPublishedA->id,
            'tenant_id' => $this->tenantActive->id,
            'page_number' => 2,
            'page_title' => 'State Politics',
            'image_path' => '/storage/tenants/test-public-daily/editions/PUB-ED-001/pages/page_2.png',
            'thumbnail_path' => '/storage/tenants/test-public-daily/editions/PUB-ED-001/thumbnails/page_2.png',
            'width' => 1400,
            'height' => 2100,
            'rotation' => 0,
            'is_visible' => true,
            'sort_order' => 2,
        ]);

        // Edition B (Latest Published)
        $this->editionPublishedB = Edition::create([
            'tenant_id' => $this->tenantActive->id,
            'edition_code' => 'PUB-ED-002',
            'edition_name' => 'Sunday Special Edition',
            'edition_title' => 'Sunday Special Edition - Full Broadsheet',
            'edition_language' => 'te',
            'category' => 'Sunday Magazine',
            'description' => 'Exclusive Sunday features, cultural reviews and sports.',
            'publish_date' => '2026-11-15',
            'status' => 'published',
            'total_pages' => 1,
            'checksum_sha256' => 'bbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbb',
            'published_at' => $now,
            'original_pdf_path' => 'private/tenants/test-public-daily/editions/PUB-ED-002/original/PUB-ED-002.pdf',
            'thumbnail_path' => '/storage/tenants/test-public-daily/editions/PUB-ED-002/thumbnails/page_1.png',
        ]);
        EditionPage::create([
            'edition_id' => $this->editionPublishedB->id,
            'tenant_id' => $this->tenantActive->id,
            'page_number' => 1,
            'page_title' => 'Sunday Cover Story',
            'image_path' => '/storage/tenants/test-public-daily/editions/PUB-ED-002/pages/page_1.png',
            'thumbnail_path' => '/storage/tenants/test-public-daily/editions/PUB-ED-002/thumbnails/page_1.png',
            'width' => 1400,
            'height' => 2100,
            'rotation' => 0,
            'is_visible' => true,
            'sort_order' => 1,
        ]);

        // 4. Create Draft and Scheduled editions (MUST NOT be exposed to public)
        $this->editionDraft = Edition::create([
            'tenant_id' => $this->tenantActive->id,
            'edition_code' => 'PUB-DRAFT-001',
            'edition_name' => 'Draft Unfinished Issue',
            'publish_date' => '2026-11-16',
            'status' => 'draft',
            'total_pages' => 1,
        ]);

        $this->editionScheduled = Edition::create([
            'tenant_id' => $this->tenantActive->id,
            'edition_code' => 'PUB-SCHED-001',
            'edition_name' => 'Scheduled Future Issue',
            'publish_date' => '2026-11-17',
            'status' => 'scheduled',
            'scheduled_at' => $now->copy()->addDay(),
            'total_pages' => 1,
        ]);
    }

    /**
     * Test 1: Public Homepage returns active tenant profile, branding, and latest edition.
     */
    public function test_public_tenant_homepage_returns_published_data_and_branding(): void
    {
        $response = $this->getJson("/api/v1/public/{$this->tenantActive->slug}/home");

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.publication.slug', 'test-public-daily')
            ->assertJsonPath('data.branding.primary_color', '#1E3A8A')
            ->assertJsonPath('data.has_editions', true)
            ->assertJsonPath('data.latest_edition.edition.edition_code', 'PUB-ED-002')
            ->assertHeader('Cache-Control');

        // Verify recent editions array includes published editions
        $this->assertCount(2, $response->json('data.recent_editions'));
    }

    /**
     * Test 2: Suspended tenant is strictly blocked with HTTP 403.
     */
    public function test_suspended_tenant_public_access_is_strictly_forbidden_403(): void
    {
        $response = $this->getJson("/api/v1/public/{$this->tenantSuspended->slug}/home");

        $response->assertStatus(403)
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'Publication is currently suspended or inactive.');
    }

    /**
     * Test 3: Inactive or non-existent tenant returns HTTP 404.
     */
    public function test_inactive_or_nonexistent_tenant_returns_404(): void
    {
        $response = $this->getJson("/api/v1/public/non-existent-newspaper-slug/home");

        $response->assertStatus(404)
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'Publication not found.');
    }

    /**
     * Test 4: Latest edition endpoint returns latest published manifest.
     */
    public function test_latest_edition_retrieves_active_published_manifest(): void
    {
        $response = $this->getJson("/api/v1/public/{$this->tenantActive->slug}/latest");

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.edition.edition_code', 'PUB-ED-002')
            ->assertJsonPath('data.edition.checksum_sha256', 'bbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbb')
            ->assertHeader('ETag');
    }

    /**
     * Test 5: Tenant with no published editions returns clean HTTP 404.
     */
    public function test_tenant_with_no_published_editions_returns_404_cleanly(): void
    {
        $emptyTenant = Tenant::create([
            'slug' => 'empty-brand-daily',
            'name' => 'Empty Brand Daily',
            'email' => 'empty@branddaily.com',
            'status' => 'active',
        ]);

        $response = $this->getJson("/api/v1/public/{$emptyTenant->slug}/latest");

        $response->assertStatus(404)
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'No published editions found for this newspaper.');
    }

    /**
     * Test 6: Specific published edition retrieval by date and by edition code.
     */
    public function test_specific_published_edition_retrieval_by_date_and_code(): void
    {
        // By Edition Code
        $resByCode = $this->getJson("/api/v1/public/{$this->tenantActive->slug}/editions/PUB-ED-001");
        $resByCode->assertStatus(200)
            ->assertJsonPath('data.edition.edition_code', 'PUB-ED-001');

        // By Date
        $resByDate = $this->getJson("/api/v1/public/{$this->tenantActive->slug}/editions/2026-11-14");
        $resByDate->assertStatus(200)
            ->assertJsonPath('data.edition.edition_code', 'PUB-ED-001');
    }

    /**
     * Test 7: Unpublished draft and scheduled editions return HTTP 404 to public readers.
     */
    public function test_unpublished_draft_or_scheduled_edition_returns_404(): void
    {
        // Draft issue
        $resDraft = $this->getJson("/api/v1/public/{$this->tenantActive->slug}/editions/PUB-DRAFT-001");
        $resDraft->assertStatus(404);

        // Scheduled future issue
        $resSched = $this->getJson("/api/v1/public/{$this->tenantActive->slug}/editions/PUB-SCHED-001");
        $resSched->assertStatus(404);
    }

    /**
     * Test 8: Dedicated digital reader payload returns pages, dimensions, and adjacent navigation.
     */
    public function test_digital_reader_payload_returns_pages_aspect_ratio_and_navigation(): void
    {
        $response = $this->getJson("/api/v1/public/{$this->tenantActive->slug}/reader/PUB-ED-001");

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.navigation.current_edition_code', 'PUB-ED-001')
            ->assertJsonPath('data.navigation.next_edition.edition_code', 'PUB-ED-002');

        $pages = $response->json('data.pages');
        $this->assertCount(2, $pages);
        $this->assertEquals(1, $pages[0]['page_number']);
        $this->assertEquals(1400, $pages[0]['width']);
        $this->assertEquals(2100, $pages[0]['height']);
        $this->assertStringContainsString('/storage/', $pages[0]['image_url']);
    }

    /**
     * Test 9: Archive calendar and monthly grouping.
     */
    public function test_archive_calendar_and_monthly_grouping(): void
    {
        $response = $this->getJson("/api/v1/public/{$this->tenantActive->slug}/archive?year=2026&month=11");

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('calendar.year', 2026)
            ->assertJsonPath('calendar.month', 11)
            ->assertJsonPath('calendar.total_editions', 2);

        $dates = $response->json('calendar.published_dates');
        $this->assertContains('2026-11-14', $dates);
        $this->assertContains('2026-11-15', $dates);
    }

    /**
     * Test 10: Public search filters published editions by query and category.
     */
    public function test_public_search_filters_published_editions_by_query_and_date(): void
    {
        // Search by title keyword
        $resSearchTitle = $this->getJson("/api/v1/public/{$this->tenantActive->slug}/search?q=Sunday");
        $resSearchTitle->assertStatus(200)
            ->assertJsonPath('data.search_mode', 'edition_metadata');
        $this->assertCount(1, $resSearchTitle->json('data.results'));
        $this->assertEquals('PUB-ED-002', $resSearchTitle->json('data.results.0.edition_code'));

        // Search by category
        $resSearchCat = $this->getJson("/api/v1/public/{$this->tenantActive->slug}/search?category=Main+Edition");
        $resSearchCat->assertStatus(200);
        $this->assertCount(1, $resSearchCat->json('data.results'));
        $this->assertEquals('PUB-ED-001', $resSearchCat->json('data.results.0.edition_code'));
    }

    /**
     * Test 11: Public branding endpoint returns sanitized styles and colors.
     */
    public function test_public_branding_endpoint_returns_sanitized_styles(): void
    {
        $response = $this->getJson("/api/v1/public/{$this->tenantActive->slug}/branding");

        $response->assertStatus(200)
            ->assertJsonPath('data.branding.primary_color', '#1E3A8A')
            ->assertJsonPath('data.branding.secondary_color', '#0EA5E9')
            ->assertJsonPath('data.branding.typography', 'Inter')
            ->assertHeader('ETag');
    }

    /**
     * Test 12: Public sitemap.xml returns valid XML containing published edition URLs.
     */
    public function test_public_sitemap_xml_generation(): void
    {
        $response = $this->get("/api/v1/public/{$this->tenantActive->slug}/sitemap.xml");

        $response->assertStatus(200)
            ->assertHeader('Content-Type', 'application/xml; charset=utf-8');

        $content = $response->getContent();
        $this->assertStringContainsString('<?xml version="1.0" encoding="UTF-8"?>', $content);
        $this->assertStringContainsString('/epaper/PUB-ED-001', $content);
        $this->assertStringContainsString('/epaper/PUB-ED-002', $content);
        // Ensure unpublished editions are not in sitemap
        $this->assertStringNotContainsString('PUB-DRAFT-001', $content);
    }

    /**
     * Test 13: Conditional ETag returns HTTP 304 Not Modified.
     */
    public function test_conditional_etag_and_304_not_modified(): void
    {
        // First request to get ETag
        $firstRes = $this->getJson("/api/v1/public/{$this->tenantActive->slug}/latest");
        $firstRes->assertStatus(200);
        $etag = $firstRes->headers->get('ETag');
        $this->assertNotEmpty($etag);

        // Second request with If-None-Match
        $secondRes = $this->withHeaders(['If-None-Match' => $etag])
            ->getJson("/api/v1/public/{$this->tenantActive->slug}/latest");

        $secondRes->assertStatus(304);
    }

    /**
     * Test 14: Zero private storage paths exposed in any public response.
     */
    public function test_zero_private_storage_paths_exposed_in_public_endpoints(): void
    {
        $response = $this->getJson("/api/v1/public/{$this->tenantActive->slug}/latest");
        $json = $response->getContent();

        $this->assertStringNotContainsString('private/', $json);
        $this->assertStringNotContainsString('PUB-ED-002.pdf', $json);
        $this->assertStringNotContainsString('original_pdf_path', $json);
    }

    /**
     * Test 15: Custom domain resolution via Host header.
     */
    public function test_custom_domain_resolution_via_host_header(): void
    {
        // Request with custom verified domain host header and omitted slug
        $response = $this->withHeaders(['Host' => 'epaper.testpublicdaily.com'])
            ->getJson("/api/v1/public/epaper.testpublicdaily.com/latest");

        $response->assertStatus(200)
            ->assertJsonPath('data.edition.edition_code', 'PUB-ED-002');
    }

    /**
     * Test 16: Cross-tenant isolation in public endpoints.
     */
    public function test_cross_tenant_isolation_in_public_endpoints(): void
    {
        $tenantOther = Tenant::create([
            'slug' => 'other-tenant-daily',
            'name' => 'Other Tenant Daily',
            'email' => 'other@tenantdaily.com',
            'status' => 'active',
        ]);

        // Attempt to request Tenant A's edition code under Tenant Other's slug -> MUST 404
        $response = $this->getJson("/api/v1/public/{$tenantOther->slug}/editions/PUB-ED-001");
        $response->assertStatus(404);
    }
}
