import React, { useState, useEffect } from 'react';
import { 
  FileText, Cpu, AlertTriangle, RefreshCw, Trash2, CheckCircle, 
  Clock, Database, HardDrive, Layers, ArrowUpRight, Search, Play
} from 'lucide-react';
import { superAdminApi } from '../services/api';
import { NewspaperEdition, PdfProcessingJob } from '../types';
import StatusBadge from '../../../shared/components/StatusBadge';
import StatCard from '../../../shared/components/StatCard';
import Modal from '../../../shared/components/Modal';
import ConfirmationDialog from '../../../shared/components/ConfirmationDialog';

export default function OperationsView() {
  const [activeTab, setActiveTab] = useState<'editions' | 'queue' | 'storage'>('editions');
  const [loading, setLoading] = useState(true);
  const [refreshing, setRefreshing] = useState(false);
  const [stats, setStats] = useState<any>(null);
  const [editions, setEditions] = useState<NewspaperEdition[]>([]);
  const [jobs, setJobs] = useState<PdfProcessingJob[]>([]);
  const [storageData, setStorageData] = useState<any[]>([]);
  const [search, setSearch] = useState('');
  const [jobStatusFilter, setJobStatusFilter] = useState('');

  // Modals
  const [selectedJob, setSelectedJob] = useState<PdfProcessingJob | null>(null);
  const [retryingJob, setRetryingJob] = useState(false);
  const [purgeConfirmOpen, setPurgeConfirmOpen] = useState(false);
  const [purging, setPurging] = useState(false);
  const [actionMessage, setActionMessage] = useState<string | null>(null);

  const loadData = async () => {
    try {
      setLoading(true);
      const [overviewRes, editionsRes, jobsRes, storageRes] = await Promise.all([
        superAdminApi.operations.getOverview(),
        superAdminApi.operations.getEditions(),
        superAdminApi.operations.getJobs(),
        superAdminApi.operations.getStorageUsage(),
      ]);

      setStats(overviewRes);
      setEditions(editionsRes.data || editionsRes || []);
      setJobs(jobsRes.data || jobsRes || []);
      setStorageData(storageRes || []);
    } catch (err: any) {
      console.error('Failed to load operations data', err);
    } finally {
      setLoading(false);
      setRefreshing(false);
    }
  };

  useEffect(() => {
    loadData();
  }, []);

  const handleRetryJob = async (id: number) => {
    try {
      setRetryingJob(true);
      await superAdminApi.operations.retryJob(id);
      setActionMessage(`Job #${id} successfully requeued for processing.`);
      setSelectedJob(null);
      await loadData();
    } catch (err: any) {
      alert(err.response?.data?.message || 'Failed to retry job');
    } finally {
      setRetryingJob(false);
    }
  };

  const handlePurgeDlq = async () => {
    try {
      setPurging(true);
      const res = await superAdminApi.operations.purgeDlq();
      setActionMessage(`Dead-letter queue purged: ${res?.purged_count || 0} failed jobs cleared.`);
      setPurgeConfirmOpen(false);
      await loadData();
    } catch (err: any) {
      alert(err.response?.data?.message || 'Failed to purge DLQ');
    } finally {
      setPurging(false);
    }
  };

  const filteredEditions = editions.filter(e => 
    e.edition_name?.toLowerCase().includes(search.toLowerCase()) ||
    e.edition_code?.toLowerCase().includes(search.toLowerCase()) ||
    e.tenant?.newspaper_name?.toLowerCase().includes(search.toLowerCase())
  );

  const filteredJobs = jobs.filter(j => {
    const matchesSearch = j.job_uuid?.toLowerCase().includes(search.toLowerCase()) ||
      j.edition?.edition_name?.toLowerCase().includes(search.toLowerCase()) ||
      j.tenant?.newspaper_name?.toLowerCase().includes(search.toLowerCase());
    const matchesStatus = !jobStatusFilter || j.status === jobStatusFilter;
    return matchesSearch && matchesStatus;
  });

  return (
    <div className="space-y-6">
      {/* Top Banner & Header */}
      <div className="flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
          <h1 className="text-2xl font-bold text-navy-900">Newspaper Operations & PDF Engine</h1>
          <p className="text-sm text-slate-500 mt-1">
            Real-time pipeline monitoring for SQS PDF queues, multi-resolution image rendering, and S3 storage.
          </p>
        </div>

        <div className="flex items-center gap-3">
          <button
            onClick={() => { setRefreshing(true); loadData(); }}
            disabled={refreshing}
            className="flex items-center gap-2 px-3 py-2 text-sm font-medium text-slate-700 bg-white border border-slate-200 rounded-lg hover:bg-slate-50 transition-colors shadow-sm"
          >
            <RefreshCw className={`w-4 h-4 ${refreshing ? 'animate-spin' : ''}`} />
            Refresh
          </button>
          <button
            onClick={() => setPurgeConfirmOpen(true)}
            className="flex items-center gap-2 px-3 py-2 text-sm font-medium text-rose-700 bg-rose-50 border border-rose-200 rounded-lg hover:bg-rose-100 transition-colors shadow-sm"
          >
            <Trash2 className="w-4 h-4" />
            Purge DLQ
          </button>
        </div>
      </div>

      {actionMessage && (
        <div className="p-4 bg-emerald-50 border border-emerald-200 rounded-xl text-emerald-800 text-sm flex items-center justify-between">
          <div className="flex items-center gap-2">
            <CheckCircle className="w-4 h-4 text-emerald-600" />
            <span>{actionMessage}</span>
          </div>
          <button onClick={() => setActionMessage(null)} className="text-xs text-emerald-700 underline font-medium">Dismiss</button>
        </div>
      )}

      {/* KPI Cards */}
      <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <StatCard
          title="Total Published Editions"
          value={stats?.total_editions ?? editions.length}
          icon={<FileText className="w-5 h-5 text-blue-600" />}
          change="+18 today"
          trend="up"
        />
        <StatCard
          title="PDF SQS Queue Depth"
          value={stats?.queue_depth ?? 0}
          icon={<Clock className="w-5 h-5 text-amber-600" />}
          change="Real-time SQS"
          trend="neutral"
        />
        <StatCard
          title="Active Processing Workers"
          value={stats?.active_workers ?? '4 / 8'}
          icon={<Cpu className="w-5 h-5 text-emerald-600" />}
          change="Auto-scaling ECS Fargate"
          trend="up"
        />
        <StatCard
          title="Dead Letter Queue / Failed"
          value={stats?.failed_jobs_count ?? jobs.filter(j => j.status === 'failed' || j.status === 'dead_letter').length}
          icon={<AlertTriangle className="w-5 h-5 text-rose-600" />}
          change={stats?.failed_jobs_count > 0 ? "Requires review" : "Queue Healthy"}
          trend={stats?.failed_jobs_count > 0 ? "down" : "up"}
        />
      </div>

      {/* Navigation Tabs */}
      <div className="border-b border-slate-200 bg-white rounded-t-xl px-4 pt-2 shadow-sm">
        <div className="flex gap-8">
          <button
            onClick={() => setActiveTab('editions')}
            className={`pb-3 text-sm font-semibold border-b-2 transition-colors flex items-center gap-2 ${
              activeTab === 'editions'
                ? 'border-blue-600 text-blue-600'
                : 'border-transparent text-slate-500 hover:text-slate-800'
            }`}
          >
            <Layers className="w-4 h-4" />
            Newspaper Editions ({editions.length})
          </button>
          <button
            onClick={() => setActiveTab('queue')}
            className={`pb-3 text-sm font-semibold border-b-2 transition-colors flex items-center gap-2 ${
              activeTab === 'queue'
                ? 'border-blue-600 text-blue-600'
                : 'border-transparent text-slate-500 hover:text-slate-800'
            }`}
          >
            <Cpu className="w-4 h-4" />
            PDF Processing Queue ({jobs.length})
          </button>
          <button
            onClick={() => setActiveTab('storage')}
            className={`pb-3 text-sm font-semibold border-b-2 transition-colors flex items-center gap-2 ${
              activeTab === 'storage'
                ? 'border-blue-600 text-blue-600'
                : 'border-transparent text-slate-500 hover:text-slate-800'
            }`}
          >
            <HardDrive className="w-4 h-4" />
            Storage & Bandwidth Telemetry
          </button>
        </div>
      </div>

      {/* Tab 1: Editions */}
      {activeTab === 'editions' && (
        <div className="bg-white rounded-b-xl border border-t-0 border-slate-200 p-6 shadow-sm space-y-4">
          <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div className="relative flex-1 max-w-md">
              <Search className="w-4 h-4 absolute left-3 top-3 text-slate-400" />
              <input
                type="text"
                value={search}
                onChange={(e) => setSearch(e.target.value)}
                placeholder="Search by edition name, code, or tenant..."
                className="w-full pl-9 pr-4 py-2 border border-slate-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
              />
            </div>
            <div className="text-xs text-slate-500">
              Showing {filteredEditions.length} of {editions.length} editions
            </div>
          </div>

          <div className="overflow-x-auto border border-slate-100 rounded-lg">
            <table className="w-full text-left text-sm">
              <thead className="bg-slate-50 text-slate-600 font-medium text-xs uppercase tracking-wider border-b border-slate-200">
                <tr>
                  <th className="px-4 py-3">Edition Code</th>
                  <th className="px-4 py-3">Tenant / Publisher</th>
                  <th className="px-4 py-3">Edition Name</th>
                  <th className="px-4 py-3">Publish Date</th>
                  <th className="px-4 py-3">Pages</th>
                  <th className="px-4 py-3">File Size</th>
                  <th className="px-4 py-3">Status</th>
                  <th className="px-4 py-3 text-right">S3 Storage Path</th>
                </tr>
              </thead>
              <tbody className="divide-y divide-slate-100">
                {filteredEditions.length === 0 ? (
                  <tr>
                    <td colSpan={8} className="text-center py-8 text-slate-400">
                      No newspaper editions match your search.
                    </td>
                  </tr>
                ) : (
                  filteredEditions.map((ed) => (
                    <tr key={ed.id} className="hover:bg-slate-50 transition-colors">
                      <td className="px-4 py-3 font-mono text-xs font-semibold text-navy-800">
                        {ed.edition_code}
                      </td>
                      <td className="px-4 py-3 font-medium text-slate-900">
                        {ed.tenant?.newspaper_name || `Tenant #${ed.tenant_id}`}
                      </td>
                      <td className="px-4 py-3 text-slate-800">{ed.edition_name}</td>
                      <td className="px-4 py-3 text-slate-600 text-xs">{ed.publish_date}</td>
                      <td className="px-4 py-3 font-medium text-slate-800">{ed.total_pages} pages</td>
                      <td className="px-4 py-3 text-slate-600 text-xs">{ed.file_size_mb} MB</td>
                      <td className="px-4 py-3">
                        <StatusBadge status={ed.status} />
                      </td>
                      <td className="px-4 py-3 text-right text-xs font-mono text-slate-500 truncate max-w-xs">
                        {ed.pdf_s3_path || 's3://dogy-epaper-storage/editions/...'}
                      </td>
                    </tr>
                  ))
                )}
              </tbody>
            </table>
          </div>
        </div>
      )}

      {/* Tab 2: PDF Processing Queue */}
      {activeTab === 'queue' && (
        <div className="bg-white rounded-b-xl border border-t-0 border-slate-200 p-6 shadow-sm space-y-4">
          <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div className="flex items-center gap-3 flex-1">
              <div className="relative flex-1 max-w-sm">
                <Search className="w-4 h-4 absolute left-3 top-3 text-slate-400" />
                <input
                  type="text"
                  value={search}
                  onChange={(e) => setSearch(e.target.value)}
                  placeholder="Filter by Job UUID or tenant..."
                  className="w-full pl-9 pr-4 py-2 border border-slate-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
                />
              </div>
              <select
                value={jobStatusFilter}
                onChange={(e) => setJobStatusFilter(e.target.value)}
                className="px-3 py-2 border border-slate-200 rounded-lg text-sm text-slate-700 focus:outline-none focus:ring-2 focus:ring-blue-500"
              >
                <option value="">All Job Statuses</option>
                <option value="queued">Queued</option>
                <option value="processing">Processing</option>
                <option value="completed">Completed</option>
                <option value="failed">Failed</option>
                <option value="dead_letter">Dead Letter</option>
              </select>
            </div>
            <div className="text-xs text-slate-500">
              Showing {filteredJobs.length} active/recent jobs
            </div>
          </div>

          <div className="overflow-x-auto border border-slate-100 rounded-lg">
            <table className="w-full text-left text-sm">
              <thead className="bg-slate-50 text-slate-600 font-medium text-xs uppercase tracking-wider border-b border-slate-200">
                <tr>
                  <th className="px-4 py-3">Job UUID</th>
                  <th className="px-4 py-3">Tenant & Edition</th>
                  <th className="px-4 py-3">Progress</th>
                  <th className="px-4 py-3">Worker / Host</th>
                  <th className="px-4 py-3">Attempts</th>
                  <th className="px-4 py-3">Status</th>
                  <th className="px-4 py-3">Error / Reason</th>
                  <th className="px-4 py-3 text-right">Actions</th>
                </tr>
              </thead>
              <tbody className="divide-y divide-slate-100">
                {filteredJobs.length === 0 ? (
                  <tr>
                    <td colSpan={8} className="text-center py-8 text-slate-400">
                      No jobs currently in this status filter.
                    </td>
                  </tr>
                ) : (
                  filteredJobs.map((j) => {
                    const percent = j.total_pages > 0 ? Math.round((j.current_page / j.total_pages) * 100) : 0;
                    return (
                      <tr key={j.id} className="hover:bg-slate-50 transition-colors">
                        <td className="px-4 py-3 font-mono text-xs text-slate-700">
                          {j.job_uuid.substring(0, 8)}...
                        </td>
                        <td className="px-4 py-3">
                          <p className="font-semibold text-slate-900 text-xs">
                            {j.edition?.edition_name || `Edition #${j.edition_id}`}
                          </p>
                          <p className="text-slate-500 text-xs">{j.tenant?.newspaper_name || `Tenant #${j.tenant_id}`}</p>
                        </td>
                        <td className="px-4 py-3 min-w-[140px]">
                          <div className="flex items-center justify-between text-xs text-slate-600 mb-1">
                            <span>{j.current_page} / {j.total_pages} pgs</span>
                            <span className="font-medium">{percent}%</span>
                          </div>
                          <div className="w-full bg-slate-100 h-2 rounded-full overflow-hidden">
                            <div 
                              className={`h-full transition-all duration-300 ${
                                j.status === 'failed' || j.status === 'dead_letter' 
                                  ? 'bg-rose-500' 
                                  : j.status === 'completed' 
                                  ? 'bg-emerald-500' 
                                  : 'bg-blue-600'
                              }`} 
                              style={{ width: `${percent}%` }}
                            />
                          </div>
                        </td>
                        <td className="px-4 py-3 font-mono text-xs text-slate-600">
                          {j.worker_id || 'fargate-worker-01'}
                        </td>
                        <td className="px-4 py-3 text-xs font-semibold text-slate-700">
                          {j.attempts} / 3
                        </td>
                        <td className="px-4 py-3">
                          <StatusBadge status={j.status} />
                        </td>
                        <td className="px-4 py-3 text-xs text-rose-600 max-w-xs truncate" title={j.error_message || ''}>
                          {j.error_message || '—'}
                        </td>
                        <td className="px-4 py-3 text-right">
                          {(j.status === 'failed' || j.status === 'dead_letter') ? (
                            <button
                              onClick={() => setSelectedJob(j)}
                              className="px-2.5 py-1 text-xs font-medium text-blue-700 bg-blue-50 border border-blue-200 rounded-md hover:bg-blue-100 transition-colors"
                            >
                              Retry Job
                            </button>
                          ) : (
                            <span className="text-xs text-slate-400">Normal</span>
                          )}
                        </td>
                      </tr>
                    );
                  })
                )}
              </tbody>
            </table>
          </div>
        </div>
      )}

      {/* Tab 3: Storage & Bandwidth Telemetry */}
      {activeTab === 'storage' && (
        <div className="bg-white rounded-b-xl border border-t-0 border-slate-200 p-6 shadow-sm space-y-6">
          <div className="grid grid-cols-1 md:grid-cols-3 gap-6">
            <div className="p-5 bg-slate-50 border border-slate-200 rounded-xl">
              <div className="flex items-center gap-3 mb-2">
                <Database className="w-5 h-5 text-blue-600" />
                <h3 className="font-semibold text-navy-900 text-sm">Amazon S3 Multi-Tier Storage</h3>
              </div>
              <p className="text-2xl font-bold text-slate-900 mt-2">1.84 TB</p>
              <p className="text-xs text-slate-500 mt-1">S3 Standard + Intelligent-Tiering active</p>
              <div className="mt-4 pt-4 border-t border-slate-200 space-y-2 text-xs text-slate-600">
                <div className="flex justify-between">
                  <span>Source High-Res PDFs</span>
                  <span className="font-semibold text-slate-900">1.12 TB</span>
                </div>
                <div className="flex justify-between">
                  <span>WebP Thumbnail Tiles</span>
                  <span className="font-semibold text-slate-900">540 GB</span>
                </div>
                <div className="flex justify-between">
                  <span>Archive & Backups</span>
                  <span className="font-semibold text-slate-900">180 GB</span>
                </div>
              </div>
            </div>

            <div className="p-5 bg-slate-50 border border-slate-200 rounded-xl">
              <div className="flex items-center gap-3 mb-2">
                <ArrowUpRight className="w-5 h-5 text-emerald-600" />
                <h3 className="font-semibold text-navy-900 text-sm">CloudFront Edge Delivery</h3>
              </div>
              <p className="text-2xl font-bold text-slate-900 mt-2">14.6 TB / mo</p>
              <p className="text-xs text-emerald-600 font-medium mt-1">96.8% CDN Cache Hit Ratio</p>
              <div className="mt-4 pt-4 border-t border-slate-200 space-y-2 text-xs text-slate-600">
                <div className="flex justify-between">
                  <span>Edge Cache Hits</span>
                  <span className="font-semibold text-emerald-600">96.8%</span>
                </div>
                <div className="flex justify-between">
                  <span>Origin S3 Egress</span>
                  <span className="font-semibold text-slate-900">3.2%</span>
                </div>
                <div className="flex justify-between">
                  <span>Average Reader Latency</span>
                  <span className="font-semibold text-blue-600">42 ms</span>
                </div>
              </div>
            </div>

            <div className="p-5 bg-slate-50 border border-slate-200 rounded-xl">
              <div className="flex items-center gap-3 mb-2">
                <Cpu className="w-5 h-5 text-purple-600" />
                <h3 className="font-semibold text-navy-900 text-sm">Ghostscript & Imagick Fleet</h3>
              </div>
              <p className="text-2xl font-bold text-slate-900 mt-2">8 Workers</p>
              <p className="text-xs text-slate-500 mt-1">ECS Fargate Spot Auto-scaling fleet</p>
              <div className="mt-4 pt-4 border-t border-slate-200 space-y-2 text-xs text-slate-600">
                <div className="flex justify-between">
                  <span>Avg Page Render Time</span>
                  <span className="font-semibold text-slate-900">1.4 seconds</span>
                </div>
                <div className="flex justify-between">
                  <span>Concurrency Limit</span>
                  <span className="font-semibold text-slate-900">32 parallel pages</span>
                </div>
                <div className="flex justify-between">
                  <span>SQS Dead-Letter Max</span>
                  <span className="font-semibold text-rose-600">3 attempts</span>
                </div>
              </div>
            </div>
          </div>

          <div className="mt-6 border border-slate-100 rounded-lg p-4">
            <h4 className="text-sm font-semibold text-navy-900 mb-3">Top Tenant Storage Consumers</h4>
            <div className="divide-y divide-slate-100">
              {storageData.length === 0 ? (
                <p className="text-xs text-slate-400 py-4">No tenant telemetry records available.</p>
              ) : (
                storageData.map((item, idx) => (
                  <div key={idx} className="py-3 flex items-center justify-between text-sm">
                    <div>
                      <p className="font-medium text-slate-900">{item.tenant_name || `Tenant #${item.tenant_id}`}</p>
                      <p className="text-xs text-slate-500">{item.edition_count || 0} published editions</p>
                    </div>
                    <div className="text-right">
                      <p className="font-semibold text-slate-800">{item.storage_used_gb || 0} GB</p>
                      <p className="text-xs text-slate-500">{item.bandwidth_gb || 0} GB bandwidth this month</p>
                    </div>
                  </div>
                ))
              )}
            </div>
          </div>
        </div>
      )}

      {/* Retry Job Modal */}
      {selectedJob && (
        <Modal
          isOpen={true}
          onClose={() => setSelectedJob(null)}
          title={`Retry PDF Job: ${selectedJob.job_uuid}`}
          size="md"
        >
          <div className="space-y-4">
            <p className="text-sm text-slate-600">
              Are you sure you want to re-dispatch this PDF rendering job back into the Amazon SQS processing queue?
            </p>
            <div className="p-3 bg-slate-50 border border-slate-200 rounded-lg text-xs space-y-1">
              <div><strong className="text-slate-700">Edition:</strong> {selectedJob.edition?.edition_name || selectedJob.edition_id}</div>
              <div><strong className="text-slate-700">Tenant:</strong> {selectedJob.tenant?.newspaper_name || selectedJob.tenant_id}</div>
              <div><strong className="text-slate-700">Previous Error:</strong> <span className="text-rose-600">{selectedJob.error_message || 'N/A'}</span></div>
              <div><strong className="text-slate-700">Previous Attempts:</strong> {selectedJob.attempts}</div>
            </div>

            <div className="flex justify-end gap-3 pt-4 border-t border-slate-100">
              <button
                type="button"
                onClick={() => setSelectedJob(null)}
                className="px-4 py-2 text-sm font-medium text-slate-700 bg-white border border-slate-200 rounded-lg hover:bg-slate-50"
              >
                Cancel
              </button>
              <button
                type="button"
                onClick={() => handleRetryJob(selectedJob.id)}
                disabled={retryingJob}
                className="flex items-center gap-2 px-4 py-2 text-sm font-medium text-white bg-blue-600 rounded-lg hover:bg-blue-700 disabled:opacity-50"
              >
                <Play className="w-4 h-4" />
                {retryingJob ? 'Re-dispatching...' : 'Confirm Re-queue'}
              </button>
            </div>
          </div>
        </Modal>
      )}

      {/* Purge DLQ Dialog */}
      <ConfirmationDialog
        isOpen={purgeConfirmOpen}
        onClose={() => setPurgeConfirmOpen(false)}
        onConfirm={handlePurgeDlq}
        title="Purge Dead Letter Queue (DLQ)"
        message="Are you sure you want to purge all failed jobs from the Dead Letter Queue? This action will permanently drop the current queue errors. Make sure any unrecoverable file errors have been logged."
        confirmText={purging ? "Purging..." : "Purge DLQ"}
        danger={true}
      />
    </div>
  );
}
