<?php

namespace App\Modules\TenantAdmin\Services;

use App\Modules\TenantAdmin\Models\Edition;
use App\Modules\TenantAdmin\Models\PublishingSchedule;
use App\Modules\TenantAdmin\Models\TenantNotification;
use App\Modules\TenantAdmin\Models\NewspaperProfile;
use App\Modules\SuperAdmin\Models\TenantBranding;
use App\Modules\SuperAdmin\Models\Tenant;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Carbon\Carbon;

class PublishingService
{
    public function scheduleEdition(int $tenantId, int $editionId, string $scheduledAt, string $timezone = 'Asia/Kolkata', ?int $userId = null): Edition
    {
        $edition = Edition::where('tenant_id', $tenantId)->findOrFail($editionId);

        // Disallow scheduling editions that failed processing or are not ready
        if ($edition->status === 'processing_failed') {
            throw ValidationException::withMessages([
                'edition' => ['Cannot schedule an edition that failed PDF processing. Upload a valid PDF.'],
            ]);
        }

        if (in_array($edition->status, ['draft', 'uploaded', 'processing'])) {
            throw ValidationException::withMessages([
                'edition' => ['Cannot schedule an edition that has not completed processing and review.'],
            ]);
        }

        if ($edition->pages()->count() === 0) {
            throw ValidationException::withMessages([
                'edition' => ['Cannot schedule an edition with zero pages. Please upload and process a PDF first.'],
            ]);
        }

        // Prohibit scheduling editions containing synthetic GD fallback pages
        $hasSynthetic = $edition->pages()
            ->where('metadata', 'like', '%"engine":"gd_fallback"%')
            ->exists();
        if ($hasSynthetic) {
            throw ValidationException::withMessages([
                'edition' => ['Cannot schedule an edition containing synthetic broadsheet pages. Authentic Ghostscript rasterization is required.'],
            ]);
        }

        // Verify source PDF bytes and integrity
        $this->verifySourcePdfChecksum($edition);

        $scheduleTime = Carbon::parse($scheduledAt, $timezone);
        if ($scheduleTime->isPast()) {
            throw ValidationException::withMessages([
                'scheduled_at' => ['Scheduled publication time must be in the future.'],
            ]);
        }

        $edition->status = 'scheduled';
        $edition->scheduled_at = $scheduleTime;
        $edition->save();

        PublishingSchedule::updateOrCreate(
            ['edition_id' => $edition->id],
            [
                'tenant_id' => $tenantId,
                'scheduled_by_user_id' => $userId,
                'scheduled_at' => $scheduleTime,
                'timezone' => $timezone,
                'status' => 'pending',
                'published_at' => null,
                'cancelled_at' => null,
            ]
        );

        TenantAuditHelper::log($tenantId, $userId, 'edition_scheduled', 'publishing', "Scheduled edition {$edition->edition_code} for {$scheduleTime->toDateTimeString()}.");

        return $edition->fresh(['schedule']);
    }

    public function publishNow(int $tenantId, int $editionId, ?int $userId = null): Edition
    {
        $edition = Edition::where('tenant_id', $tenantId)->findOrFail($editionId);

        // Disallow publishing editions that failed processing or are still processing
        if ($edition->status === 'processing_failed') {
            throw ValidationException::withMessages([
                'edition' => ['Cannot publish an edition that failed PDF processing. Upload a valid PDF.'],
            ]);
        }

        if (in_array($edition->status, ['draft', 'uploaded', 'processing'])) {
            throw ValidationException::withMessages([
                'edition' => ['Cannot publish an edition that has not completed processing and review.'],
            ]);
        }

        $pages = $edition->pages()->orderBy('sort_order', 'asc')->get();
        if ($pages->count() === 0) {
            throw ValidationException::withMessages([
                'edition' => ['Cannot publish an edition with zero pages.'],
            ]);
        }

        // Prohibit publishing editions containing synthetic GD fallback pages
        $hasSynthetic = $pages->contains(function ($p) {
            return is_array($p->metadata) && ($p->metadata['engine'] ?? null) === 'gd_fallback';
        }) || $edition->pages()->where('metadata->engine', 'gd_fallback')->exists();
        if ($hasSynthetic) {
            throw ValidationException::withMessages([
                'edition' => ['Cannot publish an edition containing synthetic broadsheet pages. Authentic Ghostscript rasterization is required.'],
            ]);
        }

        // Verify source PDF bytes and integrity
        $this->verifySourcePdfChecksum($edition);

        $now = Carbon::now();

        // Generate full Public Reader Manifest
        $manifest = $this->generatePublicManifest($edition, $pages);

        $edition->status = 'published';
        $edition->published_at = $now;
        $edition->public_manifest = $manifest;
        $edition->manifest_generated_at = $now;
        $edition->save();

        // Update schedule if exists
        PublishingSchedule::where('edition_id', $edition->id)->update([
            'status' => 'executed',
            'published_at' => $now,
        ]);

        TenantNotification::create([
            'tenant_id' => $tenantId,
            'user_id' => $userId,
            'type' => 'edition_published',
            'title' => 'Edition Published',
            'message' => "Edition [{$edition->edition_name}] is now live and published.",
            'data' => ['edition_id' => $edition->id],
            'is_read' => false,
        ]);

        TenantAuditHelper::log($tenantId, $userId, 'edition_published', 'publishing', "Published edition {$edition->edition_code} immediately.");

        return $edition;
    }

