import React, { useState } from 'react';
import { Calendar, Tag, Globe, FileText, Hash } from 'lucide-react';
import { Modal } from './Modal';
import { Edition } from '../types';

interface CreateEditionModalProps {
  isOpen: boolean;
  onClose: () => void;
  onSubmit: (data: Partial<Edition>) => Promise<void>;
  initialData?: Edition | null;
}

export const CreateEditionModal: React.FC<CreateEditionModalProps> = ({
  isOpen,
  onClose,
  onSubmit,
  initialData,
}) => {
  const isEditing = Boolean(initialData);
  const today = new Date().toISOString().split('T')[0];

  const [formData, setFormData] = useState({
    edition_title: initialData?.edition_title || '',
    edition_code: initialData?.edition_code || '',
    edition_date: initialData?.edition_date || today,
    edition_language: initialData?.edition_language || 'en',
    category: initialData?.category || 'Main Edition',
    description: initialData?.description || '',
  });

  const [loading, setLoading] = useState(false);
  const [error, setError] = useState<string | null>(null);

  // Sync state if initialData changes
  React.useEffect(() => {
    if (initialData) {
      setFormData({
        edition_title: initialData.edition_title || '',
        edition_code: initialData.edition_code || '',
        edition_date: initialData.edition_date || today,
        edition_language: initialData.edition_language || 'en',
        category: initialData.category || 'Main Edition',
        description: initialData.description || '',
      });
    } else {
      setFormData({
        edition_title: '',
        edition_code: '',
        edition_date: today,
        edition_language: 'en',
        category: 'Main Edition',
        description: '',
      });
    }
    setError(null);
  }, [initialData, isOpen, today]);

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    if (!formData.edition_title.trim() || !formData.edition_date) {
      setError('Title and edition date are required.');
      return;
    }

    setLoading(true);
    setError(null);
    try {
      await onSubmit(formData);
      onClose();
    } catch (err: any) {
      setError(err?.response?.data?.message || 'Failed to save edition. Please try again.');
    } finally {
      setLoading(false);
    }
  };

  return (
    <Modal
      isOpen={isOpen}
      onClose={onClose}
      title={isEditing ? 'Edit Edition Details' : 'Create New Digital Edition'}
      size="md"
    >
      <form onSubmit={handleSubmit} className="space-y-4">
        {error && (
          <div className="p-3 text-xs bg-rose-50 border border-rose-200 text-rose-700 rounded-xl">
            {error}
          </div>
        )}

        <div>
          <label className="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1.5">
            Edition Title *
          </label>
          <div className="relative">
            <input
              type="text"
              required
              placeholder="e.g. Hyderabad Morning City Edition"
              value={formData.edition_title}
              onChange={(e) => setFormData({ ...formData, edition_title: e.target.value })}
              className="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:outline-hidden focus:ring-2 focus:ring-primary-500 focus:bg-white text-navy-950 font-medium transition-all"
            />
          </div>
        </div>

        <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
          <div>
            <label className="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1.5">
              Edition Date *
            </label>
            <div className="relative">
              <input
                type="date"
                required
                value={formData.edition_date}
                onChange={(e) => setFormData({ ...formData, edition_date: e.target.value })}
                className="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:outline-hidden focus:ring-2 focus:ring-primary-500 focus:bg-white text-navy-950 font-medium transition-all"
              />
            </div>
          </div>

          <div>
            <label className="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1.5">
              Edition Code
            </label>
            <input
              type="text"
              placeholder="e.g. HYD-MAIN-01"
              value={formData.edition_code}
              onChange={(e) => setFormData({ ...formData, edition_code: e.target.value })}
              className="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:outline-hidden focus:ring-2 focus:ring-primary-500 focus:bg-white text-navy-950 font-medium transition-all uppercase"
            />
          </div>
        </div>

        <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
          <div>
            <label className="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1.5">
              Language
            </label>
            <select
              value={formData.edition_language}
              onChange={(e) => setFormData({ ...formData, edition_language: e.target.value })}
              className="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:outline-hidden focus:ring-2 focus:ring-primary-500 focus:bg-white text-navy-950 font-medium transition-all"
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
              Category
            </label>
            <input
              type="text"
              placeholder="e.g. Main Edition, Sports, District"
              value={formData.category}
              onChange={(e) => setFormData({ ...formData, category: e.target.value })}
              className="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:outline-hidden focus:ring-2 focus:ring-primary-500 focus:bg-white text-navy-950 font-medium transition-all"
            />
          </div>
        </div>

        <div>
          <label className="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1.5">
            Description / Editorial Notes
          </label>
          <textarea
            rows={3}
            placeholder="Optional internal editorial notes or edition summary..."
            value={formData.description}
            onChange={(e) => setFormData({ ...formData, description: e.target.value })}
            className="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:outline-hidden focus:ring-2 focus:ring-primary-500 focus:bg-white text-navy-950 font-medium transition-all resize-none"
          />
        </div>

        <div className="flex items-center justify-end gap-3 pt-3 border-t border-slate-100">
          <button
            type="button"
            onClick={onClose}
            disabled={loading}
            className="px-4 py-2.5 text-sm font-medium text-slate-600 bg-white border border-slate-200 rounded-xl hover:bg-slate-50 transition-colors"
          >
            Cancel
          </button>
          <button
            type="submit"
            disabled={loading}
            className="px-5 py-2.5 text-sm font-semibold text-white bg-primary-600 hover:bg-primary-700 rounded-xl shadow-xs transition-colors flex items-center gap-2 disabled:opacity-50"
          >
            {loading && (
              <svg className="animate-spin h-4 w-4 text-white" viewBox="0 0 24 24">
                <circle className="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" strokeWidth="4" fill="none" />
                <path className="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z" />
              </svg>
            )}
            {isEditing ? 'Save Changes' : 'Create Edition'}
          </button>
        </div>
      </form>
    </Modal>
  );
};
