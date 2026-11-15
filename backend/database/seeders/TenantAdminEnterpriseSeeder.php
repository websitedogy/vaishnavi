<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;

class TenantAdminEnterpriseSeeder extends Seeder
{
    public function run(): void
    {
        $now = Carbon::now();

        // 1. Fetch existing tenants
        $tenant1 = DB::table('tenants')->where('slug', 'telangana-samacharam')->first();
        $tenant2 = DB::table('tenants')->where('slug', 'andhra-prabha-digital')->first();

        if (!$tenant1) {
            $t1Id = DB::table('tenants')->insertGetId([
                'name' => 'Telangana Samacharam',
                'slug' => 'telangana-samacharam',
                'company_name' => 'Samacharam Media Pvt Ltd',
                'contact_person' => 'K. R. Rao',
                'email' => 'publisher@samacharam.com',
                'phone' => '+91 98480 12345',
                'domain' => 'epaper.samacharam.com',
                'state' => 'Telangana',
                'district' => 'Hyderabad',
                'mandal' => 'Khairatabad',
                'subscription' => 'Professional Publisher',
                'status' => 'active',
                'storage_limit_mb' => 51200,
                'storage_used_mb' => 14250.50,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
            $tenant1 = DB::table('tenants')->where('id', $t1Id)->first();
        }

        if (!$tenant2) {
            $t2Id = DB::table('tenants')->insertGetId([
                'name' => 'Andhra Prabha Digital',
                'slug' => 'andhra-prabha-digital',
                'company_name' => 'Prabha Publications Ltd',
                'contact_person' => 'S. V. Murthy',
                'email' => 'editions@andhraprabha.com',
                'phone' => '+91 98490 54321',
                'domain' => 'epaper.andhraprabha.com',
                'state' => 'Andhra Pradesh',
                'district' => 'Vijayawada',
                'mandal' => 'Governorpet',
                'subscription' => 'Enterprise Media Group',
                'status' => 'active',
                'storage_limit_mb' => 512000,
                'storage_used_mb' => 98420.00,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
            $tenant2 = DB::table('tenants')->where('id', $t2Id)->first();
        }

        // 2. Seed Newspaper Profiles for Tenant 1 and Tenant 2
        DB::table('newspaper_profiles')->updateOrInsert(
            ['tenant_id' => $tenant1->id],
            [
                'newspaper_name' => 'Telangana Samacharam Daily',
                'company_name' => 'Samacharam Media Pvt Ltd',
                'publisher_name' => 'K. R. Rao',
                'editor_name' => 'M. Venkat Ramana',
                'contact_person' => 'K. R. Rao',
                'email' => 'publisher@samacharam.com',
                'phone' => '+91 98480 12345',
                'whatsapp' => '+91 98480 12345',
                'address' => 'Plot 42, Journalist Colony, Road No 3, Banjara Hills',
                'state' => 'Telangana',
                'district' => 'Hyderabad',
                'mandal' => 'Khairatabad',
                'pincode' => '500034',
                'language' => 'te',
                'logo_url' => '/assets/branding/samacharam-logo.png',
                'favicon_url' => '/favicon.ico',
                'about_newspaper' => 'Telangana Samacharam is a leading Telugu daily newspaper offering comprehensive news, investigative journalism, regional reports, and editorial commentary.',
                'social_links' => json_encode([
                    'facebook' => 'https://facebook.com/telanganasamacharam',
                    'twitter' => 'https://twitter.com/tsamacharam',
                    'youtube' => 'https://youtube.com/@telanganasamacharam',
                    'telegram' => 'https://t.me/telanganasamacharam',
                ]),
                'created_at' => $now,
                'updated_at' => $now,
            ]
        );

        DB::table('newspaper_profiles')->updateOrInsert(
            ['tenant_id' => $tenant2->id],
            [
                'newspaper_name' => 'Andhra Prabha Digital',
                'company_name' => 'Prabha Publications Ltd',
                'publisher_name' => 'S. V. Murthy',
                'editor_name' => 'T. Krishna Prasad',
                'contact_person' => 'S. V. Murthy',
                'email' => 'editions@andhraprabha.com',
                'phone' => '+91 98490 54321',
                'whatsapp' => '+91 98490 54321',
                'address' => 'Prabha Bhavan, Bandar Road',
                'state' => 'Andhra Pradesh',
                'district' => 'Vijayawada',
                'mandal' => 'Governorpet',
                'pincode' => '520002',
                'language' => 'te',
                'logo_url' => '/assets/branding/prabha-logo.png',
                'favicon_url' => '/favicon.ico',
                'about_newspaper' => 'Andhra Prabha is one of the oldest and most respected Telugu newspapers, established in 1938 with extensive coverage across Andhra Pradesh and Telangana.',
                'social_links' => json_encode([
                    'facebook' => 'https://facebook.com/andhraprabha',
                    'twitter' => 'https://twitter.com/andhraprabha',
                ]),
                'created_at' => $now,
                'updated_at' => $now,
            ]
        );

        // 3. Seed Staff Users with RBAC permissions
        $staffMembers = [
            // Tenant 1 Staff
            [
                'tenant_id' => $tenant1->id,
                'name' => 'K. R. Rao (Owner)',
                'email' => 'owner@samacharam.com',
                'password' => Hash::make('TenantPassword123!'),
                'role' => 'admin',
                'is_active' => true,
                'permissions' => json_encode(['*']),
            ],
            [
                'tenant_id' => $tenant1->id,
                'name' => 'M. Venkat (Chief Editor)',
                'email' => 'editor@samacharam.com',
                'password' => Hash::make('TenantPassword123!'),
                'role' => 'editor',
                'is_active' => true,
                'permissions' => json_encode(['editions.*', 'publishing.*', 'pages.*', 'media.*', 'reports.*']),
            ],
            [
                'tenant_id' => $tenant1->id,
                'name' => 'S. Ramesh (Senior Reporter)',
                'email' => 'reporter@samacharam.com',
                'password' => Hash::make('TenantPassword123!'),
                'role' => 'reporter',
                'is_active' => true,
                'permissions' => json_encode(['editions.view', 'media.view', 'reports.view']),
            ],
            // Tenant 2 Staff
            [
                'tenant_id' => $tenant2->id,
                'name' => 'S. V. Murthy (Admin)',
                'email' => 'admin@andhraprabha.com',
                'password' => Hash::make('TenantPassword123!'),
                'role' => 'admin',
                'is_active' => true,
                'permissions' => json_encode(['*']),
            ],
        ];

        foreach ($staffMembers as $staff) {
            DB::table('users')->updateOrInsert(
                ['email' => $staff['email']],
                array_merge($staff, ['created_at' => $now, 'updated_at' => $now])
            );
        }

        // 4. Seed Website Appearance Templates
        $templates = [
            [
                'tenant_id' => null, // Global
                'name' => 'Classic Broadheet',
                'slug' => 'classic-broadsheet',
                'description' => 'Traditional multi-column layout with bold headline masthead and date-driven archive picker.',
                'thumbnail_url' => '/assets/templates/classic.png',
                'version' => '1.2.0',
                'layout_config' => json_encode([
                    'columns' => 4,
                    'masthead_style' => 'centered',
                    'show_issue_number' => true,
                    'sidebar_archive' => true,
                ]),
                'color_palette' => json_encode([
                    'primary' => '#1E3A8A',
                    'secondary' => '#0284C7',
                    'background' => '#FFFFFF',
                    'surface' => '#F8FAFC',
                ]),
                'is_active' => true,
                'is_default' => true,
            ],
            [
                'tenant_id' => null, // Global
                'name' => 'Modern Tabloid Digital',
                'slug' => 'modern-tabloid',
                'description' => 'Sleek, image-first mobile-optimized interface with dark mode reader controls and quick page flipping.',
                'thumbnail_url' => '/assets/templates/modern.png',
                'version' => '2.0.0',
                'layout_config' => json_encode([
                    'columns' => 3,
                    'masthead_style' => 'minimal',
                    'show_issue_number' => false,
                    'sidebar_archive' => false,
                ]),
                'color_palette' => json_encode([
                    'primary' => '#2563EB',
                    'secondary' => '#38BDF8',
                    'background' => '#F8FAFC',
                    'surface' => '#FFFFFF',
                ]),
                'is_active' => true,
                'is_default' => false,
            ],
            [
                'tenant_id' => null, // Global
                'name' => 'Minimalist Compact',
                'slug' => 'minimalist-compact',
                'description' => 'High-contrast typography with ultra-fast initial page loading for regional low-bandwidth readers.',
                'thumbnail_url' => '/assets/templates/minimalist.png',
                'version' => '1.0.0',
                'layout_config' => json_encode([
                    'columns' => 2,
                    'masthead_style' => 'compact_left',
                    'show_issue_number' => true,
                    'sidebar_archive' => true,
                ]),
                'color_palette' => json_encode([
                    'primary' => '#0F172A',
                    'secondary' => '#475569',
                    'background' => '#FFFFFF',
                    'surface' => '#F1F5F9',
                ]),
                'is_active' => true,
                'is_default' => false,
            ],
        ];

        foreach ($templates as $tmpl) {
            DB::table('newspaper_templates')->updateOrInsert(
                ['slug' => $tmpl['slug']],
                array_merge($tmpl, ['created_at' => $now, 'updated_at' => $now])
            );
        }

        // 5. Seed Realistic Newspaper Editions & Pages for Tenant 1
        $editions = [
            [
                'tenant_id' => $tenant1->id,
                'edition_code' => 'HYD-2026-01-25',
                'edition_name' => 'Hyderabad Main Daily',
                'edition_title' => 'Hyderabad Main Daily - Sunday Edition',
                'edition_language' => 'te',
                'category' => 'Main Edition',
                'description' => 'Full morning edition covering Greater Hyderabad, Telangana state policy, and national news.',
                'publish_date' => '2026-01-25',
                'total_pages' => 4,
                'file_size_mb' => 18.50,
                'thumbnail_path' => '/storage/tenants/telangana-samacharam/editions/HYD-2026-01-25/thumbnails/page_1.png',
                'original_pdf_path' => 'private/tenants/telangana-samacharam/editions/HYD-2026-01-25/original/HYD-2026-01-25.pdf',
                'status' => 'published',
                'published_at' => $now->copy()->subHours(2),
            ],
            [
                'tenant_id' => $tenant1->id,
                'edition_code' => 'HYD-2026-01-26',
                'edition_name' => 'Republic Day Special Issue',
                'edition_title' => 'Republic Day Special Issue 2026',
                'edition_language' => 'te',
                'category' => 'Special Issue',
                'description' => 'Special commemorative Republic Day edition with exclusive interviews and state parade features.',
                'publish_date' => '2026-01-26',
                'total_pages' => 8,
                'file_size_mb' => 32.20,
                'thumbnail_path' => '/storage/tenants/telangana-samacharam/editions/HYD-2026-01-26/thumbnails/page_1.png',
                'original_pdf_path' => 'private/tenants/telangana-samacharam/editions/HYD-2026-01-26/original/HYD-2026-01-26.pdf',
                'status' => 'scheduled',
                'scheduled_at' => $now->copy()->addDay()->setTime(4, 30),
            ],
            [
                'tenant_id' => $tenant1->id,
                'edition_code' => 'KNR-2026-01-25',
                'edition_name' => 'Karimnagar District Tabloid',
                'edition_title' => 'Karimnagar District Edition',
                'edition_language' => 'te',
                'category' => 'District Tabloid',
                'description' => 'Regional district edition covering local agricultural markets, municipal updates, and rural sports.',
                'publish_date' => '2026-01-25',
                'total_pages' => 4,
                'file_size_mb' => 12.00,
                'thumbnail_path' => null,
                'original_pdf_path' => null,
                'status' => 'draft',
            ],
        ];

        foreach ($editions as $ed) {
            DB::table('newspaper_editions')->updateOrInsert(
                ['tenant_id' => $ed['tenant_id'], 'edition_code' => $ed['edition_code']],
                array_merge($ed, ['created_at' => $now, 'updated_at' => $now])
            );

            $editionId = DB::table('newspaper_editions')
                ->where('tenant_id', $ed['tenant_id'])
                ->where('edition_code', $ed['edition_code'])
                ->value('id');

            // Seed Pages for the published edition
            if ($ed['status'] === 'published') {
                for ($p = 1; $p <= 4; $p++) {
                    DB::table('edition_pages')->updateOrInsert(
                        ['edition_id' => $editionId, 'page_number' => $p],
                        [
                            'tenant_id' => $tenant1->id,
                            'page_title' => $p === 1 ? 'Front Page / Main Headlines' : ($p === 2 ? 'State Affairs & Politics' : ($p === 3 ? 'Business & Economy' : 'Sports & Regional')),
                            'image_path' => "/storage/tenants/telangana-samacharam/editions/{$ed['edition_code']}/pages/page_{$p}.png",
                            'thumbnail_path' => "/storage/tenants/telangana-samacharam/editions/{$ed['edition_code']}/thumbnails/page_{$p}.png",
                            'width' => 1400,
                            'height' => 2100,
                            'rotation' => 0,
                            'is_visible' => true,
                            'sort_order' => $p,
                            'created_at' => $now,
                            'updated_at' => $now,
                        ]
                    );
                }
            }
        }

        // 6. Seed Sample Media Files for Tenant 1
        $media = [
            [
                'tenant_id' => $tenant1->id,
                'name' => 'Main Masthead Logo 2026.png',
                'path' => 'tenants/telangana-samacharam/media/logos/masthead-2026.png',
                'disk' => 'public',
                'type' => 'image',
                'mime_type' => 'image/png',
                'size' => 245000,
                'thumbnail_path' => 'tenants/telangana-samacharam/media/logos/masthead-2026.png',
                'alt_text' => 'Telangana Samacharam Header Logo',
            ],
            [
                'tenant_id' => $tenant1->id,
                'name' => 'Republic Day Ad Banner.jpg',
                'path' => 'tenants/telangana-samacharam/media/ads/republic-day-banner.jpg',
                'disk' => 'public',
                'type' => 'image',
                'mime_type' => 'image/jpeg',
                'size' => 450000,
                'thumbnail_path' => 'tenants/telangana-samacharam/media/ads/republic-day-banner.jpg',
                'alt_text' => 'Republic Day Sponsor Banner',
            ],
        ];

        foreach ($media as $m) {
            DB::table('media_files')->updateOrInsert(
                ['tenant_id' => $m['tenant_id'], 'name' => $m['name']],
                array_merge($m, ['created_at' => $now, 'updated_at' => $now])
            );
        }

        // 7. Seed Sample Notifications for Tenant 1
        $notifications = [
            [
                'tenant_id' => $tenant1->id,
                'type' => 'edition_published',
                'title' => 'Edition Published Successfully',
                'message' => 'Hyderabad Main Daily (HYD-2026-01-25) has been published and is live.',
                'data' => json_encode(['edition_code' => 'HYD-2026-01-25']),
                'is_read' => true,
                'read_at' => $now,
            ],
            [
                'tenant_id' => $tenant1->id,
                'type' => 'pdf_processed',
                'title' => 'PDF Processing Complete',
                'message' => 'Republic Day Special Issue (8 pages) has been extracted and converted to WebP.',
                'data' => json_encode(['edition_code' => 'HYD-2026-01-26']),
                'is_read' => false,
            ],
            [
                'tenant_id' => $tenant1->id,
                'type' => 'quota_warning',
                'title' => 'Storage Quota Alert',
                'message' => 'Your tenant storage utilization has reached 28% of 50 GB.',
                'data' => json_encode(['usage_percent' => 28]),
                'is_read' => false,
            ],
        ];

        foreach ($notifications as $notif) {
            DB::table('tenant_notifications')->insert(
                array_merge($notif, ['created_at' => $now, 'updated_at' => $now])
            );
        }
    }
}
