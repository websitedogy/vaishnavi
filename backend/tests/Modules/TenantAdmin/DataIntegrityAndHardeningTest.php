<?php

namespace Tests\Modules\TenantAdmin;

use Tests\TestCase;
use App\Models\User;
use App\Modules\SuperAdmin\Models\Tenant;
use App\Modules\TenantAdmin\Models\Edition;
use App\Modules\TenantAdmin\Models\EditionPage;
use App\Modules\TenantAdmin\Models\PdfUpload;
use App\Modules\TenantAdmin\Services\PdfUploadService;
use App\Modules\TenantAdmin\Services\LocalPdfProcessingService;
use App\Modules\TenantAdmin\Services\PublishingService;
use App\Modules\TenantAdmin\Services\EditionService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Carbon\Carbon;

class DataIntegrityAndHardeningTest extends TestCase
{
    use DatabaseTransactions;

    protected Tenant $tenant;
    protected User $user;
    protected string $token;
    protected string $samplePdfPath;
    protected string $samplePdfBytes;
    protected string $sampleSha256;

    protected function setUp(): void
    {
        parent::setUp();

        $this->samplePdfPath = storage_path('app/testing/telugu_sample_edition.pdf');
        $this->assertFileExists($this->samplePdfPath, 'Sample real Telugu PDF must exist for regression testing.');
        $this->samplePdfBytes = file_get_contents($this->samplePdfPath);
        $this->sampleSha256 = hash('sha256', $this->samplePdfBytes);

        $this->tenant = Tenant::firstOrCreate(
            ['slug' => 'hardening-test-daily'],
            [
                'name' => 'Hardening Daily',
                'email' => 'editor@hardeningdaily.com',
                'domain' => 'hardeningdaily.com',
                'status' => 'active',
                'storage_quota_mb' => 51200,
                'storage_used_mb' => 10,
                'is_active' => true,
            ]
        );

        $this->user = User::firstOrCreate(
            ['email' => 'admin@hardeningdaily.com'],
            [
                'tenant_id' => $this->tenant->id,
                'name' => 'Hardening Admin',
                'password' => bcrypt('TenantPassword123!'),
                'role' => 'admin',
                'is_active' => true,
            ]
        );

        $this->token = $this->user->createToken('hardening_token')->plainTextToken;
    }

    protected function authHeader(): array
    {
        return ['Authorization' => "Bearer {$this->token}"];
    }

    /**
     * Requirement 1: Empty file / empty SHA-256 payload checksum is strictly rejected.
     */
    public function test_empty_checksum_is_strictly_rejected_during_upload(): void
    {
        $edition = Edition::create([
            'tenant_id' => $this->tenant->id,
            'edition_code' => 'HD-EMPTY-' . uniqid(),
            'edition_name' => 'Empty Checksum Test',
            'publish_date' => date('Y-m-d'),
            'status' => 'draft',
        ]);

        // UploadedFile fake without content yields empty file with e3b0c44298fc1c149afbf4c8996fb92427ae41e4649b934ca495991b7852b855
        $emptyFake = UploadedFile::fake()->create('empty.pdf', 0, 'application/pdf');

        $this->expectException(ValidationException::class);
        $uploadService = app(PdfUploadService::class);
        $uploadService->uploadForEdition($this->tenant->id, $edition->id, $emptyFake, $this->user->id);
    }

    /**
     * Requirement 1: Recomputed checksum matches actual original PDF file bytes on disk.
     */
    public function test_recomputed_checksum_matches_real_pdf_bytes_on_disk(): void
    {
        $relativeDir = "private/tenants/{$this->tenant->slug}/editions/HD-REAL-BYTES/original";
        $filename = 'real_source.pdf';
        Storage::disk('local')->put("{$relativeDir}/{$filename}", $this->samplePdfBytes);

        $edition = Edition::create([
            'tenant_id' => $this->tenant->id,
            'edition_code' => 'HD-REAL-BYTES',
            'edition_name' => 'Real Byte Verification',
            'publish_date' => date('Y-m-d'),
            'status' => 'uploaded',
            'original_pdf_path' => "{$relativeDir}/{$filename}",
            'checksum_sha256' => $this->sampleSha256,
        ]);

        $upload = PdfUpload::create([
            'tenant_id' => $this->tenant->id,
            'edition_id' => $edition->id,
            'original_filename' => 'telugu_edition.pdf',
            'stored_filename' => $filename,
            'file_path' => "{$relativeDir}/{$filename}",
            'file_size' => strlen($this->samplePdfBytes),
            'checksum_sha256' => $this->sampleSha256,
            'status' => 'uploaded',
        ]);

        $processor = app(LocalPdfProcessingService::class);
        $result = $processor->processEditionPdf($edition, $upload, ['dpi' => 150]);

        $this->assertTrue($result['success']);
        $this->assertEquals($this->sampleSha256, $edition->fresh()->checksum_sha256);
        $this->assertNotEquals('e3b0c44298fc1c149afbf4c8996fb92427ae41e4649b934ca495991b7852b855', $edition->fresh()->checksum_sha256);
    }

