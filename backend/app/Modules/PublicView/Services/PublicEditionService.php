<?php

namespace App\Modules\PublicView\Services;

use App\Modules\SuperAdmin\Models\Tenant;
use App\Modules\TenantAdmin\Models\Edition;
use Carbon\Carbon;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class PublicEditionService
{
    /**
     * Get the latest published edition for a tenant.
     */
    public function getLatest(Tenant $tenant): ?Edition
    {
        return Edition::where('tenant_id', $tenant->id)
            ->where('status', 'published')
            ->orderBy('publish_date', 'desc')
            ->first();
    }

    /**
     * Get a specific published edition by date (YYYY-MM-DD) or edition code.
     */
    public function getByDateOrCode(Tenant $tenant, string $dateOrCode): ?Edition
    {
        $isDate = (bool) preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateOrCode);

        return Edition::where('tenant_id', $tenant->id)
            ->where('status', 'published')
            ->where(function ($q) use ($dateOrCode, $isDate) {
                $q->where('edition_code', $dateOrCode);
                if ($isDate) {
                    $q->orWhere('publish_date', $dateOrCode);
                }
            })
            ->first();
    }

    /**
     * Get recent published editions (excluding drafts, scheduled, failed).
     */
    public function getRecentEditions(Tenant $tenant, int $limit = 6): Collection
    {
        return Edition::where('tenant_id', $tenant->id)
            ->where('status', 'published')
            ->orderBy('publish_date', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Paginate published editions with optional filtering by category or date.
     */
    public function paginate(Tenant $tenant, array $filters = [], int $perPage = 12): LengthAwarePaginator
    {
        $query = Edition::where('tenant_id', $tenant->id)
            ->where('status', 'published');

        if (!empty($filters['category'])) {
            $query->where('category', $filters['category']);
        }

        if (!empty($filters['from_date'])) {
            $query->whereDate('publish_date', '>=', $filters['from_date']);
        }

        if (!empty($filters['to_date'])) {
            $query->whereDate('publish_date', '<=', $filters['to_date']);
        }

        if (!empty($filters['language'])) {
            $query->where('edition_language', $filters['language']);
        }

        return $query->orderBy('publish_date', 'desc')
            ->paginate($perPage);
    }

    /**
     * Get monthly archive data including list of published dates and available editions.
     */
    public function getArchiveCalendar(Tenant $tenant, int $year, int $month): array
    {
        $editions = Edition::where('tenant_id', $tenant->id)
            ->where('status', 'published')
            ->whereYear('publish_date', $year)
            ->whereMonth('publish_date', $month)
            ->orderBy('publish_date', 'desc')
            ->get();

        $publishedDates = $editions->pluck('publish_date')
            ->map(function ($date) {
                return $date instanceof Carbon ? $date->toDateString() : (string) $date;
            })
            ->unique()
            ->values()
            ->all();

        // Available years for dropdown
        $availableYears = Edition::where('tenant_id', $tenant->id)
            ->where('status', 'published')
            ->selectRaw('DISTINCT strftime("%Y", publish_date) as year')
            ->pluck('year')
            ->filter()
            ->map(fn($y) => (int)$y)
            ->sortDesc()
            ->values()
            ->all();

        // Fallback for MySQL if strftime returned null
        if (empty($availableYears)) {
            $availableYears = Edition::where('tenant_id', $tenant->id)
                ->where('status', 'published')
                ->selectRaw('DISTINCT YEAR(publish_date) as year')
                ->pluck('year')
                ->filter()
                ->map(fn($y) => (int)$y)
                ->sortDesc()
                ->values()
                ->all();
        }

        if (empty($availableYears)) {
            $availableYears = [(int) date('Y')];
        }

        return [
            'year' => $year,
            'month' => $month,
            'total_editions' => $editions->count(),
            'published_dates' => $publishedDates,
            'available_years' => $availableYears,
            'editions' => $editions,
        ];
    }

    /**
     * Get distinct categories for published editions.
     */
    public function getCategories(Tenant $tenant): array
    {
        return Edition::where('tenant_id', $tenant->id)
            ->where('status', 'published')
            ->whereNotNull('category')
            ->select('category')
            ->distinct()
            ->pluck('category')
            ->values()
            ->all();
    }

    /**
     * Get adjacent edition navigation (previous and next published edition relative to current).
     */
    public function getAdjacentEditions(Tenant $tenant, Edition $edition): array
    {
        $prev = Edition::where('tenant_id', $tenant->id)
            ->where('status', 'published')
            ->where('publish_date', '<', $edition->publish_date)
            ->orderBy('publish_date', 'desc')
            ->first(['id', 'edition_code', 'edition_title', 'edition_name', 'publish_date']);

        $next = Edition::where('tenant_id', $tenant->id)
            ->where('status', 'published')
            ->where('publish_date', '>', $edition->publish_date)
            ->orderBy('publish_date', 'asc')
            ->first(['id', 'edition_code', 'edition_title', 'edition_name', 'publish_date']);

        return [
            'previous' => $prev,
            'next' => $next,
        ];
    }
}
