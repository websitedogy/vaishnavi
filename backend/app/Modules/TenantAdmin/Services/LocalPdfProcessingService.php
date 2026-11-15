<?php

namespace App\Modules\TenantAdmin\Services;

use App\Modules\TenantAdmin\Models\Edition;
use App\Modules\TenantAdmin\Models\EditionPage;
use App\Modules\TenantAdmin\Models\PdfUpload;
use App\Modules\TenantAdmin\Models\TenantNotification;
use App\Modules\SuperAdmin\Models\Tenant;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class LocalPdfProcessingService
{
    /**
     * Cache the discovered Ghostscript binary path.
     */
    protected ?string $ghostscriptBinary = null;

    public function processEditionPdf(Edition $edition, PdfUpload $upload, array $options = []): array
    {
        $tenant = Tenant::find($edition->tenant_id);
        $fullPdfPath = Storage::path($upload->file_path);
        $dpi = isset($options['dpi']) ? (int)$options['dpi'] : 150;
        $allowSyntheticFallback = (bool)($options['allow_synthetic_fallback'] ?? false);
        $publicBaseDir = "public/tenants/{$tenant->slug}/editions/{$edition->edition_code}";

        try {
            $edition->status = 'processing';
            $edition->save();

            $upload->status = 'processing';
            $upload->save();

            // 0. Validate File Integrity and Compute/Verify SHA-256 Checksum
            if (!Storage::disk('local')->exists($upload->file_path)) {
                throw new \InvalidArgumentException("Uploaded PDF file is missing.");
            }
            if ($upload->file_size !== null && $upload->file_size === 0) {
                throw new \InvalidArgumentException("Uploaded PDF file is empty (0 bytes).");
            }
            if (!file_exists($fullPdfPath) || filesize($fullPdfPath) === 0) {
                throw new \InvalidArgumentException("Uploaded PDF file is empty (0 bytes).");
            }

            // Recalculate SHA-256 from actual file bytes on disk
            $diskChecksum = hash_file('sha256', $fullPdfPath);
            $emptySha256 = 'e3b0c44298fc1c149afbf4c8996fb92427ae41e4649b934ca495991b7852b855';

            if (!$diskChecksum || $diskChecksum === $emptySha256) {
                throw new \InvalidArgumentException("Source PDF has an empty or invalid SHA-256 payload checksum.");
            }

            if ($upload->checksum_sha256 && $upload->checksum_sha256 !== $diskChecksum) {
                throw new \InvalidArgumentException("Checksum mismatch detected: Upload record checksum [{$upload->checksum_sha256}] does not match source file on disk [{$diskChecksum}].");
            }

            // Guarantee edition and upload records have the exact byte checksum
            $edition->checksum_sha256 = $diskChecksum;
            $upload->checksum_sha256 = $diskChecksum;

            // Check Ghostscript engine availability
            $gs = (!empty($options['mock_missing_ghostscript'])) ? null : $this->findGhostscriptBinary();
            if (!$gs && !$allowSyntheticFallback) {
                throw new \RuntimeException("PDF Processing Engine Unavailable: Ghostscript binary not found on server.");
            }

            // 1. Detect Page Count
            $pageCount = $this->extractPageCount($fullPdfPath, $allowSyntheticFallback);
            if ($pageCount < 1) {
                if (!$allowSyntheticFallback) {
                    throw new \RuntimeException("Corrupted PDF: No readable pages found in document.");
                }
                $pageCount = 4;
            }

            // 2. Prepare Public Storage Directories for rendered images
            Storage::makeDirectory("{$publicBaseDir}/pages");
            Storage::makeDirectory("{$publicBaseDir}/thumbnails");

            $generatedPages = [];
            $firstPageThumbnail = null;

            // Delete previous pages if re-processing
            EditionPage::where('edition_id', $edition->id)->delete();

            // 3. Render Page Images & Thumbnails for each page
            for ($pageNumber = 1; $pageNumber <= $pageCount; $pageNumber++) {
                $pageRelativePath = "{$publicBaseDir}/pages/page_{$pageNumber}.png";
                $thumbRelativePath = "{$publicBaseDir}/thumbnails/page_{$pageNumber}.png";

                $pageFullPath = Storage::path($pageRelativePath);
                $thumbFullPath = Storage::path($thumbRelativePath);

                // Ensure clean state before rendering page
                if (file_exists($pageFullPath)) {
                    @unlink($pageFullPath);
                }
                if (file_exists($thumbFullPath)) {
                    @unlink($thumbFullPath);
                }

                // Ensure target directories exist on filesystem
                @mkdir(dirname($pageFullPath), 0777, true);
                @mkdir(dirname($thumbFullPath), 0777, true);

                // Render page using Ghostscript (strict real rendering or explicit demo fallback)
                $renderResult = $this->renderPage(
                    $fullPdfPath,
                    $pageNumber,
                    $pageCount,
                    $pageFullPath,
                    $thumbFullPath,
                    $edition,
                    $dpi,
                    $allowSyntheticFallback,
                    $gs
                );

                $pageImageUrl = Storage::url($pageRelativePath);
                $thumbnailUrl = Storage::url($thumbRelativePath);

                if ($pageNumber === 1) {
                    $firstPageThumbnail = $thumbnailUrl;
                }

                $pageTitle = $this->getDefaultPageTitle($pageNumber, $pageCount);

                $pageRecord = EditionPage::create([
                    'edition_id' => $edition->id,
                    'tenant_id' => $edition->tenant_id,
                    'page_number' => $pageNumber,
                    'page_title' => $pageTitle,
                    'image_path' => $pageImageUrl,
                    'thumbnail_path' => $thumbnailUrl,
                    'width' => $renderResult['width'],
                    'height' => $renderResult['height'],
                    'rotation' => 0,
                    'is_visible' => true,
                    'sort_order' => $pageNumber,
                    'metadata' => [
                        'dpi' => $renderResult['dpi'],
                        'format' => 'png',
                        'engine' => $renderResult['engine'],
                        'extracted_at' => Carbon::now()->toIso8601String(),
                    ],
                ]);

                $generatedPages[] = $pageRecord;
            }

            // 4. Update Edition Status to Ready for Review
            $edition->total_pages = $pageCount;
            $edition->thumbnail_path = $firstPageThumbnail;
            $edition->status = 'ready_for_review';
            $edition->save();

            // 5. Update Upload Status
            $upload->page_count = $pageCount;
            $upload->status = 'completed';
            $upload->error_message = null;
            $upload->save();

            // 6. Send In-App Notification
            TenantNotification::create([
                'tenant_id' => $edition->tenant_id,
                'user_id' => $edition->created_by_user_id,
                'type' => 'pdf_processed',
                'title' => 'PDF Processing Completed',
                'message' => "Edition [{$edition->edition_name}] ({$pageCount} pages) processed successfully and is ready for review.",
                'data' => [
                    'edition_id' => $edition->id,
                    'edition_code' => $edition->edition_code,
                    'page_count' => $pageCount,
                ],
                'is_read' => false,
            ]);

            TenantAuditHelper::log(
                $edition->tenant_id,
                $edition->created_by_user_id,
                'pdf_processed',
                'editions',
                "Processed {$pageCount} pages for edition {$edition->edition_code}."
            );

            return [
                'success' => true,
                'page_count' => $pageCount,
                'pages' => $generatedPages,
            ];

        } catch (\Throwable $e) {
            // Clean up partial output and generated database records on failure (Section A.8)
            try {
                Storage::deleteDirectory("{$publicBaseDir}/pages");
                Storage::deleteDirectory("{$publicBaseDir}/thumbnails");
            } catch (\Throwable $cleanupEx) {
                Log::warning("Failed to clean up partial directory on failure: " . $cleanupEx->getMessage());
            }
            EditionPage::where('edition_id', $edition->id)->delete();

            $edition->status = 'processing_failed';
            $edition->total_pages = 0;
            $edition->save();

            $upload->status = 'failed';
            $upload->error_message = $e->getMessage();
            $upload->save();

            TenantNotification::create([
                'tenant_id' => $edition->tenant_id,
                'user_id' => $edition->created_by_user_id,
                'type' => 'pdf_failed',
                'title' => 'PDF Processing Error',
                'message' => "Failed to process PDF for edition [{$edition->edition_name}]: " . $e->getMessage(),
                'data' => ['edition_id' => $edition->id],
                'is_read' => false,
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    public function retryProcessing(int $tenantId, int $editionId): array
    {
        $edition = Edition::where('tenant_id', $tenantId)->findOrFail($editionId);
        $upload = PdfUpload::where('edition_id', $edition->id)->latest()->first();

        if (!$upload || !Storage::exists($upload->file_path)) {
            throw new \RuntimeException('No source PDF file found to re-process.');
        }

        return $this->processEditionPdf($edition, $upload);
    }

    /**
     * Render page: Attempts Ghostscript first for 100% pixel fidelity.
     * Prohibits synthetic broadsheet generation on real editions unless explicitly enabled for demo fixtures.
     */
    protected function renderPage(
        string $pdfPath,
        int $pageNumber,
        int $totalPages,
        string $pageFullPath,
        string $thumbFullPath,
        Edition $edition,
        int $dpi = 150,
        bool $allowSyntheticFallback = false,
        ?string $gs = null
    ): array {
        $gsBinary = $gs ?? $this->findGhostscriptBinary();

        if ($gsBinary && file_exists($pdfPath) && filesize($pdfPath) > 50) {
            $rendered = $this->renderPageWithGhostscript(
                $gsBinary,
                $pdfPath,
                $pageNumber,
                $pageFullPath,
                $thumbFullPath,
                $dpi,
                $allowSyntheticFallback
            );
            if ($rendered) {
                return $rendered;
            }
        }

        if (!$allowSyntheticFallback) {
            throw new \RuntimeException("PDF rendering failed on page {$pageNumber}: Ghostscript was unavailable or failed, and synthetic broadsheet fallback is strictly forbidden for real editions.");
        }

        // Demo fallback: GD high-res broadsheet simulation (strictly for testing/demo fixtures)
        Log::info("Demo mode: Using GD broadsheet renderer fallback for fixture on page {$pageNumber}.");
        $this->renderNewspaperPageImage($edition, $pageNumber, $totalPages, $pageFullPath, $thumbFullPath);

        return [
            'width' => 1200,
            'height' => 1800,
            'dpi' => $dpi,
            'engine' => 'gd_fallback',
        ];
    }

    /**
     * Render real PDF page to PNG using Ghostscript CLI.
     */
    protected function renderPageWithGhostscript(
        string $gsBinary,
        string $pdfPath,
        int $pageNumber,
        string $pageFullPath,
        string $thumbFullPath,
        int $dpi = 150,
        bool $allowSyntheticFallback = false
    ): ?array {
        try {
            // Command to render high-res page image
            $cmd = sprintf(
                '"%s" -dSAFER -dBATCH -dNOPAUSE -sDEVICE=png16m -r%d -dFirstPage=%d -dLastPage=%d "-sOutputFile=%s" "%s"',
                $gsBinary,
                $dpi,
                $pageNumber,
                $pageNumber,
                $pageFullPath,
                $pdfPath
            );

            $output = [];
            $returnCode = 0;
            @exec($cmd . ' 2>&1', $output, $returnCode);

            $outputStr = implode(' ', $output);

            // Check for encrypted/password-protected PDF
            if (stripos($outputStr, 'requires a password') !== false || stripos($outputStr, 'password did not work') !== false) {
                throw new \RuntimeException("Password Required: PDF is encrypted or password-protected and cannot be rendered without credentials.");
            }

            if ($returnCode === 0 && file_exists($pageFullPath) && filesize($pageFullPath) > 0) {
                $imageInfo = @getimagesize($pageFullPath);
                $width = $imageInfo ? $imageInfo[0] : 1200;
                $height = $imageInfo ? $imageInfo[1] : 1800;

                // Generate thumbnail from the real extracted PNG
                $this->createThumbnailFromImage($pageFullPath, $thumbFullPath);

                return [
                    'width' => $width,
                    'height' => $height,
                    'dpi' => $dpi,
                    'engine' => 'ghostscript',
                ];
            }

            $tail = implode(' ', array_slice($output, -4));
            Log::warning("Ghostscript returned exit code {$returnCode} on page {$pageNumber}: {$tail}");

            if (!$allowSyntheticFallback) {
                throw new \RuntimeException("PDF rendering failed on page {$pageNumber}: Ghostscript exit code {$returnCode}. {$tail}");
            }
        } catch (\Throwable $e) {
            if (!$allowSyntheticFallback) {
                throw $e;
            }
            Log::warning("Ghostscript render failed for page {$pageNumber}: " . $e->getMessage());
        }

        return null;
    }

    /**
     * Create scaled thumbnail (300 x 450) from an existing PNG image using GD.
     */
    protected function createThumbnailFromImage(string $sourcePath, string $thumbPath): void
    {
        if (!file_exists($sourcePath)) return;

        $source = @imagecreatefrompng($sourcePath);
        if (!$source) return;

        $srcW = imagesx($source);
        $srcH = imagesy($source);

        $thumbW = 300;
        $thumbH = (int)round(($srcH / $srcW) * $thumbW);

        $thumb = imagecreatetruecolor($thumbW, $thumbH);
        imagecopyresampled($thumb, $source, 0, 0, 0, 0, $thumbW, $thumbH, $srcW, $srcH);

        imagepng($thumb, $thumbPath);

        imagedestroy($source);
        imagedestroy($thumb);
    }

    /**
     * Discover available Ghostscript binary on the host system.
     */
    protected function findGhostscriptBinary(): ?string
    {
        if ($this->ghostscriptBinary !== null) {
            return $this->ghostscriptBinary ?: null;
        }

        // 1. Check custom environment variable
        $customPath = env('GHOSTSCRIPT_PATH');
        if ($customPath && file_exists($customPath)) {
            $this->ghostscriptBinary = $customPath;
            return $this->ghostscriptBinary;
        }

        // 2. Check standard Windows installations
        $windowsPaths = [
            'C:\\Program Files\\gs\\gs10.06.0\\bin\\gswin64c.exe',
            'C:\\Program Files\\gs\\gs10.05.0\\bin\\gswin64c.exe',
            'C:\\Program Files\\gs\\gs10.04.0\\bin\\gswin64c.exe',
            'C:\\Program Files\\gs\\gs10.03.0\\bin\\gswin64c.exe',
            'C:\\Program Files (x86)\\gs\\gs10.06.0\\bin\\gswin32c.exe',
        ];

        foreach ($windowsPaths as $path) {
            if (file_exists($path)) {
                $this->ghostscriptBinary = $path;
                return $this->ghostscriptBinary;
            }
        }

        // Check Windows wildcard match in C:\Program Files\gs\
        $gsDirs = @glob('C:\\Program Files\\gs\\*\\bin\\gswin64c.exe');
        if (!empty($gsDirs) && file_exists($gsDirs[0])) {
            $this->ghostscriptBinary = $gsDirs[0];
            return $this->ghostscriptBinary;
        }

        // 3. Check system PATH for gs / gswin64c
        $executables = ['gswin64c', 'gs', 'gswin32c'];
        foreach ($executables as $bin) {
            $checkCmd = (PHP_OS_FAMILY === 'Windows') ? "where.exe {$bin} 2>NUL" : "which {$bin} 2>/dev/null";
            $output = [];
            @exec($checkCmd, $output, $code);
            if ($code === 0 && !empty($output[0])) {
                $this->ghostscriptBinary = trim($output[0]);
                return $this->ghostscriptBinary;
            }
        }

        $this->ghostscriptBinary = ''; // Marked as not found
        return null;
    }

    protected function extractPageCount(string $filePath, bool $allowSyntheticFallback = false): int
    {
        if (!file_exists($filePath)) {
            if ($allowSyntheticFallback) return 4;
            throw new \RuntimeException("PDF file does not exist: {$filePath}");
        }

        $content = @file_get_contents($filePath);
        if (!$content) {
            if ($allowSyntheticFallback) return 4;
            throw new \RuntimeException("Unable to read PDF file contents: {$filePath}");
        }

        if (strncmp($content, '%PDF-', 5) !== 0) {
            Log::warning("File [{$filePath}] does not contain standard %PDF header magic bytes.");
            if (!$allowSyntheticFallback) {
                throw new \RuntimeException("Corrupted PDF: Missing standard %PDF header magic bytes.");
            }
        }

        if (strpos($content, '/Encrypt') !== false) {
            Log::warning("PDF [{$filePath}] contains /Encrypt dictionary (password-protected or encrypted).");
            if (!$allowSyntheticFallback) {
                throw new \RuntimeException("Password Required: PDF is encrypted or password-protected and cannot be processed without credentials.");
            }
        }

        // Search for /Count <num> in PDF catalog
        if (preg_match('/\/Count\s+(\d+)/', $content, $matches)) {
            $count = (int)$matches[1];
            if ($count > 0 && $count <= 100) {
                return $count;
            }
        }

        // Count /Type /Page occurrences
        $pageOccurrences = preg_match_all('/\/Type\s*\/Page[^s]/', $content);
        if ($pageOccurrences > 0 && $pageOccurrences <= 100) {
            return $pageOccurrences;
        }

        if (!$allowSyntheticFallback) {
            throw new \RuntimeException("Corrupted PDF: Unable to determine readable page count from PDF structure.");
        }

        return 4; // demo fallback
    }

    protected function renderNewspaperPageImage(
        Edition $edition,
        int $pageNumber,
        int $totalPages,
        string $pageFullPath,
        string $thumbFullPath
    ): void {
        $width = 1200;
        $height = 1800;

        $im = imagecreatetruecolor($width, $height);

        // Colors
        $white = imagecolorallocate($im, 255, 255, 255);
        $paperBg = imagecolorallocate($im, 250, 250, 248);
        $navy = imagecolorallocate($im, 15, 23, 42);
        $blue = imagecolorallocate($im, 37, 99, 235);
        $slate = imagecolorallocate($im, 100, 116, 139);
        $lightGray = imagecolorallocate($im, 226, 232, 240);
        $ruleLine = imagecolorallocate($im, 203, 213, 225);

        // Fill background
        imagefilledrectangle($im, 0, 0, $width, $height, $paperBg);

        // Border
        imagerectangle($im, 20, 20, $width - 20, $height - 20, $ruleLine);

        // Header / Masthead Area
        imagefilledrectangle($im, 40, 40, $width - 40, 140, $white);
        imagerectangle($im, 40, 40, $width - 40, 140, $ruleLine);

        // Newspaper Title text
        $title = $edition->edition_name ?? 'DOGY EPAPER';
        imagestring($im, 5, 60, 60, strtoupper($title), $navy);
        
        // Edition Info line
        $dateStr = Carbon::parse($edition->publish_date)->format('l, d F Y');
        $infoLine = "{$dateStr}  |  Edition: {$edition->edition_code}  |  Page {$pageNumber} of {$totalPages}";
        imagestring($im, 4, 60, 100, $infoLine, $slate);

        // Horizontal Rule
        imageline($im, 40, 160, $width - 40, 160, $blue);

        // Column layout simulation
        $colWidth = 265;
        $gap = 20;
        for ($col = 0; $col < 4; $col++) {
            $x1 = 40 + ($col * ($colWidth + $gap));
            $x2 = $x1 + $colWidth;

            // Column Header Box
            imagefilledrectangle($im, $x1, 180, $x2, 220, $lightGray);
            $colTitle = "SECTION " . chr(65 + $col) . " - " . ($col === 0 ? "LEAD STORY" : "REGIONAL DESK");
            imagestring($im, 3, $x1 + 10, 195, $colTitle, $navy);

            // Column text lines simulation
            for ($lineY = 240; $lineY < $height - 100; $lineY += 18) {
                $lineLength = ($col === 0 && $lineY < 500) ? $colWidth - 10 : rand($colWidth - 60, $colWidth - 10);
                imageline($im, $x1, $lineY, $x1 + $lineLength, $lineY, $slate);
            }
        }

        // Center Big Headline Box for Page 1
        if ($pageNumber === 1) {
            imagefilledrectangle($im, 50, 250, 600, 450, $white);
            imagerectangle($im, 50, 250, 600, 450, $blue);
            imagestring($im, 5, 70, 280, "HISTORIC DEVELOPMENT IN REGIONAL PUBLISHING", $navy);
            imagestring($im, 4, 70, 320, "Digital transformation accelerates newspaper reader outreach.", $slate);
            imagestring($im, 3, 70, 360, "Cloud infrastructure enables seamless multi-district digital printing.", $slate);
        }

        // Footer Bar
        imagefilledrectangle($im, 40, $height - 70, $width - 40, $height - 40, $white);
        imagerectangle($im, 40, $height - 70, $width - 40, $height - 40, $ruleLine);
        $footerText = "© " . date('Y') . " {$title}  •  Printed & Published digitally via DOGY ePAPER SaaS  •  Page {$pageNumber}";
        imagestring($im, 3, 60, $height - 60, $footerText, $slate);

        // Save High-Res Page Image
        @mkdir(dirname($pageFullPath), 0777, true);
        imagepng($im, $pageFullPath);

        // Generate Thumbnail (300 x 450)
        $thumbWidth = 300;
        $thumbHeight = 450;
        $thumbIm = imagecreatetruecolor($thumbWidth, $thumbHeight);
        imagecopyresampled($thumbIm, $im, 0, 0, 0, 0, $thumbWidth, $thumbHeight, $width, $height);

        @mkdir(dirname($thumbFullPath), 0777, true);
        imagepng($thumbIm, $thumbFullPath);

        // Free memory
        imagedestroy($im);
        imagedestroy($thumbIm);
    }

    protected function getDefaultPageTitle(int $pageNumber, int $totalPages): string
    {
        if ($pageNumber === 1) return 'Front Page / Main Headlines';
        if ($pageNumber === 2) return 'State & Regional Affairs';
        if ($pageNumber === 3) return 'Editorial & Opinions';
        if ($pageNumber === 4) return 'Business & Markets';
        if ($pageNumber === $totalPages) return 'Sports & Classifieds';
        return "Page {$pageNumber}";
    }
}
