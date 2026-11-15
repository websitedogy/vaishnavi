import React, { useState, useEffect } from 'react';
import { 
  Shield, UserCheck, Key, Lock, Clock, AlertTriangle, 
  Plus, Search, RefreshCw, XCircle, CheckCircle, Smartphone
} from 'lucide-react';
import { superAdminApi } from '../services/api';
import { SuperAdminUser, SuperAdminRole, SuperAdminPermission } from '../types';
import StatusBadge from '../../../shared/components/StatusBadge';
import Modal from '../../../shared/components/Modal';

export default function SecurityView() {
  const [activeTab, setActiveTab] = useState<'admins' | 'roles' | 'sessions' | 'audit' | 'logins'>('admins');
  const [loading, setLoading] = useState(true);
  const [refreshing, setRefreshing] = useState(false);
  
  // Data states
  const [admins, setAdmins] = useState<SuperAdminUser[]>([]);
  const [roles, setRoles] = useState<SuperAdminRole[]>([]);
  const [permissions, setPermissions] = useState<Record<string, SuperAdminPermission[]>>({});
  const [sessions, setSessions] = useState<any[]>([]);
  const [auditLogs, setAuditLogs] = useState<any[]>([]);
  const [loginLogs, setLoginLogs] = useState<any[]>([]);

  // Modals & form state
  const [createAdminOpen, setCreateAdminOpen] = useState(false);
  const [submittingAdmin, setSubmittingAdmin] = useState(false);
  const [newAdmin, setNewAdmin] = useState({
    name: '',
    email: '',
    password: '',
    role_id: 2,
    phone: '',
  });
  const [feedback, setFeedback] = useState<string | null>(null);

  const loadData = async () => {
    try {
      setLoading(true);
      const [adminsRes, rolesRes, permsRes, sessionsRes, auditRes, loginRes] = await Promise.all([
        superAdminApi.security.getAdmins(),
        superAdminApi.security.getRoles(),
        superAdminApi.security.getPermissions(),
        superAdminApi.security.getSessions(),
        superAdminApi.security.getAuditLogs(),
        superAdminApi.security.getLoginLogs(),
      ]);

      setAdmins(adminsRes.data || adminsRes || []);
      setRoles(rolesRes || []);
      setPermissions(permsRes || {});
      setSessions(sessionsRes || []);
      setAuditLogs(auditRes.data || auditRes || []);
      setLoginLogs(loginRes.data || loginRes || []);
    } catch (err: any) {
      console.error('Failed to load security data', err);
    } finally {
      setLoading(false);
      setRefreshing(false);
    }
  };

  useEffect(() => {
    loadData();
  }, []);

  const handleCreateAdmin = async (e: React.FormEvent) => {
    e.preventDefault();
    try {
      setSubmittingAdmin(true);
      await superAdminApi.security.createAdmin(newAdmin);
      setFeedback(`Admin user ${newAdmin.email} created successfully.`);
      setCreateAdminOpen(false);
      setNewAdmin({ name: '', email: '', password: '', role_id: 2, phone: '' });
      await loadData();
    } catch (err: any) {
      alert(err.response?.data?.message || 'Failed to create admin user');
    } finally {
      setSubmittingAdmin(false);
    }
  };

  const handleRevokeSession = async (id: number) => {
    if (!confirm('Are you sure you want to revoke this active admin session? The user will be signed out immediately.')) return;
    try {
      await superAdminApi.security.revokeSession(id);
      setFeedback('Session revoked successfully.');
      await loadData();
    } catch (err: any) {
      alert(err.response?.data?.message || 'Failed to revoke session');
    }
  };

  return (
    <div className="space-y-6">
      {/* Top Header */}
      <div className="flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
          <h1 className="text-2xl font-bold text-navy-900">Security & Access Management</h1>
          <p className="text-sm text-slate-500 mt-1">
            Enterprise RBAC governance, administrative credentials, active sessions, and tamper-evident audit logs.
          </p>
        </div>

        <div className="flex items-center gap-3">
          <button
            onClick={() => { setRefreshing(true); loadData(); }}
            disabled={refreshing}
            className="flex items-center gap-2 px-3 py-2 text-sm font-medium text-slate-700 bg-white border border-slate-200 rounded-lg hover:bg-slate-50 shadow-sm"
          >
            <RefreshCw className={`w-4 h-4 ${refreshing ? 'animate-spin' : ''}`} />
            Refresh
          </button>
          <button
            onClick={() => setCreateAdminOpen(true)}
            className="flex items-center gap-2 px-3 py-2 text-sm font-medium text-white bg-blue-600 rounded-lg hover:bg-blue-700 shadow-sm"
          >
            <Plus className="w-4 h-4" />
            Create Admin User
          </button>
        </div>
      </div>

      {feedback && (
        <div className="p-4 bg-emerald-50 border border-emerald-200 rounded-xl text-emerald-800 text-sm flex items-center justify-between">
          <div className="flex items-center gap-2">
            <CheckCircle className="w-4 h-4 text-emerald-600" />
            <span>{feedback}</span>
          </div>
          <button onClick={() => setFeedback(null)} className="text-xs text-emerald-700 underline font-medium">Dismiss</button>
        </div>
      )}

      {/* Navigation Tabs */}
      <div className="border-b border-slate-200 bg-white rounded-t-xl px-4 pt-2 shadow-sm">
        <div className="flex flex-wrap gap-6">
          <button
            onClick={() => setActiveTab('admins')}
            className={`pb-3 text-sm font-semibold border-b-2 transition-colors flex items-center gap-2 ${
              activeTab === 'admins' ? 'border-blue-600 text-blue-600' : 'border-transparent text-slate-500 hover:text-slate-800'
            }`}
          >
            <UserCheck className="w-4 h-4" />
            Staff Administrators ({admins.length})
          </button>
          <button
            onClick={() => setActiveTab('roles')}
            className={`pb-3 text-sm font-semibold border-b-2 transition-colors flex items-center gap-2 ${
              activeTab === 'roles' ? 'border-blue-600 text-blue-600' : 'border-transparent text-slate-500 hover:text-slate-800'
            }`}
          >
            <Shield className="w-4 h-4" />
            RBAC Matrix ({roles.length} Roles)
          </button>
          <button
            onClick={() => setActiveTab('sessions')}
            className={`pb-3 text-sm font-semibold border-b-2 transition-colors flex items-center gap-2 ${
              activeTab === 'sessions' ? 'border-blue-600 text-blue-600' : 'border-transparent text-slate-500 hover:text-slate-800'
            }`}
          >
            <Lock className="w-4 h-4" />
            Active Sessions ({sessions.length})
          </button>
          <button
            onClick={() => setActiveTab('audit')}
            className={`pb-3 text-sm font-semibold border-b-2 transition-colors flex items-center gap-2 ${
              activeTab === 'audit' ? 'border-blue-600 text-blue-600' : 'border-transparent text-slate-500 hover:text-slate-800'
            }`}
          >
            <Clock className="w-4 h-4" />
            Audit Logs ({auditLogs.length})
          </button>
          <button
            onClick={() => setActiveTab('logins')}
            className={`pb-3 text-sm font-semibold border-b-2 transition-colors flex items-center gap-2 ${
              activeTab === 'logins' ? 'border-blue-600 text-blue-600' : 'border-transparent text-slate-500 hover:text-slate-800'
            }`}
          >
            <Smartphone className="w-4 h-4" />
            Login History ({loginLogs.length})
          </button>
        </div>
      </div>

      {/* Tab 1: Staff Administrators */}
      {activeTab === 'admins' && (
        <div className="bg-white rounded-b-xl border border-t-0 border-slate-200 p-6 shadow-sm">
          <div className="overflow-x-auto border border-slate-100 rounded-lg">
            <table className="w-full text-left text-sm">
              <thead className="bg-slate-50 text-slate-600 font-medium text-xs uppercase tracking-wider border-b border-slate-200">
                <tr>
                  <th className="px-4 py-3">Administrator</th>
                  <th className="px-4 py-3">Role</th>
                  <th className="px-4 py-3">Status</th>
                  <th className="px-4 py-3">2FA Status</th>
                  <th className="px-4 py-3">Last Login</th>
                  <th className="px-4 py-3 text-right">Actions</th>
                </tr>
              </thead>
              <tbody className="divide-y divide-slate-100">
                {admins.map((admin) => (
                  <tr key={admin.id} className="hover:bg-slate-50 transition-colors">
                    <td className="px-4 py-3">
                      <p className="font-semibold text-slate-900">{admin.name}</p>
                      <p className="text-xs text-slate-500">{admin.email}</p>
                    </td>
                    <td className="px-4 py-3">
                      <span className="inline-flex items-center px-2 py-0.5 rounded text-xs font-semibold bg-blue-50 text-blue-700 border border-blue-200">
                        {admin.role?.name || 'Administrator'}
                      </span>
                    </td>
                    <td className="px-4 py-3">
                      <StatusBadge status={admin.status || 'active'} />
                    </td>
                    <td className="px-4 py-3">
                      {admin.two_factor_enabled ? (
                        <span className="text-xs font-semibold text-emerald-700 flex items-center gap-1">
                          <CheckCircle className="w-3.5 h-3.5" /> Enforced
                        </span>
                      ) : (
                        <span className="text-xs text-slate-400">Optional</span>
                      )}
                    </td>
                    <td className="px-4 py-3 text-xs text-slate-500">
                      {admin.last_login_at || 'Never'}
                    </td>
                    <td className="px-4 py-3 text-right">
                      <button className="text-xs text-blue-600 hover:underline font-medium">Edit Role</button>
                    </td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
        </div>
      )}

      {/* Tab 2: RBAC Matrix */}
      {activeTab === 'roles' && (
        <div className="bg-white rounded-b-xl border border-t-0 border-slate-200 p-6 shadow-sm space-y-6">
          <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
            {roles.map((r) => (
              <div key={r.id} className="p-4 border border-slate-200 rounded-xl bg-slate-50">
                <div className="flex items-center justify-between mb-2">
                  <h3 className="font-semibold text-navy-900 text-sm">{r.name}</h3>
                  <span className="text-xs font-mono text-slate-500">{r.slug}</span>
                </div>
                <p className="text-xs text-slate-600 min-h-[36px]">{r.description || 'System access role'}</p>
                <div className="mt-3 pt-3 border-t border-slate-200 flex items-center justify-between text-xs text-slate-500">
                  <span>Role Hierarchy Level</span>
                  <span className="font-semibold text-blue-600">Tier #{r.id}</span>
                </div>
              </div>
            ))}
          </div>

          <div className="pt-4">
            <h4 className="text-sm font-semibold text-navy-900 mb-3">Enterprise Permission Matrix</h4>
            <div className="overflow-x-auto border border-slate-200 rounded-lg">
              <table className="w-full text-left text-xs">
                <thead className="bg-slate-50 text-slate-700 border-b border-slate-200">
                  <tr>
                    <th className="px-4 py-3 font-semibold">Permission / Scope</th>
                    <th className="px-4 py-3 font-semibold">Platform Owner</th>
                    <th className="px-4 py-3 font-semibold">Super Admin</th>
                    <th className="px-4 py-3 font-semibold">Infra Engineer</th>
                    <th className="px-4 py-3 font-semibold">Support Mgr</th>
                    <th className="px-4 py-3 font-semibold">Billing Officer</th>
                  </tr>
                </thead>
                <tbody className="divide-y divide-slate-100">
                  {Object.entries(permissions).map(([group, perms]) => (
                    <React.Fragment key={group}>
                      <tr className="bg-slate-100/60 font-semibold text-slate-800 uppercase tracking-wider text-[11px]">
                        <td colSpan={6} className="px-4 py-2">{group} Management</td>
                      </tr>
                      {perms.map((p) => (
                        <tr key={p.id} className="hover:bg-slate-50">
                          <td className="px-4 py-2 font-mono text-slate-700">{p.slug}</td>
                          <td className="px-4 py-2 text-emerald-600 font-bold">✓</td>
                          <td className="px-4 py-2 text-emerald-600 font-bold">✓</td>
                          <td className="px-4 py-2 text-slate-400">{group === 'infrastructure' || group === 'backups' ? '✓' : '—'}</td>
                          <td className="px-4 py-2 text-slate-400">{group === 'support' || group === 'tenants' ? '✓' : '—'}</td>
                          <td className="px-4 py-2 text-slate-400">{group === 'subscriptions' || group === 'payments' ? '✓' : '—'}</td>
                        </tr>
                      ))}
                    </React.Fragment>
                  ))}
                </tbody>
              </table>
            </div>
          </div>
        </div>
      )}

      {/* Tab 3: Active Sessions */}
      {activeTab === 'sessions' && (
        <div className="bg-white rounded-b-xl border border-t-0 border-slate-200 p-6 shadow-sm space-y-4">
          <p className="text-xs text-slate-500">
            Active authenticated browser tokens and IP bindings. Super admins can instantly terminate untrusted sessions.
          </p>
          <div className="overflow-x-auto border border-slate-100 rounded-lg">
            <table className="w-full text-left text-sm">
              <thead className="bg-slate-50 text-slate-600 font-medium text-xs uppercase tracking-wider border-b border-slate-200">
                <tr>
                  <th className="px-4 py-3">Administrator</th>
                  <th className="px-4 py-3">IP Address</th>
                  <th className="px-4 py-3">User Agent</th>
                  <th className="px-4 py-3">Last Active</th>
                  <th className="px-4 py-3 text-right">Actions</th>
                </tr>
              </thead>
              <tbody className="divide-y divide-slate-100">
                {sessions.map((s, idx) => (
                  <tr key={idx} className="hover:bg-slate-50 transition-colors">
                    <td className="px-4 py-3 font-semibold text-slate-900">{s.admin_name || 'Platform Owner'}</td>
                    <td className="px-4 py-3 font-mono text-xs text-slate-700">{s.ip_address || '127.0.0.1'}</td>
                    <td className="px-4 py-3 text-xs text-slate-600 truncate max-w-sm">{s.user_agent || 'Mozilla/5.0 (Windows NT 10.0; Win64; x64)'}</td>
                    <td className="px-4 py-3 text-xs text-slate-500">{s.last_activity || 'Just now'}</td>
                    <td className="px-4 py-3 text-right">
                      <button
                        onClick={() => handleRevokeSession(s.id || 1)}
                        className="px-2.5 py-1 text-xs font-medium text-rose-700 bg-rose-50 border border-rose-200 rounded hover:bg-rose-100"
                      >
                        Revoke
                      </button>
                    </td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
        </div>
      )}

      {/* Tab 4: Audit Logs */}
      {activeTab === 'audit' && (
        <div className="bg-white rounded-b-xl border border-t-0 border-slate-200 p-6 shadow-sm space-y-4">
          <div className="overflow-x-auto border border-slate-100 rounded-lg">
            <table className="w-full text-left text-sm">
              <thead className="bg-slate-50 text-slate-600 font-medium text-xs uppercase tracking-wider border-b border-slate-200">
                <tr>
                  <th className="px-4 py-3">Action</th>
                  <th className="px-4 py-3">Administrator</th>
                  <th className="px-4 py-3">Target Entity</th>
                  <th className="px-4 py-3">IP Address</th>
                  <th className="px-4 py-3">Timestamp</th>
                </tr>
              </thead>
              <tbody className="divide-y divide-slate-100">
                {auditLogs.length === 0 ? (
                  <tr>
                    <td colSpan={5} className="text-center py-8 text-slate-400">No audit logs recorded yet.</td>
                  </tr>
                ) : (
                  auditLogs.map((log) => (
                    <tr key={log.id} className="hover:bg-slate-50 transition-colors">
                      <td className="px-4 py-3 font-mono text-xs font-bold text-navy-900">{log.action}</td>
                      <td className="px-4 py-3 text-xs text-slate-800">{log.admin_user?.name || `Admin #${log.admin_user_id || 'sys'}`}</td>
                      <td className="px-4 py-3 font-mono text-xs text-slate-600">{log.entity_type ? `${log.entity_type} #${log.entity_id}` : 'Platform'}</td>
                      <td className="px-4 py-3 font-mono text-xs text-slate-500">{log.ip_address || '127.0.0.1'}</td>
                      <td className="px-4 py-3 text-xs text-slate-500">{log.created_at}</td>
                    </tr>
                  ))
                )}
              </tbody>
            </table>
          </div>
        </div>
      )}

      {/* Tab 5: Login History */}
      {activeTab === 'logins' && (
        <div className="bg-white rounded-b-xl border border-t-0 border-slate-200 p-6 shadow-sm space-y-4">
          <div className="overflow-x-auto border border-slate-100 rounded-lg">
            <table className="w-full text-left text-sm">
              <thead className="bg-slate-50 text-slate-600 font-medium text-xs uppercase tracking-wider border-b border-slate-200">
                <tr>
                  <th className="px-4 py-3">Email Attempted</th>
                  <th className="px-4 py-3">Status</th>
                  <th className="px-4 py-3">IP Address</th>
                  <th className="px-4 py-3">Device / User Agent</th>
                  <th className="px-4 py-3">Timestamp</th>
                </tr>
              </thead>
              <tbody className="divide-y divide-slate-100">
                {loginLogs.map((l) => (
                  <tr key={l.id} className="hover:bg-slate-50 transition-colors">
                    <td className="px-4 py-3 font-medium text-slate-900 text-xs">{l.email}</td>
                    <td className="px-4 py-3">
                      <StatusBadge status={l.status === 'success' ? 'success' : 'failed'} />
                    </td>
                    <td className="px-4 py-3 font-mono text-xs text-slate-700">{l.ip_address}</td>
                    <td className="px-4 py-3 text-xs text-slate-500 truncate max-w-xs">{l.user_agent}</td>
                    <td className="px-4 py-3 text-xs text-slate-500">{l.created_at}</td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
        </div>
      )}

      {/* Create Admin Modal */}
      <Modal
        isOpen={createAdminOpen}
        onClose={() => setCreateAdminOpen(false)}
        title="Create New Super Admin Staff Account"
        size="md"
      >
        <form onSubmit={handleCreateAdmin} className="space-y-4">
          <div>
            <label className="block text-xs font-semibold text-slate-700 mb-1">Full Name</label>
            <input
              type="text"
              required
              value={newAdmin.name}
              onChange={(e) => setNewAdmin({ ...newAdmin, name: e.target.value })}
              className="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
              placeholder="e.g. Anand Sharma"
            />
          </div>

          <div>
            <label className="block text-xs font-semibold text-slate-700 mb-1">Email Address</label>
            <input
              type="email"
              required
              value={newAdmin.email}
              onChange={(e) => setNewAdmin({ ...newAdmin, email: e.target.value })}
              className="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
              placeholder="staff@dogyepaper.com"
            />
          </div>

          <div>
            <label className="block text-xs font-semibold text-slate-700 mb-1">Temporary Password</label>
            <input
              type="password"
              required
              minLength={8}
              value={newAdmin.password}
              onChange={(e) => setNewAdmin({ ...newAdmin, password: e.target.value })}
              className="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
              placeholder="Minimum 8 characters"
            />
          </div>

          <div>
            <label className="block text-xs font-semibold text-slate-700 mb-1">Administrative Role</label>
            <select
              value={newAdmin.role_id}
              onChange={(e) => setNewAdmin({ ...newAdmin, role_id: parseInt(e.target.value) })}
              className="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
            >
              {roles.map((r) => (
                <option key={r.id} value={r.id}>{r.name}</option>
              ))}
            </select>
          </div>

          <div className="flex justify-end gap-3 pt-4 border-t border-slate-100">
            <button
              type="button"
              onClick={() => setCreateAdminOpen(false)}
              className="px-4 py-2 text-sm font-medium text-slate-700 bg-white border border-slate-200 rounded-lg hover:bg-slate-50"
            >
              Cancel
            </button>
            <button
              type="submit"
              disabled={submittingAdmin}
              className="px-4 py-2 text-sm font-medium text-white bg-blue-600 rounded-lg hover:bg-blue-700 disabled:opacity-50"
            >
              {submittingAdmin ? 'Creating...' : 'Create Admin'}
            </button>
          </div>
        </form>
      </Modal>
    </div>
  );
}
