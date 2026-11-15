<?php

namespace App\Modules\TenantAdmin\Services;

use App\Modules\TenantAdmin\Models\NewspaperTemplate;
use App\Modules\SuperAdmin\Models\TenantBranding;
use Illuminate\Validation\ValidationException;

class TemplateService
{
    public function listTemplates(int $tenantId): array
    {
        // System global templates (tenant_id is null) plus tenant custom templates
        $templates = NewspaperTemplate::whereNull('tenant_id')
            ->orWhere('tenant_id', $tenantId)
            ->where('is_active', true)
            ->get();

        $activeBranding = TenantBranding::where('tenant_id', $tenantId)->first();

        return [
            'templates' => $templates,
            'current_theme' => [
                'primary_color' => $activeBranding->primary_color ?? '#2563EB',
                'secondary_color' => $activeBranding->secondary_color ?? '#0EA5E9',
                'typography' => $activeBranding->typography ?? 'Inter',
            ],
        ];
    }

    public function applyTemplate(int $tenantId, int $templateId, ?int $userId = null): array
    {
        $template = NewspaperTemplate::where(function ($q) use ($tenantId) {
            $q->whereNull('tenant_id')->orWhere('tenant_id', $tenantId);
        })->findOrFail($templateId);

        $branding = TenantBranding::firstOrCreate(['tenant_id' => $tenantId]);

        $colors = $template->color_palette ?? [];
        if (!empty($colors['primary'])) {
            $branding->primary_color = $colors['primary'];
        }
        if (!empty($colors['secondary'])) {
            $branding->secondary_color = $colors['secondary'];
        }

        $layout = $template->layout_config ?? [];
        $header = $branding->header_config ?? [];
        if (isset($layout['masthead_style'])) {
            $header['logo_position'] = $layout['masthead_style'] === 'centered' ? 'center' : 'left';
            $branding->header_config = $header;
        }

        $branding->version = ($branding->version ?? 1) + 1;
        $branding->save();

        TenantAuditHelper::log($tenantId, $userId, 'template_applied', 'templates', "Applied website template [{$template->name}].");

        return [
            'applied_template' => $template,
            'branding' => $branding,
        ];
    }
}
