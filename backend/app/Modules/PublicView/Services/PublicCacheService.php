<?php

namespace App\Modules\PublicView\Services;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PublicCacheService
{
    /**
     * Check conditional ETag header and return 304 if client matches.
     */
    public function handleConditionalEtag(Request $request, string $etag, ?int $maxAge = 60, ?int $sMaxAge = 300): ?JsonResponse
    {
        $ifNoneMatch = $request->header('If-None-Match');
        if ($ifNoneMatch && trim($ifNoneMatch) === trim($etag)) {
            return response()->json(null, 304, [
                'ETag' => $etag,
                'Cache-Control' => "public, max-age={$maxAge}, s-maxage={$sMaxAge}, must-revalidate",
            ]);
        }
        return null;
    }

    /**
     * Attach standard public caching headers to a JsonResponse.
     */
    public function withCacheHeaders(JsonResponse $response, string $etag, int $maxAge = 60, int $sMaxAge = 300): JsonResponse
    {
        return $response->withHeaders([
            'ETag' => $etag,
            'Cache-Control' => "public, max-age={$maxAge}, s-maxage={$sMaxAge}, must-revalidate",
        ]);
    }
}
