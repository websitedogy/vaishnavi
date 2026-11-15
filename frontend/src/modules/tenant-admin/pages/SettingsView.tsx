import React, { useEffect, useState } from 'react';
import {
  Settings,
  Save,
  CheckCircle2,
  AlertCircle,
  RefreshCw,
  Clock,
  Globe,
  Sliders,
  Shield,
} from 'lucide-react';
import { settingsApi } from '../services/api';
import { TenantSettings } from '../types';

export const SettingsView: React.FC = () => {
  const [settings, setSettings] = useState<Partial<TenantSettings>>({
    default_timezone: 'Asia/Kolkata',
    default_language: 'en',
    default_publish_time: '06:00',
    auto_publish_on_upload: false,
    enable_watermark: false,
    watermark_text: '',
    retention_days: 365,
  });

  const [loading, setLoading] = useState(true);
  const [saving, setSaving] = useState(false);
  const [success, setSuccess] = useState<string | null>(null);
  const [error, setError] = useState<string | null>(null);

  const fetchSettings = async () => {
    setLoading(true);
    try {
      const res = await settingsApi.get();
      if (res.data) setSettings(res.data);
    } catch (err: any) {
      console.error('Failed to load settings:', err);
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => {
    fetchSettings();
  }, []);

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    setSaving(true);
    setSuccess(null);
    setError(null);
    try {
      const res = await settingsApi.update(settings);
      if (res.data) setSettings(res.data);
      setSuccess('Publishing preferences and system settings updated successfully.');
      setTimeout(() => setSuccess(null), 4000);
    } catch (err: any) {
      setError(err?.response?.data?.message || 'Failed to update settings.');
    } finally {
      setSaving(false);
    }
  };

  if (loading) {
    return (
      <div className="flex flex-col items-center justify-center min-h-[350px] space-y-3">
        <RefreshCw className="w-8 h-8 text-primary-600 animate-spin" />
        <p className="text-sm font-medium text-slate-500">Loading publication settings...</p>
      </div>
    );
  }

  return (
    <div className="max-w-4xl mx-auto space-y-6">
      {/* Header */}
      <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
          <h1 className="text-xl font-bold text-navy-950 flex items-center gap-2">
            <Settings className="w-6 h-6 text-primary-600" />
            Workspace & Publishing Preferences
          </h1>
          <p className="text-xs text-slate-500 mt-1">
            Configure newsroom release automation, default publication timezones, and edition retention policies
          </p>
        </div>

        <button
          onClick={handleSubmit}
          disabled={saving}
          className="px-5 py-2.5 bg-primary-600 hover:bg-primary-700 text-white rounded-xl text-xs font-semibold shadow-xs transition flex items-center gap-2 disabled:opacity-50"
        >
          <Save className="w-4 h-4" />
          {saving ? 'Saving...' : 'Save Preferences'}
        </button>
      </div>

      {success && (
        <div className="p-3.5 bg-emerald-50 border border-emerald-200 text-emerald-800 text-xs font-medium rounded-xl flex items-center gap-2">
          <CheckCircle2 className="w-4 h-4 text-emerald-600 flex-shrink-0" />
          {success}
        </div>
      )}

      {error && (
        <div className="p-3.5 bg-rose-50 border border-rose-200 text-rose-800 text-xs font-medium rounded-xl flex items-center gap-2">
          <AlertCircle className="w-4 h-4 text-rose-600 flex-shrink-0" />
          {error}
        </div>
      )}

      <form onSubmit={handleSubmit} className="space-y-6">
        {/* Defaults */}
        <div className="bg-white p-6 rounded-2xl border border-slate-200/80 shadow-xs space-y-4">
          <h2 className="text-sm font-bold text-navy-950 border-b border-slate-100 pb-2">
            Default Editorial Parameters
          </h2>

          <div className="grid grid-cols-1 sm:grid-cols-3 gap-4">
            <div>
              <label className="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1.5">
                Default Timezone
              </label>
              <select
                value={settings.default_timezone || 'Asia/Kolkata'}
                onChange={(e) => setSettings({ ...settings, default_timezone: e.target.value })}
                className="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs font-medium text-navy-950 focus:outline-hidden focus:ring-2 focus:ring-primary-500"
              >
                <option value="Asia/Kolkata">Asia/Kolkata (IST - UTC+05:30)</option>
                <option value="UTC">UTC (Universal Time)</option>
                <option value="America/New_York">America/New_York (EST/EDT)</option>
                <option value="Europe/London">Europe/London (GMT/BST)</option>
                <option value="Asia/Dubai">Asia/Dubai (GST - UTC+04:00)</option>
              </select>
            </div>

            <div>
              <label className="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1.5">
                Default Language
              </label>
              <select
                value={settings.default_language || 'en'}
                onChange={(e) => setSettings({ ...settings, default_language: e.target.value })}
                className="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs font-medium text-navy-950 focus:outline-hidden focus:ring-2 focus:ring-primary-500"
              >
                <option value="en">English (en)</option>
                <option value="te">Telugu (te)</option>
                <option value="hi">Hindi (hi)</option>
                <option value="ur">Urdu (ur)</option>
                <option value="ta">Tamil (ta)</option>
                <option value="kn">Kannada (kn)</option>
              </select>
            </div>

            <div>
              <label className="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1.5">
                Morning Release Target
              </label>
              <input
                type="time"
                value={settings.default_publish_time || '06:00'}
                onChange={(e) => setSettings({ ...settings, default_publish_time: e.target.value })}
                className="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs font-medium text-navy-950 focus:outline-hidden focus:ring-2 focus:ring-primary-500"
              />
            </div>
          </div>
        </div>

        {/* Automation & Processing */}
        <div className="bg-white p-6 rounded-2xl border border-slate-200/80 shadow-xs space-y-4">
          <h2 className="text-sm font-bold text-navy-950 border-b border-slate-100 pb-2">
            Ingestion & Automation Switches
          </h2>

          <div className="space-y-4">
            <label className="flex items-start gap-3 cursor-pointer">
              <input
                type="checkbox"
                checked={Boolean(settings.auto_publish_on_upload)}
                onChange={(e) => setSettings({ ...settings, auto_publish_on_upload: e.target.checked })}
                className="w-4 h-4 mt-0.5 rounded-sm text-primary-600 focus:ring-primary-500 border-slate-300"
              />
              <div>
                <span className="text-xs font-bold text-navy-950 block">
                  Automatic Instant Publish on PDF Extraction
                </span>
                <span className="text-xs text-slate-500">
                  When enabled, editions will automatically go live to the public reader the moment all pages finish rendering.
                </span>
              </div>
            </label>

            <div className="border-t border-slate-100 pt-4">
              <label className="flex items-start gap-3 cursor-pointer">
                <input
                  type="checkbox"
                  checked={Boolean(settings.enable_watermark)}
                  onChange={(e) => setSettings({ ...settings, enable_watermark: e.target.checked })}
                  className="w-4 h-4 mt-0.5 rounded-sm text-primary-600 focus:ring-primary-500 border-slate-300"
                />
                <div>
                  <span className="text-xs font-bold text-navy-950 block">
                    Enable Watermark Protection on Reader Pages
                  </span>
                  <span className="text-xs text-slate-500">
                    Overlays digital copyright or subscriber protection text across broadsheet pages.
                  </span>
                </div>
              </label>

              {settings.enable_watermark && (
                <div className="mt-3 pl-7">
                  <input
                    type="text"
                    value={settings.watermark_text || ''}
                    onChange={(e) => setSettings({ ...settings, watermark_text: e.target.value })}
                    placeholder="e.g. Telangana Samacharam Digital Edition – For Registered Reader Use Only"
                    className="w-full px-3.5 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs font-medium text-navy-950"
                  />
                </div>
              )}
            </div>
          </div>
        </div>

        {/* Retention & Archiving */}
        <div className="bg-white p-6 rounded-2xl border border-slate-200/80 shadow-xs space-y-4">
          <h2 className="text-sm font-bold text-navy-950 border-b border-slate-100 pb-2">
            Archival & Retention Policy
          </h2>

          <div>
            <label className="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1.5">
              Auto-Archive Editions Older Than (Days)
            </label>
            <input
              type="number"
              min={30}
              max={3650}
              value={settings.retention_days || 365}
              onChange={(e) => setSettings({ ...settings, retention_days: Number(e.target.value) })}
              className="w-full sm:w-64 px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs font-medium text-navy-950 focus:outline-hidden focus:ring-2 focus:ring-primary-500"
            />
            <p className="text-[11px] text-slate-500 mt-1">
              Editions older than this will be moved to cold archive to optimize public reader directory performance.
            </p>
          </div>
        </div>
      </form>
    </div>
  );
};
