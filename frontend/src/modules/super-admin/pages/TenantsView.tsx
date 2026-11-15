import React, { useState, useEffect } from 'react';
import { api } from '../services/api';
import { Tenant, SubscriptionPackage } from '../types';
import { DataTable, Column } from '../../../shared/components/DataTable';
import { StatusBadge } from '../../../shared/components/StatusBadge';
import { ConfirmationDialog } from '../../../shared/components/ConfirmationDialog';
import {
  Building2,
  Plus,
  Search,
  Filter,
  MoreVertical,
  Eye,
  CheckCircle,
  AlertOctagon,
  RotateCw,
  Archive,
  Trash2,
  ExternalLink,
} from 'lucide-react';

interface TenantsViewProps {
  onOpenCreateWizard: () => void;
  onOpenDetails: (id: number) => void;
}

export const TenantsView: React.FC<TenantsViewProps> = ({
  onOpenCreateWizard,
  onOpenDetails,
}) => {
  const [tenants, setTenants] = useState<Tenant[]>([]);
  const [loading, setLoading] = useState(true);
  const [search, setSearch] = useState('');
  const [statusFilter, setStatusFilter] = useState('');
  const [currentPage, setCurrentPage] = useState(1);
  const [totalPages, setTotalPages] = useState(1);
  const [totalCount, setTotalCount] = useState(0);

  // Dialog State
  const [dialogConfig, setDialogConfig] = useState<{
    isOpen: boolean;
    title: string;
    message: string;
    confirmLabel: string;
    isDestructive?: boolean;
    requiredText?: string;
    action: () => Promise<void>;
  }>({
    isOpen: false,
    title: '',
    message: '',
    confirmLabel: 'Confirm',
    action: async () => {},
  });

  const fetchTenants = (page = 1) => {
    setLoading(true);
    api.tenants
      .list({ page, search, status: statusFilter, per_page: 10 })
      .then((res: any) => {
        setTenants(res.data);
        setCurrentPage(res.current_page);
        setTotalPages(res.last_page);
        setTotalCount(res.total);
      })
      .catch((err) => console.error(err))
      .finally(() => setLoading(false));
  };

  useEffect(() => {
    fetchTenants(1);
  }, [search, statusFilter]);

  const handleActivate = (tenant: Tenant) => {
    setDialogConfig({
      isOpen: true,
      title: `Activate Tenant: ${tenant.name}`,
      message: `Are you sure you want to activate ${tenant.name}? Full platform publishing will be immediately restored.`,
      confirmLabel: 'Activate Tenant',
      action: async () => {
        await api.tenants.activate(tenant.id);
        fetchTenants(currentPage);
      },
    });
  };

  const handleSuspend = (tenant: Tenant) => {
    setDialogConfig({
      isOpen: true,
      title: `Suspend Tenant: ${tenant.name}`,
      message: `Suspending will block newspaper edition publishing and public domain access. Enter the operational reason below:`,
      confirmLabel: 'Suspend Tenant',
      isDestructive: true,
      action: async () => {
        await api.tenants.suspend(tenant.id, 'Super Admin manual suspension.');
        fetchTenants(currentPage);
      },
    });
  };

  const handleRenew = (tenant: Tenant) => {
    setDialogConfig({
      isOpen: true,
      title: `Renew Subscription: ${tenant.name}`,
      message: `Extend subscription expiration for ${tenant.name} by 1 month.`,
      confirmLabel: 'Confirm Renewal',
      action: async () => {
        await api.tenants.renew(tenant.id, 1);
        fetchTenants(currentPage);
      },
    });
  };

  const handleDelete = (tenant: Tenant) => {
    const confirmCode = `DELETE-${tenant.slug.toUpperCase()}`;
    setDialogConfig({
      isOpen: true,
      title: `Permanently Delete Tenant: ${tenant.name}`,
      message: `This action permanently wipes all tenant database tables, editions, page image archives, and domains. This cannot be undone.`,
      confirmLabel: 'Permanently Delete',
      isDestructive: true,
      requiredText: confirmCode,
      action: async () => {
        await api.tenants.delete(tenant.id, confirmCode);
        fetchTenants(currentPage);
      },
    });
  };

  const columns: Column<Tenant>[] = [
    {
      key: 'id',
      header: 'Tenant ID',
      className: 'w-24 font-mono text-xs font-semibold text-slate-500',
      render: (t) => `#${t.id}`,
    },
    {
      key: 'name',
      header: 'Newspaper & Publisher',
      render: (t) => (
        <div>
          <button
            onClick={() => onOpenDetails(t.id)}
            className="font-bold text-slate-900 hover:text-blue-600 text-left block"
          >
            {t.name}
          </button>
          <span className="text-[11px] text-slate-500 block">{t.company_name}</span>
        </div>
      ),
    },
    {
      key: 'contact',
      header: 'Contact Person',
      render: (t) => (
        <div>
          <p className="font-medium text-slate-800">{t.contact_person}</p>
          <p className="text-[11px] text-slate-400">{t.email}</p>
        </div>
      ),
    },
    {
      key: 'location',
      header: 'State / District',
      render: (t) => (
        <span className="text-xs text-slate-600">
          {t.district ? `${t.district}, ${t.state}` : t.state || 'India'}
        </span>
      ),
    },
    {
      key: 'domain',
      header: 'Primary Domain',
      render: (t) => (
        <span className="font-mono text-xs text-blue-600">
          {t.domain || t.primary_domain?.host || 'Pending'}
        </span>
      ),
    },
    {
      key: 'subscription',
      header: 'Package',
      render: (t) => (
        <div>
          <span className="font-semibold text-slate-800 text-xs block">{t.subscription}</span>
          <span className="text-[10px] text-slate-400 capitalize">{t.billing_cycle}</span>
        </div>
      ),
    },
    {
      key: 'status',
      header: 'Status',
      render: (t) => <StatusBadge status={t.status} />,
    },
    {
      key: 'actions',
      header: 'Actions',
      className: 'text-right',
      render: (t) => (
        <div className="flex items-center justify-end space-x-1.5">
          <button
            onClick={() => onOpenDetails(t.id)}
            className="p-1.5 text-slate-400 hover:text-blue-600 hover:bg-blue-50 rounded-lg transition-colors"
            title="Inspect Tenant Architecture"
          >
            <Eye className="w-4 h-4" />
          </button>

          {t.status === 'suspended' ? (
            <button
              onClick={() => handleActivate(t)}
              className="p-1.5 text-emerald-600 hover:bg-emerald-50 rounded-lg transition-colors"
              title="Activate Tenant"
            >
              <CheckCircle className="w-4 h-4" />
            </button>
          ) : (
            <button
              onClick={() => handleSuspend(t)}
              className="p-1.5 text-amber-600 hover:bg-amber-50 rounded-lg transition-colors"
              title="Suspend Tenant"
            >
              <AlertOctagon className="w-4 h-4" />
            </button>
          )}

          <button
            onClick={() => handleRenew(t)}
            className="p-1.5 text-slate-500 hover:text-slate-800 hover:bg-slate-100 rounded-lg transition-colors"
            title="Renew Subscription"
          >
            <RotateCw className="w-4 h-4" />
          </button>

          <button
            onClick={() => handleDelete(t)}
            className="p-1.5 text-rose-500 hover:text-rose-700 hover:bg-rose-50 rounded-lg transition-colors"
            title="Delete Tenant"
          >
            <Trash2 className="w-4 h-4" />
          </button>
        </div>
      ),
    },
  ];

  return (
    <div className="space-y-6">
      {/* Header and Controls */}
      <div className="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
          <h2 className="text-xl font-bold text-slate-900 tracking-tight">Tenant Publishers Management</h2>
          <p className="text-xs text-slate-500 mt-0.5">
            Centrally oversee all white-label newspaper tenant databases, custom domains, and licenses.
          </p>
        </div>

        <button
          onClick={onOpenCreateWizard}
          className="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-xs font-bold rounded-xl flex items-center space-x-2 shadow-xs transition-colors self-start sm:self-auto"
        >
          <Plus className="w-4 h-4" />
          <span>New Publisher Wizard</span>
        </button>
      </div>

      {/* Filter and Search Bar */}
      <div className="bg-white border border-slate-200 rounded-xl p-3.5 flex flex-wrap items-center justify-between gap-3 shadow-2xs">
        <div className="flex items-center space-x-2 flex-1 max-w-sm">
          <Search className="w-4 h-4 text-slate-400" />
          <input
            type="text"
            value={search}
            onChange={(e) => setSearch(e.target.value)}
            placeholder="Search by newspaper, company, email..."
            className="w-full text-xs border-none focus:outline-none placeholder-slate-400"
          />
        </div>

        <div className="flex items-center space-x-2 text-xs">
          <Filter className="w-3.5 h-3.5 text-slate-400" />
          <select
            value={statusFilter}
            onChange={(e) => setStatusFilter(e.target.value)}
            className="border border-slate-200 rounded-lg px-2.5 py-1.5 bg-white text-slate-700 focus:ring-1 focus:ring-blue-500 text-xs"
          >
            <option value="">All Statuses</option>
            <option value="active">Active</option>
            <option value="trial">Trial</option>
            <option value="suspended">Suspended</option>
            <option value="expired">Expired</option>
            <option value="pending">Pending</option>
          </select>
        </div>
      </div>

      {/* Table */}
      <DataTable
        columns={columns}
        data={tenants}
        isLoading={loading}
        currentPage={currentPage}
        totalPages={totalPages}
        totalRecords={totalCount}
        onPageChange={(page) => fetchTenants(page)}
      />

      {/* Confirmation Dialog */}
      <ConfirmationDialog
        isOpen={dialogConfig.isOpen}
        onClose={() => setDialogConfig((prev) => ({ ...prev, isOpen: false }))}
        onConfirm={dialogConfig.action}
        title={dialogConfig.title}
        message={dialogConfig.message}
        confirmLabel={dialogConfig.confirmLabel}
        isDestructive={dialogConfig.isDestructive}
        requiredConfirmationText={dialogConfig.requiredText}
      />
    </div>
  );
};
