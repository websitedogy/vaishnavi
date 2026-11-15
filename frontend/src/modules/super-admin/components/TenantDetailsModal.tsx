import React, { useState, useEffect } from 'react';
import { Modal } from '../../../shared/components/Modal';
import { StatusBadge } from '../../../shared/components/StatusBadge';
import { api } from '../services/api';
import {
  Building2,
  Globe,
  CreditCard,
  DollarSign,
  Palette,
  HardDrive,
  History,
  ShieldAlert,
  Headphones,
  CheckCircle,
  ExternalLink,
  Loader2,
} from 'lucide-react';

interface TenantDetailsModalProps {
  tenantId: number | null;
  isOpen: boolean;
  onClose: () => void;
}

export const TenantDetailsModal: React.FC<TenantDetailsModalProps> = ({
  tenantId,
  isOpen,
  onClose,
}) => {
  const [activeTab, setActiveTab] = useState('overview');
  const [loading, setLoading] = useState(false);
  const [details, setDetails] = useState<any>(null);

  useEffect(() => {
    if (isOpen && tenantId) {
      setLoading(true);
      api.tenants
        .get(tenantId)
        .then((data) => setDetails(data))
        .catch((err) => console.error(err))
        .finally(() => setLoading(false));
    }
  }, [isOpen, tenantId]);

  if (!isOpen || !tenantId) return null;

  const tenant = details?.tenant;

  const tabs = [
    { id: 'overview', name: 'Overview', icon: Building2 },
    { id: 'company', name: 'Company Info', icon: Building2 },
    { id: 'domains', name: 'Domain & SSL', icon: Globe },
    { id: 'subscription', name: 'Subscription', icon: CreditCard },
    { id: 'payments', name: 'Payments', icon: DollarSign },
    { id: 'branding', name: 'Branding', icon: Palette },
    { id: 'storage', name: 'Storage & Usage', icon: HardDrive },
    { id: 'activity', name: 'Activity Logs', icon: History },
    { id: 'security', name: 'Security', icon: ShieldAlert },
    { id: 'support', name: 'Support', icon: Headphones },
  ];

  return (
    <Modal
      isOpen={isOpen}
      onClose={onClose}
      title={tenant?.name || 'Tenant Details'}
      description={`Tenant ID: #${tenant?.id} • Slug: ${tenant?.slug || ''}`}
      maxWidth="4xl"
    >
      {loading || !tenant ? (
        <div className="py-20 flex flex-col items-center justify-center text-slate-400">
          <Loader2 className="w-8 h-8 animate-spin text-blue-600 mb-2" />
          <p className="text-xs">Fetching tenant architecture details...</p>
        </div>
      ) : (
        <div className="space-y-6">
          {/* Top Tenant Summary Banner */}
          <div className="bg-slate-50 border border-slate-200 rounded-xl p-4 flex flex-wrap items-center justify-between gap-4">
            <div className="flex items-center space-x-3">
              <div className="w-12 h-12 rounded-xl bg-blue-600 text-white font-black flex items-center justify-center text-lg">
                {tenant.name.charAt(0)}
              </div>
              <div>
                <h4 className="text-base font-bold text-slate-900">{tenant.name}</h4>
                <p className="text-xs text-slate-500">{tenant.company_name} • {tenant.email}</p>
              </div>
            </div>
            <div className="flex items-center space-x-2">
              <StatusBadge status={tenant.status} size="md" />
              <span className="text-xs font-semibold px-2.5 py-1 rounded-full bg-slate-200 text-slate-700">
                {tenant.subscription}
              </span>
            </div>
          </div>

          {/* Tab Navigation */}
          <div className="flex items-center space-x-1 border-b border-slate-200 overflow-x-auto pb-1 text-xs">
            {tabs.map((tab) => {
              const Icon = tab.icon;
              const isCurr = activeTab === tab.id;
              return (
                <button
                  key={tab.id}
                  onClick={() => setActiveTab(tab.id)}
                  className={`flex items-center space-x-1.5 px-3 py-2 rounded-lg font-semibold flex-shrink-0 transition-colors ${
                    isCurr
                      ? 'bg-blue-600 text-white shadow-xs'
                      : 'text-slate-500 hover:text-slate-900 hover:bg-slate-100'
                  }`}
                >
                  <Icon className="w-3.5 h-3.5" />
                  <span>{tab.name}</span>
                </button>
              );
            })}
          </div>

          {/* Tab Content */}
          <div className="text-xs text-slate-700 min-h-[250px]">
            {activeTab === 'overview' && (
              <div className="grid grid-cols-2 gap-4 divide-y divide-slate-100">
                <div className="p-2 space-y-1">
                  <span className="text-slate-400 block font-medium">Newspaper Name</span>
                  <span className="font-semibold text-slate-900 text-sm">{tenant.name}</span>
                </div>
                <div className="p-2 space-y-1">
                  <span className="text-slate-400 block font-medium">Primary Domain</span>
                  <span className="font-mono text-blue-600">{tenant.domain || 'Not configured'}</span>
                </div>
                <div className="p-2 space-y-1">
                  <span className="text-slate-400 block font-medium">Storage Quota</span>
                  <span className="font-semibold text-slate-900">{tenant.storage_used_mb} MB / {tenant.storage_limit_mb} MB</span>
                </div>
                <div className="p-2 space-y-1">
                  <span className="text-slate-400 block font-medium">Default Language</span>
                  <span className="font-semibold text-slate-900 uppercase">{tenant.default_language}</span>
                </div>
                <div className="p-2 space-y-1">
                  <span className="text-slate-400 block font-medium">Created Date</span>
                  <span className="text-slate-700">{new Date(tenant.created_at).toLocaleDateString()}</span>
                </div>
                <div className="p-2 space-y-1">
                  <span className="text-slate-400 block font-medium">Subscription Expiry</span>
                  <span className="text-slate-700">{tenant.subscription_ends_at ? new Date(tenant.subscription_ends_at).toLocaleDateString() : 'N/A (Trial)'}</span>
                </div>
              </div>
            )}

            {activeTab === 'company' && (
              <div className="space-y-3">
                <div className="grid grid-cols-2 gap-4">
                  <div>
                    <span className="text-slate-400 block font-medium">Company Name</span>
                    <span className="font-semibold text-slate-900">{tenant.company_name}</span>
                  </div>
                  <div>
                    <span className="text-slate-400 block font-medium">Contact Person</span>
                    <span className="font-semibold text-slate-900">{tenant.contact_person}</span>
                  </div>
                  <div>
                    <span className="text-slate-400 block font-medium">Publisher Email</span>
                    <span className="text-slate-900">{tenant.email}</span>
                  </div>
                  <div>
                    <span className="text-slate-400 block font-medium">Mobile Number</span>
                    <span className="text-slate-900">{tenant.phone || 'N/A'}</span>
                  </div>
                  <div>
                    <span className="text-slate-400 block font-medium">State & District</span>
                    <span className="text-slate-900">{tenant.district}, {tenant.state}</span>
                  </div>
                  <div>
                    <span className="text-slate-400 block font-medium">Mandal</span>
                    <span className="text-slate-900">{tenant.mandal || 'N/A'}</span>
                  </div>
                </div>
              </div>
            )}

            {activeTab === 'domains' && (
              <div className="space-y-4">
                <h5 className="font-bold text-slate-900">Custom Domains & SSL Certificates</h5>
                <div className="border border-slate-200 rounded-xl divide-y divide-slate-200">
                  {tenant.domains?.map((d: any) => (
                    <div key={d.id} className="p-3 flex items-center justify-between">
                      <div>
                        <div className="flex items-center space-x-2">
                          <span className="font-mono font-semibold text-slate-900">{d.host}</span>
                          {d.is_primary && (
                            <span className="px-1.5 py-0.5 rounded bg-blue-100 text-blue-800 text-[10px] font-bold">
                              PRIMARY
                            </span>
                          )}
                        </div>
                        <p className="text-[11px] text-slate-400 mt-0.5">
                          CloudFront: {d.cloudfront_distribution_id || 'Pending attachment'}
                        </p>
                      </div>
                      <div className="flex items-center space-x-2">
                        <StatusBadge status={d.verification_status} />
                        <StatusBadge status={d.ssl_status} />
                      </div>
                    </div>
                  ))}
                </div>
              </div>
            )}

            {activeTab === 'subscription' && (
              <div className="space-y-4">
                <div className="bg-slate-50 p-4 rounded-xl border border-slate-200 space-y-2">
                  <div className="flex justify-between">
                    <span className="font-bold text-slate-900">Active Package:</span>
                    <span className="font-semibold text-blue-600">{tenant.subscription}</span>
                  </div>
                  <div className="flex justify-between">
                    <span>Billing Cycle:</span>
                    <span className="capitalize">{tenant.billing_cycle}</span>
                  </div>
                  <div className="flex justify-between">
                    <span>Amount:</span>
                    <span className="font-semibold text-slate-900">₹{tenant.subscription_amount}</span>
                  </div>
                </div>
              </div>
            )}

            {activeTab === 'payments' && (
              <div className="space-y-3">
                <h5 className="font-bold text-slate-900">Recent Payment Transactions</h5>
                <div className="border border-slate-200 rounded-xl divide-y divide-slate-200">
                  {tenant.payments?.length === 0 ? (
                    <p className="p-4 text-center text-slate-400">No payment records found.</p>
                  ) : (
                    tenant.payments?.map((p: any) => (
                      <div key={p.id} className="p-3 flex justify-between items-center">
                        <div>
                          <p className="font-mono font-bold text-slate-900">{p.transaction_reference}</p>
                          <p className="text-[11px] text-slate-400">{p.gateway.toUpperCase()} • {new Date(p.created_at).toLocaleDateString()}</p>
                        </div>
                        <div className="text-right">
                          <p className="font-bold text-slate-900">₹{p.amount}</p>
                          <StatusBadge status={p.status} />
                        </div>
                      </div>
                    ))
                  )}
                </div>
              </div>
            )}

            {activeTab === 'branding' && (
              <div className="space-y-3">
                <div className="flex items-center space-x-4">
                  <div className="flex items-center space-x-2">
                    <div className="w-6 h-6 rounded-md border" style={{ backgroundColor: tenant.branding?.primary_color || '#2563EB' }} />
                    <span className="font-mono">{tenant.branding?.primary_color || '#2563EB'} (Primary)</span>
                  </div>
                  <div className="flex items-center space-x-2">
                    <div className="w-6 h-6 rounded-md border" style={{ backgroundColor: tenant.branding?.secondary_color || '#0EA5E9' }} />
                    <span className="font-mono">{tenant.branding?.secondary_color || '#0EA5E9'} (Secondary)</span>
                  </div>
                </div>
              </div>
            )}

            {activeTab === 'storage' && (
              <div className="space-y-3">
                <div className="bg-slate-50 p-4 rounded-xl border border-slate-200 space-y-2">
                  <div className="flex justify-between">
                    <span>Used Storage:</span>
                    <span className="font-bold text-slate-900">{tenant.storage_used_mb} MB</span>
                  </div>
                  <div className="flex justify-between">
                    <span>Storage Limit:</span>
                    <span className="font-bold text-slate-900">{tenant.storage_limit_mb} MB</span>
                  </div>
                  <div className="w-full bg-slate-200 h-2 rounded-full overflow-hidden">
                    <div
                      className="bg-blue-600 h-full rounded-full"
                      style={{ width: `${Math.min(100, (tenant.storage_used_mb / tenant.storage_limit_mb) * 100)}%` }}
                    />
                  </div>
                </div>
              </div>
            )}

            {activeTab === 'activity' && (
              <div className="space-y-3">
                <div className="border border-slate-200 rounded-xl divide-y divide-slate-100 max-h-60 overflow-y-auto">
                  {details?.activity_logs?.map((act: any) => (
                    <div key={act.id} className="p-2.5">
                      <div className="flex justify-between font-mono text-[11px]">
                        <span className="font-bold text-blue-600">{act.action}</span>
                        <span className="text-slate-400">{new Date(act.performed_at).toLocaleString()}</span>
                      </div>
                      <p className="text-slate-600 mt-1">{act.summary}</p>
                    </div>
                  ))}
                </div>
              </div>
            )}

            {activeTab === 'security' && (
              <div className="space-y-3">
                <div className="p-3 bg-slate-50 rounded-xl border border-slate-200 space-y-1">
                  <p className="font-semibold text-slate-900">Tenant Isolation Status</p>
                  <p className="text-slate-600">Database Schema: <span className="font-mono font-bold text-slate-800">{tenant.database_name}</span></p>
                  <p className="text-slate-600">AWS S3 Prefix: <span className="font-mono font-bold text-slate-800">{tenant.storage_prefix}</span></p>
                </div>
              </div>
            )}

            {activeTab === 'support' && (
              <div className="space-y-3">
                {details?.support_tickets?.length === 0 ? (
                  <p className="p-4 text-center text-slate-400">No support tickets recorded for this publisher.</p>
                ) : (
                  details?.support_tickets?.map((t: any) => (
                    <div key={t.id} className="p-3 border border-slate-200 rounded-xl flex justify-between">
                      <div>
                        <p className="font-bold text-slate-900">{t.subject}</p>
                        <p className="text-[11px] text-slate-500">#{t.ticket_number} • {t.category}</p>
                      </div>
                      <StatusBadge status={t.status} />
                    </div>
                  ))
                )}
              </div>
            )}
          </div>
        </div>
      )}
    </Modal>
  );
};
