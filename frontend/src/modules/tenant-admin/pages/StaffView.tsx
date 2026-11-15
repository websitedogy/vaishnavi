import React, { useEffect, useState } from 'react';
import {
  Users,
  UserPlus,
  ShieldCheck,
  Edit,
  Trash2,
  RefreshCw,
  AlertCircle,
  CheckCircle2,
  Lock,
  Mail,
  User,
} from 'lucide-react';
import { staffApi, rolesApi } from '../services/api';
import { TenantStaff } from '../types';
import { Modal } from '../components/Modal';
import { ConfirmationDialog } from '../components/ConfirmationDialog';

export const StaffView: React.FC = () => {
  const [staffList, setStaffList] = useState<TenantStaff[]>([]);
  const [roles, setRoles] = useState<any[]>([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);
  const [success, setSuccess] = useState<string | null>(null);

  // Modals state
  const [isAddOpen, setIsAddOpen] = useState(false);
  const [isMatrixOpen, setIsMatrixOpen] = useState(false);
  const [editingStaff, setEditingStaff] = useState<TenantStaff | null>(null);
  const [deletingStaff, setDeletingStaff] = useState<TenantStaff | null>(null);
  const [actionLoading, setActionLoading] = useState(false);

  // Form state
  const [formData, setFormData] = useState({
    name: '',
    email: '',
    password: '',
    role: 'editor',
  });

  const fetchData = async () => {
    setLoading(true);
    try {
      const [stRes, roRes] = await Promise.all([
        staffApi.getAll(),
        rolesApi.getAll(),
      ]);
      setStaffList(stRes.data?.data || stRes.data || []);
      setRoles(roRes.data?.data || roRes.data || []);
    } catch (err: any) {
      setError(err?.response?.data?.message || 'Failed to load staff list.');
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => {
    fetchData();
  }, []);

  const handleOpenAdd = () => {
    setEditingStaff(null);
    setFormData({ name: '', email: '', password: '', role: 'editor' });
    setIsAddOpen(true);
  };

  const handleOpenEdit = (staff: TenantStaff) => {
    setEditingStaff(staff);
    setFormData({
      name: staff.name,
      email: staff.email,
      password: '',
      role: staff.role || 'editor',
    });
    setIsAddOpen(true);
  };

  const handleFormSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    setActionLoading(true);
    try {
      if (editingStaff) {
        await staffApi.update(editingStaff.id, {
          name: formData.name,
          email: formData.email,
          role: formData.role,
        });
        setSuccess('Staff member updated successfully.');
      } else {
        await staffApi.create(formData);
        setSuccess('New staff member added and access credentials configured.');
      }
      setIsAddOpen(false);
      setTimeout(() => setSuccess(null), 4000);
      await fetchData();
    } catch (err: any) {
      alert(err?.response?.data?.message || 'Failed to save staff member.');
    } finally {
      setActionLoading(false);
    }
  };

  const handleDeleteConfirm = async () => {
    if (!deletingStaff) return;
    setActionLoading(true);
    try {
      await staffApi.delete(deletingStaff.id);
      setDeletingStaff(null);
      setSuccess('Staff member removed.');
      setTimeout(() => setSuccess(null), 3000);
      await fetchData();
    } catch (err: any) {
      alert(err?.response?.data?.message || 'Failed to delete staff member.');
    } finally {
      setActionLoading(false);
    }
  };

  const getRoleBadgeColor = (role: string) => {
    switch (role?.toLowerCase()) {
      case 'admin':
        return 'bg-purple-100 text-purple-800 border-purple-200';
      case 'editor':
        return 'bg-primary-100 text-primary-800 border-primary-200';
      case 'reporter':
        return 'bg-emerald-100 text-emerald-800 border-emerald-200';
      default:
        return 'bg-slate-100 text-slate-800 border-slate-200';
    }
  };

  return (
    <div className="space-y-6">
      {/* Header */}
      <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
          <h1 className="text-xl font-bold text-navy-950 flex items-center gap-2">
            <Users className="w-6 h-6 text-primary-600" />
            Staff & Editorial Team (RBAC)
          </h1>
          <p className="text-xs text-slate-500 mt-1">
            Manage newsroom staff, assign role permissions (Admin, Editor, Reporter), and monitor access
          </p>
        </div>

        <div className="flex items-center gap-3">
          <button
            onClick={() => setIsMatrixOpen(true)}
            className="px-4 py-2.5 rounded-xl border border-slate-200 bg-white hover:bg-slate-50 text-slate-700 text-xs font-semibold shadow-2xs transition flex items-center gap-2"
          >
            <ShieldCheck className="w-4 h-4 text-primary-600" />
            Role Permissions Matrix
          </button>
          <button
            onClick={handleOpenAdd}
            className="px-4 py-2.5 bg-primary-600 hover:bg-primary-700 text-white rounded-xl text-xs font-semibold shadow-xs transition flex items-center gap-2"
          >
            <UserPlus className="w-4 h-4" />
            Add Staff Member
          </button>
        </div>
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

      {/* Staff Table */}
      <div className="bg-white rounded-2xl border border-slate-200/80 shadow-xs overflow-hidden">
        <div className="px-6 py-4 border-b border-slate-100 flex items-center justify-between">
          <h3 className="font-bold text-navy-950 text-sm">Active Newsroom Team Members</h3>
          <button
            onClick={() => fetchData()}
            className="p-1.5 rounded-lg border border-slate-200 hover:bg-slate-100 text-slate-500"
            title="Refresh"
          >
            <RefreshCw className={`w-3.5 h-3.5 ${loading ? 'animate-spin' : ''}`} />
          </button>
        </div>

        <div className="overflow-x-auto">
          <table className="w-full text-left border-collapse text-xs">
            <thead>
              <tr className="bg-slate-50/80 text-[11px] font-bold text-slate-500 uppercase tracking-wider border-b border-slate-100">
                <th className="py-3 px-6">Name & Email</th>
                <th className="py-3 px-4">Role Assignment</th>
                <th className="py-3 px-4">Account Status</th>
                <th className="py-3 px-4">Joined Date</th>
                <th className="py-3 px-6 text-right">Actions</th>
              </tr>
            </thead>
            <tbody className="divide-y divide-slate-100">
              {loading ? (
                <tr>
                  <td colSpan={5} className="py-12 text-center text-slate-400">
                    <RefreshCw className="w-6 h-6 text-primary-500 animate-spin mx-auto mb-2" />
                    Loading staff members...
                  </td>
                </tr>
              ) : staffList.length > 0 ? (
                staffList.map((st) => (
                  <tr key={st.id} className="hover:bg-slate-50/60 transition">
                    <td className="py-3.5 px-6">
                      <div className="flex items-center gap-3">
                        <div className="w-8 h-8 rounded-full bg-primary-100 text-primary-700 font-bold flex items-center justify-center text-xs">
                          {st.name?.charAt(0) || 'U'}
                        </div>
                        <div>
                          <p className="font-bold text-navy-950">{st.name}</p>
                          <p className="text-[11px] text-slate-500">{st.email}</p>
                        </div>
                      </div>
                    </td>
                    <td className="py-3.5 px-4">
                      <span
                        className={`inline-block px-2.5 py-0.5 rounded-full text-[11px] font-bold uppercase border ${getRoleBadgeColor(
                          st.role
                        )}`}
                      >
                        {st.role}
                      </span>
                    </td>
                    <td className="py-3.5 px-4">
                      <span className="inline-flex items-center gap-1.5 text-emerald-600 font-semibold">
                        <span className="w-1.5 h-1.5 rounded-full bg-emerald-500" />
                        Active
                      </span>
                    </td>
                    <td className="py-3.5 px-4 text-slate-500 font-mono text-[11px]">
                      {st.created_at ? new Date(st.created_at).toLocaleDateString() : 'N/A'}
                    </td>
                    <td className="py-3.5 px-6 text-right space-x-1.5">
                      <button
                        onClick={() => handleOpenEdit(st)}
                        className="p-1.5 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-700 transition"
                        title="Edit Staff"
                      >
                        <Edit className="w-3.5 h-3.5" />
                      </button>
                      <button
                        onClick={() => setDeletingStaff(st)}
                        className="p-1.5 rounded-lg bg-rose-50 hover:bg-rose-100 text-rose-600 transition"
                        title="Delete Staff"
                      >
                        <Trash2 className="w-3.5 h-3.5" />
                      </button>
                    </td>
                  </tr>
                ))
              ) : (
                <tr>
                  <td colSpan={5} className="py-10 text-center text-slate-400">
                    No staff members configured.
                  </td>
                </tr>
              )}
            </tbody>
          </table>
        </div>
      </div>

      {/* Add / Edit Staff Modal */}
      <Modal
        isOpen={isAddOpen}
        onClose={() => setIsAddOpen(false)}
        title={editingStaff ? 'Edit Staff Member' : 'Add New Editorial Team Member'}
        size="md"
      >
        <form onSubmit={handleFormSubmit} className="space-y-4">
          <div>
            <label className="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1.5">
              Full Name *
            </label>
            <input
              type="text"
              required
              placeholder="e.g. Ramesh Kumar"
              value={formData.name}
              onChange={(e) => setFormData({ ...formData, name: e.target.value })}
              className="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs font-medium text-navy-950 focus:outline-hidden focus:ring-2 focus:ring-primary-500"
            />
          </div>

          <div>
            <label className="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1.5">
              Email Address *
            </label>
            <input
              type="email"
              required
              placeholder="ramesh@newspaper.com"
              value={formData.email}
              onChange={(e) => setFormData({ ...formData, email: e.target.value })}
              className="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs font-medium text-navy-950 focus:outline-hidden focus:ring-2 focus:ring-primary-500"
            />
          </div>

          {!editingStaff && (
            <div>
              <label className="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1.5">
                Initial Password *
              </label>
              <input
                type="password"
                required
                placeholder="Secure initial password"
                value={formData.password}
                onChange={(e) => setFormData({ ...formData, password: e.target.value })}
                className="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs font-medium text-navy-950 focus:outline-hidden focus:ring-2 focus:ring-primary-500"
              />
            </div>
          )}

          <div>
            <label className="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1.5">
              Role Assignment *
            </label>
            <select
              value={formData.role}
              onChange={(e) => setFormData({ ...formData, role: e.target.value })}
              className="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs font-medium text-navy-950 focus:outline-hidden focus:ring-2 focus:ring-primary-500"
            >
              <option value="editor">Editor (Create, upload PDF, edit pages, publish)</option>
              <option value="reporter">Reporter (Upload drafts, create pages)</option>
              <option value="admin">Admin (Full tenant control including staff & billing)</option>
            </select>
          </div>

          <div className="flex items-center justify-end gap-3 pt-3 border-t border-slate-100">
            <button
              type="button"
              onClick={() => setIsAddOpen(false)}
              className="px-4 py-2 text-xs font-semibold text-slate-600 bg-white border border-slate-200 rounded-xl hover:bg-slate-50"
            >
              Cancel
            </button>
            <button
              type="submit"
              disabled={actionLoading}
              className="px-5 py-2 text-xs font-semibold text-white bg-primary-600 hover:bg-primary-700 rounded-xl shadow-xs transition"
            >
              {actionLoading ? 'Saving...' : editingStaff ? 'Update Staff' : 'Add Staff'}
            </button>
          </div>
        </form>
      </Modal>

      {/* Permissions Matrix Modal */}
      <Modal
        isOpen={isMatrixOpen}
        onClose={() => setIsMatrixOpen(false)}
        title="Role-Based Access Control (RBAC) Matrix"
        size="lg"
      >
        <div className="space-y-4">
          <p className="text-xs text-slate-600">
            Permissions granted to each role within this tenant workspace:
          </p>

          <table className="w-full text-left border-collapse text-xs">
            <thead>
              <tr className="bg-slate-50 font-bold text-navy-900 border-b border-slate-200">
                <th className="py-2.5 px-3">Permission / Capability</th>
                <th className="py-2.5 px-3 text-center">Reporter</th>
                <th className="py-2.5 px-3 text-center">Editor</th>
                <th className="py-2.5 px-3 text-center">Tenant Admin</th>
              </tr>
            </thead>
            <tbody className="divide-y divide-slate-100">
              <tr>
                <td className="py-2.5 px-3 font-medium text-slate-700">View Editions & Pages</td>
                <td className="py-2.5 px-3 text-center text-emerald-600 font-bold">✓</td>
                <td className="py-2.5 px-3 text-center text-emerald-600 font-bold">✓</td>
                <td className="py-2.5 px-3 text-center text-emerald-600 font-bold">✓</td>
              </tr>
              <tr>
                <td className="py-2.5 px-3 font-medium text-slate-700">Upload PDF Editions</td>
                <td className="py-2.5 px-3 text-center text-emerald-600 font-bold">✓</td>
                <td className="py-2.5 px-3 text-center text-emerald-600 font-bold">✓</td>
                <td className="py-2.5 px-3 text-center text-emerald-600 font-bold">✓</td>
              </tr>
              <tr>
                <td className="py-2.5 px-3 font-medium text-slate-700">Reorder & Rotate Pages</td>
                <td className="py-2.5 px-3 text-center text-slate-300">✗</td>
                <td className="py-2.5 px-3 text-center text-emerald-600 font-bold">✓</td>
                <td className="py-2.5 px-3 text-center text-emerald-600 font-bold">✓</td>
              </tr>
              <tr>
                <td className="py-2.5 px-3 font-medium text-slate-700">Publish & Schedule Editions</td>
                <td className="py-2.5 px-3 text-center text-slate-300">✗</td>
                <td className="py-2.5 px-3 text-center text-emerald-600 font-bold">✓</td>
                <td className="py-2.5 px-3 text-center text-emerald-600 font-bold">✓</td>
              </tr>
              <tr>
                <td className="py-2.5 px-3 font-medium text-slate-700">Branding & CSS Customization</td>
                <td className="py-2.5 px-3 text-center text-slate-300">✗</td>
                <td className="py-2.5 px-3 text-center text-slate-300">✗</td>
                <td className="py-2.5 px-3 text-center text-emerald-600 font-bold">✓</td>
              </tr>
              <tr>
                <td className="py-2.5 px-3 font-medium text-slate-700">Staff & Team Management</td>
                <td className="py-2.5 px-3 text-center text-slate-300">✗</td>
                <td className="py-2.5 px-3 text-center text-slate-300">✗</td>
                <td className="py-2.5 px-3 text-center text-emerald-600 font-bold">✓</td>
              </tr>
              <tr>
                <td className="py-2.5 px-3 font-medium text-slate-700">Subscription & Storage Limits</td>
                <td className="py-2.5 px-3 text-center text-slate-300">✗</td>
                <td className="py-2.5 px-3 text-center text-slate-300">✗</td>
                <td className="py-2.5 px-3 text-center text-emerald-600 font-bold">✓</td>
              </tr>
            </tbody>
          </table>

          <div className="flex justify-end pt-3 border-t border-slate-100">
            <button
              onClick={() => setIsMatrixOpen(false)}
              className="px-4 py-2 bg-slate-100 text-slate-700 rounded-xl text-xs font-semibold hover:bg-slate-200 transition"
            >
              Close
            </button>
          </div>
        </div>
      </Modal>

      {/* Delete Staff Dialog */}
      <ConfirmationDialog
        isOpen={Boolean(deletingStaff)}
        onClose={() => setDeletingStaff(null)}
        onConfirm={handleDeleteConfirm}
        title="Remove Staff Member"
        message={`Remove "${deletingStaff?.name}" (${deletingStaff?.email}) from your workspace? They will immediately lose access to all editions and management tools.`}
        confirmLabel="Remove Staff"
        isDestructive={true}
        isLoading={actionLoading}
      />
    </div>
  );
};
