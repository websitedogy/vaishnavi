<?php

namespace App\Modules\PublicView\Services;

use App\Modules\SuperAdmin\Models\Tenant;
use App\Modules\TenantAdmin\Models\Edition;
use App\Modules\TenantAdmin\Models\NewspaperProfile;
use Carbon\Carbon;
use Illuminate\Http\Response;

class PublicSeoService
{
    /**
     * Generate XML Sitemap listing all published editions for a tenant.
     */
    public function generateSitemapXml(Tenant $tenant, string $baseUrl): Response
    {
        $profile = NewspaperProfile::where('tenant_id', $tenant->id)->first();
        $newspaperName = htmlspecialchars($profile?->newspaper_name ?? $tenant->name, ENT_XML1, 'UTF-8');

        $editions = Edition::where('tenant_id', $tenant->id)
            ->where('status', 'published')
            ->orderBy('publish_date', 'desc')
            ->limit(500)
            ->get();

        $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";

        // Homepage
        $homeUrl = htmlspecialchars(rtrim($baseUrl, '/') . "/{$tenant->slug}", ENT_XML1, 'UTF-8');
        $xml .= "  <url>\n";
        $xml .= "    <loc>{$homeUrl}</loc>\n";
        $xml .= "    <changefreq>daily</changefreq>\n";
        $xml .= "    <priority>1.0</priority>\n";
        $xml .= "  </url>\n";

        // Archive page
        $archiveUrl = htmlspecialchars(rtrim($baseUrl, '/') . "/{$tenant->slug}/archive", ENT_XML1, 'UTF-8');
        $xml .= "  <url>\n";
        $xml .= "    <loc>{$archiveUrl}</loc>\n";
        $xml .= "    <changefreq>daily</changefreq>\n";
        $xml .= "    <priority>0.8</priority>\n";
        $xml .= "  </url>\n";

        // Published Editions
        foreach ($editions as $ed) {
            $editionDate = $ed->publish_date instanceof Carbon ? $ed->publish_date->format('Y-m-d') : (string) $ed->publish_date;
            $editionUrl = htmlspecialchars(rtrim($baseUrl, '/') . "/{$tenant->slug}/epaper/{$ed->edition_code}", ENT_XML1, 'UTF-8');
            $lastmod = $ed->updated_at ? $ed->updated_at->toAtomString() : date('c');

            $xml .= "  <url>\n";
            $xml .= "    <loc>{$editionUrl}</loc>\n";
            $xml .= "    <lastmod>{$lastmod}</lastmod>\n";
            $xml .= "    <changefreq>never</changefreq>\n";
            $xml .= "    <priority>0.9</priority>\n";
            $xml .= "  </url>\n";
        }

        $xml .= '</urlset>';

        return response($xml, 200, [
            'Content-Type' => 'application/xml; charset=utf-8',
            'Cache-Control' => 'public, max-age=3600, s-maxage=7200',
        ]);
    }

    /**
     * Get SEO metadata for an edition or homepage.
     */
    public function getSeoMetadata(Tenant $tenant, ?Edition $edition = null, string $baseUrl = ''): array
    {
        $profile = NewspaperProfile::where('tenant_id', $tenant->id)->first();
        $name = $profile?->newspaper_name ?? $tenant->name;
        $tagline = $profile?->about_newspaper ?? 'Read today\'s digital ePaper online';

        if ($edition) {
            $date = $edition->publish_date instanceof Carbon ? $edition->publish_date->format('d M Y') : (string) $edition->publish_date;
            $title = "{$edition->edition_name} - {$date} | {$name} ePaper";
            $description = $edition->description ?? "Read {$name} digital edition for {$date}. Browse full broadsheet pages online.";
            $canonical = rtrim($baseUrl, '/') . "/{$tenant->slug}/epaper/{$edition->edition_code}";
            $ogImage = $edition->thumbnail_path ? (str_starts_with($edition->thumbnail_path, 'http') ? $edition->thumbnail_path : rtrim($baseUrl, '/') . $edition->thumbnail_path) : null;
        } else {
            $title = "{$name} - Official Digital ePaper";
            $description = "{$name} ePaper: {$tagline}. Read today's complete newspaper edition online.";
            $canonical = rtrim($baseUrl, '/') . "/{$tenant->slug}";
            $ogImage = $profile?->logo_url ? (str_starts_with($profile->logo_url, 'http') ? $profile->logo_url : rtrim($baseUrl, '/') . $profile->logo_url) : null;
        }

        return [
            'title' => $title,
            'description' => $description,
            'canonical_url' => $canonical,
            'og_type' => $edition ? 'article' : 'website',
            'og_title' => $title,
            'og_description' => $description,
            'og_image' => $ogImage,
            'twitter_card' => 'summary_large_image',
            'twitter_title' => $title,
            'twitter_description' => $description,
            'twitter_image' => $ogImage,
            'publisher' => $profile?->publisher_name ?? $name,
            'language' => $tenant->default_language ?? 'te',
        ];
    }
}
