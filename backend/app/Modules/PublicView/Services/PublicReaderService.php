<?php

namespace App\Modules\PublicView\Services;

use App\Modules\SuperAdmin\Models\Tenant;
use App\Modules\TenantAdmin\Models\Edition;
use App\Modules\TenantAdmin\Models\NewspaperProfile;

class PublicReaderService
{
    protected PublicEditionService $editionService;
    protected PublicManifestService $manifestService;
    protected PublicTenantService $tenantService;

    public function __construct(
        PublicEditionService $editionService,
        PublicManifestService $manifestService,
        PublicTenantService $tenantService
    ) {
        $this->editionService = $editionService;
        $this->manifestService = $manifestService;
        $this->tenantService = $tenantService;
    }

    /**
     * Get complete reader payload for an edition.
     */
    public function getReaderPayload(Tenant $tenant, Edition $edition): array
    {
        $manifest = $this->manifestService->getManifest($edition, $tenant);
        $adjacent = $this->editionService->getAdjacentEditions($tenant, $edition);
        $branding = $this->tenantService->getPublicBranding($tenant);
        $profile = NewspaperProfile::where('tenant_id', $tenant->id)->first();

        // Permissions configuration (default download false unless enabled)
        $permissions = [
            'allow_page_download' => false,
            'allow_pdf_download' => false,
            'allow_share' => true,
            'allow_print' => true,
        ];

        return [
            'edition' => $manifest['edition'] ?? [],
            'publication' => $manifest['publication'] ?? [],
            'branding' => $branding,
            'pages' => $manifest['pages'] ?? [],
            'navigation' => [
                'current_edition_code' => $edition->edition_code,
                'previous_edition' => $adjacent['previous'],
                'next_edition' => $adjacent['next'],
            ],
            'permissions' => $permissions,
            'compiled_at' => $manifest['compiled_at'] ?? now()->toIso8601String(),
        ];
    }
}