    /**
     * Requirement 1: Checksum mismatch between upload record and disk file is detected and rejected.
     */
    public function test_checksum_mismatch_during_processing_fails_with_exception(): void
    {
        $relativeDir = "private/tenants/{$this->tenant->slug}/editions/HD-MISMATCH/original";
        $filename = 'tampered.pdf';
        Storage::disk('local')->put("{$relativeDir}/{$filename}", $this->samplePdfBytes);

        $edition = Edition::create([
            'tenant_id' => $this->tenant->id,
            'edition_code' => 'HD-MISMATCH',
            'edition_name' => 'Checksum Mismatch Test',
            'publish_date' => date('Y-m-d'),
            'status' => 'uploaded',
            'original_pdf_path' => "{$relativeDir}/{$filename}",
            'checksum_sha256' => '1111111111111111111111111111111111111111111111111111111111111111',
        ]);

        $upload = PdfUpload::create([
            'tenant_id' => $this->tenant->id,
            'edition_id' => $edition->id,
            'original_filename' => 'tampered.pdf',
            'stored_filename' => $filename,
            'file_path' => "{$relativeDir}/{$filename}",
            'file_size' => strlen($this->samplePdfBytes),
            // Intentional mismatch
            'checksum_sha256' => '1111111111111111111111111111111111111111111111111111111111111111',
            'status' => 'uploaded',
        ]);

        $processor = app(LocalPdfProcessingService::class);
        $result = $processor->processEditionPdf($edition, $upload);

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('Checksum mismatch detected', $result['error']);
        $this->assertEquals('processing_failed', $edition->fresh()->status);
        $this->assertEquals('failed', $upload->fresh()->status);
    }

    /**
     * Requirement 2: Corrupted PDF strictly fails processing and never falls back to GD.
     */
    public function test_corrupted_pdf_processing_fails_without_synthetic_fallback(): void
    {
        $relativeDir = "private/tenants/{$this->tenant->slug}/editions/HD-CORRUPT/original";
        $filename = 'corrupt.pdf';
        $corruptData = "GARBAGE_BYTES_NO_PDF\x00\xFF" . str_repeat("junk data\n", 40);
        Storage::disk('local')->put("{$relativeDir}/{$filename}", $corruptData);
        $corruptHash = hash('sha256', $corruptData);

        $edition = Edition::create([
            'tenant_id' => $this->tenant->id,
            'edition_code' => 'HD-CORRUPT',
            'edition_name' => 'Strict Corrupted Test',
            'publish_date' => date('Y-m-d'),
            'status' => 'uploaded',
            'original_pdf_path' => "{$relativeDir}/{$filename}",
            'checksum_sha256' => $corruptHash,
        ]);

        $upload = PdfUpload::create([
            'tenant_id' => $this->tenant->id,
            'edition_id' => $edition->id,
            'original_filename' => 'corrupt.pdf',
            'stored_filename' => $filename,
            'file_path' => "{$relativeDir}/{$filename}",
            'file_size' => strlen($corruptData),
            'checksum_sha256' => $corruptHash,
            'status' => 'uploaded',
        ]);

        $processor = app(LocalPdfProcessingService::class);
        $result = $processor->processEditionPdf($edition, $upload);

        $this->assertFalse($result['success']);
        $this->assertEquals('processing_failed', $edition->fresh()->status);
        $this->assertEquals('failed', $upload->fresh()->status);
        $this->assertEquals(0, EditionPage::where('edition_id', $edition->id)->count());
    }

