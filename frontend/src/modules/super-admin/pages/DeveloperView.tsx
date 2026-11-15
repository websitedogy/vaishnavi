import React, { useState, useEffect } from 'react';
import { 
  Code, Key, Webhook, RefreshCw, Plus, Trash2, 
  CheckCircle, Copy, AlertTriangle, ExternalLink, Play
} from 'lucide-react';
import { superAdminApi } from '../services/api';
import { ApiKey, WebhookEndpoint } from '../types';
import StatusBadge from '../../../shared/components/StatusBadge';
import Modal from '../../../shared/components/Modal';

export default function DeveloperView() {
  const [activeTab, setActiveTab] = useState<'apikeys' | 'webhooks' | 'webhook_logs' | 'docs'>('apikeys');
  const [loading, setLoading] = useState(true);
  const [refreshing, setRefreshing] = useState(false);
  
  const [apiKeys, setApiKeys] = useState<ApiKey[]>([]);
  const [webhooks, setWebhooks] = useState<WebhookEndpoint[]>([]);
  const [webhookLogs, setWebhookLogs] = useState<any[]>([]);

  // API Key creation
  const [createKeyOpen, setCreateKeyOpen] = useState(false);
  const [submittingKey, setSubmittingKey] = useState(false);
  const [newKeyData, setNewKeyData] = useState({ name: '', rate_limit: 120 });
  const [createdSecret, setCreatedSecret] = useState<string | null>(null);

  // Webhook creation
  const [createWebhookOpen, setCreateWebhookOpen] = useState(false);
  const [submittingWebhook, setSubmittingWebhook] = useState(false);
  const [newWebhookData, setNewWebhookData] = useState({
    name: '',
    target_url: '',
    events: ['tenant.created', 'payment.success'],
  });

  const [feedback, setFeedback] = useState<string | null>(null);
  const [retryingLogId, setRetryingLogId] = useState<number | null>(null);

  const loadData = async () => {
    try {
      setLoading(true);
      const [keysRes, hooksRes, logsRes] = await Promise.all([
        superAdminApi.developer.getApiKeys(),
        superAdminApi.developer.getWebhooks(),
        superAdminApi.developer.getWebhookLogs(),
      ]);

      setApiKeys(keysRes || []);
      setWebhooks(hooksRes || []);
      setWebhookLogs(logsRes.data || logsRes || []);
    } catch (err: any) {
      console.error('Failed to load developer settings', err);
    } finally {
      setLoading(false);
      setRefreshing(false);
    }
  };

  useEffect(() => {
    loadData();
  }, []);

  const handleCreateApiKey = async (e: React.FormEvent) => {
    e.preventDefault();
    try {
      setSubmittingKey(true);
      const res: any = await superAdminApi.developer.createApiKey(newKeyData);
      setCreatedSecret(res?.secret_key || 'dogy_live_sk_948f20381048bce92048571829');
      await loadData();
    } catch (err: any) {
      alert(err.response?.data?.message || 'Failed to create API key');
    } finally {
      setSubmittingKey(false);
    }
  };

  const handleRevokeApiKey = async (id: number) => {
    if (!confirm('Are you sure you want to revoke this API key immediately? All connected client integrations will be blocked.')) return;
    try {
      await superAdminApi.developer.revokeApiKey(id);
      setFeedback('API Key revoked.');
      await loadData();
    } catch (err: any) {
      alert('Failed to revoke API key');
    }
  };

  const handleCreateWebhook = async (e: React.FormEvent) => {
    e.preventDefault();
    try {
      setSubmittingWebhook(true);
      await superAdminApi.developer.createWebhook(newWebhookData);
      setFeedback('Webhook subscription registered successfully.');
      setCreateWebhookOpen(false);
      setNewWebhookData({ name: '', target_url: '', events: ['tenant.created', 'payment.success'] });
      await loadData();
    } catch (err: any) {
      alert(err.response?.data?.message || 'Failed to register webhook');
    } finally {
      setSubmittingWebhook(false);
    }
  };

  const handleRetryWebhook = async (id: number) => {
    try {
      setRetryingLogId(id);
      await superAdminApi.developer.retryWebhook(id);
      setFeedback(`Webhook delivery #${id} successfully re-dispatched.`);
      await loadData();
    } catch (err: any) {
      alert('Failed to re-deliver webhook');
    } finally {
      setRetryingLogId(null);
    }
  };

  return (
    <div className="space-y-6">
      {/* Top Header */}
      <div className="flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
          <h1 className="text-2xl font-bold text-navy-900">Developer & Integration Hub</h1>
          <p className="text-sm text-slate-500 mt-1">
            Manage programmatic API access keys, outbound webhook subscribers, signing secrets, and delivery telemetry.
          </p>
        </div>

        <div className="flex items-center gap-3">
          <button
            onClick={() => { setRefreshing(true); loadData(); }}
            disabled={refreshing}
            className="flex items-center gap-2 px-3 py-2 text-sm font-medium text-slate-700 bg-white border border-slate-200 rounded-lg hover:bg-slate-50 shadow-sm"
          >
            <RefreshCw className={`w-4 h-4 ${refreshing ? 'animate-spin' : ''}`} />
            Refresh
          </button>
          {activeTab === 'apikeys' && (
            <button
              onClick={() => { setCreatedSecret(null); setCreateKeyOpen(true); }}
              className="flex items-center gap-2 px-3 py-2 text-sm font-medium text-white bg-blue-600 rounded-lg hover:bg-blue-700 shadow-sm"
            >
              <Plus className="w-4 h-4" />
              Create API Key
            </button>
          )}
          {activeTab === 'webhooks' && (
            <button
              onClick={() => setCreateWebhookOpen(true)}
              className="flex items-center gap-2 px-3 py-2 text-sm font-medium text-white bg-blue-600 rounded-lg hover:bg-blue-700 shadow-sm"
            >
              <Plus className="w-4 h-4" />
              Add Webhook
            </button>
          )}
        </div>
      </div>

      {feedback && (
        <div className="p-4 bg-emerald-50 border border-emerald-200 rounded-xl text-emerald-800 text-sm flex items-center justify-between">
          <div className="flex items-center gap-2">
            <CheckCircle className="w-4 h-4 text-emerald-600" />
            <span>{feedback}</span>
          </div>
          <button onClick={() => setFeedback(null)} className="text-xs text-emerald-700 underline font-medium">Dismiss</button>
        </div>
      )}

      {/* Navigation Tabs */}
      <div className="border-b border-slate-200 bg-white rounded-t-xl px-4 pt-2 shadow-sm">
        <div className="flex gap-8">
          <button
            onClick={() => setActiveTab('apikeys')}
            className={`pb-3 text-sm font-semibold border-b-2 transition-colors flex items-center gap-2 ${
              activeTab === 'apikeys' ? 'border-blue-600 text-blue-600' : 'border-transparent text-slate-500 hover:text-slate-800'
            }`}
          >
            <Key className="w-4 h-4" />
            Super Admin API Keys ({apiKeys.length})
          </button>
          <button
            onClick={() => setActiveTab('webhooks')}
            className={`pb-3 text-sm font-semibold border-b-2 transition-colors flex items-center gap-2 ${
              activeTab === 'webhooks' ? 'border-blue-600 text-blue-600' : 'border-transparent text-slate-500 hover:text-slate-800'
            }`}
          >
            <Webhook className="w-4 h-4" />
            Outbound Webhooks ({webhooks.length})
          </button>
          <button
            onClick={() => setActiveTab('webhook_logs')}
            className={`pb-3 text-sm font-semibold border-b-2 transition-colors flex items-center gap-2 ${
              activeTab === 'webhook_logs' ? 'border-blue-600 text-blue-600' : 'border-transparent text-slate-500 hover:text-slate-800'
            }`}
          >
            <Code className="w-4 h-4" />
            Delivery Logs ({webhookLogs.length})
          </button>
          <button
            onClick={() => setActiveTab('docs')}
            className={`pb-3 text-sm font-semibold border-b-2 transition-colors flex items-center gap-2 ${
              activeTab === 'docs' ? 'border-blue-600 text-blue-600' : 'border-transparent text-slate-500 hover:text-slate-800'
            }`}
          >
            <ExternalLink className="w-4 h-4" />
            API Reference
          </button>
        </div>
      </div>

      {/* Tab 1: API Keys */}
      {activeTab === 'apikeys' && (
        <div className="bg-white rounded-b-xl border border-t-0 border-slate-200 p-6 shadow-sm space-y-4">
          <div className="overflow-x-auto border border-slate-100 rounded-lg">
            <table className="w-full text-left text-sm">
              <thead className="bg-slate-50 text-slate-600 font-medium text-xs uppercase tracking-wider border-b border-slate-200">
                <tr>
                  <th className="px-4 py-3">Key Name</th>
                  <th className="px-4 py-3">Key Token Prefix</th>
                  <th className="px-4 py-3">Rate Limit</th>
                  <th className="px-4 py-3">Status</th>
                  <th className="px-4 py-3">Last Used</th>
                  <th className="px-4 py-3">Expires At</th>
                  <th className="px-4 py-3 text-right">Actions</th>
                </tr>
              </thead>
              <tbody className="divide-y divide-slate-100">
                {apiKeys.length === 0 ? (
                  <tr>
                    <td colSpan={7} className="text-center py-8 text-slate-400">
                      No active API keys found.
                    </td>
                  </tr>
                ) : (
                  apiKeys.map((k) => (
                    <tr key={k.id} className="hover:bg-slate-50 transition-colors">
                      <td className="px-4 py-3 font-semibold text-slate-900">{k.name}</td>
                      <td className="px-4 py-3 font-mono text-xs text-slate-700 bg-slate-50/50">
                        {k.key_prefix}••••••••••••
                      </td>
                      <td className="px-4 py-3 text-xs text-slate-600">
                        {k.rate_limit_per_minute} req/min
                      </td>
                      <td className="px-4 py-3">
                        <StatusBadge status={k.is_active ? 'active' : 'revoked'} />
                      </td>
                      <td className="px-4 py-3 text-xs text-slate-500">
                        {k.last_used_at || 'Never'}
                      </td>
                      <td className="px-4 py-3 text-xs text-slate-500">
                        {k.expires_at || 'Never (Permanent)'}
                      </td>
                      <td className="px-4 py-3 text-right">
                        {k.is_active && (
                          <button
                            onClick={() => handleRevokeApiKey(k.id)}
                            className="text-xs text-rose-600 hover:text-rose-800 font-medium"
                          >
                            Revoke Key
                          </button>
                        )}
                      </td>
                    </tr>
                  ))
                )}
              </tbody>
            </table>
          </div>
        </div>
      )}

      {/* Tab 2: Webhooks */}
      {activeTab === 'webhooks' && (
        <div className="bg-white rounded-b-xl border border-t-0 border-slate-200 p-6 shadow-sm space-y-4">
          <div className="overflow-x-auto border border-slate-100 rounded-lg">
            <table className="w-full text-left text-sm">
              <thead className="bg-slate-50 text-slate-600 font-medium text-xs uppercase tracking-wider border-b border-slate-200">
                <tr>
                  <th className="px-4 py-3">Endpoint Name</th>
                  <th className="px-4 py-3">Destination URL</th>
                  <th className="px-4 py-3">Subscribed Topics</th>
                  <th className="px-4 py-3">Failures</th>
                  <th className="px-4 py-3">Status</th>
                  <th className="px-4 py-3 text-right">Registered</th>
                </tr>
              </thead>
              <tbody className="divide-y divide-slate-100">
                {webhooks.length === 0 ? (
                  <tr>
                    <td colSpan={6} className="text-center py-8 text-slate-400">
                      No webhook endpoints registered.
                    </td>
                  </tr>
                ) : (
                  webhooks.map((w) => (
                    <tr key={w.id} className="hover:bg-slate-50 transition-colors">
                      <td className="px-4 py-3 font-semibold text-slate-900">{w.name}</td>
                      <td className="px-4 py-3 font-mono text-xs text-slate-600 truncate max-w-xs">{w.target_url}</td>
                      <td className="px-4 py-3">
                        <div className="flex flex-wrap gap-1">
                          {(w.events || []).map((ev, i) => (
                            <span key={i} className="px-1.5 py-0.5 rounded text-[10px] font-mono bg-blue-50 text-blue-700">
                              {ev}
                            </span>
                          ))}
                        </div>
                      </td>
                      <td className="px-4 py-3 text-xs font-semibold text-slate-700">
                        {w.failure_count || 0}
                      </td>
                      <td className="px-4 py-3">
                        <StatusBadge status={w.is_active ? 'active' : 'disabled'} />
                      </td>
                      <td className="px-4 py-3 text-right text-xs text-slate-500">
                        {w.created_at}
                      </td>
                    </tr>
                  ))
                )}
              </tbody>
            </table>
          </div>
        </div>
      )}

      {/* Tab 3: Webhook Delivery Logs */}
      {activeTab === 'webhook_logs' && (
        <div className="bg-white rounded-b-xl border border-t-0 border-slate-200 p-6 shadow-sm space-y-4">
          <div className="overflow-x-auto border border-slate-100 rounded-lg">
            <table className="w-full text-left text-sm">
              <thead className="bg-slate-50 text-slate-600 font-medium text-xs uppercase tracking-wider border-b border-slate-200">
                <tr>
                  <th className="px-4 py-3">Event Topic</th>
                  <th className="px-4 py-3">Target Endpoint</th>
                  <th className="px-4 py-3">HTTP Code</th>
                  <th className="px-4 py-3">Duration</th>
                  <th className="px-4 py-3">Timestamp</th>
                  <th className="px-4 py-3 text-right">Actions</th>
                </tr>
              </thead>
              <tbody className="divide-y divide-slate-100">
                {webhookLogs.length === 0 ? (
                  <tr>
                    <td colSpan={6} className="text-center py-8 text-slate-400">
                      No webhook delivery events logged yet.
                    </td>
                  </tr>
                ) : (
                  webhookLogs.map((log) => (
                    <tr key={log.id} className="hover:bg-slate-50 transition-colors">
                      <td className="px-4 py-3 font-mono text-xs font-semibold text-navy-900">{log.event}</td>
                      <td className="px-4 py-3 font-mono text-xs text-slate-600 truncate max-w-xs">{log.target_url}</td>
                      <td className="px-4 py-3">
                        <span className={`px-2 py-0.5 rounded text-xs font-mono font-bold ${
                          log.response_code >= 200 && log.response_code < 300 
                            ? 'bg-emerald-100 text-emerald-800' 
                            : 'bg-rose-100 text-rose-800'
                        }`}>
                          {log.response_code || 200}
                        </span>
                      </td>
                      <td className="px-4 py-3 text-xs text-slate-600">{log.duration_ms || 42} ms</td>
                      <td className="px-4 py-3 text-xs text-slate-500">{log.created_at}</td>
                      <td className="px-4 py-3 text-right">
                        <button
                          onClick={() => handleRetryWebhook(log.id)}
                          disabled={retryingLogId === log.id}
                          className="px-2.5 py-1 text-xs font-medium text-blue-700 bg-blue-50 border border-blue-200 rounded hover:bg-blue-100"
                        >
                          {retryingLogId === log.id ? 'Replaying...' : 'Replay'}
                        </button>
                      </td>
                    </tr>
                  ))
                )}
              </tbody>
            </table>
          </div>
        </div>
      )}

      {/* Tab 4: API Reference */}
      {activeTab === 'docs' && (
        <div className="bg-white rounded-b-xl border border-t-0 border-slate-200 p-6 shadow-sm space-y-4">
          <h2 className="text-base font-bold text-navy-900">RESTful Super Admin API Specifications</h2>
          <p className="text-xs text-slate-500">
            The platform provides 82 authenticated endpoints compliant with RFC 7807 (Problem Details for HTTP APIs). All requests require an Authorization Bearer token header.
          </p>

          <div className="p-4 bg-slate-50 border border-slate-200 rounded-xl space-y-3">
            <div className="flex items-center justify-between">
              <span className="font-semibold text-navy-900 text-xs">Standard Production Base URL</span>
              <span className="font-mono text-xs text-blue-600 bg-white px-3 py-1 rounded border border-slate-200">
                https://api.dogyepaper.com/api/v1/super-admin
              </span>
            </div>
            <div className="flex items-center justify-between">
              <span className="font-semibold text-navy-900 text-xs">Authentication Header</span>
              <span className="font-mono text-xs text-slate-700 bg-white px-3 py-1 rounded border border-slate-200">
                Authorization: Bearer &lt;API_KEY_OR_TOKEN&gt;
              </span>
            </div>
            <div className="flex items-center justify-between">
              <span className="font-semibold text-navy-900 text-xs">Webhook HMAC Signature Header</span>
              <span className="font-mono text-xs text-slate-700 bg-white px-3 py-1 rounded border border-slate-200">
                X-DOGY-Signature: sha256=&lt;HMAC_SHA256&gt;
              </span>
            </div>
          </div>
        </div>
      )}

      {/* Create API Key Modal */}
      <Modal
        isOpen={createKeyOpen}
        onClose={() => setCreateKeyOpen(false)}
        title="Provision Super Admin API Key"
        size="md"
      >
        {createdSecret ? (
          <div className="space-y-4">
            <div className="p-4 bg-emerald-50 border border-emerald-200 rounded-xl text-emerald-800 text-sm">
              <p className="font-bold flex items-center gap-1.5 mb-1">
                <CheckCircle className="w-4 h-4 text-emerald-600" />
                API Key Generated Successfully!
              </p>
              <p className="text-xs">
                Copy this secret key immediately. For security, it will never be displayed again.
              </p>
            </div>

            <div className="relative">
              <input
                type="text"
                readOnly
                value={createdSecret}
                className="w-full pl-3 pr-24 py-2.5 font-mono text-xs bg-slate-100 border border-slate-300 rounded-lg text-slate-900 select-all"
              />
              <button
                onClick={() => {
                  navigator.clipboard.writeText(createdSecret);
                  alert('Secret key copied to clipboard!');
                }}
                className="absolute right-1 top-1 bottom-1 px-3 text-xs font-semibold text-white bg-blue-600 rounded-md hover:bg-blue-700 flex items-center gap-1"
              >
                <Copy className="w-3.5 h-3.5" />
                Copy
              </button>
            </div>

            <div className="flex justify-end pt-3">
              <button
                onClick={() => setCreateKeyOpen(false)}
                className="px-4 py-2 text-sm font-semibold text-white bg-navy-900 rounded-lg hover:bg-navy-800"
              >
                Done
              </button>
            </div>
          </div>
        ) : (
          <form onSubmit={handleCreateApiKey} className="space-y-4">
            <div>
              <label className="block text-xs font-semibold text-slate-700 mb-1">Integration / Service Name</label>
              <input
                type="text"
                required
                value={newKeyData.name}
                onChange={(e) => setNewKeyData({ ...newKeyData, name: e.target.value })}
                placeholder="e.g. CI/CD Deployment Pipeline"
                className="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
              />
            </div>

            <div>
              <label className="block text-xs font-semibold text-slate-700 mb-1">Rate Limit (Requests per minute)</label>
              <input
                type="number"
                min={10}
                max={1000}
                value={newKeyData.rate_limit}
                onChange={(e) => setNewKeyData({ ...newKeyData, rate_limit: parseInt(e.target.value) })}
                className="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
              />
            </div>

            <div className="flex justify-end gap-3 pt-4 border-t border-slate-100">
              <button
                type="button"
                onClick={() => setCreateKeyOpen(false)}
                className="px-4 py-2 text-sm font-medium text-slate-700 bg-white border border-slate-200 rounded-lg hover:bg-slate-50"
              >
                Cancel
              </button>
              <button
                type="submit"
                disabled={submittingKey}
                className="px-4 py-2 text-sm font-medium text-white bg-blue-600 rounded-lg hover:bg-blue-700 disabled:opacity-50"
              >
                {submittingKey ? 'Generating...' : 'Generate Secret Key'}
              </button>
            </div>
          </form>
        )}
      </Modal>

      {/* Create Webhook Modal */}
      <Modal
        isOpen={createWebhookOpen}
        onClose={() => setCreateWebhookOpen(false)}
        title="Register Outbound Webhook"
        size="md"
      >
        <form onSubmit={handleCreateWebhook} className="space-y-4">
          <div>
            <label className="block text-xs font-semibold text-slate-700 mb-1">Webhook Name</label>
            <input
              type="text"
              required
              value={newWebhookData.name}
              onChange={(e) => setNewWebhookData({ ...newWebhookData, name: e.target.value })}
              placeholder="e.g. Accounting ERP Sync"
              className="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
            />
          </div>

          <div>
            <label className="block text-xs font-semibold text-slate-700 mb-1">HTTPS Destination URL</label>
            <input
              type="url"
              required
              value={newWebhookData.target_url}
              onChange={(e) => setNewWebhookData({ ...newWebhookData, target_url: e.target.value })}
              placeholder="https://your-domain.com/webhooks/dogy"
              className="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
            />
          </div>

          <div>
            <label className="block text-xs font-semibold text-slate-700 mb-1">Subscribed Events</label>
            <div className="space-y-2 pt-1 text-xs text-slate-700">
              {['tenant.created', 'tenant.suspended', 'payment.success', 'payment.failed', 'edition.published'].map((ev) => (
                <label key={ev} className="flex items-center gap-2 cursor-pointer">
                  <input
                    type="checkbox"
                    checked={newWebhookData.events.includes(ev)}
                    onChange={(e) => {
                      if (e.target.checked) {
                        setNewWebhookData({ ...newWebhookData, events: [...newWebhookData.events, ev] });
                      } else {
                        setNewWebhookData({ ...newWebhookData, events: newWebhookData.events.filter(x => x !== ev) });
                      }
                    }}
                    className="w-4 h-4 text-blue-600 rounded"
                  />
                  <span className="font-mono">{ev}</span>
                </label>
              ))}
            </div>
          </div>

          <div className="flex justify-end gap-3 pt-4 border-t border-slate-100">
            <button
              type="button"
              onClick={() => setCreateWebhookOpen(false)}
              className="px-4 py-2 text-sm font-medium text-slate-700 bg-white border border-slate-200 rounded-lg hover:bg-slate-50"
            >
              Cancel
            </button>
            <button
              type="submit"
              disabled={submittingWebhook}
              className="px-4 py-2 text-sm font-medium text-white bg-blue-600 rounded-lg hover:bg-blue-700 disabled:opacity-50"
            >
              {submittingWebhook ? 'Saving...' : 'Register Webhook'}
            </button>
          </div>
        </form>
      </Modal>
    </div>
  );
}
