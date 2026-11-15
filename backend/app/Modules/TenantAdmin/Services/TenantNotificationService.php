<?php

namespace App\Modules\TenantAdmin\Services;

use App\Modules\TenantAdmin\Models\TenantNotification;
use Carbon\Carbon;

class TenantNotificationService
{
    public function listNotifications(int $tenantId, bool $unreadOnly = false): array
    {
        $query = TenantNotification::where('tenant_id', $tenantId);

        if ($unreadOnly) {
            $query->where('is_read', false);
        }

        $notifications = $query->orderBy('created_at', 'desc')->limit(50)->get();
        $unreadCount = TenantNotification::where('tenant_id', $tenantId)->where('is_read', false)->count();

        return [
            'notifications' => $notifications,
            'unread_count' => $unreadCount,
        ];
    }

    public function markAsRead(int $tenantId, int $notificationId): TenantNotification
    {
        $notif = TenantNotification::where('tenant_id', $tenantId)->findOrFail($notificationId);
        $notif->is_read = true;
        $notif->read_at = Carbon::now();
        $notif->save();

        return $notif;
    }

    public function markAllAsRead(int $tenantId): int
    {
        return TenantNotification::where('tenant_id', $tenantId)
            ->where('is_read', false)
            ->update([
                'is_read' => true,
                'read_at' => Carbon::now(),
            ]);
    }

    public function deleteNotification(int $tenantId, int $notificationId): bool
    {
        $notif = TenantNotification::where('tenant_id', $tenantId)->findOrFail($notificationId);
        $notif->delete();
        return true;
    }
}
