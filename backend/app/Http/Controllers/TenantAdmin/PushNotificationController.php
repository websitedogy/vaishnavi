<?php

namespace App\Http\Controllers\TenantAdmin;

use App\Http\Controllers\Controller;
use App\Http\Controllers\TenantAdmin\Concerns\ResolvesTenant;
use App\Models\PushNotification;
use App\Models\PushSubscriber;
use App\Services\TenantSettingsService;
use Illuminate\Http\Request;

class PushNotificationController extends Controller
{
    use ResolvesTenant;

    public function __construct(protected TenantSettingsService $settings) {}

    public function index()
    {
        $notifications = PushNotification::where('tenant_id', $this->tenantId())->latest()->paginate(10);
        $subscriberCount = PushSubscriber::where('tenant_id', $this->tenantId())->count();
        $config = $this->settings->get($this->tenantId(), 'notifications');

        return view('tenant-admin.push.index', compact('notifications', 'subscriberCount', 'config'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'title' => 'required|string|max:255',
            'body' => 'required|string',
            'topic' => 'nullable|string|max:100',
            'channel' => 'required|in:browser,mobile',
            'scheduled_at' => 'nullable|date',
        ]);
        $data['tenant_id'] = $this->tenantId();
        $data['status'] = $data['scheduled_at'] ? 'scheduled' : 'sent';
        $data['sent_at'] = $data['scheduled_at'] ? null : now();
        $data['subscriber_count'] = PushSubscriber::where('tenant_id', $this->tenantId())->count();
        PushNotification::create($data);

        return back()->with('success', 'Notification saved.');
    }

    public function updateConfig(Request $request)
    {
        $this->settings->save($this->tenantId(), 'notifications', $request->except('_token'));

        return back()->with('success', 'Push notification settings saved.');
    }
}
