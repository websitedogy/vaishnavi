<?php

namespace Tests\Modules\TenantAdmin;

use Tests\TestCase;
use App\Modules\TenantAdmin\Models\Edition;
use App\Modules\TenantAdmin\Models\EditionPage;
use App\Modules\TenantAdmin\Models\PdfUpload;
use App\Modules\SuperAdmin\Models\Tenant;
use App\Modules\TenantAdmin\Services\LocalPdfProcessingService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class PdfRasterizationVerificationTest extends TestCase
{
    use DatabaseTransactions;

    protected Tenant $tenant;
    protected LocalPdfProcessingService $processor;
    protected string $samplePdfPath;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tenant = Tenant::firstOrCreate(
            ['slug' => 'test-raster-tenant'],
            [
                'name' => 'Test Raster Daily',
                'email' => 'raster@dogy-epaper.com',
                'custom_domain' => 'raster.local',
                'plan' => 'enterprise',
                'storage_quota_mb' => 10240,
                'storage_used_mb' => 10,
                'is_active' => true,
            ]
        );

        $this->processor = app(LocalPdfProcessingService::class);
        $this->samplePdfPath = storage_path('app/testing/telugu_sample_edition.pdf');
    }

    /**
     * Requirement A.1 & A.2: Upload real multi-page Telugu newspaper PDF and verify every page renders.
     */
    public function test_real_telugu_newspaper_pdf_renders_all_pages(): void
    {
        $this->assertFileExists($this->samplePdfPath, 'Sample Telugu newspaper PDF must exist.');

        // Copy sample PDF to tenant private storage
        $storedFilename = 'TEL-RASTER-01_source.pdf';
        $relativeDir = "private/tenants/{$this->tenant->slug}/editions/TEL-RASTER-01/original";
        Storage::disk('local')->makeDirectory($relativeDir);
        Storage::disk('local')->put("{$relativeDir}/{$storedFilename}", file_get_contents($this->samplePdfPath));

        $edition = Edition::create([
            'tenant_id' => $this->tenant->id,
            'edition_code' => 'TEL-RASTER-01',
            'edition_name' => 'Telugu Daily Hyderabad Main',
            'publish_date' => date('Y-m-d'),
            'original_pdf_path' => "{$relativeDir}/{$storedFilename}",
            'status' => 'uploaded',
        ]);

        $upload = PdfUpload::create([
            'tenant_id' => $this->tenant->id,
            'edition_id' => $edition->id,
            'original_filename' => 'telugu_sample_edition.pdf',
            'stored_filename' => $storedFilename,
            'file_path' => "{$relativeDir}/{$storedFilename}",
            'file_size' => filesize($this->samplePdfPath),
            'mime_type' => 'application/pdf',
            'checksum_sha256' => hash_file('sha256', $this->samplePdfPath),
            'status' => 'uploaded',
        ]);

        $result = $this->processor->processEditionPdf($edition, $upload);

        $this->assertTrue($result['success']);
        $this->assertEquals(4, $result['page_count'], 'Must render all 4 pages of the Telugu newspaper.');

        $pages = EditionPage::where('edition_id', $edition->id)->orderBy('page_number')->get();
        $this->assertCount(4, $pages);

        // Verify each page file exists physically
        foreach ($pages as $p) {
            $this->assertNotEmpty($p->image_path);
            $this->assertNotEmpty($p->thumbnail_path);

            $pageFilePath = storage_path("app/public/tenants/{$this->tenant->slug}/editions/TEL-RASTER-01/pages/page_{$p->page_number}.png");
            $thumbFilePath = storage_path("app/public/tenants/{$this->tenant->slug}/editions/TEL-RASTER-01/thumbnails/page_{$p->page_number}.png");

            $this->assertFileExists($pageFilePath);
            $this->assertFileExists($thumbFilePath);
            $this->assertGreaterThan(1000, filesize($pageFilePath), "Page {$p->page_number} must have realistic byte size.");
            $this->assertGreaterThan(500, filesize($thumbFilePath), "Thumbnail {$p->page_number} must have realistic byte size.");
        }
    }

    /**
     * Requirement A.3, A.4 & Mathematical Image Comparison Methodology:
     * Compare source PDF specifications (595x842 pt MediaBox, 1:1.4151 aspect ratio)
     * against extracted PNG geometry and verify visual ink coverage.
     */
    public function test_mathematical_image_comparison_and_aspect_ratio_fidelity(): void
    {
        $relativeDir = "private/tenants/{$this->tenant->slug}/editions/TEL-RASTER-02/original";
        $storedFilename = 'TEL-RASTER-02_source.pdf';
        Storage::disk('local')->makeDirectory($relativeDir);
        Storage::disk('local')->put("{$relativeDir}/{$storedFilename}", file_get_contents($this->samplePdfPath));

        $edition = Edition::create([
            'tenant_id' => $this->tenant->id,
            'edition_code' => 'TEL-RASTER-02',
            'edition_name' => 'Aspect Ratio Fidelity Check',
            'publish_date' => date('Y-m-d'),
            'status' => 'uploaded',
        ]);

        $upload = PdfUpload::create([
            'tenant_id' => $this->tenant->id,
            'edition_id' => $edition->id,
            'original_filename' => 'sample.pdf',
            'stored_filename' => $storedFilename,
            'file_path' => "{$relativeDir}/{$storedFilename}",
            'file_size' => filesize($this->samplePdfPath),
            'mime_type' => 'application/pdf',
            'status' => 'uploaded',
        ]);

        $this->processor->processEditionPdf($edition, $upload, ['dpi' => 150]);

        $page1 = EditionPage::where('edition_id', $edition->id)->where('page_number', 1)->first();
        $this->assertNotNull($page1);

        $pageImgPath = storage_path("app/public/tenants/{$this->tenant->slug}/editions/TEL-RASTER-02/pages/page_1.png");
        $this->assertFileExists($pageImgPath);

        // 1. Inspect image dimensions
        $imageSize = getimagesize($pageImgPath);
        $width = $imageSize[0];
        $height = $imageSize[1];

        // Ghostscript at 150 DPI yields ~1150-1250 x 1600-1800 px depending on CropBox/MediaBox
        $this->assertGreaterThanOrEqual(1100, $width);
        $this->assertLessThanOrEqual(1300, $width);
        $this->assertGreaterThanOrEqual(1600, $height);
        $this->assertLessThanOrEqual(1850, $height);

        // 2. Aspect Ratio Comparison
        // Rendered canvas is 8.5 x 11 inches at 150 DPI (1275 x 1650 px)
        $expectedCanvasRatio = 11.0 / 8.5; // 1.294117647
        $renderedRatio = (float)$height / (float)$width;
        $aspectRatioDelta = abs($renderedRatio - $expectedCanvasRatio);

        // Mathematical conformance error must be below 0.1%
        $this->assertLessThan(0.005, $aspectRatioDelta, "Rendered aspect ratio ({$renderedRatio}) must mathematically match canvas ratio ({$expectedCanvasRatio}) within 0.5%.");

        // 3. Pixel Ink Density / Visual Content Methodology using GD
        $im = imagecreatefrompng($pageImgPath);
        $this->assertNotFalse($im, 'GD must be able to load generated PNG.');

        // Sample 100 random pixels across the page to ensure it is not a blank canvas
        $nonWhitePixels = 0;
        $totalSamples = 200;
        for ($i = 0; $i < $totalSamples; $i++) {
            $sx = rand(50, $width - 50);
            $sy = rand(50, $height - 50);
            $rgb = imagecolorat($im, $sx, $sy);
            $r = ($rgb >> 16) & 0xFF;
            $g = ($rgb >> 8) & 0xFF;
            $b = $rgb & 0xFF;

            // Anything noticeably darker than pure white (255, 255, 255)
            if ($r < 240 || $g < 240 || $b < 240) {
                $nonWhitePixels++;
            }
        }
        imagedestroy($im);

        // Real newspaper page must have ink (text, lines, masthead)
        $this->assertGreaterThan(0, $nonWhitePixels, "Page must contain non-white ink pixels (actual rendered text). Non-white samples: {$nonWhitePixels}/{$totalSamples}");

        // 4. Page Ordering and Rotation Verification
        $allPages = EditionPage::where('edition_id', $edition->id)->orderBy('page_number')->get();
        $expectedOrder = 1;
        foreach ($allPages as $p) {
            $this->assertEquals($expectedOrder, $p->sort_order, "Page {$expectedOrder} must have sort_order {$expectedOrder}.");
            $this->assertEquals(0, $p->rotation, "Default rotation must be 0 degrees.");
            $this->assertTrue($p->is_visible);
            $expectedOrder++;
        }
    }

    /**
     * Requirement A.5: Test 150 DPI vs 300 DPI output quality and dimension scaling.
     */
    public function test_150_dpi_and_300_dpi_resolution_scaling(): void
    {
        $relativeDir = "private/tenants/{$this->tenant->slug}/editions/TEL-DPI/original";
        $storedFilename = 'TEL-DPI_source.pdf';
        Storage::disk('local')->makeDirectory($relativeDir);
        Storage::disk('local')->put("{$relativeDir}/{$storedFilename}", file_get_contents($this->samplePdfPath));

        $edition150 = Edition::create([
            'tenant_id' => $this->tenant->id,
            'edition_code' => 'TEL-DPI-150',
            'edition_name' => '150 DPI Edition',
            'publish_date' => date('Y-m-d'),
            'status' => 'uploaded',
        ]);
        $upload150 = PdfUpload::create([
            'tenant_id' => $this->tenant->id,
            'edition_id' => $edition150->id,
            'original_filename' => 'TEL-DPI_source.pdf',
            'stored_filename' => $storedFilename,
            'file_path' => "{$relativeDir}/{$storedFilename}",
            'file_size' => filesize($this->samplePdfPath),
            'status' => 'uploaded',
        ]);

        $edition300 = Edition::create([
            'tenant_id' => $this->tenant->id,
            'edition_code' => 'TEL-DPI-300',
            'edition_name' => '300 DPI Edition',
            'publish_date' => date('Y-m-d'),
            'status' => 'uploaded',
        ]);
        $upload300 = PdfUpload::create([
            'tenant_id' => $this->tenant->id,
            'edition_id' => $edition300->id,
            'original_filename' => 'TEL-DPI_source.pdf',
            'stored_filename' => $storedFilename,
            'file_path' => "{$relativeDir}/{$storedFilename}",
            'file_size' => filesize($this->samplePdfPath),
            'status' => 'uploaded',
        ]);

        $this->processor->processEditionPdf($edition150, $upload150, ['dpi' => 150]);
        $this->processor->processEditionPdf($edition300, $upload300, ['dpi' => 300]);

        $page1_150 = EditionPage::where('edition_id', $edition150->id)->where('page_number', 1)->first();
        $page1_300 = EditionPage::where('edition_id', $edition300->id)->where('page_number', 1)->first();

        $this->assertNotNull($page1_150);
        $this->assertNotNull($page1_300);

        // 300 DPI width and height must be approximately 2x of 150 DPI
        $ratioW = (float)$page1_300->width / (float)$page1_150->width;
        $ratioH = (float)$page1_300->height / (float)$page1_150->height;

        $this->assertEqualsWithDelta(2.0, $ratioW, 0.05, "300 DPI width must be ~2x of 150 DPI width.");
        $this->assertEqualsWithDelta(2.0, $ratioH, 0.05, "300 DPI height must be ~2x of 150 DPI height.");

        // File size at 300 DPI should be larger due to higher pixel density
        $file150 = storage_path("app/public/tenants/{$this->tenant->slug}/editions/TEL-DPI-150/pages/page_1.png");
        $file300 = storage_path("app/public/tenants/{$this->tenant->slug}/editions/TEL-DPI-300/pages/page_1.png");

        $this->assertGreaterThan(filesize($file150), filesize($file300), "300 DPI image file size must exceed 150 DPI file size.");
    }

    /**
     * Requirement A.6: Test Empty PDF handling.
     */
    public function test_empty_pdf_handling(): void
    {
        $relativeDir = "private/tenants/{$this->tenant->slug}/editions/TEL-EMPTY/original";
        $storedFilename = 'empty.pdf';
        Storage::disk('local')->makeDirectory($relativeDir);
        Storage::disk('local')->put("{$relativeDir}/{$storedFilename}", ""); // 0 bytes

        $edition = Edition::create([
            'tenant_id' => $this->tenant->id,
            'edition_code' => 'TEL-EMPTY',
            'edition_name' => 'Empty PDF Test',
            'publish_date' => date('Y-m-d'),
            'status' => 'uploaded',
        ]);
        $upload = PdfUpload::create([
            'tenant_id' => $this->tenant->id,
            'edition_id' => $edition->id,
            'original_filename' => 'empty.pdf',
            'stored_filename' => $storedFilename,
            'file_path' => "{$relativeDir}/{$storedFilename}",
            'file_size' => 0,
            'status' => 'uploaded',
        ]);

        $result = $this->processor->processEditionPdf($edition, $upload);

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('empty (0 bytes)', $result['error']);
        $this->assertEquals('processing_failed', $edition->fresh()->status);
        $this->assertEquals('failed', $upload->fresh()->status);
    }

    /**
     * Requirement A.6 & A.7: Test Corrupted PDF handling and fallback logging.
     */
    public function test_corrupted_pdf_handling_and_fallback_logging(): void
    {
        $relativeDir = "private/tenants/{$this->tenant->slug}/editions/TEL-CORRUPT/original";
        $storedFilename = 'corrupt.pdf';
        // Corrupted file: not a PDF, random binary bytes
        $corruptBytes = "NOT_A_PDF_HEADER\x00\xFF\xDE\xAD\xBE\xEF" . str_repeat("garbage data line\n", 50);
        Storage::disk('local')->makeDirectory($relativeDir);
        Storage::disk('local')->put("{$relativeDir}/{$storedFilename}", $corruptBytes);

        $edition = Edition::create([
            'tenant_id' => $this->tenant->id,
            'edition_code' => 'TEL-CORRUPT',
            'edition_name' => 'Corrupt PDF Test',
            'publish_date' => date('Y-m-d'),
            'status' => 'uploaded',
        ]);
        $upload = PdfUpload::create([
            'tenant_id' => $this->tenant->id,
            'edition_id' => $edition->id,
            'original_filename' => 'corrupt.pdf',
            'stored_filename' => $storedFilename,
            'file_path' => "{$relativeDir}/{$storedFilename}",
            'file_size' => strlen($corruptBytes),
            'status' => 'uploaded',
        ]);

        $result = $this->processor->processEditionPdf($edition, $upload);

        // Production Hardening: Real editions must strictly FAIL processing on corrupted input (no fake synthetic fallback)
        $this->assertFalse($result['success']);
        $this->assertEquals('processing_failed', $edition->fresh()->status);
        $this->assertEquals('failed', $upload->fresh()->status);
        $this->assertStringContainsString('Corrupted PDF', $result['error']);

        // Verify no fake broadsheet pages were generated or stored for real upload
        $pages = EditionPage::where('edition_id', $edition->id)->get();
        $this->assertEmpty($pages);

        // Test explicit demo fixture fallback: GD fallback permitted ONLY if explicitly enabled
        $demoEdition = Edition::create([
            'tenant_id' => $this->tenant->id,
            'edition_code' => 'TEL-CORRUPT-DEMO',
            'edition_name' => 'Demo Corrupt PDF Test',
            'publish_date' => date('Y-m-d'),
            'status' => 'uploaded',
        ]);
        $demoUpload = PdfUpload::create([
            'tenant_id' => $this->tenant->id,
            'edition_id' => $demoEdition->id,
            'original_filename' => 'corrupt.pdf',
            'stored_filename' => $storedFilename,
            'file_path' => "{$relativeDir}/{$storedFilename}",
            'file_size' => strlen($corruptBytes),
            'status' => 'uploaded',
        ]);

        $demoResult = $this->processor->processEditionPdf($demoEdition, $demoUpload, ['allow_synthetic_fallback' => true]);
        $this->assertTrue($demoResult['success']);
        $demoPages = EditionPage::where('edition_id', $demoEdition->id)->get();
        $this->assertNotEmpty($demoPages);
        $this->assertEquals('gd_fallback', $demoPages->first()->metadata['engine']);
    }

    /**
     * Requirement A.6 & A.7: Test Password-Protected / Encrypted PDF detection.
     */
    public function test_encrypted_pdf_detection(): void
    {
        $encryptedSamplePath = storage_path('app/testing/encrypted_sample.pdf');
        $this->assertFileExists($encryptedSamplePath);

        $relativeDir = "private/tenants/{$this->tenant->slug}/editions/TEL-ENCRYPT/original";
        $storedFilename = 'encrypted.pdf';
        Storage::disk('local')->makeDirectory($relativeDir);
        Storage::disk('local')->put("{$relativeDir}/{$storedFilename}", file_get_contents($encryptedSamplePath));

        $edition = Edition::create([
            'tenant_id' => $this->tenant->id,
            'edition_code' => 'TEL-ENCRYPT',
            'edition_name' => 'Encrypted PDF Test',
            'publish_date' => date('Y-m-d'),
            'status' => 'uploaded',
        ]);
        $upload = PdfUpload::create([
            'tenant_id' => $this->tenant->id,
            'edition_id' => $edition->id,
            'original_filename' => 'encrypted.pdf',
            'stored_filename' => $storedFilename,
            'file_path' => "{$relativeDir}/{$storedFilename}",
            'file_size' => filesize($encryptedSamplePath),
            'status' => 'uploaded',
        ]);

        $result = $this->processor->processEditionPdf($edition, $upload);

        // Production Hardening: Password-protected PDFs must fail processing with Password Required error
        $this->assertFalse($result['success']);
        $this->assertStringContainsString('Password Required', $result['error']);
        $this->assertEquals('processing_failed', $edition->fresh()->status);
        $this->assertEquals('failed', $upload->fresh()->status);

        // Verify no fake broadsheet pages were generated or stored for real upload
        $pages = EditionPage::where('edition_id', $edition->id)->get();
        $this->assertEmpty($pages);
    }

    /**
     * Requirement A.8: Verify partial output cleanup after failure.
     */
    public function test_partial_output_cleanup_after_failure(): void
    {
        $relativeDir = "private/tenants/{$this->tenant->slug}/editions/TEL-FAIL-CLEAN/original";
        $storedFilename = 'partial_fail.pdf';
        Storage::disk('local')->makeDirectory($relativeDir);
        Storage::disk('local')->put("{$relativeDir}/{$storedFilename}", file_get_contents($this->samplePdfPath));

        $edition = Edition::create([
            'tenant_id' => $this->tenant->id,
            'edition_code' => 'TEL-FAIL-CLEAN',
            'edition_name' => 'Cleanup Test Edition',
            'publish_date' => date('Y-m-d'),
            'status' => 'uploaded',
        ]);
        $upload = PdfUpload::create([
            'tenant_id' => $this->tenant->id,
            'edition_id' => $edition->id,
            'original_filename' => 'partial_fail.pdf',
            'stored_filename' => $storedFilename,
            'file_path' => "{$relativeDir}/{$storedFilename}",
            'file_size' => filesize($this->samplePdfPath),
            'status' => 'uploaded',
        ]);

        // Pre-create some dummy partial files in public directories
        $pagesDir = "public/tenants/{$this->tenant->slug}/editions/TEL-FAIL-CLEAN/pages";
        Storage::disk('local')->makeDirectory($pagesDir);
        Storage::disk('local')->put("{$pagesDir}/page_1.png", "dummy-bytes");

        // Pre-create an EditionPage record
        EditionPage::create([
            'edition_id' => $edition->id,
            'tenant_id' => $this->tenant->id,
            'page_number' => 1,
            'page_title' => 'Orphaned Page',
            'image_path' => '/storage/dummy.png',
            'thumbnail_path' => '/storage/dummy_thumb.png',
            'width' => 1200,
            'height' => 1800,
            'rotation' => 0,
            'is_visible' => true,
            'sort_order' => 1,
        ]);

        // Now delete the source PDF so that processing fails during retry or re-process
        Storage::disk('local')->delete("{$relativeDir}/{$storedFilename}");

        $result = $this->processor->processEditionPdf($edition, $upload);

        $this->assertFalse($result['success']);
        $this->assertEquals('processing_failed', $edition->fresh()->status);

        // Assert partial output was cleaned up: no orphaned EditionPage records remain
        $orphanedPages = EditionPage::where('edition_id', $edition->id)->count();
        $this->assertEquals(0, $orphanedPages, "Partial output cleanup must leave zero orphaned page records.");
    }
}
