<?php

namespace App\Modules\PublicView\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\PublicView\Services\PublicSeoService;
use App\Modules\SuperAdmin\Models\Tenant;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class SitemapController extends Controller
{
    protected PublicSeoService $seoService;

    public function __construct(PublicSeoService $seoService)
    {
        $this->seoService = $seoService;
    }

    /**
     * Generate XML Sitemap for the public tenant newspaper.
     */
    public function sitemap(Request $request): Response
    {
        /** @var Tenant $tenant */
        $tenant = $request->attributes->get('public_tenant');

        return $this->seoService->generateSitemapXml($tenant, $request->root());
    }

    /**
     * Generate standard robots.txt for the public tenant newspaper.
     */
    public function robots(Request $request): Response
    {
        /** @var Tenant $tenant */
        $tenant = $request->attributes->get('public_tenant');
        $sitemapUrl = $request->root() . "/api/v1/public/{$tenant->slug}/sitemap.xml";

        $txt = "User-agent: *\n";
        $txt .= "Allow: /\n";
        $txt .= "Disallow: /api/v1/tenant-admin/\n";
        $txt .= "Disallow: /api/v1/super-admin/\n";
        $txt .= "Sitemap: {$sitemapUrl}\n";

        return response($txt, 200, [
            'Content-Type' => 'text/plain; charset=utf-8',
            'Cache-Control' => 'public, max-age=86400',
        ]);
    }
}
