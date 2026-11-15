<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\PlatformSetting;
use App\Models\Tenant;
use Illuminate\Http\Request;

class StorageManagementController extends Controller
{
    public function index()
    {
        $settings = [
            'image_upload_size_mb' => PlatformSetting::getValue('storage.image_upload_size_mb', '5'),
            'video_storage_limit_gb' => PlatformSetting::getValue('storage.video_storage_limit_gb', '50'),
            'tenant_disk_limit_gb' => PlatformSetting::getValue('storage.tenant_disk_limit_gb', '100'),
        ];

        $diskTotal = @disk_total_space(base_path()) ?: 1;
        $diskFree = @disk_free_space(base_path()) ?: 0;
        $usedBytes = $diskTotal - $diskFree;

        $usage = [
            'used_gb' => round($usedBytes / 1024 / 1024 / 1024, 2),
            'total_gb' => round($diskTotal / 1024 / 1024 / 1024, 2),
            'percent' => round(($usedBytes / max($diskTotal, 1)) * 100, 1),
        ];

        $tenantUsage = Tenant::withCount(['newsPosts', 'users'])
            ->get()
            ->map(function (Tenant $tenant) {
                $estimatedGb = round((($tenant->news_posts_count * 18) + ($tenant->users_count * 4)) / 1024, 2);

                return [
                    'name' => $tenant->name,
                    'estimated_gb' => $estimatedGb,
                    'posts' => $tenant->news_posts_count,
                ];
            })
            ->sortByDesc('estimated_gb')
            ->values();

        return view('super-admin.storage.index', compact('settings', 'usage', 'tenantUsage'));
    }

    public function update(Request $request)
    {
        $data = $request->validate([
            'image_upload_size_mb' => ['required', 'integer', 'min:1', 'max:100'],
            'video_storage_limit_gb' => ['required', 'integer', 'min:1', 'max:5000'],
            'tenant_disk_limit_gb' => ['required', 'integer', 'min:1', 'max:5000'],
        ]);

        PlatformSetting::setValue('storage', 'storage.image_upload_size_mb', (string) $data['image_upload_size_mb']);
        PlatformSetting::setValue('storage', 'storage.video_storage_limit_gb', (string) $data['video_storage_limit_gb']);
        PlatformSetting::setValue('storage', 'storage.tenant_disk_limit_gb', (string) $data['tenant_disk_limit_gb']);

        return back()->with('success', 'Storage limits updated successfully.');
    }
}