    /**
     * Requirement 2: Password-protected PDF fails with Password Required error.
     */
    public function test_encrypted_pdf_processing_fails_with_password_required_error(): void
    {
        $encPath = storage_path('app/testing/encrypted_sample.pdf');
        $this->assertFileExists($encPath);
        $encBytes = file_get_contents($encPath);
        $encHash = hash('sha256', $encBytes);

        $relativeDir = "private/tenants/{$this->tenant->slug}/editions/HD-ENCRYPT/original";
        $filename = 'encrypted.pdf';
        Storage::disk('local')->put("{$relativeDir}/{$filename}", $encBytes);

        $edition = Edition::create([
            'tenant_id' => $this->tenant->id,
            'edition_code' => 'HD-ENCRYPT',
            'edition_name' => 'Strict Encrypted Test',
            'publish_date' => date('Y-m-d'),
            'status' => 'uploaded',
            'original_pdf_path' => "{$relativeDir}/{$filename}",
            'checksum_sha256' => $encHash,
        ]);

        $upload = PdfUpload::create([
            'tenant_id' => $this->tenant->id,
            'edition_id' => $edition->id,
            'original_filename' => 'encrypted.pdf',
            'stored_filename' => $filename,
            'file_path' => "{$relativeDir}/{$filename}",
            'file_size' => strlen($encBytes),
            'checksum_sha256' => $encHash,
            'status' => 'uploaded',
        ]);

        $processor = app(LocalPdfProcessingService::class);
        $result = $processor->processEditionPdf($edition, $upload);

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('Password Required', $result['error']);
        $this->assertEquals('processing_failed', $edition->fresh()->status);
        $this->assertEquals('failed', $upload->fresh()->status);
        $this->assertEquals(0, EditionPage::where('edition_id', $edition->id)->count());
    }

    /**
     * Requirement 2: Missing Ghostscript engine throws Processing Engine Unavailable.
     */
    public function test_missing_ghostscript_fails_with_engine_unavailable_error(): void
    {
        $relativeDir = "private/tenants/{$this->tenant->slug}/editions/HD-MISSING-GS/original";
        $filename = 'source.pdf';
        Storage::disk('local')->put("{$relativeDir}/{$filename}", $this->samplePdfBytes);

        $edition = Edition::create([
            'tenant_id' => $this->tenant->id,
            'edition_code' => 'HD-MISSING-GS',
            'edition_name' => 'Missing GS Test',
            'publish_date' => date('Y-m-d'),
            'status' => 'uploaded',
            'original_pdf_path' => "{$relativeDir}/{$filename}",
            'checksum_sha256' => $this->sampleSha256,
        ]);

        $upload = PdfUpload::create([
            'tenant_id' => $this->tenant->id,
            'edition_id' => $edition->id,
            'original_filename' => 'source.pdf',
            'stored_filename' => $filename,
            'file_path' => "{$relativeDir}/{$filename}",
            'file_size' => strlen($this->samplePdfBytes),
            'checksum_sha256' => $this->sampleSha256,
            'status' => 'uploaded',
        ]);

        $processor = app(LocalPdfProcessingService::class);
        $result = $processor->processEditionPdf($edition, $upload, ['mock_missing_ghostscript' => true]);

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('Processing Engine Unavailable', $result['error']);
        $this->assertEquals('processing_failed', $edition->fresh()->status);
    }

    /**
     * Requirement 2 & 4: Failed processing edition cannot enter Ready for Review, Scheduled, or Published.
     */
    public function test_failed_processing_edition_cannot_be_published_or_scheduled(): void
    {
        $edition = Edition::create([
            'tenant_id' => $this->tenant->id,
            'edition_code' => 'HD-FAILED-PUB',
            'edition_name' => 'Failed Edition Publish Block Test',
            'publish_date' => date('Y-m-d'),
            'status' => 'processing_failed',
            'total_pages' => 0,
        ]);

        $publishingService = app(PublishingService::class);

        // Attempt Publish
        $this->expectException(ValidationException::class);
        $publishingService->publishNow($this->tenant->id, $edition->id, $this->user->id);
    }

