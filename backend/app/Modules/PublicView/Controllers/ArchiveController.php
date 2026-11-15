<?php

namespace App\Modules\PublicView\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\PublicView\Services\PublicEditionService;
use App\Modules\PublicView\Services\PublicManifestService;
use App\Modules\SuperAdmin\Models\Tenant;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ArchiveController extends Controller
{
    protected PublicEditionService $editionService;
    protected PublicManifestService $manifestService;

    public function __construct(
        PublicEditionService $editionService,
        PublicManifestService $manifestService
    ) {
        $this->editionService = $editionService;
        $this->manifestService = $manifestService;
    }

    /**
     * Get published editions archive list and calendar days.
     */
    public function index(Request $request): JsonResponse
    {
        /** @var Tenant $tenant */
        $tenant = $request->attributes->get('public_tenant');

        $year = (int) $request->query('year', date('Y'));
        $month = (int) $request->query('month', date('n'));

        // Clamp year and month
        $year = max(2000, min(2100, $year));
        $month = max(1, min(12, $month));

        $calendar = $this->editionService->getArchiveCalendar($tenant, $year, $month);

        $formattedEditions = collect($calendar['editions'])->map(function ($ed) {
            return [
                'id' => $ed->id,
                'edition_code' => $ed->edition_code,
                'edition_title' => $ed->edition_title ?? $ed->edition_name,
                'edition_name' => $ed->edition_name,
                'category' => $ed->category ?? 'Main Edition',
                'publish_date' => $ed->publish_date ? $ed->publish_date->format('Y-m-d') : null,
                'total_pages' => $ed->total_pages,
                'thumbnail_url' => $this->manifestService->sanitizePublicUrl($ed->thumbnail_path),
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $formattedEditions,
            'calendar' => [
                'year' => $calendar['year'],
                'month' => $calendar['month'],
                'total_editions' => $calendar['total_editions'],
                'available_years' => $calendar['available_years'],
                'published_dates' => $calendar['published_dates'],
            ],
        ], 200, [
            'Cache-Control' => 'public, max-age=180, s-maxage=600',
        ]);
    }
}
