<?php

namespace App\Modules\TenantAdmin\Services;

use App\Modules\TenantAdmin\Models\Edition;
use App\Modules\TenantAdmin\Models\EditionPage;
use App\Modules\SuperAdmin\Models\Tenant;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class PageManagementService
{
    public function getPages(int $tenantId, int $editionId)
    {
        Edition::where('tenant_id', $tenantId)->findOrFail($editionId);

        return EditionPage::where('tenant_id', $tenantId)
            ->where('edition_id', $editionId)
            ->orderBy('sort_order', 'asc')
            ->get();
    }

    public function reorderPages(int $tenantId, int $editionId, array $pageIdsOrder, ?int $userId = null)
    {
        $edition = Edition::where('tenant_id', $tenantId)->findOrFail($editionId);

        return \Illuminate\Support\Facades\DB::transaction(function () use ($tenantId, $editionId, $edition, $pageIdsOrder, $userId) {
            // Two-pass update inside atomic transaction to prevent unique constraint collision on ['edition_id', 'page_number']
            foreach ($pageIdsOrder as $index => $pageId) {
                EditionPage::where('tenant_id', $tenantId)
                    ->where('edition_id', $editionId)
                    ->where('id', $pageId)
                    ->update([
                        'sort_order' => $index + 1,
                        'page_number' => -($index + 1),
                    ]);
            }

            foreach ($pageIdsOrder as $index => $pageId) {
                EditionPage::where('tenant_id', $tenantId)
                    ->where('edition_id', $editionId)
                    ->where('id', $pageId)
                    ->update([
                        'page_number' => $index + 1,
                    ]);
            }

            TenantAuditHelper::log($tenantId, $userId, 'pages_reordered', 'pages', "Reordered pages for edition {$edition->edition_code}.");

            return $this->getPages($tenantId, $editionId);
        });
    }

    public function rotatePage(int $tenantId, int $editionId, int $pageId, int $angle = 90, ?int $userId = null): EditionPage
    {
        $edition = Edition::where('tenant_id', $tenantId)->findOrFail($editionId);
        $page = EditionPage::where('tenant_id', $tenantId)
            ->where('edition_id', $editionId)
            ->findOrFail($pageId);

        $newRotation = ($page->rotation + $angle) % 360;
        $page->rotation = $newRotation;
        $page->save();

        TenantAuditHelper::log($tenantId, $userId, 'page_rotated', 'pages', "Rotated page {$page->page_number} of edition {$edition->edition_code} to {$newRotation}°.");

        return $page;
    }

    public function toggleVisibility(int $tenantId, int $editionId, int $pageId, bool $isVisible, ?int $userId = null): EditionPage
    {
        $page = EditionPage::where('tenant_id', $tenantId)
            ->where('edition_id', $editionId)
            ->findOrFail($pageId);

        $page->is_visible = $isVisible;
        $page->save();

        TenantAuditHelper::log($tenantId, $userId, 'page_visibility', 'pages', "Updated visibility for page {$page->page_number} to " . ($isVisible ? 'visible' : 'hidden') . ".");

        return $page;
    }

    public function replacePage(int $tenantId, int $editionId, int $pageId, UploadedFile $file, ?int $userId = null): EditionPage
    {
        $edition = Edition::where('tenant_id', $tenantId)->findOrFail($editionId);
        $page = EditionPage::where('tenant_id', $tenantId)
            ->where('edition_id', $editionId)
            ->findOrFail($pageId);

        $tenant = Tenant::findOrFail($tenantId);
        $filename = "page_{$page->page_number}_v" . time() . '.' . $file->getClientOriginalExtension();
        $storedPath = $file->storeAs("public/tenants/{$tenant->slug}/editions/{$edition->edition_code}/pages", $filename);
        $publicUrl = Storage::url($storedPath);

        $page->image_path = $publicUrl;
        $page->save();

        TenantAuditHelper::log($tenantId, $userId, 'page_replaced', 'pages', "Replaced artwork for page {$page->page_number} of edition {$edition->edition_code}.");

        return $page;
    }

    public function deletePage(int $tenantId, int $editionId, int $pageId, ?int $userId = null): bool
    {
        $edition = Edition::where('tenant_id', $tenantId)->findOrFail($editionId);
        $page = EditionPage::where('tenant_id', $tenantId)
            ->where('edition_id', $editionId)
            ->findOrFail($pageId);

        if ($edition->pages()->count() <= 1) {
            throw ValidationException::withMessages([
                'page' => ['An edition must have at least one page. You cannot delete the final page.'],
            ]);
        }

        $pageNumber = $page->page_number;
        $page->delete();

        // Renumber remaining pages
        $remaining = EditionPage::where('edition_id', $editionId)->orderBy('sort_order', 'asc')->get();
        foreach ($remaining as $idx => $p) {
            $p->page_number = $idx + 1;
            $p->sort_order = $idx + 1;
            $p->save();
        }

        $edition->total_pages = count($remaining);
        $edition->save();

        TenantAuditHelper::log($tenantId, $userId, 'page_deleted', 'pages', "Deleted page {$pageNumber} from edition {$edition->edition_code}.");

        return true;
    }
}
