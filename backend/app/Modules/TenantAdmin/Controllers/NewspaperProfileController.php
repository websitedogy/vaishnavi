<?php

namespace App\Modules\TenantAdmin\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\TenantAdmin\Services\NewspaperProfileService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class NewspaperProfileController extends Controller
{
    protected NewspaperProfileService $profileService;

    public function __construct(NewspaperProfileService $profileService)
    {
        $this->profileService = $profileService;
    }

    public function show(Request $request): JsonResponse
    {
        $tenantId = $request->attributes->get('tenant_id') ?? $request->user()->tenant_id;
        $profile = $this->profileService->getProfile($tenantId);

        return response()->json([
            'success' => true,
            'data' => $profile,
        ]);
    }

    public function update(Request $request): JsonResponse
    {
        $tenantId = $request->attributes->get('tenant_id') ?? $request->user()->tenant_id;
        $validated = $request->validate([
            'newspaper_name' => 'sometimes|string|max:255',
            'company_name' => 'nullable|string|max:255',
            'publisher_name' => 'nullable|string|max:255',
            'editor_name' => 'nullable|string|max:255',
            'contact_person' => 'nullable|string|max:255',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:30',
            'whatsapp' => 'nullable|string|max:30',
            'address' => 'nullable|string',
            'state' => 'nullable|string|max:100',
            'district' => 'nullable|string|max:100',
            'mandal' => 'nullable|string|max:100',
            'pincode' => 'nullable|string|max:10',
            'language' => 'sometimes|string|max:10',
            'about_newspaper' => 'nullable|string',
            'social_links' => 'nullable|array',
        ]);

        $profile = $this->profileService->updateProfile($tenantId, $validated, $request->user()->id);

        return response()->json([
            'success' => true,
            'message' => 'Newspaper profile details saved.',
            'data' => $profile,
        ]);
    }

    public function uploadLogo(Request $request): JsonResponse
    {
        $request->validate([
            'logo' => 'required|image|mimes:png,jpg,jpeg,svg,webp|max:5120',
        ]);

        $tenantId = $request->attributes->get('tenant_id') ?? $request->user()->tenant_id;
        $logoUrl = $this->profileService->uploadLogo($tenantId, $request->file('logo'), $request->user()->id);

        return response()->json([
            'success' => true,
            'message' => 'Logo uploaded successfully.',
            'data' => ['logo_url' => $logoUrl],
        ]);
    }

    public function uploadFavicon(Request $request): JsonResponse
    {
        $request->validate([
            'favicon' => 'required|image|mimes:ico,png,svg|max:1024',
        ]);

        $tenantId = $request->attributes->get('tenant_id') ?? $request->user()->tenant_id;
        $faviconUrl = $this->profileService->uploadFavicon($tenantId, $request->file('favicon'), $request->user()->id);

        return response()->json([
            'success' => true,
            'message' => 'Favicon uploaded successfully.',
            'data' => ['favicon_url' => $faviconUrl],
        ]);
    }
}
