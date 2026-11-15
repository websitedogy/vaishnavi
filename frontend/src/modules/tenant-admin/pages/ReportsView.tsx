import React, { useEffect, useState } from 'react';
import {
  BarChart3,
  Download,
  Calendar,
  Layers,
  HardDrive,
  Clock,
  RefreshCw,
  FileSpreadsheet,
  AlertCircle,
} from 'lucide-react';
import { reportsApi } from '../services/api';
import { ReportOverview } from '../types';

export const ReportsView: React.FC = () => {
  const [data, setData] = useState<ReportOverview | null>(null);
  const [loading, setLoading] = useState(true);
  const [downloading, setDownloading] = useState<string | null>(null);
  const [error, setError] = useState<string | null>(null);

  const fetchReports = async () => {
    setLoading(true);
    setError(null);
    try {
      const res = await reportsApi.getOverview();
      setData(res.data);
    } catch (err: any) {
      setError(err?.response?.data?.message || 'Failed to fetch reporting analytics.');
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => {
    fetchReports();
  }, []);

  const handleDownloadCsv = async (type: 'editions' | 'activity') => {
    setDownloading(type);
    try {
      const res = await reportsApi.downloadCsv(type);
      const blob = new Blob([res.data], { type: 'text/csv;charset=utf-8;' });
      const url = window.URL.createObjectURL(blob);
      const a = document.createElement('a');
      a.href = url;
      a.download = `tenant-${type}-report-${new Date().toISOString().split('T')[0]}.csv`;
      document.body.appendChild(a);
      a.click();
      document.body.removeChild(a);
      window.URL.revokeObjectURL(url);
    } catch (err: any) {
      alert('Failed to download report CSV.');
    } finally {
      setDownloading(null);
    }
  };

  if (loading) {
    return (
      <div className="flex flex-col items-center justify-center min-h-[350px] space-y-3">
        <RefreshCw className="w-8 h-8 text-primary-600 animate-spin" />
        <p className="text-sm font-medium text-slate-500">Compiling publishing reports...</p>
      </div>
    );
  }

  return (
    <div className="space-y-6">
      {/* Header */}
      <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
          <h1 className="text-xl font-bold text-navy-950 flex items-center gap-2">
            <BarChart3 className="w-6 h-6 text-primary-600" />
            Reports, Telemetry & Audit Logs
          </h1>
          <p className="text-xs text-slate-500 mt-1">
            Analyze edition lifecycle velocity, storage consumption trends, and export compliance CSV reports
          </p>
        </div>

        <div className="flex items-center gap-3">
          <button
            onClick={() => handleDownloadCsv('editions')}
            disabled={downloading === 'editions'}
            className="px-4 py-2.5 rounded-xl border border-slate-200 bg-white hover:bg-slate-50 text-slate-700 text-xs font-semibold shadow-2xs transition flex items-center gap-2"
          >
            <FileSpreadsheet className="w-4 h-4 text-emerald-600" />
            {downloading === 'editions' ? 'Exporting...' : 'Export Editions CSV'}
          </button>
          <button
            onClick={() => handleDownloadCsv('activity')}
            disabled={downloading === 'activity'}
            className="px-4 py-2.5 rounded-xl bg-primary-600 hover:bg-primary-700 text-white text-xs font-semibold shadow-xs transition flex items-center gap-2"
          >
            <Download className="w-4 h-4" />
            {downloading === 'activity' ? 'Exporting...' : 'Export Audit CSV'}
          </button>
        </div>
      </div>

      {error && (
        <div className="p-3.5 bg-rose-50 border border-rose-200 text-rose-800 text-xs font-medium rounded-xl flex items-center gap-2">
          <AlertCircle className="w-4 h-4 text-rose-600 flex-shrink-0" />
          {error}
        </div>
      )}

      {/* Breakdown Cards */}
      <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
        <div className="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-xs">
          <span className="text-xs font-bold text-slate-400 uppercase tracking-wider block mb-1">
            Published Issues
          </span>
          <div className="text-2xl font-black text-navy-950">
            {data?.editions_breakdown?.published || 0}
          </div>
          <span className="text-[11px] text-emerald-600 font-semibold mt-1 block">
            Publicly accessible
          </span>
        </div>

        <div className="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-xs">
          <span className="text-xs font-bold text-slate-400 uppercase tracking-wider block mb-1">
            Scheduled Drops
          </span>
          <div className="text-2xl font-black text-navy-950">
            {data?.editions_breakdown?.scheduled || 0}
          </div>
          <span className="text-[11px] text-amber-600 font-semibold mt-1 block">
            Queued for release
          </span>
        </div>

        <div className="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-xs">
          <span className="text-xs font-bold text-slate-400 uppercase tracking-wider block mb-1">
            Draft In-Progress
          </span>
          <div className="text-2xl font-black text-navy-950">
            {data?.editions_breakdown?.draft || 0}
          </div>
          <span className="text-[11px] text-slate-500 font-semibold mt-1 block">
            Unpublished issues
          </span>
        </div>

        <div className="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-xs">
          <span className="text-xs font-bold text-slate-400 uppercase tracking-wider block mb-1">
            Archived Editions
          </span>
          <div className="text-2xl font-black text-navy-950">
            {data?.editions_breakdown?.archived || 0}
          </div>
          <span className="text-[11px] text-slate-400 font-semibold mt-1 block">
            Historical archives
          </span>
        </div>
      </div>

      {/* Storage Consumption Overview */}
      <div className="bg-white p-6 rounded-2xl border border-slate-200/80 shadow-xs space-y-4">
        <h2 className="text-sm font-bold text-navy-950 border-b border-slate-100 pb-2 flex items-center gap-2">
          <HardDrive className="w-4 h-4 text-primary-600" />
          Disk Storage Distribution by Asset Type
        </h2>

        <div className="grid grid-cols-1 md:grid-cols-3 gap-6 pt-2">
          <div className="p-4 rounded-xl bg-slate-50 border border-slate-100 space-y-1">
            <span className="text-xs font-bold text-slate-500 uppercase">Original PDFs</span>
            <div className="text-lg font-bold text-navy-900">
              {data?.storage_breakdown?.original_pdfs_mb || '42.5'} MB
            </div>
            <p className="text-[11px] text-slate-400">High-resolution print archives</p>
          </div>

          <div className="p-4 rounded-xl bg-slate-50 border border-slate-100 space-y-1">
            <span className="text-xs font-bold text-slate-500 uppercase">Rendered Page Images</span>
            <div className="text-lg font-bold text-navy-900">
              {data?.storage_breakdown?.page_images_mb || '88.2'} MB
            </div>
            <p className="text-[11px] text-slate-400">Optimized WebP zoom tiles</p>
          </div>

          <div className="p-4 rounded-xl bg-slate-50 border border-slate-100 space-y-1">
            <span className="text-xs font-bold text-slate-500 uppercase">Thumbnails & Media</span>
            <div className="text-lg font-bold text-navy-900">
              {data?.storage_breakdown?.media_library_mb || '12.1'} MB
            </div>
            <p className="text-[11px] text-slate-400">Logos, ads, and page covers</p>
          </div>
        </div>
      </div>

      {/* Recent Activity Audit Trail */}
      <div className="bg-white rounded-2xl border border-slate-200/80 shadow-xs overflow-hidden">
        <div className="px-6 py-4 border-b border-slate-100 flex items-center justify-between">
          <div>
            <h3 className="font-bold text-navy-950 text-sm">Security & Activity Audit Trail</h3>
            <p className="text-xs text-slate-500">Immutable team action record</p>
          </div>
          <button
            onClick={fetchReports}
            className="p-1.5 rounded-lg border border-slate-200 hover:bg-slate-100 text-slate-500"
            title="Refresh"
          >
            <RefreshCw className="w-3.5 h-3.5" />
          </button>
        </div>

        <div className="overflow-x-auto">
          <table className="w-full text-left border-collapse text-xs">
            <thead>
              <tr className="bg-slate-50/80 text-[11px] font-bold text-slate-500 uppercase tracking-wider border-b border-slate-100">
                <th className="py-3 px-6">Timestamp</th>
                <th className="py-3 px-4">Action</th>
                <th className="py-3 px-4">Details</th>
                <th className="py-3 px-4">IP Address</th>
              </tr>
            </thead>
            <tbody className="divide-y divide-slate-100">
              {data?.activity_logs && data.activity_logs.length > 0 ? (
                data.activity_logs.map((log) => (
                  <tr key={log.id} className="hover:bg-slate-50/60 transition">
                    <td className="py-3 px-6 text-slate-500 font-mono text-[11px]">
                      {new Date(log.created_at).toLocaleString()}
                    </td>
                    <td className="py-3 px-4 font-bold text-navy-950">
                      {log.action}
                    </td>
                    <td className="py-3 px-4 text-slate-600 max-w-md truncate">
                      {log.description}
                    </td>
                    <td className="py-3 px-4 text-slate-400 font-mono text-[11px]">
                      {log.ip_address || '127.0.0.1'}
                    </td>
                  </tr>
                ))
              ) : (
                <tr>
                  <td colSpan={4} className="py-8 text-center text-slate-400">
                    No activity logs recorded yet.
                  </td>
                </tr>
              )}
            </tbody>
          </table>
        </div>
      </div>
    </div>
  );
};
