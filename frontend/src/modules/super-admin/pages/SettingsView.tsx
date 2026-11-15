import React, { useState, useEffect } from 'react';
import { 
  Settings, Save, Globe, Cloud, CreditCard, Shield, 
  CheckCircle, RefreshCw, AlertCircle
} from 'lucide-react';
import { superAdminApi } from '../services/api';

export default function SettingsView() {
  const [activeTab, setActiveTab] = useState<'general' | 'cloud' | 'payments' | 'security'>('general');
  const [loading, setLoading] = useState(true);
  const [saving, setSaving] = useState(false);
  const [feedback, setFeedback] = useState<string | null>(null);

  const [settings, setSettings] = useState<Record<string, any>>({
    // General
    platform_name: 'DOGY ePAPER SaaS',
    support_email: 'support@dogyepaper.com',
    support_phone: '+91 98765 43210',
    default_timezone: 'Asia/Kolkata',
    default_currency: 'INR',
    maintenance_mode: 'false',

    // Cloud & Infrastructure
    aws_region: 'ap-south-1',
    s3_bucket_name: 'dogy-epaper-storage',
    sqs_queue_name: 'dogy-pdf-processing-queue',
    cloudfront_domain: 'd2xyz987.cloudfront.net',
    max_pdf_concurrency: '32',

    // Payment Gateways
    razorpay_key_id: 'rzp_live_k823901kd83',
    razorpay_webhook_secret: '••••••••••••••••',
    stripe_mode: 'live',
    stripe_publishable_key: 'pk_live_891723491823',

    // Security & Policies
    session_timeout_minutes: '60',
    enforce_admin_2fa: 'true',
    max_login_attempts: '5',
    password_expiry_days: '90',
  });

  const loadData = async () => {
    try {
      setLoading(true);
      const res = await superAdminApi.settings.getSettings();
      if (res && Object.keys(res).length > 0) {
        setSettings((prev) => ({ ...prev, ...res }));
      }
    } catch (err: any) {
      console.error('Failed to load settings', err);
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => {
    loadData();
  }, []);

  const handleSave = async (e: React.FormEvent) => {
    e.preventDefault();
    try {
      setSaving(true);
      await superAdminApi.settings.updateSettings(settings);
      setFeedback('Platform settings updated and synced across AWS clusters.');
    } catch (err: any) {
      alert(err.response?.data?.message || 'Failed to update platform settings');
    } finally {
      setSaving(false);
    }
  };

  const handleChange = (key: string, val: string) => {
    setSettings((prev) => ({ ...prev, [key]: val }));
  };

  return (
    <div className="space-y-6">
      {/* Top Header */}
      <div className="flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
          <h1 className="text-2xl font-bold text-navy-900">Platform Global Settings</h1>
          <p className="text-sm text-slate-500 mt-1">
            Global SaaS configuration, AWS service references, payment gateway keys, and security baselines.
          </p>
        </div>

        <button
          onClick={handleSave}
          disabled={saving}
          className="flex items-center gap-2 px-4 py-2 text-sm font-semibold text-white bg-blue-600 rounded-lg hover:bg-blue-700 shadow-sm disabled:opacity-50 self-start md:self-auto"
        >
          <Save className="w-4 h-4" />
          {saving ? 'Saving...' : 'Save Configuration'}
        </button>
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

      {/* Tabs */}
      <div className="border-b border-slate-200 bg-white rounded-t-xl px-4 pt-2 shadow-sm">
        <div className="flex gap-8">
          <button
            onClick={() => setActiveTab('general')}
            className={`pb-3 text-sm font-semibold border-b-2 transition-colors flex items-center gap-2 ${
              activeTab === 'general' ? 'border-blue-600 text-blue-600' : 'border-transparent text-slate-500 hover:text-slate-800'
            }`}
          >
            <Globe className="w-4 h-4" />
            General & Operations
          </button>
          <button
            onClick={() => setActiveTab('cloud')}
            className={`pb-3 text-sm font-semibold border-b-2 transition-colors flex items-center gap-2 ${
              activeTab === 'cloud' ? 'border-blue-600 text-blue-600' : 'border-transparent text-slate-500 hover:text-slate-800'
            }`}
          >
            <Cloud className="w-4 h-4" />
            AWS & Storage Fleet
          </button>
          <button
            onClick={() => setActiveTab('payments')}
            className={`pb-3 text-sm font-semibold border-b-2 transition-colors flex items-center gap-2 ${
              activeTab === 'payments' ? 'border-blue-600 text-blue-600' : 'border-transparent text-slate-500 hover:text-slate-800'
            }`}
          >
            <CreditCard className="w-4 h-4" />
            Payment Gateways
          </button>
          <button
            onClick={() => setActiveTab('security')}
            className={`pb-3 text-sm font-semibold border-b-2 transition-colors flex items-center gap-2 ${
              activeTab === 'security' ? 'border-blue-600 text-blue-600' : 'border-transparent text-slate-500 hover:text-slate-800'
            }`}
          >
            <Shield className="w-4 h-4" />
            Security & Compliance
          </button>
        </div>
      </div>

      {/* Tab 1: General */}
      {activeTab === 'general' && (
        <div className="bg-white rounded-b-xl border border-t-0 border-slate-200 p-6 shadow-sm space-y-6">
          <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
              <label className="block text-xs font-semibold text-slate-700 mb-1">Platform Brand Name</label>
              <input
                type="text"
                value={settings.platform_name || ''}
                onChange={(e) => handleChange('platform_name', e.target.value)}
                className="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
              />
            </div>

            <div>
              <label className="block text-xs font-semibold text-slate-700 mb-1">Central Support Email</label>
              <input
                type="email"
                value={settings.support_email || ''}
                onChange={(e) => handleChange('support_email', e.target.value)}
                className="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
              />
            </div>

            <div>
              <label className="block text-xs font-semibold text-slate-700 mb-1">Support Phone Hotline</label>
              <input
                type="text"
                value={settings.support_phone || ''}
                onChange={(e) => handleChange('support_phone', e.target.value)}
                className="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
              />
            </div>

            <div>
              <label className="block text-xs font-semibold text-slate-700 mb-1">Primary Default Timezone</label>
              <select
                value={settings.default_timezone || 'Asia/Kolkata'}
                onChange={(e) => handleChange('default_timezone', e.target.value)}
                className="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
              >
                <option value="Asia/Kolkata">Asia/Kolkata (IST +05:30)</option>
                <option value="UTC">UTC (Universal Coordinated Time)</option>
                <option value="America/New_York">America/New_York (EST)</option>
              </select>
            </div>

            <div>
              <label className="block text-xs font-semibold text-slate-700 mb-1">Platform Base Currency</label>
              <select
                value={settings.default_currency || 'INR'}
                onChange={(e) => handleChange('default_currency', e.target.value)}
                className="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
              >
                <option value="INR">INR (₹ - Indian Rupee)</option>
                <option value="USD">USD ($ - US Dollar)</option>
                <option value="EUR">EUR (€ - Euro)</option>
              </select>
            </div>

            <div>
              <label className="block text-xs font-semibold text-slate-700 mb-1">Global Maintenance Mode</label>
              <select
                value={settings.maintenance_mode || 'false'}
                onChange={(e) => handleChange('maintenance_mode', e.target.value)}
                className="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
              >
                <option value="false">Disabled (Normal Operations)</option>
                <option value="true">Enabled (503 Scheduled Maintenance)</option>
              </select>
            </div>
          </div>
        </div>
      )}

      {/* Tab 2: AWS & Cloud */}
      {activeTab === 'cloud' && (
        <div className="bg-white rounded-b-xl border border-t-0 border-slate-200 p-6 shadow-sm space-y-6">
          <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
              <label className="block text-xs font-semibold text-slate-700 mb-1">Primary AWS Region</label>
              <input
                type="text"
                value={settings.aws_region || 'ap-south-1'}
                onChange={(e) => handleChange('aws_region', e.target.value)}
                className="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 font-mono"
              />
            </div>

            <div>
              <label className="block text-xs font-semibold text-slate-700 mb-1">S3 Production Bucket Name</label>
              <input
                type="text"
                value={settings.s3_bucket_name || 'dogy-epaper-storage'}
                onChange={(e) => handleChange('s3_bucket_name', e.target.value)}
                className="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 font-mono"
              />
            </div>

            <div>
              <label className="block text-xs font-semibold text-slate-700 mb-1">Amazon SQS PDF Queue URL / Name</label>
              <input
                type="text"
                value={settings.sqs_queue_name || 'dogy-pdf-processing-queue'}
                onChange={(e) => handleChange('sqs_queue_name', e.target.value)}
                className="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 font-mono"
              />
            </div>

            <div>
              <label className="block text-xs font-semibold text-slate-700 mb-1">CloudFront CDN Origin Domain</label>
              <input
                type="text"
                value={settings.cloudfront_domain || 'd2xyz987.cloudfront.net'}
                onChange={(e) => handleChange('cloudfront_domain', e.target.value)}
                className="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 font-mono"
              />
            </div>

            <div>
              <label className="block text-xs font-semibold text-slate-700 mb-1">Ghostscript Max Concurrency</label>
              <input
                type="number"
                min={1}
                max={64}
                value={settings.max_pdf_concurrency || '32'}
                onChange={(e) => handleChange('max_pdf_concurrency', e.target.value)}
                className="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
              />
            </div>
          </div>
        </div>
      )}

      {/* Tab 3: Payments */}
      {activeTab === 'payments' && (
        <div className="bg-white rounded-b-xl border border-t-0 border-slate-200 p-6 shadow-sm space-y-6">
          <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
              <label className="block text-xs font-semibold text-slate-700 mb-1">Razorpay Key ID</label>
              <input
                type="text"
                value={settings.razorpay_key_id || ''}
                onChange={(e) => handleChange('razorpay_key_id', e.target.value)}
                className="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 font-mono"
              />
            </div>

            <div>
              <label className="block text-xs font-semibold text-slate-700 mb-1">Razorpay Webhook Secret</label>
              <input
                type="password"
                value={settings.razorpay_webhook_secret || ''}
                onChange={(e) => handleChange('razorpay_webhook_secret', e.target.value)}
                className="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 font-mono"
              />
            </div>

            <div>
              <label className="block text-xs font-semibold text-slate-700 mb-1">Stripe Environment Mode</label>
              <select
                value={settings.stripe_mode || 'live'}
                onChange={(e) => handleChange('stripe_mode', e.target.value)}
                className="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
              >
                <option value="test">Test / Sandbox</option>
                <option value="live">Live Production</option>
              </select>
            </div>

            <div>
              <label className="block text-xs font-semibold text-slate-700 mb-1">Stripe Publishable Key</label>
              <input
                type="text"
                value={settings.stripe_publishable_key || ''}
                onChange={(e) => handleChange('stripe_publishable_key', e.target.value)}
                className="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 font-mono"
              />
            </div>
          </div>
        </div>
      )}

      {/* Tab 4: Security */}
      {activeTab === 'security' && (
        <div className="bg-white rounded-b-xl border border-t-0 border-slate-200 p-6 shadow-sm space-y-6">
          <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
              <label className="block text-xs font-semibold text-slate-700 mb-1">Inactivity Session Timeout (Minutes)</label>
              <input
                type="number"
                min={15}
                max={480}
                value={settings.session_timeout_minutes || '60'}
                onChange={(e) => handleChange('session_timeout_minutes', e.target.value)}
                className="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
              />
            </div>

            <div>
              <label className="block text-xs font-semibold text-slate-700 mb-1">Enforce 2-Factor Authentication (2FA)</label>
              <select
                value={settings.enforce_admin_2fa || 'true'}
                onChange={(e) => handleChange('enforce_admin_2fa', e.target.value)}
                className="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
              >
                <option value="true">Enforced for all Super Admins</option>
                <option value="false">Optional</option>
              </select>
            </div>

            <div>
              <label className="block text-xs font-semibold text-slate-700 mb-1">Max Login Attempts Before Lockout</label>
              <input
                type="number"
                min={3}
                max={10}
                value={settings.max_login_attempts || '5'}
                onChange={(e) => handleChange('max_login_attempts', e.target.value)}
                className="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
              />
            </div>

            <div>
              <label className="block text-xs font-semibold text-slate-700 mb-1">Password Rotation Policy (Days)</label>
              <input
                type="number"
                min={30}
                max={365}
                value={settings.password_expiry_days || '90'}
                onChange={(e) => handleChange('password_expiry_days', e.target.value)}
                className="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
              />
            </div>
          </div>
        </div>
      )}
    </div>
  );
}
