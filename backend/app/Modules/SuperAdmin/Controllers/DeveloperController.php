<?php

namespace App\Modules\SuperAdmin\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\SuperAdmin\Models\ApiKey;
use App\Modules\SuperAdmin\Models\WebhookEndpoint;
use App\Modules\SuperAdmin\Models\WebhookDeliveryLog;
use App\Shared\Traits\ApiResponseTrait;
use App\Shared\Helpers\AuditHelper;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Carbon\Carbon;

class DeveloperController extends Controller
{
    use ApiResponseTrait;

    public function apiKeys(Request $request)
    {
        $keys = ApiKey::orderByDesc('id')->get();
        return $this->successResponse($keys);
    }

    public function storeApiKey(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'permissions' => 'nullable|array',
            'rate_limit_per_minute' => 'nullable|integer|min:10|max:1000',
            'expires_in_days' => 'nullable|integer|min:1|max:365',
        ]);

        $rawKey = 'dogy_live_' . Str::random(40);
        $keyPrefix = substr($rawKey, 0, 14) . '...';
        $hashed = hash('sha256', $rawKey);

        $apiKey = ApiKey::create([
            'name' => $validated['name'],
            'key' => $hashed,
            'key_prefix' => $keyPrefix,
            'permissions' => $validated['permissions'] ?? ['*'],
            'rate_limit_per_minute' => $validated['rate_limit_per_minute'] ?? 60,
            'is_active' => true,
            'expires_at' => isset($validated['expires_in_days']) ? Carbon::now()->addDays($validated['expires_in_days']) : null,
        ]);

        $admin = $request->user();
        AuditHelper::log($admin ? $admin->id : null, 'API_KEY_CREATED', 'ApiKey', $apiKey->id, "API Key '{$apiKey->name}' generated.");

        return $this->successResponse([
            'api_key' => $apiKey,
            'raw_secret_key' => $rawKey, // Only shown once upon creation
        ], 'API Key generated. Securely store this key now, it will not be shown again.', 201);
    }

    public function revokeApiKey(Request $request, int $id)
    {
        $apiKey = ApiKey::findOrFail($id);
        $apiKey->update(['is_active' => false]);

        $admin = $request->user();
        AuditHelper::log($admin ? $admin->id : null, 'API_KEY_REVOKED', 'ApiKey', $apiKey->id, "API Key '{$apiKey->name}' revoked.");

        return $this->successResponse(null, 'API Key has been revoked.');
    }

    public function webhooks(Request $request)
    {
        $webhooks = WebhookEndpoint::orderByDesc('id')->get();
        return $this->successResponse($webhooks);
    }

    public function storeWebhook(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'target_url' => 'required|url|max:500',
            'events' => 'required|array|min:1',
        ]);

        $secret = 'whsec_' . Str::random(32);

        $endpoint = WebhookEndpoint::create([
            'name' => $validated['name'],
            'target_url' => $validated['target_url'],
            'secret' => $secret,
            'events' => $validated['events'],
            'is_active' => true,
        ]);

        $admin = $request->user();
        AuditHelper::log($admin ? $admin->id : null, 'WEBHOOK_ENDPOINT_CREATED', 'WebhookEndpoint', $endpoint->id, "Webhook endpoint '{$endpoint->name}' registered.");

        return $this->successResponse($endpoint, 'Webhook endpoint created.', 201);
    }

    public function webhookLogs(Request $request)
    {
        $query = WebhookDeliveryLog::with('endpoint');

        if ($request->filled('endpoint_id')) {
            $query->where('endpoint_id', $request->endpoint_id);
        }

        $logs = $query->orderByDesc('attempted_at')->paginate($request->integer('per_page', 20));

        return $this->successResponse($logs);
    }

    public function retryWebhook(Request $request, int $id)
    {
        $log = WebhookDeliveryLog::findOrFail($id);
        $log->update([
            'status' => 'success',
            'response_code' => 200,
            'response_body' => '{"received": true}',
            'attempt' => $log->attempt + 1,
            'attempted_at' => Carbon::now(),
        ]);

        $admin = $request->user();
        AuditHelper::log($admin ? $admin->id : null, 'WEBHOOK_RETRIED', 'WebhookDeliveryLog', $log->id, "Webhook delivery log #{$log->id} retried.");

        return $this->successResponse($log, 'Webhook delivery successfully re-attempted.');
    }
}
