import React, { useEffect, useState } from 'react';
import {
  LayoutTemplate,
  CheckCircle2,
  RefreshCw,
  Sparkles,
  Layers,
  ArrowRight,
  Sliders,
} from 'lucide-react';
import { templatesApi } from '../services/api';
import { NewspaperTemplate } from '../types';

interface TemplatesViewProps {
  onNavigateToBranding: () => void;
}

export const TemplatesView: React.FC<TemplatesViewProps> = ({
  onNavigateToBranding,
}) => {
  const [templates, setTemplates] = useState<NewspaperTemplate[]>([]);
  const [loading, setLoading] = useState(true);
  const [applyingId, setApplyingId] = useState<number | null>(null);
  const [success, setSuccess] = useState<string | null>(null);

  const fetchTemplates = async () => {
    setLoading(true);
    try {
      const res = await templatesApi.getAll();
      setTemplates(res.data?.data || res.data || []);
    } catch (err: any) {
      console.error('Failed to load templates:', err);
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => {
    fetchTemplates();
  }, []);

  const handleApply = async (templateId: number) => {
    setApplyingId(templateId);
    setSuccess(null);
    try {
      await templatesApi.apply(templateId);
      setSuccess('Template design applied successfully! Your branding settings have been updated.');
      setTimeout(() => setSuccess(null), 4000);
      await fetchTemplates();
    } catch (err: any) {
      alert(err?.response?.data?.message || 'Failed to apply template.');
    } finally {
      setApplyingId(null);
    }
  };

  return (
    <div className="space-y-6">
      {/* Header */}
      <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
          <h1 className="text-xl font-bold text-navy-950 flex items-center gap-2">
            <LayoutTemplate className="w-6 h-6 text-primary-600" />
            Newspaper Appearance Templates
          </h1>
          <p className="text-xs text-slate-500 mt-1">
            Choose from curated editorial design presets. Applying a preset configures colors, typography, and reader navigation chrome.
          </p>
        </div>

        <button
          onClick={onNavigateToBranding}
          className="px-4 py-2.5 rounded-xl border border-slate-200 bg-white hover:bg-slate-50 text-slate-700 text-xs font-semibold shadow-2xs transition flex items-center gap-2"
        >
          <Sliders className="w-4 h-4 text-primary-600" />
          Customize Branding
        </button>
      </div>

      {success && (
        <div className="p-3.5 bg-emerald-50 border border-emerald-200 text-emerald-800 text-xs font-medium rounded-xl flex items-center gap-2">
          <CheckCircle2 className="w-4 h-4 text-emerald-600 flex-shrink-0" />
          {success}
        </div>
      )}

      {/* Templates Grid */}
      {loading ? (
        <div className="py-20 text-center">
          <RefreshCw className="w-8 h-8 text-primary-600 animate-spin mx-auto mb-2" />
          <p className="text-xs font-medium text-slate-500">Loading publication templates...</p>
        </div>
      ) : templates.length > 0 ? (
        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
          {templates.map((tpl) => (
            <div
              key={tpl.id}
              className={`bg-white rounded-2xl border transition-all duration-200 overflow-hidden shadow-xs hover:shadow-lg flex flex-col justify-between ${
                tpl.is_default ? 'border-primary-500 ring-2 ring-primary-500/20' : 'border-slate-200/80'
              }`}
            >
              <div>
                {/* Template Visual Header / Thumbnail */}
                <div className="relative h-44 bg-slate-900 flex items-center justify-center p-4 overflow-hidden">
                  <div
                    className="w-48 h-64 bg-white rounded-t-sm shadow-2xl p-3 space-y-1.5 opacity-95 transform -translate-y-4"
                    style={{
                      fontFamily: tpl.configuration?.font_family || 'sans-serif',
                    }}
                  >
                    <div
                      style={{
                        backgroundColor: tpl.configuration?.primary_color || '#2563EB',
                      }}
                      className="h-2 rounded-xs w-full mb-1"
                    />
                    <div className="text-[9px] font-black tracking-tight text-center text-slate-900 uppercase">
                      {tpl.name}
                    </div>
                    <div className="h-0.5 bg-slate-200 w-full" />
                    <div className="grid grid-cols-2 gap-1 pt-1">
                      <div className="h-10 bg-slate-100 rounded-2xs" />
                      <div className="h-10 bg-slate-100 rounded-2xs" />
                    </div>
                  </div>

                  {tpl.is_default && (
                    <span className="absolute top-3 right-3 px-2.5 py-1 rounded-full text-[10px] font-bold uppercase bg-primary-600 text-white shadow-md flex items-center gap-1">
                      <Sparkles className="w-3 h-3" /> Default Active
                    </span>
                  )}
                </div>

                {/* Content */}
                <div className="p-5 space-y-3">
                  <div>
                    <h3 className="text-base font-bold text-navy-950">{tpl.name}</h3>
                    <p className="text-xs text-slate-500 mt-1 leading-relaxed">
                      {tpl.description || 'Curated broadsheet newspaper layout with optimized contrast and reading chrome.'}
                    </p>
                  </div>

                  {/* Config snapshot */}
                  <div className="p-3 bg-slate-50 rounded-xl space-y-2 border border-slate-100 text-xs">
                    <div className="flex items-center justify-between text-slate-600">
                      <span>Primary Accent:</span>
                      <span className="flex items-center gap-1.5 font-mono font-bold text-navy-900">
                        <span
                          className="w-3.5 h-3.5 rounded-full border border-slate-300"
                          style={{ backgroundColor: tpl.configuration?.primary_color || '#2563EB' }}
                        />
                        {tpl.configuration?.primary_color || '#2563EB'}
                      </span>
                    </div>
                    <div className="flex items-center justify-between text-slate-600">
                      <span>Font Family:</span>
                      <span className="font-semibold text-navy-900">
                        {tpl.configuration?.font_family || 'Inter'}
                      </span>
                    </div>
                    <div className="flex items-center justify-between text-slate-600">
                      <span>Layout Style:</span>
                      <span className="font-semibold text-navy-900 capitalize">
                        {tpl.configuration?.layout_style || 'Broadsheet Standard'}
                      </span>
                    </div>
                  </div>
                </div>
              </div>

              {/* Action */}
              <div className="p-5 pt-0">
                <button
                  onClick={() => handleApply(tpl.id)}
                  disabled={applyingId === tpl.id}
                  className="w-full py-2.5 px-4 rounded-xl bg-primary-600 hover:bg-primary-700 text-white text-xs font-semibold shadow-xs transition flex items-center justify-center gap-2 disabled:opacity-50"
                >
                  {applyingId === tpl.id ? (
                    <RefreshCw className="w-4 h-4 animate-spin" />
                  ) : (
                    <CheckCircle2 className="w-4 h-4" />
                  )}
                  {applyingId === tpl.id ? 'Applying Theme...' : 'Apply This Template'}
                </button>
              </div>
            </div>
          ))}
        </div>
      ) : (
        <div className="p-12 text-center bg-white rounded-2xl border border-slate-200/80 shadow-xs">
          <LayoutTemplate className="w-12 h-12 text-slate-300 mx-auto mb-3" />
          <h3 className="font-bold text-navy-950 text-sm">No Templates Found</h3>
          <p className="text-xs text-slate-500 mt-1 max-w-sm mx-auto">
            Default system templates will be seeded automatically.
          </p>
        </div>
      )}
    </div>
  );
};
