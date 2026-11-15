<?php

namespace App\Modules\TenantAdmin\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\TenantAdmin\Services\StaffService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class RoleController extends Controller
{
    protected StaffService $staffService;

    public function __construct(StaffService $staffService)
    {
        $this->staffService = $staffService;
    }

    public function index(): JsonResponse
    {
        $roles = $this->staffService->getAvailableRoles();

        $permissions = [
            'dashboard.view' => 'Access and view dashboard statistics',
            'editions.view' => 'View editions catalog',
            'editions.create' => 'Create new edition drafts',
            'editions.edit' => 'Edit edition metadata and pages',
            'editions.delete' => 'Delete draft editions',
            'pdf.upload' => 'Upload and process edition PDFs',
            'pages.manage' => 'Reorder, rotate, and manage pages',
            'publishing.schedule' => 'Schedule editions for automated publication',
            'publishing.publish' => 'Publish editions live',
            'media.manage' => 'Upload and manage media library assets',
            'branding.manage' => 'Configure website theme and branding',
            'staff.manage' => 'Add, edit, and deactivate staff users',
            'reports.view' => 'View operational reports and export CSV',
            'settings.manage' => 'Configure publishing settings',
        ];

        return response()->json([
            'success' => true,
            'data' => [
                'roles' => $roles,
                'permission_definitions' => $permissions,
            ],
        ]);
    }
}
