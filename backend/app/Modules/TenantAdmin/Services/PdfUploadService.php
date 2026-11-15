<?php

namespace App\Modules\TenantAdmin\Services;

use App\Modules\TenantAdmin\Models\Edition;
use App\Modules\TenantAdmin\Models\PdfUpload;
use App\Modules\SuperAdmin\Models\Tenant;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Str;

class PdfUploadService
{
    protected LocalPdfProcessingService $processor;

    public function __construct(LocalPdfProcessingService $processor)
    {
        $this->processor = $processor;
    }

    public function uploadForEdition(int $tenantId, int $editionId, UploadedFile $file, ?int $userId = null): array
    {
        $tenant = Tenant::findOrFail($tenantId);
        $edition = Edition::where('tenant_id', $tenantId)->findOrFail($editionId);

        // 1. Validate File
        $mime = $file->getMimeType();
        $ext = strtolower($file->getClientOriginalExtension());
        if ($ext !== 'pdf' || !str_contains($mime, 'pdf')) {
            throw ValidationException::withMessages([
                'pdf_file' => ['The uploaded file must be a valid PDF document.'],
            ]);
        }

        $fileSize = $file->getSize();
        $fileSizeMb = round($fileSize / (1024 * 1024), 2);

        // Check file size limit (e.g. 250MB limit)
        $maxMb = 250;
        if ($fileSizeMb > $maxMb) {
            throw ValidationException::withMessages([
                'pdf_file' => ["File exceeds maximum allowed upload size of {$maxMb} MB."],
            ]);
        }

        // Validate non-empty file size
        if ($fileSize <= 0) {
            throw ValidationException::withMessages([
                'pdf_file' => ['The uploaded PDF file is empty (0 bytes). Upload a valid newspaper issue.'],
            ]);
        }

        // Check tenant storage quota
        $currentUsedMb = (float)($tenant->storage_used_mb ?? 0);
        $quotaMb = (int)($tenant->storage_limit_mb ?? 51200);
        if (($currentUsedMb + $fileSizeMb) > $quotaMb) {
            throw ValidationException::withMessages([
                'pdf_file' => ["Uploading this {$fileSizeMb} MB PDF would exceed your subscription storage quota ({$quotaMb} MB)."],
            ]);
        }

        // 2. Compute SHA256 from actual file bytes
        $checksum = hash_file('sha256', $file->getRealPath());
        $emptySha256 = 'e3b0c44298fc1c149afbf4c8996fb92427ae41e4649b934ca495991b7852b855';

        if (!$checksum || $checksum === $emptySha256) {
            throw ValidationException::withMessages([
                'pdf_file' => ['The uploaded file produced an empty SHA-256 payload checksum. File must contain valid binary data.'],
            ]);
        }

        // 3. Store Original PDF in Tenant-Scoped Storage
        $originalFilename = $file->getClientOriginalName();
        $storedFilename = $edition->edition_code . '_' . time() . '.pdf';
        $relativeDir = "private/tenants/{$tenant->slug}/editions/{$edition->edition_code}/original";
        
        $storedPath = $file->storeAs($relativeDir, $storedFilename);

        // 4. Create Upload Record
        $upload = PdfUpload::create([
            'tenant_id' => $tenantId,
            'edition_id' => $editionId,
            'uploaded_by_user_id' => $userId,
            'original_filename' => $originalFilename,
            'stored_filename' => $storedFilename,
            'file_path' => $storedPath,
            'file_size' => $fileSize,
            'mime_type' => $mime,
            'checksum_sha256' => $checksum,
            'status' => 'uploaded',
        ]);

        // Update Edition
        $edition->original_pdf_path = $storedPath;
        $edition->file_size_mb = $fileSizeMb;
        $edition->checksum_sha256 = $checksum;
        $edition->status = 'processing';
        $edition->save();

        // Update Tenant Storage Used
        $tenant->storage_used_mb = $currentUsedMb + $fileSizeMb;
        $tenant->save();

        TenantAuditHelper::log($tenantId, $userId, 'pdf_uploaded', 'editions', "Uploaded PDF {$originalFilename} ({$fileSizeMb} MB) for edition {$edition->edition_code}.");

        // 5. Trigger Local Processing Pipeline
        $processingResult = $this->processor->processEditionPdf($edition, $upload);

        return [
            'upload' => $upload->fresh(),
            'edition' => $edition->fresh()->load('pages'),
            'processing' => $processingResult,
        ];
    }
}
