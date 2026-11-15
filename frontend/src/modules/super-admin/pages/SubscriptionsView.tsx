import React, { useState, useEffect } from 'react';
import { api } from '../services/api';
import { SubscriptionPackage, TenantSubscription, SubscriptionInvoice } from '../types';
import { DataTable, Column } from '../../../shared/components/DataTable';
import { StatusBadge } from '../../../shared/components/StatusBadge';
import { Modal } from '../../../shared/components/Modal';
import {
  CreditCard,
  Plus,
  Edit2,
  CheckCircle,
  FileText,
  TrendingUp,
  Layers,
  ArrowRight,
  ShieldCheck,
} from 'lucide-react';

export const SubscriptionsView: React.FC = () => {
  const [activeTab, setActiveTab] = useState<'packages' | 'subscriptions' | 'invoices'>('packages');
  const [packages, setPackages] = useState<SubscriptionPackage[]>([]);
  const [subscriptions, setSubscriptions] = useState<TenantSubscription[]>([]);
  const [invoices, setInvoices] = useState<SubscriptionInvoice[]>([]);
  const [loading, setLoading] = useState(true);

  // Package Modal State
  const [packageModalOpen, setPackageModalOpen] = useState(false);
  const [editingPackageId, setEditingPackageId] = useState<number | null>(null);
  const [packageForm, setPackageForm] = useState({
    name: '',
    billing_cycle: 'monthly',
    price: 4999,
    currency: 'INR',
    trial_days: 14,
    max_domains: 1,
    storage_limit_gb: 10,
    pdf_upload_limit_mb: 100,
    monthly_edition_limit: 31,
    admin_user_limit: 3,
    custom_branding_enabled: true,
    priority_support_enabled: false,
    analytics_enabled: true,
  });

  // Switch Package Modal
  const [switchSub, setSwitchSub] = useState<TenantSubscription | null>(null);
  const [targetPackageId, setTargetPackageId] = useState<number>(0);

  const fetchData = () => {
    setLoading(true);
    if (activeTab === 'packages') {
      api.subscriptions
        .getPackages()
        .then((res) => {
          setPackages(res);
          if (res.length > 0) setTargetPackageId(res[0].id);
        })
        .finally(() => setLoading(false));
    } else if (activeTab === 'subscriptions') {
      api.subscriptions
        .list()
        .then((res: any) => setSubscriptions(res.data || []))
        .finally(() => setLoading(false));
    } else {
      api.subscriptions
        .invoices()
        .then((res: any) => setInvoices(res.data || []))
        .finally(() => setLoading(false));
    }
  };

  useEffect(() => {
    fetchData();
  }, [activeTab]);

  const handleSavePackage = async (e: React.FormEvent) => {
    e.preventDefault();
    try {
      if (editingPackageId) {
        await api.subscriptions.updatePackage(editingPackageId, packageForm);
      } else {
        await api.subscriptions.createPackage(packageForm);
      }
      setPackageModalOpen(false);
      setEditingPackageId(null);
      fetchData();
    } catch (err: any) {
      alert(err?.response?.data?.message || 'Failed to save package');
    }
  };

  const handleSwitchPackage = async () => {
    if (!switchSub || !targetPackageId) return;
    try {
      await api.subscriptions.changePackage(switchSub.tenant_id, targetPackageId);
      setSwitchSub(null);
      fetchData();
    } catch (err: any) {
      alert(err?.response?.data?.message || 'Failed to change subscription package');
    }
  };

  const openEditPackage = (pkg: SubscriptionPackage) => {
    setEditingPackageId(pkg.id);
    setPackageForm({
      name: pkg.name,
      billing_cycle: pkg.billing_cycle,
      price: pkg.price,
      currency: pkg.currency,
      trial_days: pkg.trial_days,
      max_domains: pkg.entitlements?.max_domains || 1,
      storage_limit_gb: pkg.entitlements?.storage_limit_gb || 10,
      pdf_upload_limit_mb: pkg.entitlements?.pdf_upload_limit_mb || 100,
      monthly_edition_limit: pkg.entitlements?.monthly_edition_limit || 31,
      admin_user_limit: pkg.entitlements?.admin_user_limit || 3,
      custom_branding_enabled: pkg.entitlements?.custom_branding_enabled ?? true,
      priority_support_enabled: pkg.entitlements?.priority_support_enabled ?? false,
      analytics_enabled: pkg.entitlements?.analytics_enabled ?? true,
    });
    setPackageModalOpen(true);
  };

  const subColumns: Column<TenantSubscription>[] = [
    {
      key: 'tenant',
      header: 'Publisher Tenant',
      render: (s) => (
        <div>
          <span className="font-bold text-slate-900 block">{s.tenant?.name || 'Publisher'}</span>
          <span className="text-[11px] text-slate-400">ID: #{s.tenant_id}</span>
        </div>
      ),
    },
    {
      key: 'package',
      header: 'Assigned Package',
      render: (s) => (
        <div>
          <span className="font-semibold text-blue-600 block">{s.package?.name || 'Custom Package'}</span>
          <span className="text-[10px] text-slate-400 capitalize">{s.billing_cycle} Recurring</span>
        </div>
      ),
    },
    {
      key: 'amount',
      header: 'Rate',
      render: (s) => <span className="font-bold text-slate-900">₹{s.amount.toLocaleString()}</span>,
    },
    {
      key: 'dates',
      header: 'Current Period',
      render: (s) => (
        <span className="text-xs text-slate-600">
          {s.current_period_end ? new Date(s.current_period_end).toLocaleDateString() : 'Active Trial'}
        </span>
      ),
    },
    {
      key: 'status',
      header: 'Status',
      render: (s) => <StatusBadge status={s.status} />,
    },
    {
      key: 'actions',
      header: 'Action',
      className: 'text-right',
      render: (s) => (
        <button
          onClick={() => {
            setSwitchSub(s);
            setTargetPackageId(packages[0]?.id || 1);
          }}
          className="px-3 py-1 bg-slate-100 hover:bg-blue-50 text-slate-700 hover:text-blue-600 rounded-lg text-xs font-semibold transition-colors"
        >
          Change Plan
        </button>
      ),
    },
  ];

  const invColumns: Column<SubscriptionInvoice>[] = [
    {
      key: 'invoice_number',
      header: 'Invoice #',
      render: (inv) => <span className="font-mono font-bold text-slate-900">{inv.invoice_number}</span>,
    },
    {
      key: 'tenant',
      header: 'Publisher Tenant',
      render: (inv) => <span className="font-medium text-slate-800">{inv.tenant?.name || 'Tenant'}</span>,
    },
    {
      key: 'amount',
      header: 'Amount + Tax (GST 18%)',
      render: (inv) => (
        <div>
          <span className="font-bold text-slate-900 block">₹{(inv.amount + inv.tax_amount).toLocaleString()}</span>
          <span className="text-[10px] text-slate-400">Base: ₹{inv.amount} • Tax: ₹{inv.tax_amount}</span>
        </div>
      ),
    },
    {
      key: 'due_date',
      header: 'Due Date',
      render: (inv) => <span className="text-xs text-slate-600">{inv.due_date ? new Date(inv.due_date).toLocaleDateString() : 'Immediate'}</span>,
    },
    {
      key: 'status',
      header: 'Payment Status',
      render: (inv) => <StatusBadge status={inv.status} />,
    },
  ];

  return (
    <div className="space-y-6">
      {/* Header */}
      <div className="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
          <h2 className="text-xl font-bold text-slate-900 tracking-tight">Subscription Engine & Packaging</h2>
          <p className="text-xs text-slate-500 mt-0.5">
            Configure publisher tiers, server-side enforced quotas, billing frequencies, and invoices.
          </p>
        </div>

        {activeTab === 'packages' && (
          <button
            onClick={() => {
              setEditingPackageId(null);
              setPackageModalOpen(true);
            }}
            className="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-xs font-bold rounded-xl flex items-center space-x-2 shadow-xs transition-colors self-start sm:self-auto"
          >
            <Plus className="w-4 h-4" />
            <span>Create Package</span>
          </button>
        )}
      </div>

      {/* Tabs */}
      <div className="flex space-x-2 border-b border-slate-200">
        <button
          onClick={() => setActiveTab('packages')}
          className={`px-4 py-2 text-xs font-bold border-b-2 transition-colors ${
            activeTab === 'packages'
              ? 'border-blue-600 text-blue-600'
              : 'border-transparent text-slate-500 hover:text-slate-800'
          }`}
        >
          Packages & Entitlements ({packages.length})
        </button>
        <button
          onClick={() => setActiveTab('subscriptions')}
          className={`px-4 py-2 text-xs font-bold border-b-2 transition-colors ${
            activeTab === 'subscriptions'
              ? 'border-blue-600 text-blue-600'
              : 'border-transparent text-slate-500 hover:text-slate-800'
          }`}
        >
          Tenant Subscriptions
        </button>
        <button
          onClick={() => setActiveTab('invoices')}
          className={`px-4 py-2 text-xs font-bold border-b-2 transition-colors ${
            activeTab === 'invoices'
              ? 'border-blue-600 text-blue-600'
              : 'border-transparent text-slate-500 hover:text-slate-800'
          }`}
        >
          Subscription Invoices
        </button>
      </div>

      {/* Content */}
      {activeTab === 'packages' && (
        <div className="grid grid-cols-1 md:grid-cols-3 gap-6">
          {packages.map((pkg) => (
            <div
              key={pkg.id}
              className={`bg-white border rounded-2xl p-6 shadow-sm relative flex flex-col justify-between ${
                pkg.is_popular ? 'border-blue-500 ring-2 ring-blue-500/20' : 'border-slate-200'
              }`}
            >
              {pkg.is_popular && (
                <span className="absolute -top-3 right-6 px-2.5 py-0.5 rounded-full bg-blue-600 text-white text-[10px] font-bold tracking-wider uppercase shadow-xs">
                  Most Popular
                </span>
              )}

              <div>
                <div className="flex items-center justify-between">
                  <h3 className="text-base font-bold text-slate-900">{pkg.name}</h3>
                  <span className="text-[10px] font-mono text-slate-400">v{pkg.version}</span>
                </div>
                <p className="text-xs text-slate-500 mt-1">{pkg.description}</p>

                <div className="mt-4 pt-4 border-t border-slate-100">
                  <span className="text-3xl font-black text-slate-900">₹{pkg.price.toLocaleString()}</span>
                  <span className="text-xs text-slate-500 capitalize"> / {pkg.billing_cycle}</span>
                </div>

                <div className="mt-6 space-y-2.5 text-xs text-slate-700">
                  <div className="font-bold text-[11px] uppercase tracking-wider text-slate-400">
                    Enforced Server Limits
                  </div>
                  <div className="flex items-center space-x-2">
                    <CheckCircle className="w-4 h-4 text-emerald-500" />
                    <span><b>{pkg.entitlements?.max_domains}</b> Custom Domain(s)</span>
                  </div>
                  <div className="flex items-center space-x-2">
                    <CheckCircle className="w-4 h-4 text-emerald-500" />
                    <span><b>{pkg.entitlements?.storage_limit_gb} GB</b> High-Speed Cloud Storage</span>
                  </div>
                  <div className="flex items-center space-x-2">
                    <CheckCircle className="w-4 h-4 text-emerald-500" />
                    <span><b>{pkg.entitlements?.pdf_upload_limit_mb} MB</b> Max PDF File Upload</span>
                  </div>
                  <div className="flex items-center space-x-2">
                    <CheckCircle className="w-4 h-4 text-emerald-500" />
                    <span><b>{pkg.entitlements?.monthly_edition_limit}</b> Published Editions / Month</span>
                  </div>
                  <div className="flex items-center space-x-2">
                    <CheckCircle className="w-4 h-4 text-emerald-500" />
                    <span><b>{pkg.entitlements?.admin_user_limit}</b> Staff Admin Users</span>
                  </div>
                  {pkg.entitlements?.priority_support_enabled && (
                    <div className="flex items-center space-x-2">
                      <CheckCircle className="w-4 h-4 text-emerald-500" />
                      <span>Dedicated Priority Support SLA</span>
                    </div>
                  )}
                </div>
              </div>

              <button
                onClick={() => openEditPackage(pkg)}
                className="mt-6 w-full py-2 bg-slate-50 hover:bg-slate-100 text-slate-700 rounded-xl text-xs font-bold border border-slate-200 flex items-center justify-center space-x-1.5 transition-colors"
              >
                <Edit2 className="w-3.5 h-3.5" />
                <span>Edit Entitlements</span>
              </button>
            </div>
          ))}
        </div>
      )}

      {activeTab === 'subscriptions' && (
        <DataTable columns={subColumns} data={subscriptions} isLoading={loading} />
      )}

      {activeTab === 'invoices' && (
        <DataTable columns={invColumns} data={invoices} isLoading={loading} />
      )}

      {/* Create / Edit Package Modal */}
      <Modal
        isOpen={packageModalOpen}
        onClose={() => setPackageModalOpen(false)}
        title={editingPackageId ? 'Edit Package Entitlements' : 'Create Subscription Package'}
        maxWidth="lg"
      >
        <form onSubmit={handleSavePackage} className="space-y-4 text-xs">
          <div className="grid grid-cols-2 gap-3">
            <div>
              <label className="block font-medium text-slate-700 mb-1">Package Name *</label>
              <input
                type="text"
                required
                value={packageForm.name}
                onChange={(e) => setPackageForm({ ...packageForm, name: e.target.value })}
                className="w-full border border-slate-300 rounded-lg px-3 py-2"
              />
            </div>
            <div>
              <label className="block font-medium text-slate-700 mb-1">Billing Cycle</label>
              <select
                value={packageForm.billing_cycle}
                onChange={(e) => setPackageForm({ ...packageForm, billing_cycle: e.target.value })}
                className="w-full border border-slate-300 rounded-lg px-3 py-2 bg-white"
              >
                <option value="monthly">Monthly</option>
                <option value="quarterly">Quarterly</option>
                <option value="half_yearly">Half-Yearly</option>
                <option value="yearly">Yearly</option>
              </select>
            </div>
            <div>
              <label className="block font-medium text-slate-700 mb-1">Price (₹) *</label>
              <input
                type="number"
                required
                value={packageForm.price}
                onChange={(e) => setPackageForm({ ...packageForm, price: Number(e.target.value) })}
                className="w-full border border-slate-300 rounded-lg px-3 py-2"
              />
            </div>
            <div>
              <label className="block font-medium text-slate-700 mb-1">Trial Period (Days)</label>
              <input
                type="number"
                value={packageForm.trial_days}
                onChange={(e) => setPackageForm({ ...packageForm, trial_days: Number(e.target.value) })}
                className="w-full border border-slate-300 rounded-lg px-3 py-2"
              />
            </div>
          </div>

          <div className="pt-2 border-t border-slate-100">
            <h5 className="font-bold text-slate-900 mb-3">Enforced Entitlements Quotas</h5>
            <div className="grid grid-cols-2 gap-3">
              <div>
                <label className="block font-medium text-slate-700 mb-1">Max Domains</label>
                <input
                  type="number"
                  value={packageForm.max_domains}
                  onChange={(e) => setPackageForm({ ...packageForm, max_domains: Number(e.target.value) })}
                  className="w-full border border-slate-300 rounded-lg px-3 py-2"
                />
              </div>
              <div>
                <label className="block font-medium text-slate-700 mb-1">Storage Limit (GB)</label>
                <input
                  type="number"
                  value={packageForm.storage_limit_gb}
                  onChange={(e) => setPackageForm({ ...packageForm, storage_limit_gb: Number(e.target.value) })}
                  className="w-full border border-slate-300 rounded-lg px-3 py-2"
                />
              </div>
              <div>
                <label className="block font-medium text-slate-700 mb-1">Max PDF File Size (MB)</label>
                <input
                  type="number"
                  value={packageForm.pdf_upload_limit_mb}
                  onChange={(e) => setPackageForm({ ...packageForm, pdf_upload_limit_mb: Number(e.target.value) })}
                  className="w-full border border-slate-300 rounded-lg px-3 py-2"
                />
              </div>
              <div>
                <label className="block font-medium text-slate-700 mb-1">Monthly Edition Limit</label>
                <input
                  type="number"
                  value={packageForm.monthly_edition_limit}
                  onChange={(e) => setPackageForm({ ...packageForm, monthly_edition_limit: Number(e.target.value) })}
                  className="w-full border border-slate-300 rounded-lg px-3 py-2"
                />
              </div>
            </div>
          </div>

          <div className="flex justify-end space-x-2 pt-3 border-t border-slate-100">
            <button
              type="button"
              onClick={() => setPackageModalOpen(false)}
              className="px-4 py-2 text-slate-600 hover:bg-slate-100 rounded-lg font-medium"
            >
              Cancel
            </button>
            <button
              type="submit"
              className="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white font-bold rounded-lg shadow-xs"
            >
              Save Package Version
            </button>
          </div>
        </form>
      </Modal>

      {/* Switch Plan Modal */}
      {switchSub && (
        <Modal
          isOpen={!!switchSub}
          onClose={() => setSwitchSub(null)}
          title={`Switch Package: ${switchSub.tenant?.name}`}
          maxWidth="sm"
        >
          <div className="space-y-4 text-xs">
            <div>
              <label className="block font-medium text-slate-700 mb-1">Select New Package</label>
              <select
                value={targetPackageId}
                onChange={(e) => setTargetPackageId(Number(e.target.value))}
                className="w-full border border-slate-300 rounded-lg px-3 py-2 bg-white"
              >
                {packages.map((p) => (
                  <option key={p.id} value={p.id}>
                    {p.name} (₹{p.price} / {p.billing_cycle})
                  </option>
                ))}
              </select>
            </div>
            <p className="text-slate-500">
              Switching packages will recalculate storage limits and automatically generate a new invoice for the publisher.
            </p>
            <div className="flex justify-end space-x-2 pt-3 border-t border-slate-100">
              <button
                type="button"
                onClick={() => setSwitchSub(null)}
                className="px-3 py-1.5 text-slate-600 hover:bg-slate-100 rounded-lg font-medium"
              >
                Cancel
              </button>
              <button
                type="button"
                onClick={handleSwitchPackage}
                className="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white font-bold rounded-lg shadow-xs"
              >
                Confirm Switch
              </button>
            </div>
          </div>
        </Modal>
      )}
    </div>
  );
};
