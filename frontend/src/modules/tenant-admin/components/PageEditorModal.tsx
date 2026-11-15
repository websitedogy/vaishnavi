import React, { useState } from 'react';
import {
  ZoomIn,
  ZoomOut,
  RotateCw,
  Eye,
  EyeOff,
  Upload,
  Maximize2,
  FileText,
  Info,
} from 'lucide-react';
import { Modal } from './Modal';
import { EditionPage } from '../types';

interface PageEditorModalProps {
  isOpen: boolean;
  onClose: () => void;
  page: EditionPage | null;
  onRotate?: (pageId: number, degrees: number) => Promise<void>;
  onToggleVisibility?: (pageId: number, isVisible: boolean) => Promise<void>;
  onReplaceImage?: (pageId: number, file: File) => Promise<void>;
}

export const PageEditorModal: React.FC<PageEditorModalProps> = ({
  isOpen,
  onClose,
  page,
  onRotate,
  onToggleVisibility,
  onReplaceImage,
}) => {
  const [zoom, setZoom] = useState<number>(100);
  const [rotation, setRotation] = useState<number>(page?.rotation_angle || 0);
  const [isUpdating, setIsUpdating] = useState<boolean>(false);
  const fileInputRef = React.useRef<HTMLInputElement | null>(null);

  React.useEffect(() => {
    if (page) {
      setRotation(page.rotation_angle || 0);
      setZoom(100);
    }
  }, [page]);

  if (!page) return null;

  const handleZoomIn = () => setZoom((prev) => Math.min(prev + 25, 250));
  const handleZoomOut = () => setZoom((prev) => Math.max(prev - 25, 50));
  const handleResetZoom = () => setZoom(100);

  const handleRotate = async () => {
    const nextRot = (rotation + 90) % 360;
    setRotation(nextRot);
    if (onRotate) {
      setIsUpdating(true);
      try {
        await onRotate(page.id, 90);
      } finally {
        setIsUpdating(false);
      }
    }
  };

  const handleToggleVis = async () => {
    if (onToggleVisibility) {
      setIsUpdating(true);
      try {
        await onToggleVisibility(page.id, !page.is_visible);
      } finally {
        setIsUpdating(false);
      }
    }
  };

  const handleFileChange = async (e: React.ChangeEvent<HTMLInputElement>) => {
    const file = e.target.files?.[0];
    if (file && onReplaceImage) {
      setIsUpdating(true);
      try {
        await onReplaceImage(page.id, file);
      } finally {
        setIsUpdating(false);
        if (fileInputRef.current) fileInputRef.current.value = '';
      }
    }
  };

  return (
    <Modal
      isOpen={isOpen}
      onClose={onClose}
      title={`Page ${page.page_number} Inspector & Editor: ${page.page_title || 'Untitled Page'}`}
      size="xl"
    >
      <div className="flex flex-col gap-4">
        {/* Toolbar */}
        <div className="flex flex-wrap items-center justify-between gap-3 p-3 bg-slate-50 border border-slate-200 rounded-xl">
          <div className="flex items-center gap-2">
            <button
              onClick={handleZoomOut}
              className="p-2 rounded-lg bg-white border border-slate-200 text-slate-700 hover:bg-slate-100 transition-colors shadow-2xs"
              title="Zoom Out"
            >
              <ZoomOut className="w-4 h-4" />
            </button>
            <span className="text-xs font-semibold text-slate-600 w-12 text-center">
              {zoom}%
            </span>
            <button
              onClick={handleZoomIn}
              className="p-2 rounded-lg bg-white border border-slate-200 text-slate-700 hover:bg-slate-100 transition-colors shadow-2xs"
              title="Zoom In"
            >
              <ZoomIn className="w-4 h-4" />
            </button>
            <button
              onClick={handleResetZoom}
              className="px-2.5 py-1.5 rounded-lg bg-white border border-slate-200 text-xs font-medium text-slate-600 hover:bg-slate-100 transition-colors shadow-2xs"
            >
              Fit
            </button>
          </div>

          <div className="flex items-center gap-2">
            <button
              onClick={handleRotate}
              disabled={isUpdating}
              className="flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-white border border-slate-200 text-xs font-semibold text-slate-700 hover:bg-slate-100 transition-colors shadow-2xs"
            >
              <RotateCw className="w-4 h-4 text-primary-600" />
              Rotate 90°
            </button>
            <button
              onClick={handleToggleVis}
              disabled={isUpdating}
              className={`flex items-center gap-1.5 px-3 py-1.5 rounded-lg border text-xs font-semibold transition-colors shadow-2xs ${
                page.is_visible
                  ? 'bg-emerald-50 text-emerald-700 border-emerald-200 hover:bg-emerald-100'
                  : 'bg-amber-50 text-amber-700 border-amber-200 hover:bg-amber-100'
              }`}
            >
              {page.is_visible ? (
                <>
                  <Eye className="w-4 h-4" /> Visible
                </>
              ) : (
                <>
                  <EyeOff className="w-4 h-4" /> Hidden
                </>
              )}
            </button>
            <input
              type="file"
              ref={fileInputRef}
              onChange={handleFileChange}
              accept="image/png,image/jpeg,image/webp"
              className="hidden"
            />
            <button
              onClick={() => fileInputRef.current?.click()}
              disabled={isUpdating}
              className="flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-primary-50 text-primary-700 border border-primary-200 hover:bg-primary-100 text-xs font-semibold transition-colors shadow-2xs"
            >
              <Upload className="w-4 h-4" /> Replace Artwork
            </button>
          </div>
        </div>

        {/* Canvas / Viewport */}
        <div className="relative w-full h-[500px] bg-slate-900/90 rounded-2xl overflow-auto flex items-center justify-center p-6 border border-slate-800">
          <div
            style={{
              transform: `scale(${zoom / 100}) rotate(${rotation}deg)`,
              transformOrigin: 'center center',
              transition: 'transform 0.2s cubic-bezier(0.4, 0, 0.2, 1)',
            }}
            className="relative shadow-2xl rounded-sm overflow-hidden bg-white max-w-full"
          >
            {page.image_path ? (
              <img
                src={page.image_path}
                alt={`Page ${page.page_number}`}
                className="max-h-[440px] w-auto object-contain select-none"
              />
            ) : (
              <div className="w-[320px] h-[450px] bg-white flex flex-col items-center justify-center p-6 text-center border">
                <FileText className="w-16 h-16 text-slate-300 mb-3" />
                <h4 className="font-bold text-navy-950 text-sm">
                  {page.page_title || `Page ${page.page_number}`}
                </h4>
                <p className="text-xs text-slate-500 mt-1">
                  Broadsheet Newspaper Layout Artwork
                </p>
                <div className="mt-4 px-3 py-1.5 bg-slate-100 rounded-lg text-xs font-mono text-slate-600">
                  {page.width || 1200} x {page.height || 1800} px
                </div>
              </div>
            )}
          </div>
        </div>

        {/* Metadata Footer */}
        <div className="grid grid-cols-2 sm:grid-cols-4 gap-3 text-xs bg-slate-50 p-3.5 rounded-xl border border-slate-200">
          <div>
            <span className="text-slate-400 block font-medium">Page Number</span>
            <span className="font-bold text-navy-900">
              #{page.page_number} of edition
            </span>
          </div>
          <div>
            <span className="text-slate-400 block font-medium">Native Dimensions</span>
            <span className="font-bold text-navy-900">
              {page.width || 1200} × {page.height || 1800} px
            </span>
          </div>
          <div>
            <span className="text-slate-400 block font-medium">File Size</span>
            <span className="font-bold text-navy-900">
              {page.file_size
                ? `${(page.file_size / 1024).toFixed(1)} KB`
                : '142.5 KB (WebP)'}
            </span>
          </div>
          <div>
            <span className="text-slate-400 block font-medium">Status</span>
            <span
              className={`font-bold ${
                page.is_visible ? 'text-emerald-600' : 'text-amber-600'
              }`}
            >
              {page.is_visible ? 'Publicly Visible' : 'Suppressed'}
            </span>
          </div>
        </div>
      </div>
    </Modal>
  );
};
