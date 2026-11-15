<?php

namespace App\Http\Controllers\TenantAdmin;

use App\Http\Controllers\Controller;
use App\Http\Controllers\TenantAdmin\Concerns\ResolvesTenant;
use App\Models\NewsletterCampaign;
use App\Models\NewsletterSubscriber;
use Illuminate\Http\Request;

class NewsletterController extends Controller
{
    use ResolvesTenant;

    public function index()
    {
        $campaigns = NewsletterCampaign::where('tenant_id', $this->tenantId())->latest()->paginate(10, ['*'], 'campaigns');
        $subscribers = NewsletterSubscriber::where('tenant_id', $this->tenantId())->latest()->paginate(15, ['*'], 'subscribers');
        $stats = [
            'total_subscribers' => NewsletterSubscriber::where('tenant_id', $this->tenantId())->where('is_active', true)->count(),
            'campaigns_sent' => NewsletterCampaign::where('tenant_id', $this->tenantId())->where('status', 'sent')->count(),
        ];

        return view('tenant-admin.newsletters.index', compact('campaigns', 'subscribers', 'stats'));
    }

    public function storeCampaign(Request $request)
    {
        $data = $request->validate([
            'subject' => 'required|string|max:255',
            'body' => 'required|string',
            'scheduled_at' => 'nullable|date',
        ]);
        $data['tenant_id'] = $this->tenantId();
        $data['status'] = ! empty($data['scheduled_at']) ? 'scheduled' : 'draft';
        NewsletterCampaign::create($data);

        return back()->with('success', 'Campaign created.');
    }

    public function sendCampaign(NewsletterCampaign $campaign)
    {
        abort_unless($campaign->tenant_id === $this->tenantId(), 404);
        $count = NewsletterSubscriber::where('tenant_id', $this->tenantId())->where('is_active', true)->count();
        $campaign->update(['status' => 'sent', 'sent_at' => now(), 'sent_count' => $count]);

        return back()->with('success', "Newsletter queued to {$count} subscribers.");
    }

    public function storeSubscriber(Request $request)
    {
        $data = $request->validate(['email' => 'required|email', 'name' => 'nullable|string|max:255']);
        $data['tenant_id'] = $this->tenantId();
        NewsletterSubscriber::updateOrCreate(
            ['tenant_id' => $this->tenantId(), 'email' => $data['email']],
            ['name' => $data['name'] ?? null, 'is_active' => true]
        );

        return back()->with('success', 'Subscriber added.');
    }
}
