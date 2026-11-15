<?php

namespace App\Modules\TenantAdmin\Services;

use App\Modules\TenantAdmin\Models\NewspaperProfile;
use App\Modules\SuperAdmin\Models\Tenant;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class NewspaperProfileService
{
    public function getProfile(int $tenantId): NewspaperProfile
    {
        return NewspaperProfile::firstOrCreate(
            ['tenant_id' => $tenantId],
            [
                'newspaper_name' => Tenant::find($tenantId)->name ?? 'My Newspaper',
                'language' => 'te',
            ]
        );
    }

    public function updateProfile(int $tenantId, array $data, ?int $userId = null): NewspaperProfile
    {
        $profile = $this->getProfile($tenantId);

        $allowedFields = [
            'newspaper_name',
            'company_name',
            'publisher_name',
            'editor_name',
            'contact_person',
            'email',
            'phone',
            'whatsapp',
            'address',
            'state',
            'district',
            'mandal',
            'pincode',
            'language',
            'about_newspaper',
            'social_links',
        ];

        $payload = array_intersect_key($data, array_flip($allowedFields));
        $profile->update($payload);

        TenantAuditHelper::log($tenantId, $userId, 'profile_updated', 'profile', 'Updated newspaper profile details.');

        return $profile;
    }

    public function uploadLogo(int $tenantId, UploadedFile $file, ?int $userId = null): string
    {
        $tenant = Tenant::findOrFail($tenantId);
        $filename = 'logo_' . time() . '.' . $file->getClientOriginalExtension();
        $path = $file->storeAs("public/tenants/{$tenant->slug}/branding", $filename);
        $publicUrl = Storage::url($path);

        $profile = $this->getProfile($tenantId);
        $profile->logo_url = $publicUrl;
        $profile->save();

        TenantAuditHelper::log($tenantId, $userId, 'logo_uploaded', 'branding', 'Uploaded newspaper logo.');

        return $publicUrl;
    }

    public function uploadFavicon(int $tenantId, UploadedFile $file, ?int $userId = null): string
    {
        $tenant = Tenant::findOrFail($tenantId);
        $filename = 'favicon_' . time() . '.' . $file->getClientOriginalExtension();
        $path = $file->storeAs("public/tenants/{$tenant->slug}/branding", $filename);
        $publicUrl = Storage::url($path);

        $profile = $this->getProfile($tenantId);
        $profile->favicon_url = $publicUrl;
        $profile->save();

        TenantAuditHelper::log($tenantId, $userId, 'favicon_uploaded', 'branding', 'Uploaded newspaper favicon.');

        return $publicUrl;
    }
}
