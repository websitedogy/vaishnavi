<?php

namespace App\Modules\TenantAdmin\Services;

use App\Modules\TenantAdmin\Models\Edition;
use App\Modules\TenantAdmin\Models\EditionPage;
use App\Modules\SuperAdmin\Models\Tenant;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Str;
use Carbon\Carbon;

class EditionService
{
    public function listEditions(int $tenantId, array $filters = []): array
    {
        $query = Edition::where('tenant_id', $tenantId)
            ->with(['author:id,name', 'schedule'])
            ->withCount('pages');

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (!empty($filters['category'])) {
            $query->where('category', $filters['category']);
        }

        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('edition_name', 'like', "%{$search}%")
                  ->orWhere('edition_code', 'like', "%{$search}%")
                  ->orWhere('edition_title', 'like', "%{$search}%");
            });
        }

        if (!empty($filters['date'])) {
            $query->where('publish_date', $filters['date']);
        }

        $perPage = (int)($filters['per_page'] ?? 15);
        $paginator = $query->orderBy('publish_date', 'desc')->paginate($perPage);

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

    public function createEdition(int $tenantId, array $data, ?int $userId = null): Edition
    {
        $code = $data['edition_code'] ?? ('ED-' . date('Ymd') . '-' . strtoupper(Str::random(4)));

        // Check unique code within tenant
        $exists = Edition::where('tenant_id', $tenantId)->where('edition_code', $code)->exists();
        if ($exists) {
            $code .= '-' . rand(10, 99);
        }

        $edition = Edition::create([
            'tenant_id' => $tenantId,
            'edition_code' => $code,
            'edition_name' => $data['edition_name'],
            'edition_title' => $data['edition_title'] ?? $data['edition_name'],
            'edition_language' => $data['edition_language'] ?? 'te',
            'category' => $data['category'] ?? 'Main Edition',
            'description' => $data['description'] ?? null,
            'publish_date' => $data['publish_date'] ?? date('Y-m-d'),
            'total_pages' => 0,
            'file_size_mb' => 0,
            'status' => 'draft',
            'created_by_user_id' => $userId,
        ]);

        TenantAuditHelper::log($tenantId, $userId, 'edition_created', 'editions', "Created draft edition {$edition->edition_code}.");

        return $edition;
    }

    public function getEdition(int $tenantId, int $editionId): Edition
    {
        return Edition::where('tenant_id', $tenantId)
            ->where('id', $editionId)
            ->with(['pages', 'schedule', 'uploads', 'author:id,name'])
            ->firstOrFail();
    }

    public function updateEdition(int $tenantId, int $editionId, array $data, ?int $userId = null): Edition
    {
        $edition = $this->getEdition($tenantId, $editionId);

        $allowedFields = [
            'edition_name',
            'edition_title',
            'edition_language',
            'category',
            'description',
            'publish_date',
        ];

        $payload = array_intersect_key($data, array_flip($allowedFields));
        $edition->update($payload);

        TenantAuditHelper::log($tenantId, $userId, 'edition_updated', 'editions', "Updated metadata for edition {$edition->edition_code}.");

        return $edition;
    }

    public function deleteEdition(int $tenantId, int $editionId, ?int $userId = null): bool
    {
        return \Illuminate\Support\Facades\DB::transaction(function () use ($tenantId, $editionId, $userId) {
            $edition = Edition::where('tenant_id', $tenantId)
                ->where('id', $editionId)
                ->firstOrFail();

            if ($edition->status === 'published') {
                throw ValidationException::withMessages([
                    'status' => ['Published editions cannot be deleted directly. Please unpublish the edition first.'],
                ]);
            }

            $tenant = Tenant::find($tenantId);
            $code = $edition->edition_code;
            $fileSizeMb = (float)($edition->file_size_mb ?? 0);
            $originalPdfPath = $edition->original_pdf_path;
            $publicDir = $tenant ? "tenants/{$tenant->slug}/editions/{$code}" : null;

            // 1. Credit storage quota back to tenant exactly once inside transaction
            if ($tenant && $fileSizeMb > 0) {
                $currentUsed = (float)($tenant->storage_used_mb ?? 0);
                $tenant->storage_used_mb = max(0, round($currentUsed - $fileSizeMb, 2));
                $tenant->save();

                // Prevent any re-crediting by resetting edition file_size_mb
                $edition->file_size_mb = 0;
                $edition->save();
            }

            // 2. Cascade delete child database records
            EditionPage::where('edition_id', $edition->id)->delete();
            \App\Modules\TenantAdmin\Models\PdfUpload::where('edition_id', $edition->id)->delete();
            \App\Modules\TenantAdmin\Models\PublishingSchedule::where('edition_id', $edition->id)->delete();
            $edition->delete();

            // 3. Register physical storage purges strictly after transaction commit
            \Illuminate\Support\Facades\DB::afterCommit(function () use ($originalPdfPath, $publicDir) {
                if ($originalPdfPath && \Illuminate\Support\Facades\Storage::disk('local')->exists($originalPdfPath)) {
                    \Illuminate\Support\Facades\Storage::disk('local')->delete($originalPdfPath);
                }
                if ($publicDir) {
                    \Illuminate\Support\Facades\Storage::disk('public')->deleteDirectory($publicDir);
                }
            });

            TenantAuditHelper::log($tenantId, $userId, 'edition_deleted', 'editions', "Deleted edition {$code} and credited {$fileSizeMb} MB storage.");

            return true;
        });
    }

    public function duplicateEdition(int $tenantId, int $editionId, ?int $userId = null): Edition
    {
        $source = $this->getEdition($tenantId, $editionId);

        $newCode = $source->edition_code . '-COPY-' . strtoupper(Str::random(3));
        $duplicate = Edition::create([
            'tenant_id' => $tenantId,
            'edition_code' => $newCode,
            'edition_name' => $source->edition_name . ' (Copy)',
            'edition_title' => $source->edition_title . ' (Copy)',
            'edition_language' => $source->edition_language,
            'category' => $source->category,
            'description' => $source->description,
            'publish_date' => date('Y-m-d'),
            'total_pages' => $source->total_pages,
            'file_size_mb' => $source->file_size_mb,
            'thumbnail_path' => $source->thumbnail_path,
            'original_pdf_path' => $source->original_pdf_path,
            'status' => 'draft',
            'created_by_user_id' => $userId,
        ]);

        // Duplicate pages
        foreach ($source->pages as $page) {
            EditionPage::create([
                'edition_id' => $duplicate->id,
                'tenant_id' => $tenantId,
                'page_number' => $page->page_number,
                'page_title' => $page->page_title,
                'image_path' => $page->image_path,
                'thumbnail_path' => $page->thumbnail_path,
                'width' => $page->width,
                'height' => $page->height,
                'rotation' => $page->rotation,
                'is_visible' => $page->is_visible,
                'sort_order' => $page->sort_order,
                'metadata' => $page->metadata,
            ]);
        }

        TenantAuditHelper::log($tenantId, $userId, 'edition_duplicated', 'editions', "Duplicated edition {$source->edition_code} to {$duplicate->edition_code}.");

        return $duplicate->load('pages');
    }

    public function archiveEdition(int $tenantId, int $editionId, ?int $userId = null): Edition
    {
        $edition = $this->getEdition($tenantId, $editionId);
        $edition->status = 'archived';
        $edition->archived_at = Carbon::now();
        $edition->save();

        TenantAuditHelper::log($tenantId, $userId, 'edition_archived', 'editions', "Archived edition {$edition->edition_code}.");

        return $edition;
    }
}
