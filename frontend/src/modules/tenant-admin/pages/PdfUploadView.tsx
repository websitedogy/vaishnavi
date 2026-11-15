import React, { useEffect, useState, useRef } from 'react';
import {
  UploadCloud,
  FileText,
  CheckCircle2,
  AlertCircle,
  RefreshCw,
  RotateCcw,
  Layers,
  ArrowRight,
  Info,
} from 'lucide-react';
import { pdfUploadApi, editionsApi } from '../services/api';
import { PdfUpload, Edition } from '../types';
import { StatusBadge } from '../components/StatusBadge';

interface PdfUploadViewProps {
  onNavigateToPages: (editionId: number) => void;
  targetEditionId?: number;
}

export const PdfUploadView: React.FC<PdfUploadViewProps> = ({
  onNavigateToPages,
  targetEditionId,
}) => {
  const [editions, setEditions] = useState<Edition[]>([]);
  const [selectedEditionId, setSelectedEditionId] = useState<number | ''>(targetEditionId || '');
  const [uploads, setUploads] = useState<PdfUpload[]>([]);
  const [loading, setLoading] = useState(true);
  const [uploading, setUploading] = useState(false);
  const [uploadProgress, setUploadProgress] = useState(0);
  const [errorMessage, setErrorMessage] = useState<string | null>(null);
  const [successMessage, setSuccessMessage] = useState<string | null>(null);

  const fileInputRef = useRef<HTMLInputElement | null>(null);
  const [dragActive, setDragActive] = useState(false);

  const fetchData = async () => {
    setLoading(true);
    try {
      const [edRes, upRes] = await Promise.all([
        editionsApi.getAll({ per_page: 50 }),
        pdfUploadApi.getUploads(),
      ]);
      const edList = edRes.data?.data || edRes.data || [];
      setEditions(edList);
      if (!selectedEditionId && edList.length > 0) {
        setSelectedEditionId(edList[0].id);
      }
      setUploads(upRes.data?.data || upRes.data || []);
    } catch (err: any) {
      setErrorMessage(err?.response?.data?.message || 'Failed to load upload records.');
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => {
    fetchData();
  }, []);

  const handleFileUpload = async (file: File) => {
    if (!selectedEditionId) {
      setErrorMessage('Please select an edition before uploading the PDF.');
      return;
    }

    if (file.type !== 'application/pdf' && !file.name.toLowerCase().endsWith('.pdf')) {
      setErrorMessage('Please select a valid PDF document (.pdf).');
      return;
    }

    setUploading(true);
    setUploadProgress(0);
    setErrorMessage(null);
    setSuccessMessage(null);

    try {
      await pdfUploadApi.upload(
        Number(selectedEditionId),
        file,
        (progress) => setUploadProgress(progress)
      );
      setSuccessMessage(`"${file.name}" uploaded successfully. Automatic page extraction & rendering complete!`);
      await fetchData();
    } catch (err: any) {
      setErrorMessage(err?.response?.data?.message || 'PDF upload or conversion failed.');
    } finally {
      setUploading(false);
      setUploadProgress(0);
      if (fileInputRef.current) fileInputRef.current.value = '';
    }
  };

  const handleDrop = (e: React.DragEvent) => {
    e.preventDefault();
    setDragActive(false);
    if (e.dataTransfer.files && e.dataTransfer.files[0]) {
      handleFileUpload(e.dataTransfer.files[0]);
    }
  };

  const handleRetry = async (uploadId: number) => {
    try {
      await pdfUploadApi.retry(uploadId);
      await fetchData();
    } catch (err: any) {
      alert(err?.response?.data?.message || 'Failed to retry upload processing.');
    }
  };

  return (
    <div className="space-y-6">
      {/* Header */}
      <div>
        <h1 className="text-xl font-bold text-navy-950 flex items-center gap-2">
          <UploadCloud className="w-6 h-6 text-primary-600" />
          PDF Ingestion & Processing Pipeline
        </h1>
        <p className="text-xs text-slate-500 mt-1">
          Upload print-ready PDF editions. The system extracts pages, generates high-res broadsheet artwork, and prepares zoom tiles.
        </p>
      </div>

      {successMessage && (
        <div className="p-3.5 bg-emerald-50 border border-emerald-200 text-emerald-800 text-xs font-medium rounded-xl flex items-center gap-2">
          <CheckCircle2 className="w-4 h-4 text-emerald-600 flex-shrink-0" />
          {successMessage}
        </div>
      )}

      {errorMessage && (
        <div className="p-3.5 bg-rose-50 border border-rose-200 text-rose-800 text-xs font-medium rounded-xl flex items-center gap-2">
          <AlertCircle className="w-4 h-4 text-rose-600 flex-shrink-0" />
          {errorMessage}
        </div>
      )}

      {/* Target Edition Selector & Drag-Drop Uploader */}
      <div className="grid grid-cols-1 lg:grid-cols-3 gap-6">
        {/* Edition selector box */}
        <div className="bg-white p-6 rounded-2xl border border-slate-200/80 shadow-xs space-y-4">
          <h2 className="text-sm font-bold text-navy-950 border-b border-slate-100 pb-2">
            1. Target Edition Assignment
          </h2>
          <p className="text-xs text-slate-500 leading-relaxed">
            Select the digital newspaper issue that will receive these rendered pages.
          </p>

          <div>
            <label className="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1.5">
              Target Edition *
            </label>
            <select
              value={selectedEditionId}
              onChange={(e) => setSelectedEditionId(e.target.value ? Number(e.target.value) : '')}
              className="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs font-medium text-navy-950 focus:outline-hidden focus:ring-2 focus:ring-primary-500"
            >
              <option value="">-- Choose an Edition --</option>
              {editions.map((ed) => (
                <option key={ed.id} value={ed.id}>
                  {ed.edition_title} ({ed.edition_date})
                </option>
              ))}
            </select>
          </div>

          <div className="p-3.5 bg-primary-50/70 border border-primary-100 rounded-xl space-y-1.5 text-xs text-primary-900">
            <div className="flex items-center gap-1.5 font-bold">
              <Info className="w-4 h-4 text-primary-600" />
              Engine Specifications
            </div>
            <p className="text-[11px] leading-relaxed text-primary-800">
              PDFs are processed with SHA256 deduplication and converted into WebP page images and thumbnails.
            </p>
          </div>
        </div>

        {/* Drag and Drop Zone */}
        <div className="lg:col-span-2 bg-white p-6 rounded-2xl border border-slate-200/80 shadow-xs flex flex-col justify-between">
          <h2 className="text-sm font-bold text-navy-950 border-b border-slate-100 pb-2 mb-4">
            2. Upload Print PDF
          </h2>

          <div
            onDragOver={(e) => {
              e.preventDefault();
              setDragActive(true);
            }}
            onDragLeave={() => setDragActive(false)}
            onDrop={handleDrop}
            className={`border-2 border-dashed rounded-2xl p-8 text-center transition-all flex flex-col items-center justify-center min-h-[220px] ${
              dragActive
                ? 'border-primary-500 bg-primary-50/40'
                : 'border-slate-300 hover:border-slate-400 bg-slate-50/50'
            }`}
          >
            <input
              type="file"
              ref={fileInputRef}
              accept="application/pdf"
              onChange={(e) => e.target.files?.[0] && handleFileUpload(e.target.files[0])}
              className="hidden"
            />

            <UploadCloud className="w-12 h-12 text-primary-600 mb-3" />
            <h3 className="font-bold text-navy-950 text-sm">
              Drag & Drop your complete Newspaper PDF here
            </h3>
            <p className="text-xs text-slate-500 mt-1 max-w-sm">
              Supports standard digital press PDFs up to 100 MB. Single file per issue.
            </p>

            <button
              type="button"
              onClick={() => fileInputRef.current?.click()}
              disabled={uploading}
              className="mt-4 px-5 py-2.5 rounded-xl bg-primary-600 hover:bg-primary-700 text-white text-xs font-semibold shadow-xs transition disabled:opacity-50"
            >
              {uploading ? 'Processing PDF...' : 'Browse Local Computer'}
            </button>
          </div>

          {/* Upload Progress Bar */}
          {uploading && (
            <div className="mt-4 space-y-1.5">
              <div className="flex justify-between text-xs font-semibold text-slate-700">
                <span>Uploading and extracting pages...</span>
                <span>{uploadProgress}%</span>
              </div>
              <div className="w-full h-2.5 bg-slate-100 rounded-full overflow-hidden">
                <div
                  className="h-full bg-primary-600 rounded-full transition-all duration-300"
                  style={{ width: `${uploadProgress}%` }}
                />
              </div>
            </div>
          )}
        </div>
      </div>

      {/* Uploads Processing History Table */}
      <div className="bg-white rounded-2xl border border-slate-200/80 shadow-xs overflow-hidden">
        <div className="px-6 py-4 border-b border-slate-100 flex items-center justify-between">
          <div>
            <h3 className="font-bold text-navy-950 text-sm">Upload Records & Ingestion Logs</h3>
            <p className="text-xs text-slate-500">Track extraction status and errors for submitted PDFs</p>
          </div>
          <button
            onClick={() => fetchData()}
            className="p-1.5 rounded-lg border border-slate-200 hover:bg-slate-100 text-slate-500"
            title="Refresh Table"
          >
            <RefreshCw className={`w-3.5 h-3.5 ${loading ? 'animate-spin' : ''}`} />
          </button>
        </div>

        <div className="overflow-x-auto">
          <table className="w-full text-left border-collapse">
            <thead>
              <tr className="bg-slate-50/80 text-[11px] font-bold text-slate-500 uppercase tracking-wider border-b border-slate-100">
                <th className="py-3 px-6">Original File Name</th>
                <th className="py-3 px-4">Target Edition</th>
                <th className="py-3 px-4">Size</th>
                <th className="py-3 px-4">Pages Extracted</th>
                <th className="py-3 px-4">Status & Progress</th>
                <th className="py-3 px-6 text-right">Actions</th>
              </tr>
            </thead>
            <tbody className="divide-y divide-slate-100 text-xs">
              {uploads.length > 0 ? (
                uploads.map((up) => (
                  <tr key={up.id} className="hover:bg-slate-50/60 transition-colors">
                    <td className="py-3.5 px-6">
                      <div className="font-bold text-navy-950 flex items-center gap-2">
                        <FileText className="w-4 h-4 text-primary-600 flex-shrink-0" />
                        <span className="truncate max-w-xs">{up.file_name}</span>
                      </div>
                      <span className="text-[11px] text-slate-400 font-mono block mt-0.5">
                        Uploaded: {new Date(up.created_at).toLocaleString()}
                      </span>
                    </td>
                    <td className="py-3.5 px-4 font-medium text-slate-700">
                      {up.edition ? up.edition.edition_title : 'N/A'}
                    </td>
                    <td className="py-3.5 px-4 text-slate-600 font-mono">
                      {(up.file_size / (1024 * 1024)).toFixed(2)} MB
                    </td>
                    <td className="py-3.5 px-4">
                      <span className="inline-flex items-center gap-1 font-bold text-navy-900">
                        {up.total_pages || 0} Pages
                      </span>
                    </td>
                    <td className="py-3.5 px-4">
                      <div className="flex items-center gap-2">
                        <StatusBadge status={up.status} />
                        <span className="font-mono text-slate-600 text-[11px]">
                          {up.progress_percentage}%
                        </span>
                      </div>
                      {up.error_message && (
                        <p className="text-[10px] text-rose-600 mt-1 max-w-xs truncate">
                          {up.error_message}
                        </p>
                      )}
                    </td>
                    <td className="py-3.5 px-6 text-right space-x-2">
                      {up.status === 'failed' && (
                        <button
                          onClick={() => handleRetry(up.id)}
                          className="px-2.5 py-1 rounded-lg bg-amber-50 hover:bg-amber-100 text-amber-700 font-semibold transition text-xs flex items-center gap-1 inline-flex"
                        >
                          <RotateCcw className="w-3.5 h-3.5" />
                          Retry
                        </button>
                      )}
                      {up.status === 'completed' && up.edition_id && (
                        <button
                          onClick={() => up.edition_id && onNavigateToPages(up.edition_id)}
                          className="px-2.5 py-1 rounded-lg bg-primary-50 hover:bg-primary-100 text-primary-700 font-semibold transition text-xs flex items-center gap-1 inline-flex"
                        >
                          <Layers className="w-3.5 h-3.5" />
                          Inspect Pages
                        </button>
                      )}
                    </td>
                  </tr>
                ))
              ) : (
                <tr>
                  <td colSpan={6} className="py-10 text-center text-slate-400">
                    No PDF ingestion records found.
                  </td>
                </tr>
              )}
            </tbody>
          </table>
        </div>
      </div>
    </div>
  );
};
