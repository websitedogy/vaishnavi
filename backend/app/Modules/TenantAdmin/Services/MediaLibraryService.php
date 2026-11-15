<?php

namespace App\Modules\TenantAdmin\Services;

use App\Modules\TenantAdmin\Models\MediaFile;
use App\Modules\SuperAdmin\Models\Tenant;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Str;

class MediaLibraryService
{
    public function listMedia(int $tenantId, array $filters = []): array
    {
        $query = MediaFile::where('tenant_id', $tenantId)->with('user:id,name');

        if (!empty($filters['type'])) {
            $query->where('type', $filters['type']);
        }

        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where('name', 'like', "%{$search}%");
        }

        $perPage = (int)($filters['per_page'] ?? 24);
        $paginator = $query->orderBy('created_at', 'desc')->paginate($perPage);

        return [
            'data' => $paginator->items(),
            'pagination' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
            ],
        ];
    }

    public function uploadMedia(int $tenantId, UploadedFile $file, ?int $userId = null): MediaFile
    {
        $tenant = Tenant::findOrFail($tenantId);

        $size = $file->getSize();
        $mime = $file->getMimeType();
        $name = $file->getClientOriginalName();
        $type = str_starts_with($mime, 'image/') ? 'image' : (str_contains($mime, 'pdf') ? 'pdf' : 'document');

        // Check storage quota
        $currentUsedMb = (float)($tenant->storage_used_mb ?? 0);
        $quotaMb = (int)($tenant->storage_limit_mb ?? 51200);
        $fileSizeMb = round($size / (1024 * 1024), 2);

        if (($currentUsedMb + $fileSizeMb) > $quotaMb) {
            throw ValidationException::withMessages([
                'file' => ['Uploading this media asset would exceed your tenant storage quota.'],
            ]);
        }

        $safeName = time() . '_' . Str::slug(pathinfo($name, PATHINFO_FILENAME)) . '.' . $file->getClientOriginalExtension();
        $storedPath = $file->storeAs("public/tenants/{$tenant->slug}/media", $safeName);
        $publicUrl = Storage::url($storedPath);

        $media = MediaFile::create([
            'tenant_id' => $tenantId,
            'user_id' => $userId,
            'name' => $name,
            'path' => $publicUrl,
            'disk' => 'public',
            'type' => $type,
            'mime_type' => $mime,
            'size' => $size,
            'thumbnail_path' => $publicUrl,
            'alt_text' => pathinfo($name, PATHINFO_FILENAME),
        ]);

        $tenant->storage_used_mb = $currentUsedMb + $fileSizeMb;
        $tenant->save();

        TenantAuditHelper::log($tenantId, $userId, 'media_uploaded', 'media', "Uploaded media file {$name} ({$fileSizeMb} MB).");

        return $media;
    }

    public function updateMedia(int $tenantId, int $mediaId, array $data, ?int $userId = null): MediaFile
    {
        $media = MediaFile::where('tenant_id', $tenantId)->findOrFail($mediaId);

        $media->update(array_intersect_key($data, array_flip(['name', 'alt_text'])));

        return $media;
    }

    public function deleteMedia(int $tenantId, int $mediaId, ?int $userId = null): bool
    {
        $media = MediaFile::where('tenant_id', $tenantId)->findOrFail($mediaId);

        $name = $media->name;
        $media->delete();

        TenantAuditHelper::log($tenantId, $userId, 'media_deleted', 'media', "Deleted media file {$name}.");

        return true;
    }
}
