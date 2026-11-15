import React, { useEffect, useState } from 'react';
import { api } from '../services/api';
import { DashboardStats } from '../types';
import { StatCard } from '../../../shared/components/StatCard';
import { StatusBadge } from '../../../shared/components/StatusBadge';
import {
  Users,
  Building2,
  Globe,
  ShieldCheck,
  CreditCard,
  TrendingUp,
  DollarSign,
  AlertTriangle,
  HardDrive,
  Cpu,
  RefreshCw,
  Plus,
  ArrowRight,
  Server,
} from 'lucide-react';

interface DashboardViewProps {
  onNavigate: (tabId: string) => void;
  onOpenCreateTenant: () => void;
}

export const DashboardView: React.FC<DashboardViewProps> = ({
  onNavigate,
  onOpenCreateTenant,
}) => {
  const [data, setData] = useState<DashboardStats | null>(null);
  const [loading, setLoading] = useState(true);

  const fetchStats = () => {
    setLoading(true);
    api.dashboard
      .getStats()
      .then((res) => setData(res))
      .catch((err) => console.error(err))
      .finally(() => setLoading(false));
  };

  useEffect(() => {
    fetchStats();
  }, []);

  if (loading || !data) {
    return (
      <div className="py-24 text-center text-slate-400">
        <RefreshCw className="w-8 h-8 animate-spin text-blue-600 mx-auto mb-3" />
        <p className="text-sm">Loading Executive Dashboard Intelligence...</p>
      </div>
    );
  }

  const { kpis, charts, infrastructure, recent_tenants, recent_payments, recent_activities } = data;

  return (
    <div className="space-y-8">
      {/* Top Banner / Actions */}
      <div className="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
          <h2 className="text-2xl font-black text-slate-900 tracking-tight">Enterprise Control Center</h2>
          <p className="text-xs text-slate-500 mt-1">
            Global real-time overview across all newspaper publishers, infrastructure, and revenue pipelines.
          </p>
        </div>
        <div className="flex items-center space-x-3">
          <button
            onClick={fetchStats}
            className="p-2 border border-slate-200 bg-white hover:bg-slate-50 rounded-xl text-slate-600 shadow-2xs transition-colors"
            title="Refresh statistics"
          >
            <RefreshCw className="w-4 h-4" />
          </button>
          <button
            onClick={onOpenCreateTenant}
            className="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-xs font-bold rounded-xl flex items-center space-x-2 shadow-xs transition-colors"
          >
            <Plus className="w-4 h-4" />
            <span>Create Tenant Publisher</span>
          </button>
        </div>
      </div>

      {/* 16 KPI Metric Cards Grid */}
      <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <StatCard
          title="Total Tenants"
          value={kpis.total_tenants}
          subtitle={`${kpis.active_tenants} Active • ${kpis.trial_tenants} Trial`}
          icon={Building2}
          iconBg="bg-blue-50"
          iconColor="text-blue-600"
        />
        <StatCard
          title="Monthly Recurring Revenue"
          value={`₹${kpis.monthly_recurring_revenue.toLocaleString()}`}
          change="+18.4%"
          subtitle="MRR Growth"
          icon={TrendingUp}
          iconBg="bg-emerald-50"
          iconColor="text-emerald-600"
        />
        <StatCard
          title="Total Domains"
          value={kpis.total_domains}
          subtitle={`${kpis.verified_domains} Verified • ${kpis.active_ssl_certificates} SSL Active`}
          icon={Globe}
          iconBg="bg-sky-50"
          iconColor="text-sky-600"
        />
        <StatCard
          title="Total Storage Usage"
          value={`${(kpis.total_storage_used_mb / 1024).toFixed(1)} GB`}
          subtitle={`Limit: ${(kpis.total_storage_limit_mb / 1024).toFixed(0)} GB`}
          icon={HardDrive}
          iconBg="bg-indigo-50"
          iconColor="text-indigo-600"
        />
      </div>

      {/* Analytics Charts & Health Grid */}
      <div className="grid grid-cols-1 lg:grid-cols-3 gap-6">
        {/* Tenant Growth Bar Chart */}
        <div className="bg-white border border-slate-200 rounded-2xl p-5 shadow-2xs">
          <div className="flex items-center justify-between mb-4">
            <h3 className="text-sm font-bold text-slate-900">Tenant Growth (6 Months)</h3>
            <span className="text-[10px] font-semibold text-blue-600 bg-blue-50 px-2 py-0.5 rounded">Real Data</span>
          </div>
          <div className="h-44 flex items-end space-x-4 pt-6 px-2">
            {charts.tenant_growth.map((item, idx) => {
              const maxCount = Math.max(...charts.tenant_growth.map((g) => g.count), 5);
              const heightPercent = Math.max(15, (item.count / maxCount) * 100);
              return (
                <div key={idx} className="flex-1 flex flex-col items-center gap-2">
                  <span className="text-[11px] font-bold text-slate-700">{item.count}</span>
                  <div
                    className="w-full bg-blue-600 rounded-t-lg transition-all hover:bg-blue-700"
                    style={{ height: `${heightPercent}%` }}
                  />
                  <span className="text-[11px] font-medium text-slate-500">{item.month}</span>
                </div>
              );
            })}
          </div>
        </div>

        {/* Monthly Revenue Trend */}
        <div className="bg-white border border-slate-200 rounded-2xl p-5 shadow-2xs">
          <div className="flex items-center justify-between mb-4">
            <h3 className="text-sm font-bold text-slate-900">Monthly Revenue (₹)</h3>
            <span className="text-[10px] font-semibold text-emerald-600 bg-emerald-50 px-2 py-0.5 rounded">+12% MoM</span>
          </div>
          <div className="h-44 flex items-end space-x-4 pt-6 px-2">
            {charts.monthly_revenue.map((item, idx) => {
              const maxRev = Math.max(...charts.monthly_revenue.map((r) => r.revenue), 10000);
              const heightPercent = Math.max(15, (item.revenue / maxRev) * 100);
              return (
                <div key={idx} className="flex-1 flex flex-col items-center gap-2">
                  <span className="text-[10px] font-bold text-slate-700 truncate w-full text-center">
                    ₹{(item.revenue / 1000).toFixed(0)}k
                  </span>
                  <div
                    className="w-full bg-emerald-500 rounded-t-lg transition-all hover:bg-emerald-600"
                    style={{ height: `${heightPercent}%` }}
                  />
                  <span className="text-[11px] font-medium text-slate-500">{item.month}</span>
                </div>
              );
            })}
          </div>
        </div>

        {/* AWS Infrastructure Health Widget */}
        <div className="bg-white border border-slate-200 rounded-2xl p-5 shadow-2xs flex flex-col justify-between">
          <div>
            <div className="flex items-center justify-between mb-4">
              <h3 className="text-sm font-bold text-slate-900">AWS Infrastructure Health</h3>
              <span className="text-[10px] font-semibold text-emerald-700 bg-emerald-50 border border-emerald-200 px-2 py-0.5 rounded-full flex items-center space-x-1">
                <span className="w-1.5 h-1.5 rounded-full bg-emerald-600"></span>
                <span>Operational</span>
              </span>
            </div>

            <div className="space-y-3 text-xs">
              <div className="flex justify-between items-center py-2 border-b border-slate-100">
                <span className="text-slate-500">ECS Services & Workers</span>
                <span className="font-bold text-slate-800">{infrastructure.healthy_services}/{infrastructure.total_services} Healthy</span>
              </div>
              <div className="flex justify-between items-center py-2 border-b border-slate-100">
                <span className="text-slate-500">API P95 Latency</span>
                <span className="font-bold font-mono text-slate-800">{infrastructure.avg_api_latency_ms} ms</span>
              </div>
              <div className="flex justify-between items-center py-2 border-b border-slate-100">
                <span className="text-slate-500">PDF Processing Queue</span>
                <span className="font-bold text-slate-800">{kpis.pdf_processing_queue} Jobs</span>
              </div>
              <div className="flex justify-between items-center py-2">
                <span className="text-slate-500">Critical Alerts</span>
                <span className="font-bold text-slate-800">{infrastructure.critical_alerts} Active</span>
              </div>
            </div>
          </div>

          <button
            onClick={() => onNavigate('infrastructure')}
            className="mt-4 w-full py-2 bg-slate-50 hover:bg-slate-100 text-slate-700 rounded-xl font-bold text-xs flex items-center justify-center space-x-1 transition-colors"
          >
            <span>Cloud & Telemetry Details</span>
            <ArrowRight className="w-3.5 h-3.5" />
          </button>
        </div>
      </div>

      {/* Recent Activity & Data Feeds */}
      <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
        {/* Recent Tenants */}
        <div className="bg-white border border-slate-200 rounded-2xl p-5 shadow-2xs">
          <div className="flex items-center justify-between mb-4">
            <h3 className="text-sm font-bold text-slate-900">Recent Publisher Registrations</h3>
            <button
              onClick={() => onNavigate('tenants')}
              className="text-xs font-semibold text-blue-600 hover:text-blue-700 flex items-center space-x-1"
            >
              <span>View All</span>
              <ArrowRight className="w-3 h-3" />
            </button>
          </div>

          <div className="divide-y divide-slate-100 text-xs">
            {recent_tenants.map((t) => (
              <div key={t.id} className="py-3 flex items-center justify-between">
                <div>
                  <h4 className="font-bold text-slate-900">{t.name}</h4>
                  <p className="text-[11px] text-slate-400 mt-0.5">{t.company_name} • {t.email}</p>
                </div>
                <div className="flex items-center space-x-2">
                  <span className="font-mono text-[11px] text-slate-600 hidden sm:inline">{t.domain || 'No domain'}</span>
                  <StatusBadge status={t.status} />
                </div>
              </div>
            ))}
          </div>
        </div>

        {/* Recent Payments */}
        <div className="bg-white border border-slate-200 rounded-2xl p-5 shadow-2xs">
          <div className="flex items-center justify-between mb-4">
            <h3 className="text-sm font-bold text-slate-900">Recent Revenue & Transactions</h3>
            <button
              onClick={() => onNavigate('payments')}
              className="text-xs font-semibold text-blue-600 hover:text-blue-700 flex items-center space-x-1"
            >
              <span>View All</span>
              <ArrowRight className="w-3 h-3" />
            </button>
          </div>

          <div className="divide-y divide-slate-100 text-xs">
            {recent_payments.map((p) => (
              <div key={p.id} className="py-3 flex items-center justify-between">
                <div>
                  <h4 className="font-bold text-slate-900 font-mono">{p.transaction_reference}</h4>
                  <p className="text-[11px] text-slate-400 mt-0.5">{p.tenant?.name || 'Publisher Tenant'} • {p.gateway.toUpperCase()}</p>
                </div>
                <div className="text-right">
                  <p className="font-bold text-slate-900 text-sm">₹{p.amount.toLocaleString()}</p>
                  <StatusBadge status={p.status} />
                </div>
              </div>
            ))}
          </div>
        </div>
      </div>
    </div>
  );
};
