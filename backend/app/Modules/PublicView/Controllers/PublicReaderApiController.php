<?php

namespace App\Modules\PublicView\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\TenantAdmin\Models\Edition;
use App\Modules\SuperAdmin\Models\Tenant;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class PublicReaderApiController extends Controller
{
    /**
     * Get the latest published edition for a publication slug or domain.
     */
    public function latest(Request $request, string $tenantSlug): JsonResponse
    {
        $tenant = Tenant::where('slug', $tenantSlug)->firstOrFail();

        if ($tenant->status !== 'active') {
            return response()->json([
                'success' => false,
                'message' => 'Publication is currently suspended or inactive.',
            ], 403);
        }

        $edition = Edition::where('tenant_id', $tenant->id)
            ->where('status', 'published')
            ->orderBy('publish_date', 'desc')
            ->firstOrFail();

        $manifest = $edition->public_manifest ?: [
            'edition' => $edition,
            'pages' => $edition->pages()->where('is_visible', true)->orderBy('sort_order', 'asc')->get(),
        ];

        $etag = '"' . md5($edition->edition_code . '_' . ($edition->checksum_sha256 ?? '') . '_' . ($edition->published_at ? $edition->published_at->timestamp : '')) . '"';

        if ($request->header('If-None-Match') === $etag) {
            return response()->json(null, 304, [
                'ETag' => $etag,
                'Cache-Control' => 'public, max-age=60, s-maxage=300, must-revalidate',
            ]);
        }

        return response()->json([
            'success' => true,
            'data' => $manifest,
        ], 200, [
            'ETag' => $etag,
            'Cache-Control' => 'public, max-age=60, s-maxage=300, must-revalidate',
        ]);
    }

    /**
     * Get a specific published edition by date (YYYY-MM-DD) or edition code.
     */
    public function show(Request $request, string $tenantSlug, string $dateOrCode): JsonResponse
    {
        $tenant = Tenant::where('slug', $tenantSlug)->firstOrFail();

        if ($tenant->status !== 'active') {
            return response()->json([
                'success' => false,
                'message' => 'Publication is currently suspended or inactive.',
            ], 403);
        }

        $isDate = (bool) preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateOrCode);

        $edition = Edition::where('tenant_id', $tenant->id)
            ->where('status', 'published')
            ->where(function ($q) use ($dateOrCode, $isDate) {
                $q->where('edition_code', $dateOrCode);
                if ($isDate) {
                    $q->orWhere('publish_date', $dateOrCode);
                }
            })
            ->firstOrFail();

        $manifest = $edition->public_manifest ?: [
            'edition' => $edition,
            'pages' => $edition->pages()->where('is_visible', true)->orderBy('sort_order', 'asc')->get(),
        ];

        $etag = '"' . md5($edition->edition_code . '_' . ($edition->checksum_sha256 ?? '') . '_' . ($edition->published_at ? $edition->published_at->timestamp : '')) . '"';

        if ($request->header('If-None-Match') === $etag) {
            return response()->json(null, 304, [
                'ETag' => $etag,
                'Cache-Control' => 'public, max-age=60, s-maxage=300, must-revalidate',
            ]);
        }

        return response()->json([
            'success' => true,
            'data' => $manifest,
        ], 200, [
            'ETag' => $etag,
            'Cache-Control' => 'public, max-age=60, s-maxage=300, must-revalidate',
        ]);
    }

    /**
     * Get published editions archive list for calendar picker.
     */
    public function archive(Request $request, string $tenantSlug): JsonResponse
    {
        $tenant = Tenant::where('slug', $tenantSlug)->firstOrFail();

        if ($tenant->status !== 'active') {
            return response()->json([
                'success' => false,
                'message' => 'Publication is currently suspended or inactive.',
            ], 403);
        }

        $year = $request->query('year', date('Y'));
        $month = $request->query('month', date('m'));

        $editions = Edition::where('tenant_id', $tenant->id)
            ->where('status', 'published')
            ->whereYear('publish_date', $year)
            ->whereMonth('publish_date', $month)
            ->orderBy('publish_date', 'desc')
            ->get(['id', 'edition_code', 'edition_title', 'edition_name', 'publish_date', 'thumbnail_path', 'total_pages']);

        return response()->json([
            'success' => true,
            'data' => $editions,
        ], 200, [
            'Cache-Control' => 'public, max-age=300, s-maxage=600',
        ]);
    }
}
