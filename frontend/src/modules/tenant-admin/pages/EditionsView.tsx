import React, { useEffect, useState } from 'react';
import {
  Newspaper,
  Plus,
  Search,
  Filter,
  Copy,
  Archive,
  Trash2,
  Edit,
  Layers,
  UploadCloud,
  Eye,
  RefreshCw,
  AlertCircle,
  CheckCircle2,
} from 'lucide-react';
import { editionsApi } from '../services/api';
import { Edition } from '../types';
import { StatusBadge } from '../components/StatusBadge';
import { ConfirmationDialog } from '../components/ConfirmationDialog';
import { CreateEditionModal } from '../components/CreateEditionModal';

interface EditionsViewProps {
  onNavigateToPages: (editionId: number) => void;
  onNavigateToUpload: (editionId?: number) => void;
}

export const EditionsView: React.FC<EditionsViewProps> = ({
  onNavigateToPages,
  onNavigateToUpload,
}) => {
  const [editions, setEditions] = useState<Edition[]>([]);
  const [loading, setLoading] = useState(true);
  const [search, setSearch] = useState('');
  const [statusFilter, setStatusFilter] = useState<string>('all');
  const [error, setError] = useState<string | null>(null);

  // Modals state
  const [isCreateOpen, setIsCreateOpen] = useState(false);
  const [editingEdition, setEditingEdition] = useState<Edition | null>(null);
  const [deletingEdition, setDeletingEdition] = useState<Edition | null>(null);
  const [actionLoading, setActionLoading] = useState(false);

  const fetchEditions = async () => {
    setLoading(true);
    setError(null);
    try {
      const res = await editionsApi.getAll({
        search: search || undefined,
        status: statusFilter !== 'all' ? statusFilter : undefined,
      });
      setEditions(res.data?.data || res.data || []);
    } catch (err: any) {
      setError(err?.response?.data?.message || 'Failed to fetch editions.');
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => {
    fetchEditions();
  }, [statusFilter]);

  const handleSearchSubmit = (e: React.FormEvent) => {
    e.preventDefault();
    fetchEditions();
  };

  const handleSaveEdition = async (data: Partial<Edition>) => {
    if (editingEdition) {
      await editionsApi.update(editingEdition.id, data);
    } else {
      await editionsApi.create(data);
    }
    await fetchEditions();
  };

  const handleDuplicate = async (id: number) => {
    setActionLoading(true);
    try {
      await editionsApi.duplicate(id);
      await fetchEditions();
    } catch (err: any) {
      alert(err?.response?.data?.message || 'Failed to duplicate edition.');
    } finally {
      setActionLoading(false);
    }
  };

  const handleArchive = async (id: number) => {
    setActionLoading(true);
    try {
      await editionsApi.archive(id);
      await fetchEditions();
    } catch (err: any) {
      alert(err?.response?.data?.message || 'Failed to archive edition.');
    } finally {
      setActionLoading(false);
    }
  };

  const handleDeleteConfirm = async () => {
    if (!deletingEdition) return;
    setActionLoading(true);
    try {
      await editionsApi.delete(deletingEdition.id);
      setDeletingEdition(null);
      await fetchEditions();
    } catch (err: any) {
      alert(err?.response?.data?.message || 'Failed to delete edition.');
    } finally {
      setActionLoading(false);
    }
  };

  return (
    <div className="space-y-6">
      {/* Header & Primary Actions */}
      <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
          <h1 className="text-xl font-bold text-navy-950 flex items-center gap-2">
            <Newspaper className="w-6 h-6 text-primary-600" />
            Editions Management
          </h1>
          <p className="text-xs text-slate-500 mt-1">
            Create, assemble, duplicate, and control all digital newspaper editions
          </p>
        </div>

        <div className="flex items-center gap-3">
          <button
            onClick={() => onNavigateToUpload()}
            className="px-4 py-2.5 rounded-xl border border-slate-200 bg-white hover:bg-slate-50 text-slate-700 text-xs font-semibold shadow-2xs transition flex items-center gap-2"
          >
            <UploadCloud className="w-4 h-4 text-primary-600" />
            Upload PDF
          </button>
          <button
            onClick={() => {
              setEditingEdition(null);
              setIsCreateOpen(true);
            }}
            className="px-4 py-2.5 rounded-xl bg-primary-600 hover:bg-primary-700 text-white text-xs font-semibold shadow-xs transition flex items-center gap-2"
          >
            <Plus className="w-4 h-4" />
            Create New Edition
          </button>
        </div>
      </div>

      {/* Filter and Search Bar */}
      <div className="p-4 bg-white rounded-2xl border border-slate-200/80 shadow-xs flex flex-col md:flex-row items-center justify-between gap-4">
        <form onSubmit={handleSearchSubmit} className="relative w-full md:w-80">
          <input
            type="text"
            placeholder="Search by title, edition code..."
            value={search}
            onChange={(e) => setSearch(e.target.value)}
            className="w-full pl-9 pr-4 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs font-medium focus:outline-hidden focus:ring-2 focus:ring-primary-500"
          />
          <Search className="w-4 h-4 text-slate-400 absolute left-3 top-2.5" />
        </form>

        <div className="flex items-center gap-2 w-full md:w-auto overflow-x-auto pb-1 md:pb-0">
          <Filter className="w-3.5 h-3.5 text-slate-400 mr-1" />
          {['all', 'published', 'scheduled', 'draft', 'archived'].map((status) => (
            <button
              key={status}
              onClick={() => setStatusFilter(status)}
              className={`px-3 py-1.5 rounded-xl text-xs font-semibold capitalize whitespace-nowrap transition-all ${
                statusFilter === status
                  ? 'bg-primary-600 text-white shadow-2xs'
                  : 'bg-slate-100 hover:bg-slate-200 text-slate-600'
              }`}
            >
              {status}
            </button>
          ))}
          <button
            onClick={() => fetchEditions()}
            className="p-1.5 rounded-lg border border-slate-200 hover:bg-slate-100 text-slate-500"
            title="Refresh"
          >
            <RefreshCw className={`w-3.5 h-3.5 ${loading ? 'animate-spin' : ''}`} />
          </button>
        </div>
      </div>

      {/* Editions Table */}
      <div className="bg-white rounded-2xl border border-slate-200/80 shadow-xs overflow-hidden">
        <div className="overflow-x-auto">
          <table className="w-full text-left border-collapse">
            <thead>
              <tr className="bg-slate-50/80 text-[11px] font-bold text-slate-500 uppercase tracking-wider border-b border-slate-100">
                <th className="py-3 px-6">Edition Details</th>
                <th className="py-3 px-4">Publication Date</th>
                <th className="py-3 px-4">Pages Count</th>
                <th className="py-3 px-4">Category / Lang</th>
                <th className="py-3 px-4">Publishing Status</th>
                <th className="py-3 px-6 text-right">Actions</th>
              </tr>
            </thead>
            <tbody className="divide-y divide-slate-100 text-xs">
              {loading ? (
                <tr>
                  <td colSpan={6} className="py-12 text-center text-slate-400">
                    <RefreshCw className="w-6 h-6 text-primary-500 animate-spin mx-auto mb-2" />
                    Fetching digital editions...
                  </td>
                </tr>
              ) : editions.length > 0 ? (
                editions.map((ed) => (
                  <tr key={ed.id} className="hover:bg-slate-50/60 transition-colors">
                    <td className="py-4 px-6">
                      <div className="font-bold text-navy-950 text-sm">{ed.edition_title}</div>
                      <div className="text-[11px] text-slate-500 font-mono mt-0.5">
                        Code: {ed.edition_code || 'N/A'} • ID: #{ed.id}
                      </div>
                    </td>
                    <td className="py-4 px-4 font-semibold text-slate-700">
                      {ed.edition_date}
                    </td>
                    <td className="py-4 px-4">
                      <span className="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full bg-slate-100 text-slate-700 font-bold text-xs">
                        <Layers className="w-3.5 h-3.5 text-primary-600" />
                        {ed.pages_count} Pages
                      </span>
                    </td>
                    <td className="py-4 px-4">
                      <span className="font-medium text-slate-700 capitalize block">
                        {ed.category || 'General'}
                      </span>
                      <span className="text-[11px] text-slate-400 font-mono uppercase">
                        {ed.edition_language || 'EN'}
                      </span>
                    </td>
                    <td className="py-4 px-4">
                      <StatusBadge status={ed.status} />
                    </td>
                    <td className="py-4 px-6 text-right space-x-1.5">
                      <button
                        onClick={() => onNavigateToPages(ed.id)}
                        className="p-1.5 rounded-lg bg-primary-50 hover:bg-primary-100 text-primary-700 transition"
                        title="Manage Pages"
                      >
                        <Layers className="w-4 h-4" />
                      </button>
                      <button
                        onClick={() => {
                          setEditingEdition(ed);
                          setIsCreateOpen(true);
                        }}
                        className="p-1.5 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-700 transition"
                        title="Edit Edition Info"
                      >
                        <Edit className="w-4 h-4" />
                      </button>
                      <button
                        onClick={() => handleDuplicate(ed.id)}
                        disabled={actionLoading}
                        className="p-1.5 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-700 transition"
                        title="Duplicate Edition"
                      >
                        <Copy className="w-4 h-4" />
                      </button>
                      {ed.status !== 'archived' && (
                        <button
                          onClick={() => handleArchive(ed.id)}
                          disabled={actionLoading}
                          className="p-1.5 rounded-lg bg-amber-50 hover:bg-amber-100 text-amber-700 transition"
                          title="Archive Edition"
                        >
                          <Archive className="w-4 h-4" />
                        </button>
                      )}
                      <button
                        onClick={() => setDeletingEdition(ed)}
                        className="p-1.5 rounded-lg bg-rose-50 hover:bg-rose-100 text-rose-600 transition"
                        title="Delete Edition"
                      >
                        <Trash2 className="w-4 h-4" />
                      </button>
                    </td>
                  </tr>
                ))
              ) : (
                <tr>
                  <td colSpan={6} className="py-12 text-center text-slate-400">
                    <Newspaper className="w-10 h-10 text-slate-300 mx-auto mb-2" />
                    No editions found matching criteria.
                  </td>
                </tr>
              )}
            </tbody>
          </table>
        </div>
      </div>

      {/* Create / Edit Modal */}
      <CreateEditionModal
        isOpen={isCreateOpen}
        onClose={() => {
          setIsCreateOpen(false);
          setEditingEdition(null);
        }}
        onSubmit={handleSaveEdition}
        initialData={editingEdition}
      />

      {/* Delete Confirmation */}
      <ConfirmationDialog
        isOpen={Boolean(deletingEdition)}
        onClose={() => setDeletingEdition(null)}
        onConfirm={handleDeleteConfirm}
        title="Delete Edition"
        message={`Are you sure you want to permanently delete "${deletingEdition?.edition_title}"? All rendered page artwork, thumbnails, and schedules for this edition will be removed.`}
        confirmLabel="Permanently Delete"
        isDestructive={true}
        isLoading={actionLoading}
      />
    </div>
  );
};