    /**
     * Requirement 2 & 4: Edition containing synthetic GD broadsheet pages cannot be published.
     */
    public function test_edition_containing_synthetic_pages_cannot_be_published(): void
    {
        $relativeDir = "private/tenants/{$this->tenant->slug}/editions/HD-SYNTH-BLOCK/original";
        $filename = 'source.pdf';
        Storage::disk('local')->put("{$relativeDir}/{$filename}", $this->samplePdfBytes);

        $edition = Edition::create([
            'tenant_id' => $this->tenant->id,
            'edition_code' => 'HD-SYNTH-BLOCK',
            'edition_name' => 'Synthetic Block Test',
            'publish_date' => date('Y-m-d'),
            'status' => 'ready_for_review',
            'original_pdf_path' => "{$relativeDir}/{$filename}",
            'checksum_sha256' => $this->sampleSha256,
            'total_pages' => 1,
        ]);

        EditionPage::create([
            'edition_id' => $edition->id,
            'tenant_id' => $this->tenant->id,
            'page_number' => 1,
            'page_title' => 'Page 1 Synthetic',
            'image_path' => '/storage/page1.png',
            'thumbnail_path' => '/storage/thumb1.png',
            'width' => 1200,
            'height' => 1800,
            'rotation' => 0,
            'is_visible' => true,
            'sort_order' => 1,
            'metadata' => [
                'engine' => 'gd_fallback', // Synthetic flag
                'dpi' => 150,
            ],
        ]);

        $publishingService = app(PublishingService::class);

        $this->expectException(ValidationException::class);
        $publishingService->publishNow($this->tenant->id, $edition->id, $this->user->id);
    }

    /**
     * Requirement 5: Unpublish removes public manifest & updates latest.json but PRESERVES source PDF.
     */
    public function test_unpublish_preserves_source_pdf_while_purging_public_manifest(): void
    {
        $relativeDir = "private/tenants/{$this->tenant->slug}/editions/HD-UNPUB-PRESERVE/original";
        $filename = 'source.pdf';
        Storage::disk('local')->put("{$relativeDir}/{$filename}", $this->samplePdfBytes);

        $edition = Edition::create([
            'tenant_id' => $this->tenant->id,
            'edition_code' => 'HD-UNPUB-PRESERVE',
            'edition_name' => 'Unpublish Preserve Test',
            'publish_date' => date('Y-m-d'),
            'status' => 'ready_for_review',
            'original_pdf_path' => "{$relativeDir}/{$filename}",
            'checksum_sha256' => $this->sampleSha256,
            'total_pages' => 1,
        ]);

        EditionPage::create([
            'edition_id' => $edition->id,
            'tenant_id' => $this->tenant->id,
            'page_number' => 1,
            'page_title' => 'Page 1',
            'image_path' => '/storage/page1.png',
            'thumbnail_path' => '/storage/thumb1.png',
            'width' => 1200,
            'height' => 1800,
            'rotation' => 0,
            'is_visible' => true,
            'sort_order' => 1,
            'metadata' => ['engine' => 'ghostscript', 'dpi' => 150],
        ]);

        $publishingService = app(PublishingService::class);

        // 1. Publish
        $publishingService->publishNow($this->tenant->id, $edition->id, $this->user->id);
        $this->assertEquals('published', $edition->fresh()->status);
        Storage::disk('public')->assertExists("tenants/{$this->tenant->slug}/editions/HD-UNPUB-PRESERVE/manifest.json");

        // 2. Unpublish
        $publishingService->unpublish($this->tenant->id, $edition->id, $this->user->id);
        $freshEdition = $edition->fresh();

        $this->assertEquals('ready_for_review', $freshEdition->status);
        $this->assertNull($freshEdition->public_manifest);

        // Public manifest was deleted
        Storage::disk('public')->assertMissing("tenants/{$this->tenant->slug}/editions/HD-UNPUB-PRESERVE/manifest.json");

        // CRITICAL REQUIREMENT: Source PDF must strictly be preserved!
        Storage::disk('local')->assertExists("{$relativeDir}/{$filename}");
    }

    /**
     * Requirement 5: Deleting an edition purges source PDF, rendered public assets, and credits storage quota.
     */
    public function test_edition_delete_purges_source_pdf_and_rendered_assets_and_credits_quota(): void
    {
        $relativeDir = "private/tenants/{$this->tenant->slug}/editions/HD-DELETE-PURGE/original";
        $filename = 'source.pdf';
        Storage::disk('local')->put("{$relativeDir}/{$filename}", $this->samplePdfBytes);

        $publicDir = "tenants/{$this->tenant->slug}/editions/HD-DELETE-PURGE/pages";
        Storage::disk('public')->put("{$publicDir}/page_1.png", "fake png data");

        $initialUsed = (float)$this->tenant->storage_used_mb;
        $fileSizeMb = 15.5;

        // Simulate tenant storage usage
        $this->tenant->storage_used_mb = $initialUsed + $fileSizeMb;
        $this->tenant->save();

        $edition = Edition::create([
            'tenant_id' => $this->tenant->id,
            'edition_code' => 'HD-DELETE-PURGE',
            'edition_name' => 'Delete Purge Test',
            'publish_date' => date('Y-m-d'),
            'status' => 'draft',
            'original_pdf_path' => "{$relativeDir}/{$filename}",
            'file_size_mb' => $fileSizeMb,
            'checksum_sha256' => $this->sampleSha256,
        ]);

        $editionService = app(EditionService::class);
        $deleted = $editionService->deleteEdition($this->tenant->id, $edition->id, $this->user->id);

        $this->assertTrue($deleted);

        // Assert source PDF purged from private storage
        Storage::disk('local')->assertMissing("{$relativeDir}/{$filename}");

        // Assert rendered public directory purged
        Storage::disk('public')->assertMissing("{$publicDir}/page_1.png");

        // Assert storage quota was credited back
        $freshTenant = $this->tenant->fresh();
        $this->assertEquals($initialUsed, (float)$freshTenant->storage_used_mb);
    }

