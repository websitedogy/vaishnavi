import React, { useEffect, useState } from 'react';
import {
  Layers,
  RotateCw,
  Eye,
  EyeOff,
  ArrowLeft,
  ArrowRight,
  Maximize2,
  Trash2,
  RefreshCw,
  AlertCircle,
  CheckCircle2,
  FileText,
  Save,
} from 'lucide-react';
import { pagesApi, editionsApi } from '../services/api';
import { Edition, EditionPage } from '../types';
import { PageEditorModal } from '../components/PageEditorModal';

interface PageManagementViewProps {
  initialEditionId?: number;
}

export const PageManagementView: React.FC<PageManagementViewProps> = ({
  initialEditionId,
}) => {
  const [editions, setEditions] = useState<Edition[]>([]);
  const [selectedEditionId, setSelectedEditionId] = useState<number | ''>(initialEditionId || '');
  const [pages, setPages] = useState<EditionPage[]>([]);
  const [loading, setLoading] = useState(false);
  const [actionLoading, setActionLoading] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const [success, setSuccess] = useState<string | null>(null);

  // Inspector modal
  const [inspectingPage, setInspectingPage] = useState<EditionPage | null>(null);

  // Fetch editions list
  useEffect(() => {
    const fetchEditions = async () => {
      try {
        const res = await editionsApi.getAll({ per_page: 50 });
        const list = res.data?.data || res.data || [];
        setEditions(list);
        if (!selectedEditionId && list.length > 0) {
          setSelectedEditionId(initialEditionId || list[0].id);
        }
      } catch (err: any) {
        setError(err?.response?.data?.message || 'Failed to fetch editions.');
      }
    };
    fetchEditions();
  }, [initialEditionId]);

  // Fetch pages when selected edition changes
  const fetchPages = async () => {
    if (!selectedEditionId) return;
    setLoading(true);
    setError(null);
    try {
      const res = await pagesApi.getByEdition(Number(selectedEditionId));
      setPages(res.data?.data || res.data || []);
    } catch (err: any) {
      setError(err?.response?.data?.message || 'Failed to load edition pages.');
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => {
    if (selectedEditionId) {
      fetchPages();
    }
  }, [selectedEditionId]);

  // Rotate a page 90 degrees
  const handleRotate = async (pageId: number, degrees = 90) => {
    setActionLoading(true);
    try {
      await pagesApi.rotate(pageId, degrees);
      await fetchPages();
      if (inspectingPage && inspectingPage.id === pageId) {
        setInspectingPage((prev) =>
          prev ? { ...prev, rotation_angle: ((prev.rotation_angle || 0) + degrees) % 360 } : null
        );
      }
    } catch (err: any) {
      alert(err?.response?.data?.message || 'Failed to rotate page.');
    } finally {
      setActionLoading(false);
    }
  };

  // Toggle Visibility
  const handleToggleVisibility = async (pageId: number, isVisible: boolean) => {
    setActionLoading(true);
    try {
      await pagesApi.toggleVisibility(pageId, isVisible);
      await fetchPages();
      if (inspectingPage && inspectingPage.id === pageId) {
        setInspectingPage((prev) => (prev ? { ...prev, is_visible: isVisible } : null));
      }
    } catch (err: any) {
      alert(err?.response?.data?.message || 'Failed to toggle visibility.');
    } finally {
      setActionLoading(false);
    }
  };

  // Reorder page Left (decrement page_number)
  const handleMove = async (index: number, direction: 'left' | 'right') => {
    const targetIndex = direction === 'left' ? index - 1 : index + 1;
    if (targetIndex < 0 || targetIndex >= pages.length) return;

    const newPages = [...pages];
    const temp = newPages[index];
    newPages[index] = newPages[targetIndex];
    newPages[targetIndex] = temp;

    const orderPayload = newPages.map((p, idx) => ({
      page_id: p.id,
      page_number: idx + 1,
    }));

    setActionLoading(true);
    try {
      await pagesApi.reorder(Number(selectedEditionId), orderPayload);
      await fetchPages();
    } catch (err: any) {
      alert(err?.response?.data?.message || 'Failed to reorder pages.');
    } finally {
      setActionLoading(false);
    }
  };

  // Replace artwork
  const handleReplaceImage = async (pageId: number, file: File) => {
    setActionLoading(true);
    try {
      await pagesApi.replaceImage(pageId, file);
      setSuccess('Page artwork replaced successfully!');
      setTimeout(() => setSuccess(null), 3000);
      await fetchPages();
      setInspectingPage(null);
    } catch (err: any) {
      alert(err?.response?.data?.message || 'Failed to replace page artwork.');
    } finally {
      setActionLoading(false);
    }
  };

  const currentEdition = editions.find((e) => e.id === Number(selectedEditionId));

  return (
    <div className="space-y-6">
      {/* Header and Edition Selector */}
      <div className="flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
          <h1 className="text-xl font-bold text-navy-950 flex items-center gap-2">
            <Layers className="w-6 h-6 text-primary-600" />
            Page Management & Layout Grid
          </h1>
          <p className="text-xs text-slate-500 mt-1">
            Reorder pages, rotate layout, inspect broadsheet artwork, and toggle page visibility
          </p>
        </div>

        <div className="flex items-center gap-3">
          <label className="text-xs font-semibold text-slate-600">Edition:</label>
          <select
            value={selectedEditionId}
            onChange={(e) => setSelectedEditionId(e.target.value ? Number(e.target.value) : '')}
            className="px-3.5 py-2 bg-white border border-slate-200 rounded-xl text-xs font-semibold text-navy-950 shadow-2xs focus:outline-hidden focus:ring-2 focus:ring-primary-500 min-w-[240px]"
          >
            {editions.map((ed) => (
              <option key={ed.id} value={ed.id}>
                {ed.edition_title} ({ed.edition_date})
              </option>
            ))}
          </select>
          <button
            onClick={fetchPages}
            disabled={loading}
            className="p-2 rounded-xl border border-slate-200 bg-white hover:bg-slate-50 text-slate-600 shadow-2xs"
            title="Refresh Pages"
          >
            <RefreshCw className={`w-4 h-4 ${loading ? 'animate-spin' : ''}`} />
          </button>
        </div>
      </div>

      {success && (
        <div className="p-3 bg-emerald-50 border border-emerald-200 text-emerald-800 text-xs font-medium rounded-xl flex items-center gap-2">
          <CheckCircle2 className="w-4 h-4 text-emerald-600 flex-shrink-0" />
          {success}
        </div>
      )}

      {error && (
        <div className="p-3 bg-rose-50 border border-rose-200 text-rose-800 text-xs font-medium rounded-xl flex items-center gap-2">
          <AlertCircle className="w-4 h-4 text-rose-600 flex-shrink-0" />
          {error}
        </div>
      )}

      {/* Overview Metadata Bar */}
      {currentEdition && (
        <div className="p-4 bg-white rounded-2xl border border-slate-200/80 shadow-xs flex flex-wrap items-center justify-between gap-4 text-xs">
          <div className="flex items-center gap-4">
            <span className="font-bold text-navy-950 text-sm">
              {currentEdition.edition_title}
            </span>
            <span className="text-slate-400">•</span>
            <span className="text-slate-600 font-medium">Date: {currentEdition.edition_date}</span>
            <span className="text-slate-400">•</span>
            <span className="text-slate-600 font-medium">{pages.length} Pages Loaded</span>
          </div>

          <div className="text-slate-500 font-medium text-[11px]">
            Tip: Use arrow controls to reorder pages. Click any page card to inspect in full resolution.
          </div>
        </div>
      )}

      {/* Pages Grid */}
      {loading ? (
        <div className="py-20 text-center">
          <RefreshCw className="w-8 h-8 text-primary-600 animate-spin mx-auto mb-2" />
          <p className="text-xs font-medium text-slate-500">Loading newspaper page cards...</p>
        </div>
      ) : pages.length > 0 ? (
        <div className="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-5">
          {pages.map((p, idx) => (
            <div
              key={p.id}
              className={`bg-white rounded-2xl border transition-all duration-200 overflow-hidden shadow-xs hover:shadow-md flex flex-col justify-between ${
                p.is_visible ? 'border-slate-200/80' : 'border-amber-300 bg-amber-50/20'
              }`}
            >
              {/* Card Header */}
              <div className="px-3.5 py-2.5 bg-slate-50 border-b border-slate-100 flex items-center justify-between">
                <span className="font-bold text-xs text-navy-900 flex items-center gap-1.5">
                  <span className="w-5 h-5 rounded-md bg-primary-600 text-white inline-flex items-center justify-center text-[11px] font-mono font-bold">
                    {p.page_number}
                  </span>
                  {p.page_title || `Page ${p.page_number}`}
                </span>

                <span
                  className={`text-[10px] font-bold uppercase px-2 py-0.5 rounded-full ${
                    p.is_visible
                      ? 'bg-emerald-100 text-emerald-800'
                      : 'bg-amber-100 text-amber-800'
                  }`}
                >
                  {p.is_visible ? 'Visible' : 'Hidden'}
                </span>
              </div>

              {/* Page Artwork Thumbnail */}
              <div
                onClick={() => setInspectingPage(p)}
                className="relative bg-slate-100 h-64 flex items-center justify-center p-3 cursor-pointer group overflow-hidden"
              >
                {p.thumbnail_path || p.image_path ? (
                  <img
                    src={p.thumbnail_path || p.image_path}
                    alt={`Page ${p.page_number}`}
                    style={{ transform: `rotate(${p.rotation_angle || 0}deg)` }}
                    className="max-h-full max-w-full object-contain rounded-xs shadow-md transition-transform duration-200 group-hover:scale-102"
                  />
                ) : (
                  <div className="text-center p-4">
                    <FileText className="w-12 h-12 text-slate-300 mx-auto mb-2" />
                    <p className="text-xs font-semibold text-slate-600">Page {p.page_number}</p>
                    <p className="text-[10px] text-slate-400 font-mono mt-1">
                      {p.width || 1200} x {p.height || 1800}
                    </p>
                  </div>
                )}

                {/* Hover inspect overlay */}
                <div className="absolute inset-0 bg-navy-950/40 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center gap-2">
                  <span className="px-3 py-1.5 rounded-lg bg-white text-navy-950 font-bold text-xs flex items-center gap-1 shadow-lg">
                    <Maximize2 className="w-3.5 h-3.5 text-primary-600" />
                    Inspect & Zoom
                  </span>
                </div>
              </div>

              {/* Card Footer / Quick Controls */}
              <div className="p-3 bg-white border-t border-slate-100 flex items-center justify-between">
                <div className="flex items-center gap-1">
                  <button
                    onClick={() => handleMove(idx, 'left')}
                    disabled={idx === 0 || actionLoading}
                    className="p-1.5 rounded-lg border border-slate-200 text-slate-600 hover:bg-slate-100 disabled:opacity-30 disabled:pointer-events-none transition"
                    title="Move Page Backward"
                  >
                    <ArrowLeft className="w-3.5 h-3.5" />
                  </button>
                  <button
                    onClick={() => handleMove(idx, 'right')}
                    disabled={idx === pages.length - 1 || actionLoading}
                    className="p-1.5 rounded-lg border border-slate-200 text-slate-600 hover:bg-slate-100 disabled:opacity-30 disabled:pointer-events-none transition"
                    title="Move Page Forward"
                  >
                    <ArrowRight className="w-3.5 h-3.5" />
                  </button>
                </div>

                <div className="flex items-center gap-1">
                  <button
                    onClick={() => handleRotate(p.id, 90)}
                    disabled={actionLoading}
                    className="p-1.5 rounded-lg border border-slate-200 text-slate-600 hover:bg-slate-100 transition"
                    title="Rotate 90 degrees"
                  >
                    <RotateCw className="w-3.5 h-3.5 text-primary-600" />
                  </button>
                  <button
                    onClick={() => handleToggleVisibility(p.id, !p.is_visible)}
                    disabled={actionLoading}
                    className={`p-1.5 rounded-lg border transition ${
                      p.is_visible
                        ? 'border-slate-200 text-slate-600 hover:bg-slate-100'
                        : 'border-amber-300 text-amber-700 bg-amber-50'
                    }`}
                    title={p.is_visible ? 'Hide Page' : 'Show Page'}
                  >
                    {p.is_visible ? <Eye className="w-3.5 h-3.5" /> : <EyeOff className="w-3.5 h-3.5" />}
                  </button>
                </div>
              </div>
            </div>
          ))}
        </div>
      ) : (
        <div className="p-12 text-center bg-white rounded-2xl border border-slate-200/80 shadow-xs">
          <Layers className="w-12 h-12 text-slate-300 mx-auto mb-3" />
          <h3 className="font-bold text-navy-950 text-sm">No Pages Found for This Edition</h3>
          <p className="text-xs text-slate-500 mt-1 max-w-sm mx-auto">
            Upload a PDF file to automatically extract and generate broadsheet newspaper pages.
          </p>
        </div>
      )}

      {/* Page Inspector Modal */}
      <PageEditorModal
        isOpen={Boolean(inspectingPage)}
        onClose={() => setInspectingPage(null)}
        page={inspectingPage}
        onRotate={handleRotate}
        onToggleVisibility={handleToggleVisibility}
        onReplaceImage={handleReplaceImage}
      />
    </div>
  );
};
