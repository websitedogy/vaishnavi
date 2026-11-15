import React, { useEffect, useState, useRef } from 'react';
import {
  Image,
  UploadCloud,
  Search,
  Filter,
  Trash2,
  Copy,
  Check,
  RefreshCw,
  AlertCircle,
  FileText,
  ExternalLink,
} from 'lucide-react';
import { mediaLibraryApi } from '../services/api';
import { MediaFile } from '../types';
import { Modal } from '../components/Modal';
import { ConfirmationDialog } from '../components/ConfirmationDialog';

export const MediaLibraryView: React.FC = () => {
  const [files, setFiles] = useState<MediaFile[]>([]);
  const [loading, setLoading] = useState(true);
  const [search, setSearch] = useState('');
  const [categoryFilter, setCategoryFilter] = useState('all');
  const [copiedId, setCopiedId] = useState<number | null>(null);

  // Upload modal state
  const [isUploadOpen, setIsUploadOpen] = useState(false);
  const [selectedFile, setSelectedFile] = useState<File | null>(null);
  const [uploadCategory, setUploadCategory] = useState('general');
  const [altText, setAltText] = useState('');
  const [uploading, setUploading] = useState(false);
  const fileInputRef = useRef<HTMLInputElement | null>(null);

  // Delete modal state
  const [deletingFile, setDeletingFile] = useState<MediaFile | null>(null);
  const [deleteLoading, setDeleteLoading] = useState(false);

  const fetchMedia = async () => {
    setLoading(true);
    try {
      const res = await mediaLibraryApi.getAll({
        category: categoryFilter !== 'all' ? categoryFilter : undefined,
        search: search || undefined,
      });
      setFiles(res.data?.data || res.data || []);
    } catch (err: any) {
      console.error('Failed to load media files:', err);
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => {
    fetchMedia();
  }, [categoryFilter]);

  const handleSearchSubmit = (e: React.FormEvent) => {
    e.preventDefault();
    fetchMedia();
  };

  const handleCopyUrl = (file: MediaFile) => {
    navigator.clipboard.writeText(file.file_path);
    setCopiedId(file.id);
    setTimeout(() => setCopiedId(null), 2000);
  };

  const handleUploadSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    if (!selectedFile) return;

    setUploading(true);
    try {
      await mediaLibraryApi.upload(selectedFile, uploadCategory, altText);
      setIsUploadOpen(false);
      setSelectedFile(null);
      setAltText('');
      await fetchMedia();
    } catch (err: any) {
      alert(err?.response?.data?.message || 'Upload failed.');
    } finally {
      setUploading(false);
    }
  };

  const handleDeleteConfirm = async () => {
    if (!deletingFile) return;
    setDeleteLoading(true);
    try {
      await mediaLibraryApi.delete(deletingFile.id);
      setDeletingFile(null);
      await fetchMedia();
    } catch (err: any) {
      alert(err?.response?.data?.message || 'Delete failed.');
    } finally {
      setDeleteLoading(false);
    }
  };

  return (
    <div className="space-y-6">
      {/* Header */}
      <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
          <h1 className="text-xl font-bold text-navy-950 flex items-center gap-2">
            <Image className="w-6 h-6 text-primary-600" />
            Media & Asset Library
          </h1>
          <p className="text-xs text-slate-500 mt-1">
            Store publication mastheads, sponsor banners, advertisement assets, and editorial graphics
          </p>
        </div>

        <button
          onClick={() => setIsUploadOpen(true)}
          className="px-4 py-2.5 bg-primary-600 hover:bg-primary-700 text-white rounded-xl text-xs font-semibold shadow-xs transition flex items-center gap-2"
        >
          <UploadCloud className="w-4 h-4" />
          Upload New Asset
        </button>
      </div>

      {/* Filter and Search Bar */}
      <div className="p-4 bg-white rounded-2xl border border-slate-200/80 shadow-xs flex flex-col md:flex-row items-center justify-between gap-4">
        <form onSubmit={handleSearchSubmit} className="relative w-full md:w-80">
          <input
            type="text"
            placeholder="Search assets by file name or alt..."
            value={search}
            onChange={(e) => setSearch(e.target.value)}
            className="w-full pl-9 pr-4 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs font-medium focus:outline-hidden focus:ring-2 focus:ring-primary-500"
          />
          <Search className="w-4 h-4 text-slate-400 absolute left-3 top-2.5" />
        </form>

        <div className="flex items-center gap-2 w-full md:w-auto overflow-x-auto">
          <Filter className="w-3.5 h-3.5 text-slate-400 mr-1" />
          {['all', 'general', 'logos', 'ads', 'masthead'].map((cat) => (
            <button
              key={cat}
              onClick={() => setCategoryFilter(cat)}
              className={`px-3 py-1.5 rounded-xl text-xs font-semibold capitalize whitespace-nowrap transition-all ${
                categoryFilter === cat
                  ? 'bg-primary-600 text-white shadow-2xs'
                  : 'bg-slate-100 hover:bg-slate-200 text-slate-600'
              }`}
            >
              {cat}
            </button>
          ))}
          <button
            onClick={() => fetchMedia()}
            className="p-1.5 rounded-lg border border-slate-200 hover:bg-slate-100 text-slate-500"
            title="Refresh"
          >
            <RefreshCw className={`w-3.5 h-3.5 ${loading ? 'animate-spin' : ''}`} />
          </button>
        </div>
      </div>

      {/* Assets Grid */}
      {loading ? (
        <div className="py-20 text-center">
          <RefreshCw className="w-8 h-8 text-primary-600 animate-spin mx-auto mb-2" />
          <p className="text-xs font-medium text-slate-500">Loading media files...</p>
        </div>
      ) : files.length > 0 ? (
        <div className="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 gap-4">
          {files.map((file) => (
            <div
              key={file.id}
              className="bg-white rounded-2xl border border-slate-200/80 shadow-xs overflow-hidden flex flex-col justify-between group hover:shadow-md transition-all"
            >
              <div className="relative h-36 bg-slate-100 flex items-center justify-center p-2 overflow-hidden">
                {file.mime_type?.startsWith('image/') || file.file_path?.match(/\.(jpeg|jpg|png|webp|svg)$/i) ? (
                  <img
                    src={file.file_path}
                    alt={file.alt_text || file.file_name}
                    className="max-h-full max-w-full object-contain"
                  />
                ) : (
                  <FileText className="w-12 h-12 text-slate-300" />
                )}

                <span className="absolute top-2 right-2 px-2 py-0.5 rounded-full text-[10px] font-bold uppercase bg-white/90 text-navy-950 backdrop-blur-xs shadow-xs">
                  {file.category || 'Asset'}
                </span>
              </div>

              <div className="p-3 bg-white space-y-2">
                <p className="text-xs font-bold text-navy-950 truncate" title={file.file_name}>
                  {file.file_name}
                </p>

                <div className="flex items-center justify-between text-[11px] text-slate-500 font-mono">
                  <span>{(file.file_size / 1024).toFixed(1)} KB</span>
                  {file.width && file.height && (
                    <span>{file.width}×{file.height}</span>
                  )}
                </div>

                <div className="flex items-center justify-between pt-2 border-t border-slate-100">
                  <button
                    onClick={() => handleCopyUrl(file)}
                    className="p-1 rounded-md text-slate-500 hover:text-primary-600 hover:bg-primary-50 transition"
                    title="Copy File Path"
                  >
                    {copiedId === file.id ? (
                      <Check className="w-3.5 h-3.5 text-emerald-600" />
                    ) : (
                      <Copy className="w-3.5 h-3.5" />
                    )}
                  </button>
                  <button
                    onClick={() => setDeletingFile(file)}
                    className="p-1 rounded-md text-slate-400 hover:text-rose-600 hover:bg-rose-50 transition"
                    title="Delete Asset"
                  >
                    <Trash2 className="w-3.5 h-3.5" />
                  </button>
                </div>
              </div>
            </div>
          ))}
        </div>
      ) : (
        <div className="p-12 text-center bg-white rounded-2xl border border-slate-200/80 shadow-xs">
          <Image className="w-12 h-12 text-slate-300 mx-auto mb-3" />
          <h3 className="font-bold text-navy-950 text-sm">No Media Assets Found</h3>
          <p className="text-xs text-slate-500 mt-1 max-w-sm mx-auto">
            Upload logos, ad banners, and graphics to manage your digital newspaper media library.
          </p>
        </div>
      )}

      {/* Upload Modal */}
      <Modal
        isOpen={isUploadOpen}
        onClose={() => setIsUploadOpen(false)}
        title="Upload Media Asset"
        size="md"
      >
        <form onSubmit={handleUploadSubmit} className="space-y-4">
          <div>
            <label className="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1.5">
              Select File *
            </label>
            <input
              type="file"
              ref={fileInputRef}
              required
              onChange={(e) => setSelectedFile(e.target.files?.[0] || null)}
              className="w-full text-xs text-slate-500 file:mr-4 file:py-2 file:px-4 file:rounded-xl file:border-0 file:text-xs file:font-semibold file:bg-primary-50 file:text-primary-700 hover:file:bg-primary-100"
            />
          </div>

          <div>
            <label className="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1.5">
              Category
            </label>
            <select
              value={uploadCategory}
              onChange={(e) => setUploadCategory(e.target.value)}
              className="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs font-medium text-navy-950 focus:outline-hidden focus:ring-2 focus:ring-primary-500"
            >
              <option value="general">General Asset</option>
              <option value="logos">Logos & Masthead</option>
              <option value="ads">Advertisements & Sponsors</option>
              <option value="editorial">Editorial Graphics</option>
            </select>
          </div>

          <div>
            <label className="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1.5">
              Alt Text / Caption
            </label>
            <input
              type="text"
              placeholder="e.g. Hyderabad Morning Banner Advertisement"
              value={altText}
              onChange={(e) => setAltText(e.target.value)}
              className="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs font-medium text-navy-950 focus:outline-hidden focus:ring-2 focus:ring-primary-500"
            />
          </div>

          <div className="flex items-center justify-end gap-3 pt-3 border-t border-slate-100">
            <button
              type="button"
              onClick={() => setIsUploadOpen(false)}
              className="px-4 py-2 text-xs font-semibold text-slate-600 bg-white border border-slate-200 rounded-xl hover:bg-slate-50"
            >
              Cancel
            </button>
            <button
              type="submit"
              disabled={uploading || !selectedFile}
              className="px-5 py-2 text-xs font-semibold text-white bg-primary-600 hover:bg-primary-700 rounded-xl shadow-xs transition disabled:opacity-50"
            >
              {uploading ? 'Uploading...' : 'Upload Asset'}
            </button>
          </div>
        </form>
      </Modal>

      {/* Delete Confirmation */}
      <ConfirmationDialog
        isOpen={Boolean(deletingFile)}
        onClose={() => setDeletingFile(null)}
        onConfirm={handleDeleteConfirm}
        title="Delete Media File"
        message={`Delete "${deletingFile?.file_name}"? If used in published editions or branding, the artwork reference will be broken.`}
        confirmLabel="Delete Asset"
        isDestructive={true}
        isLoading={deleteLoading}
      />
    </div>
  );
};
