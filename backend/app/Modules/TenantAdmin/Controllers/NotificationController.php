<?php

namespace App\Modules\TenantAdmin\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\TenantAdmin\Services\TenantNotificationService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class NotificationController extends Controller
{
    protected TenantNotificationService $notificationService;

    public function __construct(TenantNotificationService $notificationService)
    {
        $this->notificationService = $notificationService;
    }

    public function index(Request $request): JsonResponse
    {
        $tenantId = $request->attributes->get('tenant_id') ?? $request->user()->tenant_id;
        $unreadOnly = $request->boolean('unread_only');

        $result = $this->notificationService->listNotifications($tenantId, $unreadOnly);

        return response()->json([
            'success' => true,
            'data' => $result['notifications'],
            'unread_count' => $result['unread_count'],
        ]);
    }

    public function markAsRead(Request $request, int $id): JsonResponse
    {
        $tenantId = $request->attributes->get('tenant_id') ?? $request->user()->tenant_id;
        $notif = $this->notificationService->markAsRead($tenantId, $id);

        return response()->json([
            'success' => true,
            'message' => 'Notification marked as read.',
            'data' => $notif,
        ]);
    }

    public function markAllAsRead(Request $request): JsonResponse
    {
        $tenantId = $request->attributes->get('tenant_id') ?? $request->user()->tenant_id;
        $count = $this->notificationService->markAllAsRead($tenantId);

        return response()->json([
            'success' => true,
            'message' => "Marked {$count} notifications as read.",
        ]);
    }

    public function destroy(Request $request, int $id): JsonResponse
    {
        $tenantId = $request->attributes->get('tenant_id') ?? $request->user()->tenant_id;
        $this->notificationService->deleteNotification($tenantId, $id);

        return response()->json([
            'success' => true,
            'message' => 'Notification deleted.',
        ]);
    }
}
