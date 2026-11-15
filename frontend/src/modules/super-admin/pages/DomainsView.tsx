import React, { useState, useEffect } from 'react';
import { api } from '../services/api';
import { TenantDomain, Tenant } from '../types';
import { DataTable, Column } from '../../../shared/components/DataTable';
import { StatusBadge } from '../../../shared/components/StatusBadge';
import { Modal } from '../../../shared/components/Modal';
import { ConfirmationDialog } from '../../../shared/components/ConfirmationDialog';
import {
  Globe,
  Plus,
  Search,
  CheckCircle2,
  AlertTriangle,
  RotateCw,
  Trash2,
  ShieldCheck,
  Server,
  Copy,
  ExternalLink,
} from 'lucide-react';

export const DomainsView: React.FC = () => {
  const [domains, setDomains] = useState<TenantDomain[]>([]);
  const [tenants, setTenants] = useState<Tenant[]>([]);
  const [loading, setLoading] = useState(true);
  const [search, setSearch] = useState('');

  // Modals
  const [addModalOpen, setAddModalOpen] = useState(false);
  const [newDomainData, setNewDomainData] = useState({
    tenant_id: 0,
    host: '',
    type: 'custom',
    is_primary: false,
  });

  const [verifyModalDomain, setVerifyModalDomain] = useState<TenantDomain | null>(null);
  const [isVerifying, setIsVerifying] = useState(false);
  const [verifyResult, setVerifyResult] = useState<any>(null);

  // Disconnect Dialog
  const [deleteDomain, setDeleteDomain] = useState<TenantDomain | null>(null);

  const fetchDomains = () => {
    setLoading(true);
    api.domains
      .list({ search })
      .then((res: any) => setDomains(res.data || []))
      .catch((err) => console.error(err))
      .finally(() => setLoading(false));
  };

  useEffect(() => {
    fetchDomains();
    api.tenants.list({ per_page: 100 }).then((res: any) => {
      setTenants(res.data || []);
      if (res.data?.length > 0) {
        setNewDomainData((prev) => ({ ...prev, tenant_id: res.data[0].id }));
      }
    });
  }, [search]);

  const handleCreateDomain = async (e: React.FormEvent) => {
    e.preventDefault();
    if (!newDomainData.host || !newDomainData.tenant_id) return;
    try {
      const created = await api.domains.create(newDomainData);
      setAddModalOpen(false);
      setNewDomainData({ tenant_id: tenants[0]?.id || 0, host: '', type: 'custom', is_primary: false });
      fetchDomains();
      // Open verification instructions immediately
      setVerifyModalDomain(created);
    } catch (err: any) {
      alert(err?.response?.data?.message || 'Failed to register domain');
    }
  };

  const handleVerifyDns = async (simulate = false) => {
    if (!verifyModalDomain) return;
    setIsVerifying(true);
    setVerifyResult(null);
    try {
      const res = await api.domains.verify(verifyModalDomain.id, simulate);
      setVerifyResult(res);
      fetchDomains();
    } catch (err: any) {
      setVerifyResult({
        verified: false,
        message: err?.response?.data?.message || 'DNS verification failed. TXT record not propagated.',
      });
    } finally {
      setIsVerifying(false);
    }
  };

  const handleProvisionSsl = async (domain: TenantDomain) => {
    try {
      await api.domains.provisionSsl(domain.id);
      fetchDomains();
    } catch (err: any) {
      alert(err?.response?.data?.message || 'SSL provisioning failed');
    }
  };

  const handleConfirmDisconnect = async () => {
    if (!deleteDomain) return;
    try {
      await api.domains.delete(deleteDomain.id);
      setDeleteDomain(null);
      fetchDomains();
    } catch (err: any) {
      alert('Failed to disconnect domain');
    }
  };

  const columns: Column<TenantDomain>[] = [
    {
      key: 'host',
      header: 'Domain Host',
      render: (d) => (
        <div>
          <span className="font-mono font-bold text-slate-900 block">{d.host}</span>
          <span className="text-[11px] text-slate-400 capitalize">{d.type} domain</span>
        </div>
      ),
    },
    {
      key: 'tenant',
      header: 'Publisher Tenant',
      render: (d) => (
        <div>
          <span className="font-semibold text-slate-800 text-xs block">{d.tenant?.name || 'Publisher'}</span>
          <span className="text-[10px] text-slate-400 font-mono">#{d.tenant_id}</span>
        </div>
      ),
    },
    {
      key: 'verification',
      header: 'DNS Ownership',
      render: (d) => <StatusBadge status={d.verification_status} />,
    },
    {
      key: 'ssl',
      header: 'SSL Certificate',
      render: (d) => (
        <div>
          <StatusBadge status={d.ssl_status} />
          {d.ssl_expires_at && (
            <span className="text-[10px] text-slate-400 block mt-0.5">
              Expires: {new Date(d.ssl_expires_at).toLocaleDateString()}
            </span>
          )}
        </div>
      ),
    },
    {
      key: 'cloudfront',
      header: 'CloudFront CDN',
      render: (d) => (
        <span className="font-mono text-xs text-slate-600">
          {d.cloudfront_distribution_id || 'Pending provision'}
        </span>
      ),
    },
    {
      key: 'health',
      header: 'Health',
      render: (d) => <StatusBadge status={d.health_status} />,
    },
    {
      key: 'actions',
      header: 'Actions',
      className: 'text-right',
      render: (d) => (
        <div className="flex items-center justify-end space-x-1.5">
          <button
            onClick={() => {
              setVerifyModalDomain(d);
              setVerifyResult(null);
            }}
            className="p-1.5 text-blue-600 hover:bg-blue-50 rounded-lg transition-colors"
            title="Inspect DNS & Verification"
          >
            <CheckCircle2 className="w-4 h-4" />
          </button>

          {d.verification_status === 'verified' && d.ssl_status !== 'active' && (
            <button
              onClick={() => handleProvisionSsl(d)}
              className="p-1.5 text-emerald-600 hover:bg-emerald-50 rounded-lg transition-colors"
              title="Provision ACM SSL Certificate"
            >
              <ShieldCheck className="w-4 h-4" />
            </button>
          )}

          <button
            onClick={() => setDeleteDomain(d)}
            className="p-1.5 text-rose-500 hover:bg-rose-50 rounded-lg transition-colors"
            title="Disconnect Domain"
          >
            <Trash2 className="w-4 h-4" />
          </button>
        </div>
      ),
    },
  ];

  return (
    <div className="space-y-6">
      {/* Header */}
      <div className="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
          <h2 className="text-xl font-bold text-slate-900 tracking-tight">Domain & SSL Central Management</h2>
          <p className="text-xs text-slate-500 mt-0.5">
            Automated DNS validation, Amazon CloudFront multi-tenant routing, and ACM SSL life-cycle engine.
          </p>
        </div>

        <button
          onClick={() => setAddModalOpen(true)}
          className="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-xs font-bold rounded-xl flex items-center space-x-2 shadow-xs transition-colors self-start sm:self-auto"
        >
          <Plus className="w-4 h-4" />
          <span>Add Custom Domain</span>
        </button>
      </div>

      {/* Table */}
      <DataTable columns={columns} data={domains} isLoading={loading} />

      {/* Add Domain Modal */}
      <Modal isOpen={addModalOpen} onClose={() => setAddModalOpen(false)} title="Register Custom Domain" maxWidth="md">
        <form onSubmit={handleCreateDomain} className="space-y-4 text-xs">
          <div>
            <label className="block font-medium text-slate-700 mb-1">Select Publisher Tenant *</label>
            <select
              value={newDomainData.tenant_id}
              onChange={(e) => setNewDomainData({ ...newDomainData, tenant_id: Number(e.target.value) })}
              className="w-full border border-slate-300 rounded-lg px-3 py-2 bg-white"
            >
              {tenants.map((t) => (
                <option key={t.id} value={t.id}>
                  {t.name} (#{t.id})
                </option>
              ))}
            </select>
          </div>

          <div>
            <label className="block font-medium text-slate-700 mb-1">Domain Host Name *</label>
            <input
              type="text"
              required
              placeholder="e.g. epaper.mydailynews.com"
              value={newDomainData.host}
              onChange={(e) => setNewDomainData({ ...newDomainData, host: e.target.value })}
              className="w-full border border-slate-300 rounded-lg px-3 py-2 font-mono focus:ring-2 focus:ring-blue-500"
            />
          </div>

          <div className="flex items-center space-x-2">
            <input
              type="checkbox"
              id="is_primary"
              checked={newDomainData.is_primary}
              onChange={(e) => setNewDomainData({ ...newDomainData, is_primary: e.target.checked })}
              className="rounded text-blue-600"
            />
            <label htmlFor="is_primary" className="text-slate-700 font-medium">Set as Primary Publishing Domain</label>
          </div>

          <div className="flex justify-end space-x-2 pt-3 border-t border-slate-100">
            <button
              type="button"
              onClick={() => setAddModalOpen(false)}
              className="px-4 py-2 text-slate-600 hover:bg-slate-100 rounded-lg font-medium"
            >
              Cancel
            </button>
            <button
              type="submit"
              className="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white font-bold rounded-lg shadow-xs"
            >
              Register & Generate DNS Records
            </button>
          </div>
        </form>
      </Modal>

      {/* DNS Verification Challenge Modal */}
      {verifyModalDomain && (
        <Modal
          isOpen={!!verifyModalDomain}
          onClose={() => setVerifyModalDomain(null)}
          title={`DNS Challenge: ${verifyModalDomain.host}`}
          maxWidth="lg"
        >
          <div className="space-y-4 text-xs">
            <p className="text-slate-600">
              Provide these required DNS records to the newspaper publisher's IT / webmaster team. Both TXT ownership verification and CNAME traffic routing are required:
            </p>

            <div className="space-y-3 font-mono">
              <div className="p-3 bg-slate-50 border border-slate-200 rounded-xl space-y-1">
                <span className="text-slate-400 text-[10px] uppercase font-sans font-bold">1. TXT Verification Record</span>
                <p className="text-slate-800 break-all"><span className="text-slate-500 font-bold">Host:</span> {verifyModalDomain.txt_record_host || `_dogy-challenge.${verifyModalDomain.host}`}</p>
                <p className="text-blue-600 break-all"><span className="text-slate-500 font-bold">Value:</span> {verifyModalDomain.txt_record_value || `dogy-token=${verifyModalDomain.verification_token}`}</p>
              </div>

              <div className="p-3 bg-slate-50 border border-slate-200 rounded-xl space-y-1">
                <span className="text-slate-400 text-[10px] uppercase font-sans font-bold">2. CNAME Traffic Routing</span>
                <p className="text-slate-800 break-all"><span className="text-slate-500 font-bold">Host:</span> {verifyModalDomain.host}</p>
                <p className="text-emerald-600 break-all"><span className="text-slate-500 font-bold">Target:</span> cname.dogyepaper.com</p>
              </div>
            </div>

            {verifyResult && (
              <div
                className={`p-3 rounded-xl border text-xs ${
                  verifyResult.verified
                    ? 'bg-emerald-50 border-emerald-200 text-emerald-800'
                    : 'bg-amber-50 border-amber-200 text-amber-800'
                }`}
              >
                <p className="font-bold">{verifyResult.verified ? 'Verification Passed!' : 'Verification Pending'}</p>
                <p className="mt-0.5">{verifyResult.message}</p>
              </div>
            )}

            <div className="flex items-center justify-between pt-3 border-t border-slate-200">
              <button
                type="button"
                onClick={() => handleVerifyDns(true)}
                className="text-[11px] text-blue-600 hover:underline font-semibold"
                title="Simulate successful verification for development testing"
              >
                (Dev Simulation Test)
              </button>

              <div className="flex space-x-2">
                <button
                  type="button"
                  onClick={() => setVerifyModalDomain(null)}
                  className="px-3 py-1.5 text-slate-600 hover:bg-slate-100 rounded-lg font-medium"
                >
                  Close
                </button>
                <button
                  type="button"
                  disabled={isVerifying}
                  onClick={() => handleVerifyDns(false)}
                  className="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg font-bold flex items-center space-x-1.5 shadow-xs"
                >
                  {isVerifying ? <RotateCw className="w-4 h-4 animate-spin" /> : <CheckCircle2 className="w-4 h-4" />}
                  <span>Check DNS Propagation Now</span>
                </button>
              </div>
            </div>
          </div>
        </Modal>
      )}

      {/* Disconnect Domain Confirmation */}
      <ConfirmationDialog
        isOpen={!!deleteDomain}
        onClose={() => setDeleteDomain(null)}
        onConfirm={handleConfirmDisconnect}
        title="Disconnect Domain"
        message={`Are you sure you want to disconnect ${deleteDomain?.host}? All traffic routed through CloudFront CDN for this domain will immediately cease.`}
        confirmLabel="Disconnect Domain"
        isDestructive
      />
    </div>
  );
};
