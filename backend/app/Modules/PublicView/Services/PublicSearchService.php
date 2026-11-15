<?php

namespace App\Modules\PublicView\Services;

use App\Modules\SuperAdmin\Models\Tenant;
use App\Modules\TenantAdmin\Models\Edition;
use Illuminate\Pagination\LengthAwarePaginator;

class PublicSearchService
{
    /**
     * Search published editions using verified edition metadata.
     * Note: Full-text OCR search is not enabled in this pipeline; search is strictly scoped to edition metadata.
     */
    public function search(Tenant $tenant, array $params, int $perPage = 10): LengthAwarePaginator
    {
        $q = trim($params['q'] ?? '');

        $query = Edition::where('tenant_id', $tenant->id)
            ->where('status', 'published');

        if (!empty($q)) {
            // Sanitize query string
            $cleanQuery = substr(preg_replace('/[^\p{L}\p{N}\s\-_.]/u', '', $q), 0, 80);

            $query->where(function ($sub) use ($cleanQuery) {
                $sub->where('edition_title', 'like', "%{$cleanQuery}%")
                    ->orWhere('edition_name', 'like', "%{$cleanQuery}%")
                    ->orWhere('edition_code', 'like', "%{$cleanQuery}%")
                    ->orWhere('category', 'like', "%{$cleanQuery}%")
                    ->orWhere('description', 'like', "%{$cleanQuery}%");
            });
        }

        if (!empty($params['category'])) {
            $query->where('category', $params['category']);
        }

        if (!empty($params['from_date'])) {
            $query->whereDate('publish_date', '>=', $params['from_date']);
        }

        if (!empty($params['to_date'])) {
            $query->whereDate('publish_date', '<=', $params['to_date']);
        }

        if (!empty($params['language'])) {
            $query->where('edition_language', $params['language']);
        }

        return $query->orderBy('publish_date', 'desc')
            ->paginate($perPage);
    }
}
