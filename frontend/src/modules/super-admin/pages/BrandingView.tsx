import React, { useState, useEffect } from 'react';
import { api } from '../services/api';
import { PlatformBranding, TenantBranding, Tenant } from '../types';
import { Palette, Eye, RotateCcw, Check, Sparkles, Sliders } from 'lucide-react';

export const BrandingView: React.FC = () => {
  const [activeTab, setActiveTab] = useState<'platform' | 'tenants'>('platform');
  const [platformBranding, setPlatformBranding] = useState<PlatformBranding | null>(null);
  const [tenants, setTenants] = useState<Tenant[]>([]);
  const [selectedTenantId, setSelectedTenantId] = useState<number | null>(null);
  const [tenantBranding, setTenantBranding] = useState<TenantBranding | null>(null);
  const [versions, setVersions] = useState<any[]>([]);
  const [isSaving, setIsSaving] = useState(false);
  const [saveSuccess, setSaveSuccess] = useState(false);

  useEffect(() => {
    api.branding.getPlatform().then((res) => setPlatformBranding(res));
    api.tenants.list({ per_page: 50 }).then((res: any) => {
      setTenants(res.data || []);
      if (res.data?.length > 0) {
        setSelectedTenantId(res.data[0].id);
      }
    });
  }, []);

  useEffect(() => {
    if (selectedTenantId) {
      api.branding.getTenant(selectedTenantId).then((res: any) => {
        setTenantBranding(res.branding);
        setVersions(res.versions || []);
      });
    }
  }, [selectedTenantId]);

  const handleSavePlatform = async (e: React.FormEvent) => {
    e.preventDefault();
    if (!platformBranding) return;
    setIsSaving(true);
    setSaveSuccess(false);
    try {
      const updated = await api.branding.updatePlatform(platformBranding);
      setPlatformBranding(updated);
      setSaveSuccess(true);
      setTimeout(() => setSaveSuccess(false), 3000);
    } catch (err) {
      alert('Failed to update platform branding');
    } finally {
      setIsSaving(false);
    }
  };

  const handleSaveTenant = async (e: React.FormEvent) => {
    e.preventDefault();
    if (!selectedTenantId || !tenantBranding) return;
    setIsSaving(true);
    setSaveSuccess(false);
    try {
      const updated = await api.branding.updateTenant(selectedTenantId, tenantBranding);
      setTenantBranding(updated);
      setSaveSuccess(true);
      setTimeout(() => setSaveSuccess(false), 3000);
      // Refresh versions
      api.branding.getTenant(selectedTenantId).then((res: any) => setVersions(res.versions || []));
    } catch (err) {
      alert('Failed to update tenant branding');
    } finally {
      setIsSaving(false);
    }
  };

  const handleRollback = async (versionNum: number) => {
    if (!selectedTenantId) return;
    if (!confirm(`Rollback tenant branding to version snapshot v${versionNum}?`)) return;
    try {
      const restored = await api.branding.rollbackTenant(selectedTenantId, versionNum);
      setTenantBranding(restored);
      api.branding.getTenant(selectedTenantId).then((res: any) => setVersions(res.versions || []));
    } catch (err) {
      alert('Rollback failed');
    }
  };

  return (
    <div className="space-y-6">
      {/* Header */}
      <div className="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
          <h2 className="text-xl font-bold text-slate-900 tracking-tight">White-Label & Dynamic Branding Engine</h2>
          <p className="text-xs text-slate-500 mt-0.5">
            Configure default and tenant-specific design systems, sanitized CSS, logos, and version history.
          </p>
        </div>
      </div>

      {/* Tabs */}
      <div className="flex space-x-2 border-b border-slate-200">
        <button
          onClick={() => setActiveTab('platform')}
          className={`px-4 py-2 text-xs font-bold border-b-2 transition-colors ${
            activeTab === 'platform' ? 'border-blue-600 text-blue-600' : 'border-transparent text-slate-500'
          }`}
        >
          Platform Default Branding
        </button>
        <button
          onClick={() => setActiveTab('tenants')}
          className={`px-4 py-2 text-xs font-bold border-b-2 transition-colors ${
            activeTab === 'tenants' ? 'border-blue-600 text-blue-600' : 'border-transparent text-slate-500'
          }`}
        >
          Tenant Publisher Branding
        </button>
      </div>

      {/* Platform Branding Form & Preview */}
      {activeTab === 'platform' && platformBranding && (
        <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
          <form onSubmit={handleSavePlatform} className="bg-white border border-slate-200 rounded-2xl p-6 space-y-4 text-xs shadow-2xs">
            <h3 className="font-bold text-sm text-slate-900">Platform Global Defaults</h3>

            <div>
              <label className="block font-medium text-slate-700 mb-1">Platform Brand Name</label>
              <input
                type="text"
                value={platformBranding.platform_name}
                onChange={(e) => setPlatformBranding({ ...platformBranding, platform_name: e.target.value })}
                className="w-full border border-slate-300 rounded-lg px-3 py-2 font-semibold"
              />
            </div>

            <div>
              <label className="block font-medium text-slate-700 mb-1">Tagline</label>
              <input
                type="text"
                value={platformBranding.tag_line || ''}
                onChange={(e) => setPlatformBranding({ ...platformBranding, tag_line: e.target.value })}
                className="w-full border border-slate-300 rounded-lg px-3 py-2"
              />
            </div>

            <div className="grid grid-cols-2 gap-4">
              <div>
                <label className="block font-medium text-slate-700 mb-1">Primary Color</label>
                <div className="flex items-center space-x-2">
                  <input
                    type="color"
                    value={platformBranding.primary_color}
                    onChange={(e) => setPlatformBranding({ ...platformBranding, primary_color: e.target.value })}
                    className="w-9 h-9 rounded-lg border border-slate-300 cursor-pointer p-0.5"
                  />
                  <input
                    type="text"
                    value={platformBranding.primary_color}
                    onChange={(e) => setPlatformBranding({ ...platformBranding, primary_color: e.target.value })}
                    className="w-full border border-slate-300 rounded-lg px-3 py-2 font-mono uppercase"
                  />
                </div>
              </div>

              <div>
                <label className="block font-medium text-slate-700 mb-1">Secondary Color</label>
                <div className="flex items-center space-x-2">
                  <input
                    type="color"
                    value={platformBranding.secondary_color}
                    onChange={(e) => setPlatformBranding({ ...platformBranding, secondary_color: e.target.value })}
                    className="w-9 h-9 rounded-lg border border-slate-300 cursor-pointer p-0.5"
                  />
                  <input
                    type="text"
                    value={platformBranding.secondary_color}
                    onChange={(e) => setPlatformBranding({ ...platformBranding, secondary_color: e.target.value })}
                    className="w-full border border-slate-300 rounded-lg px-3 py-2 font-mono uppercase"
                  />
                </div>
              </div>
            </div>

            <div>
              <label className="block font-medium text-slate-700 mb-1">Custom CSS (Strictly Sanitized)</label>
              <textarea
                rows={5}
                value={platformBranding.custom_css || ''}
                onChange={(e) => setPlatformBranding({ ...platformBranding, custom_css: e.target.value })}
                className="w-full border border-slate-300 rounded-lg p-3 font-mono text-[11px]"
                placeholder="/* Custom CSS overrides */"
              />
            </div>

            <div className="flex items-center justify-between pt-3 border-t border-slate-100">
              {saveSuccess && (
                <span className="text-emerald-600 font-bold flex items-center space-x-1">
                  <Check className="w-4 h-4" />
                  <span>Branding published!</span>
                </span>
              )}
              <button
                type="submit"
                disabled={isSaving}
                className="ml-auto px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white font-bold rounded-lg shadow-xs"
              >
                {isSaving ? 'Publishing...' : 'Publish Platform Branding'}
              </button>
            </div>
          </form>

          {/* Live Mockup Preview */}
          <div className="bg-slate-100 border border-slate-200 rounded-2xl p-6 flex flex-col items-center justify-center">
            <span className="text-[11px] font-bold text-slate-400 uppercase tracking-widest mb-3 flex items-center space-x-1">
              <Eye className="w-3.5 h-3.5" />
              <span>Real-Time Reader Shell Mockup</span>
            </span>

            {/* Reader Simulated Chrome */}
            <div className="w-full max-w-sm bg-white rounded-xl shadow-lg border border-slate-300 overflow-hidden">
              <div
                className="p-3 text-white flex items-center justify-between"
                style={{ backgroundColor: platformBranding.primary_color }}
              >
                <div className="flex items-center space-x-2">
                  <div className="w-6 h-6 rounded bg-white/20 font-black flex items-center justify-center text-xs">D</div>
                  <span className="font-bold text-xs">{platformBranding.platform_name}</span>
                </div>
                <div className="text-[10px] opacity-80">14 Nov 2026</div>
              </div>
              <div className="p-8 text-center text-slate-400 space-y-2">
                <div className="w-24 h-32 bg-slate-100 border border-dashed border-slate-300 rounded-md mx-auto flex items-center justify-center text-[10px]">
                  PDF Page 1
                </div>
                <p className="text-[11px] text-slate-500">Newspaper Body Remains Pure & Unaltered</p>
              </div>
              <div
                className="p-2.5 text-center text-[10px] text-white"
                style={{ backgroundColor: platformBranding.secondary_color }}
              >
                White-Label Surrounding Navigation
              </div>
            </div>
          </div>
        </div>
      )}

      {/* Tenant Branding */}
      {activeTab === 'tenants' && (
        <div className="space-y-6">
          <div className="bg-white border border-slate-200 rounded-xl p-3 flex items-center space-x-3 text-xs max-w-md">
            <label className="font-bold text-slate-700 flex-shrink-0">Select Publisher:</label>
            <select
              value={selectedTenantId || 0}
              onChange={(e) => setSelectedTenantId(Number(e.target.value))}
              className="w-full border border-slate-200 rounded-lg p-1.5 bg-white font-medium"
            >
              {tenants.map((t) => (
                <option key={t.id} value={t.id}>
                  {t.name} (#{t.id})
                </option>
              ))}
            </select>
          </div>

          {tenantBranding && (
            <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
              <form onSubmit={handleSaveTenant} className="bg-white border border-slate-200 rounded-2xl p-6 space-y-4 text-xs shadow-2xs">
                <h3 className="font-bold text-sm text-slate-900">Custom Publisher Branding</h3>

                <div>
                  <label className="block font-medium text-slate-700 mb-1">Newspaper Masthead Name</label>
                  <input
                    type="text"
                    value={tenantBranding.newspaper_name}
                    onChange={(e) => setTenantBranding({ ...tenantBranding, newspaper_name: e.target.value })}
                    className="w-full border border-slate-300 rounded-lg px-3 py-2 font-bold"
                  />
                </div>

                <div className="grid grid-cols-2 gap-4">
                  <div>
                    <label className="block font-medium text-slate-700 mb-1">Primary Color</label>
                    <input
                      type="color"
                      value={tenantBranding.primary_color}
                      onChange={(e) => setTenantBranding({ ...tenantBranding, primary_color: e.target.value })}
                      className="w-9 h-9 rounded-lg border border-slate-300 cursor-pointer p-0.5"
                    />
                  </div>
                  <div>
                    <label className="block font-medium text-slate-700 mb-1">Secondary Color</label>
                    <input
                      type="color"
                      value={tenantBranding.secondary_color}
                      onChange={(e) => setTenantBranding({ ...tenantBranding, secondary_color: e.target.value })}
                      className="w-9 h-9 rounded-lg border border-slate-300 cursor-pointer p-0.5"
                    />
                  </div>
                </div>

                <div className="flex items-center justify-between pt-3 border-t border-slate-100">
                  <span className="text-[11px] text-slate-400 font-mono">Current Version: v{tenantBranding.version}</span>
                  <button
                    type="submit"
                    disabled={isSaving}
                    className="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white font-bold rounded-lg shadow-xs"
                  >
                    Save & Create Snapshot v{tenantBranding.version + 1}
                  </button>
                </div>
              </form>

              {/* Version History & Rollback */}
              <div className="bg-white border border-slate-200 rounded-2xl p-6 space-y-3 text-xs shadow-2xs">
                <h4 className="font-bold text-slate-900 flex items-center space-x-1.5">
                  <RotateCcw className="w-4 h-4 text-slate-500" />
                  <span>Branding Version History & Rollback</span>
                </h4>
                <div className="divide-y divide-slate-100 max-h-80 overflow-y-auto">
                  {versions.length === 0 ? (
                    <p className="text-slate-400 py-6 text-center">No snapshots recorded yet.</p>
                  ) : (
                    versions.map((v) => (
                      <div key={v.id} className="py-2.5 flex items-center justify-between">
                        <div>
                          <span className="font-bold font-mono text-blue-600 block">Snapshot v{v.version_number}</span>
                          <span className="text-[11px] text-slate-400">{new Date(v.published_at).toLocaleString()}</span>
                          <p className="text-[11px] text-slate-600">{v.change_summary}</p>
                        </div>
                        {v.version_number !== tenantBranding.version && (
                          <button
                            onClick={() => handleRollback(v.version_number)}
                            className="px-2.5 py-1 text-[11px] font-semibold text-blue-600 bg-blue-50 hover:bg-blue-100 rounded-lg"
                          >
                            Rollback
                          </button>
                        )}
                      </div>
                    ))
                  )}
                </div>
              </div>
            </div>
          )}
        </div>
      )}
    </div>
  );
};
