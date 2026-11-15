<?php

namespace App\Modules\PublicView\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\PublicView\Services\PublicCacheService;
use App\Modules\PublicView\Services\PublicEditionService;
use App\Modules\PublicView\Services\PublicManifestService;
use App\Modules\PublicView\Services\PublicReaderService;
use App\Modules\SuperAdmin\Models\Tenant;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ReaderController extends Controller
{
    protected PublicEditionService $editionService;
    protected PublicReaderService $readerService;
    protected PublicManifestService $manifestService;
    protected PublicCacheService $cacheService;

    public function __construct(
        PublicEditionService $editionService,
        PublicReaderService $readerService,
        PublicManifestService $manifestService,
        PublicCacheService $cacheService
    ) {
        $this->editionService = $editionService;
        $this->readerService = $readerService;
        $this->manifestService = $manifestService;
        $this->cacheService = $cacheService;
    }

    /**
     * Get digital newspaper reader payload for latest edition or specific date/code.
     */
    public function show(Request $request, string $tenantSlug, ?string $dateOrCode = null): JsonResponse
    {
        /** @var Tenant $tenant */
        $tenant = $request->attributes->get('public_tenant');

        if (empty($dateOrCode) || $dateOrCode === 'latest') {
            $edition = $this->editionService->getLatest($tenant);
        } else {
            $edition = $this->editionService->getByDateOrCode($tenant, $dateOrCode);
        }

        if (!$edition) {
            return response()->json([
                'success' => false,
                'message' => 'Requested newspaper edition is not published or does not exist.',
            ], 404);
        }

        $etag = $this->manifestService->computeETag($edition);

        $notModified = $this->cacheService->handleConditionalEtag($request, $etag, 60, 300);
        if ($notModified) {
            return $notModified;
        }

        $payload = $this->readerService->getReaderPayload($tenant, $edition);

        return $this->cacheService->withCacheHeaders(
            response()->json([
                'success' => true,
                'data' => $payload,
            ], 200),
            $etag,
            60,
            300
        );
    }
}
