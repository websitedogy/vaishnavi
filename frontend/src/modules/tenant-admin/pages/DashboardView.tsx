import React, { useEffect, useState } from 'react';
import {
  Newspaper,
  UploadCloud,
  FileText,
  Users,
  HardDrive,
  Clock,
  ArrowUpRight,
  Plus,
  CheckCircle2,
  AlertCircle,
  Eye,
  RefreshCw,
  TrendingUp,
  Layers,
  ChevronRight,
} from 'lucide-react';
import { dashboardApi } from '../services/api';
import { DashboardOverview, Edition } from '../types';
import { StatCard } from '../components/StatCard';
import { StatusBadge } from '../components/StatusBadge';

interface DashboardViewProps {
  onNavigate: (moduleKey: string) => void;
  onOpenCreateEdition: () => void;
}

export const DashboardView: React.FC<DashboardViewProps> = ({
  onNavigate,
  onOpenCreateEdition,
}) => {
  const [data, setData] = useState<DashboardOverview | null>(null);
  const [loading, setLoading] = useState(true);
  const [refreshing, setRefreshing] = useState(false);
  const [error, setError] = useState<string | null>(null);

  const fetchDashboard = async (isManual = false) => {
    if (isManual) setRefreshing(true);
    else setLoading(true);
    setError(null);
    try {
      const res = await dashboardApi.getOverview();
      setData(res.data);
    } catch (err: any) {
      setError(err?.response?.data?.message || 'Failed to load dashboard overview data.');
    } finally {
      setLoading(false);
      setRefreshing(false);
    }
  };

  useEffect(() => {
    fetchDashboard();
  }, []);

  if (loading) {
    return (
      <div className="flex flex-col items-center justify-center min-h-[400px] space-y-3">
        <RefreshCw className="w-8 h-8 text-primary-600 animate-spin" />
        <p className="text-sm font-medium text-slate-500">Loading publishing telemetry...</p>
      </div>
    );
  }

  if (error || !data) {
    return (
      <div className="p-6 bg-rose-50 border border-rose-200 rounded-2xl text-center space-y-3">
        <AlertCircle className="w-8 h-8 text-rose-600 mx-auto" />
        <p className="text-sm font-semibold text-rose-800">{error || 'Unable to retrieve dashboard metrics'}</p>
        <button
          onClick={() => fetchDashboard(true)}
          className="px-4 py-2 bg-rose-600 text-white rounded-xl text-xs font-semibold hover:bg-rose-700 transition"
        >
          Retry Connection
        </button>
      </div>
    );
  }

  const { stats, limits, today_edition, recent_editions, upload_queue, activity_logs } = data;
  const storagePercent = limits ? Math.min(Math.round((limits.used_storage_mb / (limits.storage_limit_mb || 1)) * 100), 100) : 0;
  const editionsPercent = limits ? Math.min(Math.round((stats.total_editions / (limits.max_editions_per_month || 1)) * 100), 100) : 0;

  return (
    <div className="space-y-6">
      {/* Top Banner / Today's Publication Status */}
      <div className="p-6 rounded-2xl bg-gradient-to-r from-navy-950 via-navy-900 to-primary-950 text-white shadow-xl relative overflow-hidden border border-navy-800">
        <div className="absolute right-0 top-0 bottom-0 w-1/3 bg-[radial-gradient(ellipse_at_top_right,_var(--tw-gradient-stops))] from-primary-600/20 via-transparent to-transparent pointer-events-none" />
        <div className="relative z-10 flex flex-col md:flex-row md:items-center justify-between gap-4">
          <div>
            <div className="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-primary-500/20 text-primary-300 text-xs font-semibold tracking-wide border border-primary-500/30 mb-2">
              <span className="w-2 h-2 rounded-full bg-primary-400 animate-pulse" />
              DIGITAL NEWSPAPER WORKSPACE
            </div>
            <h1 className="text-2xl font-black tracking-tight text-white">
              Tenant Publishing Overview
            </h1>
            <p className="text-slate-300 text-sm mt-1 max-w-xl">
              {today_edition ? (
                <>
                  Today's edition is <strong className="text-white">{today_edition.edition_title}</strong> (
                  <span className="capitalize text-primary-300">{today_edition.status}</span> with{' '}
                  {today_edition.pages_count} pages).
                </>
              ) : (
                'No edition has been published yet today. Upload a PDF or schedule today\'s morning issue.'
              )}
            </p>
          </div>

          <div className="flex flex-wrap items-center gap-3">
            <button
              onClick={() => fetchDashboard(true)}
              disabled={refreshing}
              className="p-2.5 rounded-xl bg-white/10 hover:bg-white/20 text-white transition-colors border border-white/15"
              title="Refresh Data"
            >
              <RefreshCw className={`w-4 h-4 ${refreshing ? 'animate-spin' : ''}`} />
            </button>
            <button
              onClick={() => onNavigate('pdf-upload')}
              className="px-4 py-2.5 rounded-xl bg-primary-600 hover:bg-primary-500 text-white text-sm font-semibold transition-all shadow-lg shadow-primary-600/30 flex items-center gap-2"
            >
              <UploadCloud className="w-4 h-4" />
              Upload PDF
            </button>
            <button
              onClick={onOpenCreateEdition}
              className="px-4 py-2.5 rounded-xl bg-white text-navy-950 hover:bg-slate-100 text-sm font-semibold transition-all shadow-md flex items-center gap-2"
            >
              <Plus className="w-4 h-4 text-primary-600" />
              New Edition
            </button>
          </div>
        </div>
      </div>

      {/* KPI Cards Grid */}
      <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <StatCard
          title="Published Editions"
          value={stats.published_editions}
          subValue={`out of ${stats.total_editions} total created`}
          icon={<Newspaper className="w-5 h-5 text-primary-600" />}
          change="+12% this month"
          isPositive={true}
        />
        <StatCard
          title="Total Printed Pages"
          value={stats.total_pages}
          subValue="Rendered high-res pages"
          icon={<Layers className="w-5 h-5 text-sky-600" />}
          change="Broadsheet layout"
          isPositive={true}
        />
        <StatCard
          title="Scheduled Editions"
          value={stats.scheduled_editions}
          subValue="Ready for automated drop"
          icon={<Clock className="w-5 h-5 text-amber-600" />}
        />
        <StatCard
          title="Storage Used"
          value={`${stats.storage_used_mb} MB`}
          subValue={limits ? `of ${limits.storage_limit_mb} MB limit` : 'Allocated disk quota'}
          icon={<HardDrive className="w-5 h-5 text-indigo-600" />}
        />
      </div>

      {/* Secondary Row: Resource Usage & Processing Queue */}
      <div className="grid grid-cols-1 lg:grid-cols-3 gap-6">
        {/* Resource Quota Progress */}
        <div className="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-xs flex flex-col justify-between">
          <div>
            <div className="flex items-center justify-between mb-4">
              <h3 className="font-bold text-navy-950 text-sm">Plan Quota & Storage</h3>
              <span className="text-xs font-semibold px-2.5 py-1 bg-primary-50 text-primary-700 rounded-full border border-primary-100">
                {limits?.plan_name || 'Standard Plan'}
              </span>
            </div>

            {/* Storage Progress */}
            <div className="space-y-4">
              <div>
                <div className="flex justify-between text-xs font-medium text-slate-600 mb-1.5">
                  <span>Storage Allocation</span>
                  <span className="font-semibold text-navy-900">
                    {stats.storage_used_mb} MB / {limits?.storage_limit_mb || 1024} MB ({storagePercent}%)
                  </span>
                </div>
                <div className="w-full h-2.5 bg-slate-100 rounded-full overflow-hidden">
                  <div
                    className={`h-full rounded-full transition-all duration-500 ${
                      storagePercent > 85 ? 'bg-rose-500' : 'bg-primary-600'
                    }`}
                    style={{ width: `${storagePercent}%` }}
                  />
                </div>
              </div>

              {/* Monthly Editions Progress */}
              <div>
                <div className="flex justify-between text-xs font-medium text-slate-600 mb-1.5">
                  <span>Monthly Editions Published</span>
                  <span className="font-semibold text-navy-900">
                    {stats.total_editions} / {limits?.max_editions_per_month || 100}
                  </span>
                </div>
                <div className="w-full h-2.5 bg-slate-100 rounded-full overflow-hidden">
                  <div
                    className="h-full bg-sky-500 rounded-full transition-all duration-500"
                    style={{ width: `${editionsPercent}%` }}
                  />
                </div>
              </div>

              <div className="pt-2 flex items-center justify-between text-xs text-slate-500 border-t border-slate-100">
                <span>Staff Seats Active:</span>
                <span className="font-semibold text-navy-900">
                  {stats.active_staff} / {limits?.max_staff || 5} seats
                </span>
              </div>
            </div>
          </div>

          <button
            onClick={() => onNavigate('subscription')}
            className="mt-5 w-full py-2.5 px-3 rounded-xl bg-slate-50 hover:bg-slate-100 border border-slate-200 text-xs font-semibold text-primary-700 flex items-center justify-center gap-1.5 transition-colors"
          >
            Upgrade Plan & Limits
            <ChevronRight className="w-3.5 h-3.5" />
          </button>
        </div>

        {/* Upload & PDF Processing Pipeline */}
        <div className="lg:col-span-2 bg-white p-5 rounded-2xl border border-slate-200/80 shadow-xs">
          <div className="flex items-center justify-between mb-4">
            <div>
              <h3 className="font-bold text-navy-950 text-sm">Processing Pipeline</h3>
              <p className="text-xs text-slate-500">Live background PDF conversion queue</p>
            </div>
            <button
              onClick={() => onNavigate('pdf-upload')}
              className="text-xs font-semibold text-primary-600 hover:text-primary-700"
            >
              View Uploads &rarr;
            </button>
          </div>

          {upload_queue && upload_queue.length > 0 ? (
            <div className="space-y-3">
              {upload_queue.map((item) => (
                <div
                  key={item.id}
                  className="p-3.5 rounded-xl border border-slate-100 bg-slate-50/70 flex items-center justify-between gap-4"
                >
                  <div className="flex items-center gap-3 min-w-0">
                    <div className="p-2 rounded-lg bg-white border border-slate-200 text-primary-600 shadow-2xs">
                      <FileText className="w-4 h-4" />
                    </div>
                    <div className="min-w-0">
                      <p className="text-xs font-bold text-navy-950 truncate">
                        {item.file_name}
                      </p>
                      <p className="text-[11px] text-slate-500">
                        {item.edition ? item.edition.edition_title : 'General Edition'} • {(item.file_size / (1024 * 1024)).toFixed(2)} MB
                      </p>
                    </div>
                  </div>

                  <div className="flex items-center gap-3 flex-shrink-0">
                    <StatusBadge status={item.status} />
                    <span className="text-xs font-mono font-medium text-slate-600">
                      {item.progress_percentage}%
                    </span>
                  </div>
                </div>
              ))}
            </div>
          ) : (
            <div className="py-10 text-center flex flex-col items-center justify-center border-2 border-dashed border-slate-200 rounded-xl bg-slate-50/50">
              <CheckCircle2 className="w-10 h-10 text-emerald-500/80 mb-2" />
              <p className="text-xs font-semibold text-navy-900">All PDF processing queues are clear</p>
              <p className="text-[11px] text-slate-500 mt-0.5">No active background conversion tasks running</p>
              <button
                onClick={() => onNavigate('pdf-upload')}
                className="mt-3 px-3 py-1.5 bg-primary-50 text-primary-700 rounded-lg text-xs font-semibold hover:bg-primary-100 transition"
              >
                Upload Edition PDF
              </button>
            </div>
          )}
        </div>
      </div>

      {/* Recent Editions Table */}
      <div className="bg-white rounded-2xl border border-slate-200/80 shadow-xs overflow-hidden">
        <div className="px-6 py-4 border-b border-slate-100 flex items-center justify-between">
          <div>
            <h3 className="font-bold text-navy-950 text-base">Recent Editions</h3>
            <p className="text-xs text-slate-500">Quick oversight of latest published and draft digital issues</p>
          </div>
          <button
            onClick={() => onNavigate('editions')}
            className="text-xs font-semibold text-primary-600 hover:text-primary-700 flex items-center gap-1"
          >
            View All Editions
            <ChevronRight className="w-3.5 h-3.5" />
          </button>
        </div>

        <div className="overflow-x-auto">
          <table className="w-full text-left border-collapse">
            <thead>
              <tr className="bg-slate-50/80 text-[11px] font-bold text-slate-500 uppercase tracking-wider border-b border-slate-100">
                <th className="py-3 px-6">Edition Name & Date</th>
                <th className="py-3 px-4">Pages</th>
                <th className="py-3 px-4">Category</th>
                <th className="py-3 px-4">Status</th>
                <th className="py-3 px-4">Published At</th>
                <th className="py-3 px-6 text-right">Actions</th>
              </tr>
            </thead>
            <tbody className="divide-y divide-slate-100 text-xs">
              {recent_editions && recent_editions.length > 0 ? (
                recent_editions.map((ed: Edition) => (
                  <tr key={ed.id} className="hover:bg-slate-50/60 transition-colors">
                    <td className="py-3.5 px-6">
                      <div className="font-bold text-navy-950">{ed.edition_title}</div>
                      <div className="text-[11px] text-slate-500 font-mono mt-0.5">
                        Date: {ed.edition_date} • {ed.edition_code || 'N/A'}
                      </div>
                    </td>
                    <td className="py-3.5 px-4 font-semibold text-slate-700">
                      {ed.pages_count} Pages
                    </td>
                    <td className="py-3.5 px-4 text-slate-600 font-medium capitalize">
                      {ed.category || 'Main Edition'}
                    </td>
                    <td className="py-3.5 px-4">
                      <StatusBadge status={ed.status} />
                    </td>
                    <td className="py-3.5 px-4 text-slate-500">
                      {ed.published_at ? new Date(ed.published_at).toLocaleString() : 'Not published'}
                    </td>
                    <td className="py-3.5 px-6 text-right space-x-2">
                      <button
                        onClick={() => onNavigate('pages')}
                        className="px-2.5 py-1.5 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-700 font-semibold transition"
                      >
                        Pages
                      </button>
                      <button
                        onClick={() => onNavigate('editions')}
                        className="px-2.5 py-1.5 rounded-lg bg-primary-50 hover:bg-primary-100 text-primary-700 font-semibold transition"
                      >
                        Manage
                      </button>
                    </td>
                  </tr>
                ))
              ) : (
                <tr>
                  <td colSpan={6} className="py-8 text-center text-slate-500">
                    No editions created yet.
                  </td>
                </tr>
              )}
            </tbody>
          </table>
        </div>
      </div>

      {/* Recent Activity Audit Logs */}
      {activity_logs && activity_logs.length > 0 && (
        <div className="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-xs">
          <h3 className="font-bold text-navy-950 text-sm mb-3">Audit Log & Recent Team Actions</h3>
          <div className="divide-y divide-slate-100">
            {activity_logs.slice(0, 5).map((log) => (
              <div key={log.id} className="py-2.5 flex items-center justify-between text-xs">
                <div className="flex items-center gap-2">
                  <span className="w-1.5 h-1.5 rounded-full bg-primary-500" />
                  <span className="font-semibold text-navy-900">{log.action}</span>
                  <span className="text-slate-500 truncate max-w-md">{log.description}</span>
                </div>
                <span className="text-slate-400 font-mono text-[11px]">
                  {new Date(log.created_at).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' })}
                </span>
              </div>
            ))}
          </div>
        </div>
      )}
    </div>
  );
};
