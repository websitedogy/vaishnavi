import React, { useEffect, useState } from 'react';
import {
  Palette,
  Save,
  CheckCircle2,
  AlertCircle,
  RefreshCw,
  Eye,
  Type,
  Layout,
  ShieldAlert,
} from 'lucide-react';
import { brandingApi } from '../services/api';
import { BrandingConfiguration } from '../types';

export const BrandingView: React.FC = () => {
  const [branding, setBranding] = useState<Partial<BrandingConfiguration>>({
    primary_color: '#2563EB',
    secondary_color: '#0EA5E9',
    background_color: '#F8FAFC',
    text_color: '#0F172A',
    font_family: 'Inter',
    header_style: 'standard',
    custom_css: '',
  });

  const [loading, setLoading] = useState(true);
  const [saving, setSaving] = useState(false);
  const [success, setSuccess] = useState<string | null>(null);
  const [error, setError] = useState<string | null>(null);

  const fetchBranding = async () => {
    setLoading(true);
    try {
      const res = await brandingApi.get();
      if (res.data) setBranding(res.data);
    } catch (err: any) {
      console.error('Failed to load branding:', err);
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => {
    fetchBranding();
  }, []);

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    setSaving(true);
    setSuccess(null);
    setError(null);
    try {
      const res = await brandingApi.update(branding);
      if (res.data) setBranding(res.data);
      setSuccess('Branding configuration saved and reader stylesheet compiled!');
      setTimeout(() => setSuccess(null), 4000);
    } catch (err: any) {
      setError(err?.response?.data?.message || 'Failed to update branding.');
    } finally {
      setSaving(false);
    }
  };

  if (loading) {
    return (
      <div className="flex flex-col items-center justify-center min-h-[350px] space-y-3">
        <RefreshCw className="w-8 h-8 text-primary-600 animate-spin" />
        <p className="text-sm font-medium text-slate-500">Loading reader branding config...</p>
      </div>
    );
  }

  return (
    <div className="space-y-6">
      {/* Header */}
      <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
          <h1 className="text-xl font-bold text-navy-950 flex items-center gap-2">
            <Palette className="w-6 h-6 text-primary-600" />
            Branding, Styling & Theme Engine
          </h1>
          <p className="text-xs text-slate-500 mt-1">
            Customize the public digital reader theme, corporate color accents, typography, and layout chrome
          </p>
        </div>

        <button
          onClick={handleSubmit}
          disabled={saving}
          className="px-5 py-2.5 bg-primary-600 hover:bg-primary-700 text-white rounded-xl text-xs font-semibold shadow-xs transition flex items-center gap-2 disabled:opacity-50"
        >
          <Save className="w-4 h-4" />
          {saving ? 'Saving...' : 'Save Branding Changes'}
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

      <div className="grid grid-cols-1 lg:grid-cols-12 gap-6">
        {/* Left Form: Theme & Colors */}
        <div className="lg:col-span-7 space-y-6">
          <div className="bg-white p-6 rounded-2xl border border-slate-200/80 shadow-xs space-y-5">
            <h2 className="text-sm font-bold text-navy-950 border-b border-slate-100 pb-2">
              Color Palette Settings
            </h2>

            <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
              <div>
                <label className="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1.5">
                  Primary Accent Color
                </label>
                <div className="flex items-center gap-2">
                  <input
                    type="color"
                    value={branding.primary_color || '#2563EB'}
                    onChange={(e) => setBranding({ ...branding, primary_color: e.target.value })}
                    className="w-10 h-10 p-0.5 rounded-xl border border-slate-300 cursor-pointer"
                  />
                  <input
                    type="text"
                    value={branding.primary_color || '#2563EB'}
                    onChange={(e) => setBranding({ ...branding, primary_color: e.target.value })}
                    className="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs font-mono uppercase"
                  />
                </div>
              </div>

              <div>
                <label className="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1.5">
                  Secondary Color
                </label>
                <div className="flex items-center gap-2">
                  <input
                    type="color"
                    value={branding.secondary_color || '#0EA5E9'}
                    onChange={(e) => setBranding({ ...branding, secondary_color: e.target.value })}
                    className="w-10 h-10 p-0.5 rounded-xl border border-slate-300 cursor-pointer"
                  />
                  <input
                    type="text"
                    value={branding.secondary_color || '#0EA5E9'}
                    onChange={(e) => setBranding({ ...branding, secondary_color: e.target.value })}
                    className="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs font-mono uppercase"
                  />
                </div>
              </div>

              <div>
                <label className="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1.5">
                  Reader Canvas Background
                </label>
                <div className="flex items-center gap-2">
                  <input
                    type="color"
                    value={branding.background_color || '#F8FAFC'}
                    onChange={(e) => setBranding({ ...branding, background_color: e.target.value })}
                    className="w-10 h-10 p-0.5 rounded-xl border border-slate-300 cursor-pointer"
                  />
                  <input
                    type="text"
                    value={branding.background_color || '#F8FAFC'}
                    onChange={(e) => setBranding({ ...branding, background_color: e.target.value })}
                    className="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs font-mono uppercase"
                  />
                </div>
              </div>

              <div>
                <label className="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1.5">
                  Reader Text Color
                </label>
                <div className="flex items-center gap-2">
                  <input
                    type="color"
                    value={branding.text_color || '#0F172A'}
                    onChange={(e) => setBranding({ ...branding, text_color: e.target.value })}
                    className="w-10 h-10 p-0.5 rounded-xl border border-slate-300 cursor-pointer"
                  />
                  <input
                    type="text"
                    value={branding.text_color || '#0F172A'}
                    onChange={(e) => setBranding({ ...branding, text_color: e.target.value })}
                    className="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs font-mono uppercase"
                  />
                </div>
              </div>
            </div>
          </div>

          {/* Typography & Chrome */}
          <div className="bg-white p-6 rounded-2xl border border-slate-200/80 shadow-xs space-y-4">
            <h2 className="text-sm font-bold text-navy-950 border-b border-slate-100 pb-2">
              Typography & Reader Layout
            </h2>

            <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
              <div>
                <label className="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1.5">
                  Masthead Font Family
                </label>
                <select
                  value={branding.font_family || 'Inter'}
                  onChange={(e) => setBranding({ ...branding, font_family: e.target.value })}
                  className="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs font-medium text-navy-950 focus:outline-hidden focus:ring-2 focus:ring-primary-500"
                >
                  <option value="Inter">Inter (Modern Clean Sans)</option>
                  <option value="Merriweather">Merriweather (Classic Editorial Serif)</option>
                  <option value="Roboto Serif">Roboto Serif (Contemporary Editorial)</option>
                  <option value="Playfair Display">Playfair Display (High Contrast Serif)</option>
                  <option value="Cinzel">Cinzel (Formal Broadsheet)</option>
                </select>
              </div>

              <div>
                <label className="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1.5">
                  Reader Header Bar Style
                </label>
                <select
                  value={branding.header_style || 'standard'}
                  onChange={(e) => setBranding({ ...branding, header_style: e.target.value })}
                  className="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs font-medium text-navy-950 focus:outline-hidden focus:ring-2 focus:ring-primary-500"
                >
                  <option value="standard">Standard Navbar with Date & Quick Page Dropdown</option>
                  <option value="minimal">Minimal Floating Toolbar</option>
                  <option value="full_masthead">Full Newspaper Masthead Banner</option>
                </select>
              </div>
            </div>
          </div>

          {/* Custom CSS */}
          <div className="bg-white p-6 rounded-2xl border border-slate-200/80 shadow-xs space-y-3">
            <div className="flex items-center justify-between border-b border-slate-100 pb-2">
              <h2 className="text-sm font-bold text-navy-950">
                Custom Reader Stylesheet (CSS)
              </h2>
              <span className="text-[11px] font-mono text-slate-400">Scoped to .epaper-reader</span>
            </div>

            <div className="p-3 bg-amber-50 border border-amber-200 rounded-xl flex items-start gap-2.5 text-xs text-amber-800">
              <ShieldAlert className="w-4 h-4 text-amber-600 flex-shrink-0 mt-0.5" />
              <p>
                <strong>Security Sanitizer Active:</strong> Custom styles are automatically scanned on save. External script tags, expressions, and remote imports are strictly stripped.
              </p>
            </div>

            <textarea
              rows={4}
              value={branding.custom_css || ''}
              onChange={(e) => setBranding({ ...branding, custom_css: e.target.value })}
              placeholder="/* Add custom scoped CSS overrides here... */&#10;.epaper-reader-nav { backdrop-filter: blur(8px); }"
              className="w-full px-3.5 py-2.5 bg-slate-900 text-slate-100 font-mono text-xs rounded-xl focus:outline-hidden focus:ring-2 focus:ring-primary-500 resize-none"
            />
          </div>
        </div>

        {/* Right Panel: Live Reader Mockup Preview */}
        <div className="lg:col-span-5">
          <div className="sticky top-6 bg-white p-6 rounded-2xl border border-slate-200/80 shadow-xs space-y-4">
            <div className="flex items-center justify-between border-b border-slate-100 pb-2">
              <h3 className="font-bold text-navy-950 text-sm flex items-center gap-1.5">
                <Eye className="w-4 h-4 text-primary-600" />
                Live Reader Sandbox Preview
              </h3>
              <span className="text-[10px] font-bold text-primary-700 bg-primary-50 px-2 py-0.5 rounded-full uppercase">
                Realtime
              </span>
            </div>

            {/* Mock Reader Canvas */}
            <div
              style={{
                backgroundColor: branding.background_color || '#F8FAFC',
                color: branding.text_color || '#0F172A',
                fontFamily: branding.font_family || 'Inter',
              }}
              className="rounded-xl border border-slate-200 shadow-inner overflow-hidden min-h-[460px] flex flex-col justify-between"
            >
              {/* Mock Header */}
              <div
                style={{ backgroundColor: branding.primary_color || '#2563EB' }}
                className="px-4 py-3 text-white flex items-center justify-between shadow-xs"
              >
                <div className="flex items-center gap-2">
                  <span className="font-black text-sm tracking-tight">TELANGANA SAMACHARAM</span>
                </div>
                <div className="flex items-center gap-2 text-[10px] font-medium opacity-90">
                  <span>15 Nov 2026</span>
                  <span className="bg-white/20 px-1.5 py-0.5 rounded-sm">Page 1 / 16</span>
                </div>
              </div>

              {/* Mock Newspaper Page Broadsheet */}
              <div className="p-4 flex-1 flex flex-col items-center justify-center">
                <div className="w-56 h-80 bg-white shadow-xl rounded-xs border border-slate-200 p-3 space-y-2 flex flex-col justify-between">
                  <div>
                    <div className="border-b-2 border-slate-900 pb-1 text-center">
                      <span className="font-black text-[11px] block tracking-wide">
                        THE DAILY CHRONICLE
                      </span>
                      <span className="text-[8px] text-slate-500">
                        VOL. 42 NO. 129 • HYDERABAD EDITION
                      </span>
                    </div>

                    <div className="mt-2 space-y-1">
                      <div
                        style={{ color: branding.primary_color || '#2563EB' }}
                        className="text-[10px] font-bold leading-tight"
                      >
                        Global Tech Summit Opens Today With AI Transformation Keynotes
                      </div>
                      <div className="h-14 bg-slate-100 rounded-xs flex items-center justify-center text-[9px] text-slate-400">
                        Broadsheet Photo
                      </div>
                      <p className="text-[7px] text-slate-600 line-clamp-3 mt-1 leading-snug">
                        Over twenty thousand delegates have gathered across Hyderabad international convention center to review digital newspaper publishing technologies...
                      </p>
                    </div>
                  </div>

                  <div className="pt-2 border-t border-slate-200 flex justify-between text-[7px] text-slate-400 font-mono">
                    <span>Page 1</span>
                    <span>City Broadsheet</span>
                  </div>
                </div>
              </div>

              {/* Mock Bottom Toolbar */}
              <div
                style={{ backgroundColor: branding.secondary_color || '#0EA5E9' }}
                className="px-4 py-2 text-white flex items-center justify-between text-[11px] font-semibold"
              >
                <span>Zoom: 100%</span>
                <span className="bg-black/20 px-2 py-0.5 rounded-full text-[10px]">
                  Turn Page &rarr;
                </span>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  );
};