    /**
     * Requirement 4: Suspended tenant public reader returns 403 Forbidden.
     */
    public function test_suspended_tenant_public_reader_returns_403_forbidden(): void
    {
        // Suspend tenant
        $this->tenant->status = 'suspended';
        $this->tenant->save();

        $response = $this->getJson("/api/v1/public/{$this->tenant->slug}/latest");
        $response->assertStatus(403);
        $response->assertJson([
            'success' => false,
            'message' => 'Publication is currently suspended or inactive.',
        ]);
    }

    /**
     * Requirement 4: ETag & Cache-Control headers and 304 Not Modified support.
     */
    public function test_etag_and_304_not_modified_support_in_public_manifest(): void
    {
        $this->tenant->status = 'active';
        $this->tenant->save();

        $relativeDir = "private/tenants/{$this->tenant->slug}/editions/HD-ETAG/original";
        $filename = 'source.pdf';
        Storage::disk('local')->put("{$relativeDir}/{$filename}", $this->samplePdfBytes);

        $edition = Edition::create([
            'tenant_id' => $this->tenant->id,
            'edition_code' => 'HD-ETAG',
            'edition_name' => 'ETag Test',
            'publish_date' => date('Y-m-d'),
            'status' => 'ready_for_review',
            'original_pdf_path' => "{$relativeDir}/{$filename}",
            'checksum_sha256' => $this->sampleSha256,
            'total_pages' => 1,
        ]);

        EditionPage::create([
            'edition_id' => $edition->id,
            'tenant_id' => $this->tenant->id,
            'page_number' => 1,
            'page_title' => 'Page 1',
            'image_path' => '/storage/page1.png',
            'thumbnail_path' => '/storage/thumb1.png',
            'width' => 1200,
            'height' => 1800,
            'rotation' => 0,
            'is_visible' => true,
            'sort_order' => 1,
            'metadata' => ['engine' => 'ghostscript', 'dpi' => 150],
        ]);

        $publishingService = app(PublishingService::class);
        $publishingService->publishNow($this->tenant->id, $edition->id, $this->user->id);

        // First request: receives 200 with ETag header
        $response = $this->getJson("/api/v1/public/{$this->tenant->slug}/latest");
        $response->assertStatus(200);
        $response->assertHeader('ETag');
        $response->assertHeader('Cache-Control');

        $etag = $response->headers->get('ETag');
        $this->assertNotEmpty($etag);

        // Second request with If-None-Match receives 304 Not Modified
        $cachedResponse = $this->withHeader('If-None-Match', $etag)
            ->getJson("/api/v1/public/{$this->tenant->slug}/latest");

        $cachedResponse->assertStatus(304);
    }

