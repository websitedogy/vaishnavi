<?php

namespace App\Modules\PublicView\Services;

use App\Modules\SuperAdmin\Models\Tenant;
use App\Modules\TenantAdmin\Models\Edition;
use Carbon\Carbon;
use Illuminate\Support\Facades\Storage;

class PublicManifestService
{
    /**
     * Get validated and sanitized public manifest for an edition.
     */
    public function getManifest(Edition $edition, Tenant $tenant): array
    {
        $manifest = $edition->public_manifest;

        if (empty($manifest)) {
            $manifest = $this->buildFallbackManifest($edition, $tenant);
        }

        // Sanitize manifest to guarantee zero private paths leak out
        return $this->sanitizeManifest($manifest, $edition, $tenant);
    }

    /**
     * Build fallback manifest if not cached in public_manifest JSON.
     */
    protected function buildFallbackManifest(Edition $edition, Tenant $tenant): array
    {
        $pages = $edition->pages()
            ->where('is_visible', true)
            ->orderBy('sort_order', 'asc')
            ->get();

        $pagesData = [];
        foreach ($pages as $p) {
            $pagesData[] = [
                'page_number' => $p->page_number,
                'page_title' => $p->page_title ?? "Page {$p->page_number}",
                'image_url' => $this->sanitizePublicUrl($p->image_path),
                'thumbnail_url' => $this->sanitizePublicUrl($p->thumbnail_path),
                'width' => (int) $p->width,
                'height' => (int) $p->height,
                'rotation' => (int) $p->rotation,
                'is_visible' => (bool) $p->is_visible,
            ];
        }

        return [
            'schema_version' => '2.0',
            'edition' => [
                'id' => $edition->id,
                'edition_code' => $edition->edition_code,
                'edition_title' => $edition->edition_title ?? $edition->edition_name,
                'edition_name' => $edition->edition_name,
                'edition_language' => $edition->edition_language ?? 'te',
                'category' => $edition->category ?? 'Main Edition',
                'description' => $edition->description,
                'publish_date' => $edition->publish_date instanceof Carbon ? $edition->publish_date->format('Y-m-d') : (string) $edition->publish_date,
                'total_pages' => count($pagesData),
                'thumbnail_url' => $this->sanitizePublicUrl($edition->thumbnail_path),
                'checksum_sha256' => $edition->checksum_sha256,
                'published_at' => $edition->published_at ? $edition->published_at->toIso8601String() : null,
            ],
            'publication' => [
                'name' => $tenant->name,
                'slug' => $tenant->slug,
                'domain' => $tenant->domain,
                'contact_email' => $tenant->email,
                'logo_url' => $this->sanitizePublicUrl($tenant->logo),
            ],
            'pages' => $pagesData,
            'compiled_at' => Carbon::now()->toIso8601String(),
        ];
    }

    /**
     * Strict security sanitization of manifest JSON.
     */
    protected function sanitizeManifest(array $manifest, Edition $edition, Tenant $tenant): array
    {
        // Enforce schema version
        $manifest['schema_version'] = $manifest['schema_version'] ?? '2.0';

        // Never leak private storage paths or original PDF paths
        if (isset($manifest['edition'])) {
            unset($manifest['edition']['original_pdf_path']);
            unset($manifest['edition']['private_storage_path']);
            unset($manifest['edition']['created_by_user_id']);
            unset($manifest['edition']['updated_by_user_id']);
        }

        // Sanitize pages
        if (isset($manifest['pages']) && is_array($manifest['pages'])) {
            foreach ($manifest['pages'] as &$page) {
                if (isset($page['image_url'])) {
                    $page['image_url'] = $this->sanitizePublicUrl($page['image_url']);
                }
                if (isset($page['thumbnail_url'])) {
                    $page['thumbnail_url'] = $this->sanitizePublicUrl($page['thumbnail_url']);
                }
                unset($page['private_path']);
                unset($page['metadata']['raw_output']);
                unset($page['metadata']['pdf_source']);
            }
        }

        return $manifest;
    }

    /**
     * Compute HTTP ETag for the edition manifest.
     */
    public function computeETag(Edition $edition): string
    {
        $publishedTs = $edition->published_at ? $edition->published_at->timestamp : '';
        $checksum = $edition->checksum_sha256 ?? '';
        return '"' . md5("{$edition->edition_code}_{$checksum}_{$publishedTs}") . '"';
    }

    /**
     * Sanitize public asset URLs.
     */
    public function sanitizePublicUrl(?string $path): ?string
    {
        if (empty($path)) {
            return null;
        }

        // Prohibit private paths
        if (str_starts_with($path, 'private/') || str_starts_with($path, '/private/')) {
            return null;
        }

        // If already full URL or starts with /storage
        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://') || str_starts_with($path, '/storage/')) {
            return $path;
        }

        return '/storage/' . ltrim($path, '/');
    }
}