    /**
     * Verify that the original source PDF exists in private storage and its SHA-256 matches.
     */
    protected function verifySourcePdfChecksum(Edition $edition): void
    {
        if (!$edition->original_pdf_path || !Storage::disk('local')->exists($edition->original_pdf_path)) {
            throw ValidationException::withMessages([
                'edition' => ['Original source PDF file is missing from private storage.'],
            ]);
        }

        $fullPdfPath = Storage::disk('local')->path($edition->original_pdf_path);
        $diskChecksum = hash_file('sha256', $fullPdfPath);
        $emptySha256 = 'e3b0c44298fc1c149afbf4c8996fb92427ae41e4649b934ca495991b7852b855';

        if (!$diskChecksum || $diskChecksum === $emptySha256) {
            throw ValidationException::withMessages([
                'edition' => ['Source PDF has an empty or invalid SHA-256 payload checksum.'],
            ]);
        }

        if ($edition->checksum_sha256 && $edition->checksum_sha256 !== $diskChecksum) {
            throw ValidationException::withMessages([
                'edition' => ["Checksum mismatch: Stored checksum [{$edition->checksum_sha256}] does not match source file on disk [{$diskChecksum}]."],
            ]);
        }

        $edition->checksum_sha256 = $diskChecksum;
    }

    public function republish(int $tenantId, int $editionId, ?int $userId = null): Edition
    {
        return $this->publishNow($tenantId, $editionId, $userId);
    }

    public function unpublish(int $tenantId, int $editionId, ?int $userId = null): Edition
    {
        $edition = Edition::where('tenant_id', $tenantId)->findOrFail($editionId);
        $tenant = Tenant::find($tenantId);

        $edition->status = 'ready_for_review';
        $edition->published_at = null;
        $edition->public_manifest = null;
        $edition->manifest_generated_at = null;
        $edition->save();

        // Remove static manifest file from public storage
        if ($tenant) {
            $manifestPath = "tenants/{$tenant->slug}/editions/{$edition->edition_code}/manifest.json";
            Storage::disk('public')->delete($manifestPath);
            $this->updateLatestPointer($tenant);
        }

        TenantAuditHelper::log($tenantId, $userId, 'edition_unpublished', 'publishing', "Unpublished edition {$edition->edition_code}. Reverted to ready for review.");

        return $edition;
    }

    public function cancelSchedule(int $tenantId, int $editionId, ?int $userId = null): Edition
    {
        $edition = Edition::where('tenant_id', $tenantId)->findOrFail($editionId);

        if ($edition->status !== 'scheduled') {
            throw ValidationException::withMessages([
                'edition' => ['Edition is not currently in scheduled status.'],
            ]);
        }

        $edition->status = 'ready_for_review';
        $edition->scheduled_at = null;
        $edition->save();

        PublishingSchedule::where('edition_id', $edition->id)->update([
            'status' => 'cancelled',
            'cancelled_at' => Carbon::now(),
        ]);

        TenantAuditHelper::log($tenantId, $userId, 'schedule_cancelled', 'publishing', "Cancelled scheduled publication for edition {$edition->edition_code}.");

        return $edition;
    }

    public function getCalendar(int $tenantId, int $year, int $month): array
    {
        $start = Carbon::createFromDate($year, $month, 1)->startOfMonth();
        $end = $start->copy()->endOfMonth();

        $editions = Edition::where('tenant_id', $tenantId)
            ->whereBetween('publish_date', [$start->format('Y-m-d'), $end->format('Y-m-d')])
            ->get(['id', 'edition_code', 'edition_name', 'publish_date', 'status', 'total_pages']);

        return [
            'year' => $year,
            'month' => $month,
            'editions' => $editions,
        ];
    }

