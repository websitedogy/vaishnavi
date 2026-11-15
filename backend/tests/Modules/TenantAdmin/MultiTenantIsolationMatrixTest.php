<?php

namespace Tests\Modules\TenantAdmin;

use Tests\TestCase;
use App\Models\User;
use App\Modules\SuperAdmin\Models\Tenant;
use App\Modules\SuperAdmin\Models\TenantBranding;
use App\Modules\TenantAdmin\Models\Edition;
use App\Modules\TenantAdmin\Models\EditionPage;
use App\Modules\TenantAdmin\Models\MediaFile;
use App\Modules\TenantAdmin\Models\TenantNotification;
use App\Models\SupportTicket;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;

class MultiTenantIsolationMatrixTest extends TestCase
{
    use DatabaseTransactions;

    protected Tenant $tenant1;
    protected Tenant $tenant2;
    protected Tenant $tenant3;

    protected User $user1;
    protected User $user2;
    protected User $user3;

    protected Edition $t1Edition;
    protected EditionPage $t1Page;
    protected MediaFile $t1Media;
    protected TenantNotification $t1Notification;
    protected SupportTicket $t1Ticket;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
        Storage::fake('public');

        // Tenant 1: Telangana Samacharam
        $this->tenant1 = Tenant::firstOrCreate(
            ['slug' => 'telangana-samacharam'],
            [
                'name' => 'Telangana Samacharam',
                'email' => 'publisher@samacharam.com',
                'status' => 'active',
                'storage_quota_mb' => 51200,
                'storage_used_mb' => 100,
                'is_active' => true,
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

        // Tenant 2: Andhra Prabha Digital
        $this->tenant2 = Tenant::firstOrCreate(
            ['slug' => 'andhra-prabha-digital'],
            [
                'name' => 'Andhra Prabha Digital',
                'email' => 'editions@andhraprabha.com',
                'status' => 'active',
                'storage_quota_mb' => 51200,
                'storage_used_mb' => 50,
                'is_active' => true,
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

        // Tenant 3: Eenadu Digital Media
        $this->tenant3 = Tenant::firstOrCreate(
            ['slug' => 'eenadu-digital-media'],
            [
                'name' => 'Eenadu Digital Media',
                'email' => 'desk@eenadu-digital.com',
                'status' => 'active',
                'storage_quota_mb' => 102400,
                'storage_used_mb' => 200,
                'is_active' => true,
            ]
        );
        $this->user3 = User::firstOrCreate(
            ['email' => 'editor@eenadu-digital.com'],
            [
                'tenant_id' => $this->tenant3->id,
                'name' => 'Editor Eenadu',
                'password' => bcrypt('TenantPassword123!'),
                'role' => 'admin',
                'is_active' => true,
            ]
        );

        // Setup Tenant 1 Resources
        $this->t1Edition = Edition::firstOrCreate(
            ['edition_code' => 'T1-SECRET-01'],
            [
                'tenant_id' => $this->tenant1->id,
                'edition_name' => 'Samacharam Morning Confidential Issue',
                'edition_title' => 'Samacharam Morning Confidential Issue',
                'edition_language' => 'te',
                'category' => 'Confidential',
                'publish_date' => Carbon::now()->toDateString(),
                'total_pages' => 2,
                'file_size_mb' => 4.5,
                'status' => 'ready_for_review',
                'original_pdf_path' => 'private/tenants/telangana-samacharam/editions/T1-SECRET-01/original/T1-SECRET-01.pdf',
            ]
        );
        $this->t1Edition->update(['status' => 'ready_for_review']);

        Storage::disk('local')->put($this->t1Edition->original_pdf_path, '%PDF-1.4 mock private pdf content');

        $this->t1Page = EditionPage::firstOrCreate(
            [
                'edition_id' => $this->t1Edition->id,
                'page_number' => 1,
            ],
            [
                'tenant_id' => $this->tenant1->id,
                'page_title' => 'Page 1 Confidential Headline',
                'image_path' => '/storage/tenants/telangana-samacharam/editions/T1-SECRET-01/pages/page_1.png',
                'thumbnail_path' => '/storage/tenants/telangana-samacharam/editions/T1-SECRET-01/thumbnails/page_1.png',
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
                'name' => 'samacharam_exclusive_banner.png',
            ],
            [
                'path' => '/storage/tenants/telangana-samacharam/media/banner.png',
                'disk' => 'public',
                'type' => 'image',
                'mime_type' => 'image/png',
                'size' => 20480,
            ]
        );

        $this->t1Notification = TenantNotification::firstOrCreate(
            [
                'tenant_id' => $this->tenant1->id,
                'title' => 'T1 Confidential Advisory',
            ],
            [
                'user_id' => $this->user1->id,
                'type' => 'system_alert',
                'message' => 'Private board update for Tenant 1 only.',
                'is_read' => false,
            ]
        );

        $this->t1Ticket = SupportTicket::firstOrCreate(
            [
                'tenant_id' => $this->tenant1->id,
                'subject' => 'T1 Infrastructure Billing Question',
            ],
            [
                'user_id' => $this->user1->id,
                'ticket_number' => 'TCK-T1-001',
                'category' => 'Billing',
                'message' => 'Inquiry regarding AWS storage allocations.',
                'priority' => 'high',
                'status' => 'open',
            ]
        );
    }

    protected function authHeaderFor(User $user): array
    {
        $token = $user->createToken('test_auth_token')->plainTextToken;
        return ['Authorization' => "Bearer {$token}"];
    }

    /**
     * Test 1: Cross-Tenant Edition Access (GET, PUT, DELETE) blocked across Tenant 2 & Tenant 3.
     */
    public function test_cross_tenant_editions_access_blocked_for_all_foreign_tenants(): void
    {
        foreach ([$this->user2, $this->user3] as $foreignUser) {
            $headers = $this->authHeaderFor($foreignUser);

            // GET single edition
            $getRes = $this->withHeaders($headers)->getJson("/api/v1/tenant-admin/editions/{$this->t1Edition->id}");
            $getRes->assertStatus(404);

            // PUT update edition
            $putRes = $this->withHeaders($headers)->putJson("/api/v1/tenant-admin/editions/{$this->t1Edition->id}", [
                'edition_name' => 'Malicious Tampered Title',
            ]);
            $putRes->assertStatus(404);

            // DELETE edition
            $delRes = $this->withHeaders($headers)->deleteJson("/api/v1/tenant-admin/editions/{$this->t1Edition->id}");
            $delRes->assertStatus(404);
        }

        // Assert Tenant 1 edition was unmodified
        $fresh = $this->t1Edition->fresh();
        $this->assertEquals('Samacharam Morning Confidential Issue', $fresh->edition_name);
        $this->assertNotNull($fresh);
    }

    /**
     * Test 2: Cross-Tenant Original PDF Download blocked; Owner allowed.
     */
    public function test_cross_tenant_original_pdf_download_blocked(): void
    {
        // Tenant 2 attempt
        $resT2 = $this->withHeaders($this->authHeaderFor($this->user2))
            ->getJson("/api/v1/tenant-admin/editions/{$this->t1Edition->id}/download-pdf");
        $resT2->assertStatus(404);

        // Tenant 3 attempt
        $resT3 = $this->withHeaders($this->authHeaderFor($this->user3))
            ->getJson("/api/v1/tenant-admin/editions/{$this->t1Edition->id}/download-pdf");
        $resT3->assertStatus(404);

        // Owner (Tenant 1) authorized download
        $resT1 = $this->withHeaders($this->authHeaderFor($this->user1))
            ->get("/api/v1/tenant-admin/editions/{$this->t1Edition->id}/download-pdf");
        $resT1->assertStatus(200);
        $resT1->assertHeader('content-type', 'application/pdf');
    }

    /**
     * Test 3: Cross-Tenant Page Operations (GET, PUT reorder, POST rotate, DELETE) blocked.
     */
    public function test_cross_tenant_page_operations_blocked(): void
    {
        foreach ([$this->user2, $this->user3] as $foreignUser) {
            $headers = $this->authHeaderFor($foreignUser);

            // GET pages
            $this->withHeaders($headers)
                ->getJson("/api/v1/tenant-admin/editions/{$this->t1Edition->id}/pages")
                ->assertStatus(404);

            // PUT reorder
            $this->withHeaders($headers)
                ->putJson("/api/v1/tenant-admin/editions/{$this->t1Edition->id}/pages/reorder", [
                    'page_ids' => [$this->t1Page->id],
                ])
                ->assertStatus(404);

            // POST rotate
            $this->withHeaders($headers)
                ->postJson("/api/v1/tenant-admin/editions/{$this->t1Edition->id}/pages/{$this->t1Page->id}/rotate", [
                    'angle' => 90,
                ])
                ->assertStatus(404);

            // DELETE page
            $this->withHeaders($headers)
                ->deleteJson("/api/v1/tenant-admin/editions/{$this->t1Edition->id}/pages/{$this->t1Page->id}")
                ->assertStatus(404);
        }

        // Page remains untouched
        $this->assertDatabaseHas('edition_pages', ['id' => $this->t1Page->id, 'rotation' => 0]);
    }

    /**
     * Test 4: Cross-Tenant Media Library Isolation.
     */
    public function test_cross_tenant_media_library_isolation(): void
    {
        // Tenant 2 lists media - must NOT contain Tenant 1's media file
        $resT2 = $this->withHeaders($this->authHeaderFor($this->user2))->getJson('/api/v1/tenant-admin/media');
        $resT2->assertStatus(200);
        $filesT2 = collect($resT2->json('data'))->pluck('name')->toArray();
        $this->assertNotContains('samacharam_exclusive_banner.png', $filesT2);

        // Tenant 3 attempt to DELETE Tenant 1's media file
        $delRes = $this->withHeaders($this->authHeaderFor($this->user3))
            ->deleteJson("/api/v1/tenant-admin/media/{$this->t1Media->id}");
        $delRes->assertStatus(404);

        // Assert file still exists in database
        $this->assertDatabaseHas('media_files', ['id' => $this->t1Media->id]);
    }

    /**
     * Test 5: Branding Isolation.
     */
    public function test_cross_tenant_branding_isolation(): void
    {
        // Set distinct brandings
        TenantBranding::updateOrCreate(
            ['tenant_id' => $this->tenant1->id],
            ['newspaper_name' => 'Samacharam Daily', 'primary_color' => '#112233']
        );
        TenantBranding::updateOrCreate(
            ['tenant_id' => $this->tenant2->id],
            ['newspaper_name' => 'Prabha Herald', 'primary_color' => '#445566']
        );

        $resT1 = $this->withHeaders($this->authHeaderFor($this->user1))->getJson('/api/v1/tenant-admin/branding');
        $resT1->assertStatus(200);
        $this->assertEquals('Samacharam Daily', $resT1->json('data.newspaper_name'));

        $resT2 = $this->withHeaders($this->authHeaderFor($this->user2))->getJson('/api/v1/tenant-admin/branding');
        $resT2->assertStatus(200);
        $this->assertEquals('Prabha Herald', $resT2->json('data.newspaper_name'));

        // Tenant 3 updating branding cannot overwrite Tenant 1
        $this->withHeaders($this->authHeaderFor($this->user3))->putJson('/api/v1/tenant-admin/branding', [
            'newspaper_name' => 'Eenadu Voice',
            'primary_color' => '#778899',
        ]);

        $t1Branding = TenantBranding::where('tenant_id', $this->tenant1->id)->first();
        $this->assertEquals('Samacharam Daily', $t1Branding->newspaper_name);
    }

    /**
     * Test 6: Reports & Storage Metrics Isolation.
     */
    public function test_cross_tenant_reports_and_storage_isolation(): void
    {
        $resT1 = $this->withHeaders($this->authHeaderFor($this->user1))->getJson('/api/v1/tenant-admin/reports/storage');
        $resT1->assertStatus(200);
        $this->assertEquals((float)$this->tenant1->fresh()->storage_used_mb, (float)$resT1->json('data.storage.used_mb'));

        $resT2 = $this->withHeaders($this->authHeaderFor($this->user2))->getJson('/api/v1/tenant-admin/reports/storage');
        $resT2->assertStatus(200);
        $this->assertEquals((float)$this->tenant2->fresh()->storage_used_mb, (float)$resT2->json('data.storage.used_mb'));

        $resT3 = $this->withHeaders($this->authHeaderFor($this->user3))->getJson('/api/v1/tenant-admin/reports/storage');
        $resT3->assertStatus(200);
        $this->assertEquals((float)$this->tenant3->fresh()->storage_used_mb, (float)$resT3->json('data.storage.used_mb'));

        // Assert strictly distinct storage values confirming no tenant data bleeding
        $this->assertNotEquals($resT1->json('data.storage.used_mb'), $resT2->json('data.storage.used_mb'));
        $this->assertNotEquals($resT2->json('data.storage.used_mb'), $resT3->json('data.storage.used_mb'));
    }

    /**
     * Test 7: Staff Management Isolation.
     */
    public function test_cross_tenant_staff_management_blocked(): void
    {
        // Tenant 2 lists staff: must NOT see Tenant 1's user
        $resT2 = $this->withHeaders($this->authHeaderFor($this->user2))->getJson('/api/v1/tenant-admin/staff');
        $resT2->assertStatus(200);
        $emails = collect($resT2->json('data'))->pluck('email')->toArray();
        $this->assertNotContains('owner@samacharam.com', $emails);

        // Tenant 3 attempts to DELETE Tenant 1's staff user
        $delRes = $this->withHeaders($this->authHeaderFor($this->user3))
            ->deleteJson("/api/v1/tenant-admin/staff/{$this->user1->id}");
        $delRes->assertStatus(404);

        // User remains in database
        $this->assertDatabaseHas('users', ['id' => $this->user1->id]);
    }

    /**
     * Test 8: Notifications Isolation.
     */
    public function test_cross_tenant_notifications_isolation(): void
    {
        // Tenant 2 lists notifications: must NOT see Tenant 1's notification
        $resT2 = $this->withHeaders($this->authHeaderFor($this->user2))->getJson('/api/v1/tenant-admin/notifications');
        $resT2->assertStatus(200);
        $titles = collect($resT2->json('data'))->pluck('title')->toArray();
        $this->assertNotContains('T1 Confidential Advisory', $titles);

        // Tenant 2 attempts to mark Tenant 1's notification as read
        $readRes = $this->withHeaders($this->authHeaderFor($this->user2))
            ->postJson("/api/v1/tenant-admin/notifications/{$this->t1Notification->id}/read");
        $readRes->assertStatus(404);

        // Tenant 3 attempts to DELETE Tenant 1's notification
        $delRes = $this->withHeaders($this->authHeaderFor($this->user3))
            ->deleteJson("/api/v1/tenant-admin/notifications/{$this->t1Notification->id}");
        $delRes->assertStatus(404);
    }

    /**
     * Test 9: Support Tickets Isolation.
     */
    public function test_cross_tenant_support_tickets_isolation(): void
    {
        // Tenant 2 lists tickets: must NOT see Tenant 1's ticket
        $resT2 = $this->withHeaders($this->authHeaderFor($this->user2))->getJson('/api/v1/tenant-admin/support/tickets');
        $resT2->assertStatus(200);
        $subjects = collect($resT2->json('data'))->pluck('subject')->toArray();
        $this->assertNotContains('T1 Infrastructure Billing Question', $subjects);

        // Tenant 2 attempts to GET Tenant 1's ticket details
        $this->withHeaders($this->authHeaderFor($this->user2))
            ->getJson("/api/v1/tenant-admin/support/tickets/{$this->t1Ticket->id}")
            ->assertStatus(404);

        // Tenant 3 attempts to reply to Tenant 1's ticket
        $this->withHeaders($this->authHeaderFor($this->user3))
            ->postJson("/api/v1/tenant-admin/support/tickets/{$this->t1Ticket->id}/reply", [
                'message' => 'Unauthorized intruder message.',
            ])
            ->assertStatus(404);

        // Tenant 3 attempts to close Tenant 1's ticket
        $this->withHeaders($this->authHeaderFor($this->user3))
            ->postJson("/api/v1/tenant-admin/support/tickets/{$this->t1Ticket->id}/close")
            ->assertStatus(404);
    }

    /**
     * Test 10: Publishing Operations Isolation.
     */
    public function test_cross_tenant_publishing_operations_blocked(): void
    {
        foreach ([$this->user2, $this->user3] as $foreignUser) {
            $headers = $this->authHeaderFor($foreignUser);

            // Attempt to publish
            $this->withHeaders($headers)
                ->postJson("/api/v1/tenant-admin/editions/{$this->t1Edition->id}/publish")
                ->assertStatus(404);

            // Attempt to schedule
            $this->withHeaders($headers)
                ->postJson("/api/v1/tenant-admin/editions/{$this->t1Edition->id}/schedule", [
                    'scheduled_at' => Carbon::now()->addDay()->toIso8601String(),
                ])
                ->assertStatus(404);

            // Attempt to unpublish
            $this->withHeaders($headers)
                ->postJson("/api/v1/tenant-admin/editions/{$this->t1Edition->id}/unpublish")
                ->assertStatus(404);

            // Attempt to cancel schedule
            $this->withHeaders($headers)
                ->postJson("/api/v1/tenant-admin/editions/{$this->t1Edition->id}/cancel-schedule")
                ->assertStatus(404);
        }

        // Assert edition status remained ready_for_review
        $this->assertEquals('ready_for_review', $this->t1Edition->fresh()->status);
    }
}
