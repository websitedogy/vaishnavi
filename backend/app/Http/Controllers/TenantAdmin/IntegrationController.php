<?php

namespace App\Http\Controllers\TenantAdmin;

use App\Http\Controllers\Controller;
use App\Http\Controllers\TenantAdmin\Concerns\ResolvesTenant;
use App\Models\TenantApiKey;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class IntegrationController extends Controller
{
    use ResolvesTenant;

    public function storeApiKey(Request $request)
    {
        $data = $request->validate(['name' => 'required|string|max:255']);
        $plain = 'tn_'.Str::random(40);
        TenantApiKey::create([
            'tenant_id' => $this->tenantId(),
            'name' => $data['name'],
            'key_prefix' => substr($plain, 0, 12),
            'key_hash' => hash('sha256', $plain),
            'is_active' => true,
        ]);

        return back()->with('success', 'API key created. Key: '.$plain.' (copy now — shown once)');
    }

    public function destroyApiKey(TenantApiKey $apiKey)
    {
        abort_unless($apiKey->tenant_id === $this->tenantId(), 404);
        $apiKey->delete();

        return back()->with('success', 'API key revoked.');
    }

    public function updateWebhooks(Request $request, TenantApiKey $apiKey)
    {
        abort_unless($apiKey->tenant_id === $this->tenantId(), 404);
        $apiKey->update(['webhooks' => ['urls' => array_filter(explode("\n", $request->input('webhook_urls', '')))]]);

        return back()->with('success', 'Webhooks updated.');
    }
}