    public function getHistory(int $tenantId, int $limit = 20)
    {
        return Edition::where('tenant_id', $tenantId)
            ->whereIn('status', ['published', 'archived'])
            ->orderBy('published_at', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Compile comprehensive Public Reader Manifest for future Public View module.
     * Generates both database payload and static JSON file for zero-latency CDN serving.
     */
    protected function generatePublicManifest(Edition $edition, $pages): array
    {
        $tenant = Tenant::find($edition->tenant_id);
        $profile = NewspaperProfile::where('tenant_id', $edition->tenant_id)->first();
        $branding = TenantBranding::where('tenant_id', $edition->tenant_id)->first();

        $pagesData = [];
        foreach ($pages as $p) {
            $pagesData[] = [
                'page_number' => $p->page_number,
                'page_title' => $p->page_title,
                'image_url' => $p->image_path,
                'thumbnail_url' => $p->thumbnail_path,
                'width' => $p->width,
                'height' => $p->height,
                'rotation' => $p->rotation,
                'is_visible' => (bool)$p->is_visible,
            ];
        }

        $manifest = [
            'schema_version' => '2.0',
            'edition' => [
                'id' => $edition->id,
                'edition_code' => $edition->edition_code,
                'edition_title' => $edition->edition_title ?? $edition->edition_name,
                'edition_name' => $edition->edition_name,
                'edition_language' => $edition->edition_language ?? 'te',
                'category' => $edition->category ?? 'Main Edition',
                'description' => $edition->description,
                'publish_date' => $edition->publish_date ? $edition->publish_date->format('Y-m-d') : date('Y-m-d'),
                'total_pages' => count($pagesData),
                'thumbnail_url' => $edition->thumbnail_path,
                'checksum_sha256' => $edition->checksum_sha256,
                'published_at' => Carbon::now()->toIso8601String(),
            ],
            'publication' => [
                'name' => $profile?->newspaper_name ?? $tenant?->name ?? 'DOGY ePaper',
                'slug' => $tenant?->slug ?? 'tenant',
                'domain' => $tenant?->domain,
                'tagline' => $profile?->about_newspaper,
                'publisher_name' => $profile?->publisher_name,
                'editor_name' => $profile?->editor_name,
                'contact_email' => $profile?->email ?? $tenant?->email,
                'contact_phone' => $profile?->phone ?? $tenant?->phone,
                'logo_url' => $profile?->logo_url ?? $branding?->logo_url,
                'favicon_url' => $profile?->favicon_url ?? $branding?->favicon_url,
            ],
            'branding' => [
                'primary_color' => $branding?->primary_color ?? '#2563EB',
                'secondary_color' => $branding?->secondary_color ?? '#0EA5E9',
                'font_family' => $branding?->typography ?? 'Inter',
                'header_style' => $branding?->header_style ?? 'standard',
                'custom_css' => $branding?->custom_css ?? '',
            ],
            'pages' => $pagesData,
            'compiled_at' => Carbon::now()->toIso8601String(),
        ];

        // Write static manifest.json in public storage for high-speed edge caching
        if ($tenant) {
            $manifestRelPath = "tenants/{$tenant->slug}/editions/{$edition->edition_code}/manifest.json";
            Storage::disk('public')->put(
                $manifestRelPath,
                json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)
            );

            // Update latest.json pointer for the tenant
            $this->updateLatestPointer($tenant, $manifest);
        }

        return $manifest;
    }

    /**
     * Update public/tenants/{slug}/latest.json pointer for Public View homepage.
     */
    protected function updateLatestPointer(Tenant $tenant, ?array $latestManifest = null): void
    {
        if ($latestManifest === null) {
            $latestEdition = Edition::where('tenant_id', $tenant->id)
                ->where('status', 'published')
                ->orderBy('publish_date', 'desc')
                ->first();

            $latestManifest = $latestEdition ? $latestEdition->public_manifest : null;
        }

        $latestPointerPath = "tenants/{$tenant->slug}/latest.json";
        if ($latestManifest) {
            Storage::disk('public')->put(
                $latestPointerPath,
                json_encode($latestManifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)
            );
        } else {
            Storage::disk('public')->delete($latestPointerPath);
        }
    }
}
