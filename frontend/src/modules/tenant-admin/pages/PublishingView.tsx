import React, { useEffect, useState } from 'react';
import {
  Calendar,
  Clock,
  Send,
  AlertCircle,
  CheckCircle2,
  RefreshCw,
  XCircle,
  Globe,
  Radio,
  FileCheck,
} from 'lucide-react';
import { publishingApi, editionsApi } from '../services/api';
import { PublishingSchedule, Edition } from '../types';
import { StatusBadge } from '../components/StatusBadge';
import { Modal } from '../components/Modal';

export const PublishingView: React.FC = () => {
  const [schedules, setSchedules] = useState<PublishingSchedule[]>([]);
  const [editions, setEditions] = useState<Edition[]>([]);
  const [loading, setLoading] = useState(true);
  const [actionLoading, setActionLoading] = useState(false);
  const [success, setSuccess] = useState<string | null>(null);
  const [error, setError] = useState<string | null>(null);

  // Schedule modal state
  const [isScheduleOpen, setIsScheduleOpen] = useState(false);
  const [selectedEditionId, setSelectedEditionId] = useState<number | ''>('');
  const [scheduleDate, setScheduleDate] = useState('');
  const [scheduleTime, setScheduleTime] = useState('06:00');
  const [timezone, setTimezone] = useState('Asia/Kolkata');

  const fetchData = async () => {
    setLoading(true);
    try {
      const [schRes, edRes] = await Promise.all([
        publishingApi.getSchedules(),
        editionsApi.getAll({ per_page: 50 }),
      ]);
      setSchedules(schRes.data?.data || schRes.data || []);
      const eds = edRes.data?.data || edRes.data || [];
      setEditions(eds);
      if (eds.length > 0 && !selectedEditionId) {
        setSelectedEditionId(eds[0].id);
      }
    } catch (err: any) {
      setError(err?.response?.data?.message || 'Failed to fetch publishing schedules.');
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => {
    fetchData();
  }, []);

  const handlePublishNow = async (editionId: number) => {
    if (!confirm('Are you sure you want to publish this edition immediately to public view?')) return;
    setActionLoading(true);
    try {
      await publishingApi.publishNow(editionId);
      setSuccess('Edition published immediately to public view!');
      setTimeout(() => setSuccess(null), 4000);
      await fetchData();
    } catch (err: any) {
      alert(err?.response?.data?.message || 'Failed to publish edition.');
    } finally {
      setActionLoading(false);
    }
  };

  const handleUnpublish = async (editionId: number) => {
    if (!confirm('Are you sure you want to retract/unpublish this edition? It will return to draft status.')) return;
    setActionLoading(true);
    try {
      await publishingApi.unpublish(editionId);
      setSuccess('Edition reverted to draft status.');
      setTimeout(() => setSuccess(null), 4000);
      await fetchData();
    } catch (err: any) {
      alert(err?.response?.data?.message || 'Failed to unpublish edition.');
    } finally {
      setActionLoading(false);
    }
  };

  const handleCreateSchedule = async (e: React.FormEvent) => {
    e.preventDefault();
    if (!selectedEditionId || !scheduleDate || !scheduleTime) {
      alert('Please select an edition, date, and target publication time.');
      return;
    }

    const scheduled_at = `${scheduleDate} ${scheduleTime}:00`;
    setActionLoading(true);
    try {
      await publishingApi.schedule({
        edition_id: Number(selectedEditionId),
        scheduled_at,
        timezone,
      });
      setSuccess('Edition successfully queued for automated release!');
      setIsScheduleOpen(false);
      setTimeout(() => setSuccess(null), 4000);
      await fetchData();
    } catch (err: any) {
      alert(err?.response?.data?.message || 'Failed to schedule publication.');
    } finally {
      setActionLoading(false);
    }
  };

  const handleCancelSchedule = async (scheduleId: number) => {
    if (!confirm('Cancel this scheduled release?')) return;
    setActionLoading(true);
    try {
      await publishingApi.cancelSchedule(scheduleId);
      await fetchData();
    } catch (err: any) {
      alert(err?.response?.data?.message || 'Failed to cancel schedule.');
    } finally {
      setActionLoading(false);
    }
  };

  return (
    <div className="space-y-6">
      {/* Header */}
      <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
          <h1 className="text-xl font-bold text-navy-950 flex items-center gap-2">
            <Radio className="w-6 h-6 text-primary-600" />
            Publishing & Automated Scheduler
          </h1>
          <p className="text-xs text-slate-500 mt-1">
            Control live digital edition distribution, morning drop times, and automated scheduling
          </p>
        </div>

        <button
          onClick={() => setIsScheduleOpen(true)}
          className="px-4 py-2.5 bg-primary-600 hover:bg-primary-700 text-white rounded-xl text-xs font-semibold shadow-xs transition flex items-center gap-2"
        >
          <Clock className="w-4 h-4" />
          Schedule New Release
        </button>
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

      {/* Instant Action Panel */}
      <div className="bg-white p-6 rounded-2xl border border-slate-200/80 shadow-xs">
        <h2 className="text-sm font-bold text-navy-950 mb-4 flex items-center gap-2">
          <Send className="w-4 h-4 text-primary-600" />
          Live Editions Control Desk
        </h2>

        <div className="overflow-x-auto">
          <table className="w-full text-left border-collapse text-xs">
            <thead>
              <tr className="bg-slate-50 text-[11px] font-bold text-slate-500 uppercase tracking-wider border-b border-slate-100">
                <th className="py-3 px-4">Edition Title</th>
                <th className="py-3 px-4">Issue Date</th>
                <th className="py-3 px-4">Pages</th>
                <th className="py-3 px-4">Current Status</th>
                <th className="py-3 px-4">Published Timestamp</th>
                <th className="py-3 px-4 text-right">Instant Action</th>
              </tr>
            </thead>
            <tbody className="divide-y divide-slate-100">
              {editions.slice(0, 8).map((ed) => (
                <tr key={ed.id} className="hover:bg-slate-50/60 transition">
                  <td className="py-3 px-4 font-bold text-navy-950">{ed.edition_title}</td>
                  <td className="py-3 px-4 font-medium text-slate-600">{ed.edition_date}</td>
                  <td className="py-3 px-4 font-semibold text-slate-700">{ed.pages_count} Pages</td>
                  <td className="py-3 px-4">
                    <StatusBadge status={ed.status} />
                  </td>
                  <td className="py-3 px-4 text-slate-500 font-mono text-[11px]">
                    {ed.published_at ? new Date(ed.published_at).toLocaleString() : 'Not published'}
                  </td>
                  <td className="py-3 px-4 text-right space-x-2">
                    {ed.status !== 'published' ? (
                      <button
                        onClick={() => handlePublishNow(ed.id)}
                        disabled={actionLoading}
                        className="px-3 py-1.5 rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white font-semibold transition text-xs shadow-2xs inline-flex items-center gap-1.5"
                      >
                        <Send className="w-3.5 h-3.5" />
                        Publish Now
                      </button>
                    ) : (
                      <button
                        onClick={() => handleUnpublish(ed.id)}
                        disabled={actionLoading}
                        className="px-3 py-1.5 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-700 font-semibold transition text-xs inline-flex items-center gap-1.5"
                      >
                        Unpublish
                      </button>
                    )}
                  </td>
                </tr>
              ))}
            </tbody>
          </table>
        </div>
      </div>

      {/* Scheduled Releases Table */}
      <div className="bg-white rounded-2xl border border-slate-200/80 shadow-xs overflow-hidden">
        <div className="px-6 py-4 border-b border-slate-100 flex items-center justify-between">
          <div>
            <h3 className="font-bold text-navy-950 text-sm">Automated Publication Schedules</h3>
            <p className="text-xs text-slate-500">Upcoming automated releases handled by cron workers</p>
          </div>
          <button
            onClick={() => fetchData()}
            className="p-1.5 rounded-lg border border-slate-200 hover:bg-slate-100 text-slate-500"
            title="Refresh Schedules"
          >
            <RefreshCw className={`w-3.5 h-3.5 ${loading ? 'animate-spin' : ''}`} />
          </button>
        </div>

        <div className="overflow-x-auto">
          <table className="w-full text-left border-collapse text-xs">
            <thead>
              <tr className="bg-slate-50/80 text-[11px] font-bold text-slate-500 uppercase tracking-wider border-b border-slate-100">
                <th className="py-3 px-6">Scheduled Edition</th>
                <th className="py-3 px-4">Release Target Time</th>
                <th className="py-3 px-4">Target Timezone</th>
                <th className="py-3 px-4">Status</th>
                <th className="py-3 px-6 text-right">Actions</th>
              </tr>
            </thead>
            <tbody className="divide-y divide-slate-100">
              {schedules.length > 0 ? (
                schedules.map((sch) => (
                  <tr key={sch.id} className="hover:bg-slate-50/60 transition">
                    <td className="py-3.5 px-6 font-bold text-navy-950">
                      {sch.edition ? sch.edition.edition_title : `Edition #${sch.edition_id}`}
                    </td>
                    <td className="py-3.5 px-4 font-semibold text-slate-700 font-mono">
                      {new Date(sch.scheduled_at).toLocaleString()}
                    </td>
                    <td className="py-3.5 px-4 text-slate-600 font-medium">
                      {sch.timezone || 'Asia/Kolkata'}
                    </td>
                    <td className="py-3.5 px-4">
                      <StatusBadge status={sch.status} />
                    </td>
                    <td className="py-3.5 px-6 text-right">
                      {sch.status === 'scheduled' && (
                        <button
                          onClick={() => handleCancelSchedule(sch.id)}
                          className="px-2.5 py-1 rounded-lg bg-rose-50 hover:bg-rose-100 text-rose-700 font-semibold transition text-xs inline-flex items-center gap-1"
                        >
                          <XCircle className="w-3.5 h-3.5" />
                          Cancel
                        </button>
                      )}
                    </td>
                  </tr>
                ))
              ) : (
                <tr>
                  <td colSpan={5} className="py-10 text-center text-slate-400">
                    <Clock className="w-8 h-8 text-slate-300 mx-auto mb-2" />
                    No automated publication schedules queued.
                  </td>
                </tr>
              )}
            </tbody>
          </table>
        </div>
      </div>

      {/* Schedule Modal */}
      <Modal
        isOpen={isScheduleOpen}
        onClose={() => setIsScheduleOpen(false)}
        title="Schedule Digital Edition Release"
        size="md"
      >
        <form onSubmit={handleCreateSchedule} className="space-y-4">
          <div>
            <label className="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1.5">
              Select Edition *
            </label>
            <select
              value={selectedEditionId}
              onChange={(e) => setSelectedEditionId(Number(e.target.value))}
              required
              className="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs font-medium text-navy-950 focus:outline-hidden focus:ring-2 focus:ring-primary-500"
            >
              <option value="">-- Choose Edition --</option>
              {editions.map((ed) => (
                <option key={ed.id} value={ed.id}>
                  {ed.edition_title} ({ed.edition_date})
                </option>
              ))}
            </select>
          </div>

          <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
              <label className="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1.5">
                Release Date *
              </label>
              <input
                type="date"
                required
                value={scheduleDate}
                onChange={(e) => setScheduleDate(e.target.value)}
                className="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs font-medium text-navy-950 focus:outline-hidden focus:ring-2 focus:ring-primary-500"
              />
            </div>

            <div>
              <label className="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1.5">
                Target Time (24h) *
              </label>
              <input
                type="time"
                required
                value={scheduleTime}
                onChange={(e) => setScheduleTime(e.target.value)}
                className="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs font-medium text-navy-950 focus:outline-hidden focus:ring-2 focus:ring-primary-500"
              />
            </div>
          </div>

          <div>
            <label className="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1.5">
              Timezone
            </label>
            <select
              value={timezone}
              onChange={(e) => setTimezone(e.target.value)}
              className="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs font-medium text-navy-950 focus:outline-hidden focus:ring-2 focus:ring-primary-500"
            >
              <option value="Asia/Kolkata">Asia/Kolkata (IST - UTC+05:30)</option>
              <option value="UTC">UTC (Universal Time Coordinated)</option>
              <option value="America/New_York">America/New_York (EST/EDT)</option>
              <option value="Europe/London">Europe/London (GMT/BST)</option>
              <option value="Asia/Dubai">Asia/Dubai (GST - UTC+04:00)</option>
            </select>
          </div>

          <div className="flex items-center justify-end gap-3 pt-3 border-t border-slate-100">
            <button
              type="button"
              onClick={() => setIsScheduleOpen(false)}
              className="px-4 py-2 text-xs font-semibold text-slate-600 bg-white border border-slate-200 rounded-xl hover:bg-slate-50"
            >
              Cancel
            </button>
            <button
              type="submit"
              disabled={actionLoading}
              className="px-5 py-2 text-xs font-semibold text-white bg-primary-600 hover:bg-primary-700 rounded-xl shadow-xs transition"
            >
              {actionLoading ? 'Scheduling...' : 'Confirm Schedule'}
            </button>
          </div>
        </form>
      </Modal>
    </div>
  );
};