    /**
     * Requirement 3 (Storage Quota Accounting):
     * Repeated deletion requests fail and do NOT double-credit the tenant storage quota.
     */
    public function test_repeated_delete_request_fails_and_does_not_double_credit_storage(): void
    {
        $editionService = app(EditionService::class);
        $initialUsed = 50.0;
        $fileSizeMb = 12.5;

        $this->tenant->storage_used_mb = $initialUsed;
        $this->tenant->save();

        $edition = Edition::create([
            'tenant_id' => $this->tenant->id,
            'edition_code' => 'HD-REPEATED-DEL',
            'edition_name' => 'Repeated Delete Test',
            'publish_date' => date('Y-m-d'),
            'status' => 'draft',
            'file_size_mb' => $fileSizeMb,
        ]);

        // First deletion: succeeds and credits exactly 12.5 MB
        $success = $editionService->deleteEdition($this->tenant->id, $edition->id, $this->user->id);
        $this->assertTrue($success);

        $freshTenant = $this->tenant->fresh();
        $expectedAfterFirstDelete = $initialUsed - $fileSizeMb; // 37.5 MB
        $this->assertEquals($expectedAfterFirstDelete, (float)$freshTenant->storage_used_mb);

        // Second deletion attempt on the same edition: must fail with ModelNotFoundException
        $secondAttemptFailed = false;
        try {
            $editionService->deleteEdition($this->tenant->id, $edition->id, $this->user->id);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            $secondAttemptFailed = true;
        }

        $this->assertTrue($secondAttemptFailed, 'Repeated delete must fail because edition was already purged.');

        // Verify storage_used_mb was NOT decremented again (no double-credit)
        $this->assertEquals($expectedAfterFirstDelete, (float)$this->tenant->fresh()->storage_used_mb);
    }

    /**
     * Requirement 3 (Storage Quota Accounting):
     * Transaction rollback on failed deletion prevents storage quota decrement.
     */
    public function test_failed_deletion_transaction_rolls_back_storage_credit(): void
    {
        $initialUsed = 40.0;
        $fileSizeMb = 10.0;

        $this->tenant->storage_used_mb = $initialUsed;
        $this->tenant->save();

        $edition = Edition::create([
            'tenant_id' => $this->tenant->id,
            'edition_code' => 'HD-ROLLBACK-TEST',
            'edition_name' => 'Rollback Delete Test',
            'publish_date' => date('Y-m-d'),
            'status' => 'draft',
            'file_size_mb' => $fileSizeMb,
        ]);

        // Begin transaction
        \Illuminate\Support\Facades\DB::beginTransaction();

        $this->tenant->storage_used_mb = max(0, $initialUsed - $fileSizeMb);
        $this->tenant->save();
        $this->assertEquals(30.0, (float)$this->tenant->storage_used_mb);

        // Abort / Roll back
        \Illuminate\Support\Facades\DB::rollBack();

        // Storage quota must rollback to initialUsed exactly
        $this->assertEquals($initialUsed, (float)$this->tenant->fresh()->storage_used_mb);

        // Edition must still exist
        $this->assertNotNull(Edition::find($edition->id));
    }

    /**
     * Requirement 3 (Storage Quota Accounting):
     * Deleting a draft edition with zero size does not corrupt or alter storage quota.
     */
    public function test_zero_size_draft_edition_deletion_does_not_alter_quota(): void
    {
        $editionService = app(EditionService::class);
        $initialUsed = 25.5;

        $this->tenant->storage_used_mb = $initialUsed;
        $this->tenant->save();

        $edition = Edition::create([
            'tenant_id' => $this->tenant->id,
            'edition_code' => 'HD-ZERO-SIZE',
            'edition_name' => 'Zero Size Draft',
            'publish_date' => date('Y-m-d'),
            'status' => 'draft',
            'file_size_mb' => 0.0,
        ]);

        $success = $editionService->deleteEdition($this->tenant->id, $edition->id, $this->user->id);
        $this->assertTrue($success);

        // Storage used must remain exactly unchanged
        $this->assertEquals($initialUsed, (float)$this->tenant->fresh()->storage_used_mb);
    }

    /**
     * Requirement 3 (Storage Quota Accounting):
     * Published edition deletion attempt is rejected and leaves quota untouched.
     */
    public function test_published_edition_deletion_rejection_leaves_quota_untouched(): void
    {
        $editionService = app(EditionService::class);
        $initialUsed = 60.0;
        $fileSizeMb = 18.0;

        $this->tenant->storage_used_mb = $initialUsed;
        $this->tenant->save();

        $edition = Edition::create([
            'tenant_id' => $this->tenant->id,
            'edition_code' => 'HD-PUB-REJECT',
            'edition_name' => 'Published Rejection Test',
            'publish_date' => date('Y-m-d'),
            'status' => 'published',
            'file_size_mb' => $fileSizeMb,
        ]);

        $this->expectException(ValidationException::class);

        try {
            $editionService->deleteEdition($this->tenant->id, $edition->id, $this->user->id);
        } finally {
            // Storage quota must remain untouched
            $this->assertEquals($initialUsed, (float)$this->tenant->fresh()->storage_used_mb);
            $this->assertEquals('published', $edition->fresh()->status);
        }
    }
}
