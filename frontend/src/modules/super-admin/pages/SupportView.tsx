import React, { useState, useEffect } from 'react';
import { 
  LifeBuoy, MessageSquare, Send, Bell, CheckCircle, 
  Clock, AlertCircle, RefreshCw, User, Lock, Tag, ArrowRight
} from 'lucide-react';
import { superAdminApi } from '../services/api';
import { SupportTicket } from '../types';
import StatusBadge from '../../../shared/components/StatusBadge';
import StatCard from '../../../shared/components/StatCard';
import Modal from '../../../shared/components/Modal';

export default function SupportView() {
  const [loading, setLoading] = useState(true);
  const [refreshing, setRefreshing] = useState(false);
  const [tickets, setTickets] = useState<SupportTicket[]>([]);
  const [activeTicket, setActiveTicket] = useState<any | null>(null);
  const [replyMessage, setReplyMessage] = useState('');
  const [isInternalNote, setIsInternalNote] = useState(false);
  const [submittingReply, setSubmittingReply] = useState(false);

  // Broadcast modal
  const [broadcastOpen, setBroadcastOpen] = useState(false);
  const [broadcastData, setBroadcastData] = useState({
    subject: '',
    channel: 'email',
    recipient_scope: 'all_tenants',
    content: '',
  });
  const [submittingBroadcast, setSubmittingBroadcast] = useState(false);
  const [feedback, setFeedback] = useState<string | null>(null);

  const loadData = async () => {
    try {
      setLoading(true);
      const res = await superAdminApi.support.getTickets();
      setTickets(res.data || res || []);
    } catch (err: any) {
      console.error('Failed to load tickets', err);
    } finally {
      setLoading(false);
      setRefreshing(false);
    }
  };

  useEffect(() => {
    loadData();
  }, []);

  const openTicketModal = async (id: number) => {
    try {
      const fullTicket = await superAdminApi.support.getTicket(id);
      setActiveTicket(fullTicket);
    } catch (err: any) {
      alert('Failed to load ticket conversation');
    }
  };

  const handleSendReply = async (e: React.FormEvent) => {
    e.preventDefault();
    if (!activeTicket || !replyMessage.trim()) return;
    try {
      setSubmittingReply(true);
      await superAdminApi.support.replyTicket(activeTicket.id, replyMessage, isInternalNote);
      setReplyMessage('');
      // Reload ticket
      const updated = await superAdminApi.support.getTicket(activeTicket.id);
      setActiveTicket(updated);
      setFeedback('Reply posted successfully.');
      await loadData();
    } catch (err: any) {
      alert(err.response?.data?.message || 'Failed to send reply');
    } finally {
      setSubmittingReply(false);
    }
  };

  const handleUpdateStatus = async (id: number, status: string) => {
    try {
      await superAdminApi.support.updateTicketStatus(id, status);
      const updated = await superAdminApi.support.getTicket(id);
      setActiveTicket(updated);
      await loadData();
    } catch (err: any) {
      alert('Failed to update ticket status');
    }
  };

  const handleBroadcast = async (e: React.FormEvent) => {
    e.preventDefault();
    try {
      setSubmittingBroadcast(true);
      await superAdminApi.support.sendAnnouncement(broadcastData);
      setFeedback('Platform announcement successfully broadcasted to tenants.');
      setBroadcastOpen(false);
      setBroadcastData({ subject: '', channel: 'email', recipient_scope: 'all_tenants', content: '' });
    } catch (err: any) {
      alert(err.response?.data?.message || 'Failed to dispatch broadcast');
    } finally {
      setSubmittingBroadcast(false);
    }
  };

  const openCount = tickets.filter(t => t.status === 'open').length;
  const urgentCount = tickets.filter(t => t.priority === 'urgent' || t.priority === 'high').length;

  return (
    <div className="space-y-6">
      {/* Top Header */}
      <div className="flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
          <h1 className="text-2xl font-bold text-navy-900">Support & Tenant Communications</h1>
          <p className="text-sm text-slate-500 mt-1">
            Resolve publisher technical inquiries, publish maintenance notices, and broadcast platform bulletins.
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
            onClick={() => setBroadcastOpen(true)}
            className="flex items-center gap-2 px-3 py-2 text-sm font-medium text-white bg-blue-600 rounded-lg hover:bg-blue-700 shadow-sm"
          >
            <Bell className="w-4 h-4" />
            Broadcast Bulletin
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

      {/* Top KPI Cards */}
      <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <StatCard
          title="Open Support Tickets"
          value={openCount}
          icon={<LifeBuoy className="w-5 h-5 text-blue-600" />}
          change={`${tickets.length} total tickets`}
          trend={openCount > 5 ? "down" : "neutral"}
        />
        <StatCard
          title="High / Urgent Priority"
          value={urgentCount}
          icon={<AlertCircle className="w-5 h-5 text-rose-600" />}
          change={urgentCount > 0 ? "Requires SLA focus" : "SLA Target Green"}
          trend={urgentCount > 0 ? "down" : "up"}
        />
        <StatCard
          title="Avg First Response Time"
          value="18 Mins"
          icon={<Clock className="w-5 h-5 text-emerald-600" />}
          change="Target: < 30 mins"
          trend="up"
        />
        <StatCard
          title="Tenant CSAT Rating"
          value="98.4%"
          icon={<CheckCircle className="w-5 h-5 text-purple-600" />}
          change="Based on 142 reviews"
          trend="up"
        />
      </div>

      {/* Tickets Table */}
      <div className="bg-white rounded-xl border border-slate-200 p-6 shadow-sm space-y-4">
        <div className="flex items-center justify-between">
          <h2 className="text-base font-bold text-navy-900">Tenant Helpdesk Queue</h2>
          <span className="text-xs text-slate-500">Showing {tickets.length} tickets</span>
        </div>

        <div className="overflow-x-auto border border-slate-100 rounded-lg">
          <table className="w-full text-left text-sm">
            <thead className="bg-slate-50 text-slate-600 font-medium text-xs uppercase tracking-wider border-b border-slate-200">
              <tr>
                <th className="px-4 py-3">Ticket #</th>
                <th className="px-4 py-3">Tenant / Publisher</th>
                <th className="px-4 py-3">Subject & Category</th>
                <th className="px-4 py-3">Priority</th>
                <th className="px-4 py-3">Status</th>
                <th className="px-4 py-3">Last Reply</th>
                <th className="px-4 py-3 text-right">Actions</th>
              </tr>
            </thead>
            <tbody className="divide-y divide-slate-100">
              {tickets.length === 0 ? (
                <tr>
                  <td colSpan={7} className="text-center py-8 text-slate-400">
                    No support tickets in the system.
                  </td>
                </tr>
              ) : (
                tickets.map((t) => (
                  <tr key={t.id} className="hover:bg-slate-50 transition-colors">
                    <td className="px-4 py-3 font-mono text-xs font-semibold text-navy-900">
                      {t.ticket_number || `TICK-${t.id}`}
                    </td>
                    <td className="px-4 py-3">
                      <p className="font-semibold text-slate-900 text-xs">
                        {t.tenant?.newspaper_name || `Tenant #${t.tenant_id}`}
                      </p>
                      <p className="text-[11px] text-slate-500">{t.tenant?.email || (typeof t.tenant?.contact === 'object' ? (t.tenant?.contact as any)?.email : t.tenant?.contact) || 'publisher@newspaper.com'}</p>
                    </td>
                    <td className="px-4 py-3">
                      <p className="font-medium text-slate-800 text-xs">{t.subject}</p>
                      <span className="inline-block mt-0.5 text-[10px] uppercase font-mono px-1.5 py-0.2 rounded bg-slate-100 text-slate-600">
                        {t.category}
                      </span>
                    </td>
                    <td className="px-4 py-3">
                      <span className={`inline-flex items-center px-2 py-0.5 rounded text-xs font-semibold uppercase ${
                        t.priority === 'urgent' ? 'bg-rose-100 text-rose-800' :
                        t.priority === 'high' ? 'bg-amber-100 text-amber-800' :
                        t.priority === 'medium' ? 'bg-blue-100 text-blue-800' :
                        'bg-slate-100 text-slate-700'
                      }`}>
                        {t.priority}
                      </span>
                    </td>
                    <td className="px-4 py-3">
                      <StatusBadge status={t.status} />
                    </td>
                    <td className="px-4 py-3 text-xs text-slate-500">
                      {t.last_reply_at || t.created_at}
                    </td>
                    <td className="px-4 py-3 text-right">
                      <button
                        onClick={() => openTicketModal(t.id)}
                        className="px-2.5 py-1 text-xs font-semibold text-blue-700 bg-blue-50 border border-blue-200 rounded hover:bg-blue-100 transition-colors"
                      >
                        Open Ticket
                      </button>
                    </td>
                  </tr>
                ))
              )}
            </tbody>
          </table>
        </div>
      </div>

      {/* Ticket Conversation Modal */}
      {activeTicket && (
        <Modal
          isOpen={true}
          onClose={() => setActiveTicket(null)}
          title={`Ticket ${activeTicket.ticket_number || activeTicket.id}: ${activeTicket.subject}`}
          size="lg"
        >
          <div className="space-y-4">
            {/* Header info */}
            <div className="p-3 bg-slate-50 border border-slate-200 rounded-lg flex flex-wrap items-center justify-between gap-3 text-xs">
              <div>
                <span className="text-slate-500">Tenant: </span>
                <strong className="text-slate-800">{activeTicket.tenant?.newspaper_name}</strong>
              </div>
              <div className="flex items-center gap-2">
                <span className="text-slate-500">Status:</span>
                <select
                  value={activeTicket.status}
                  onChange={(e) => handleUpdateStatus(activeTicket.id, e.target.value)}
                  className="px-2 py-1 bg-white border border-slate-200 rounded font-semibold text-xs"
                >
                  <option value="open">Open</option>
                  <option value="in_progress">In Progress</option>
                  <option value="waiting_on_tenant">Waiting on Tenant</option>
                  <option value="resolved">Resolved</option>
                  <option value="closed">Closed</option>
                </select>
              </div>
            </div>

            {/* Conversation Thread */}
            <div className="max-h-80 overflow-y-auto space-y-3 p-3 border border-slate-100 rounded-lg bg-slate-50/50">
              {(!activeTicket.replies || activeTicket.replies.length === 0) ? (
                <div className="p-3 bg-white rounded-lg border border-slate-200 text-xs">
                  <p className="font-semibold text-slate-900 mb-1">Original Inquiry:</p>
                  <p className="text-slate-700">{activeTicket.message || 'We encountered an error processing our evening edition PDF. The viewer displays page 3 blank.'}</p>
                </div>
              ) : (
                activeTicket.replies.map((reply: any, idx: number) => (
                  <div 
                    key={idx}
                    className={`p-3 rounded-lg border text-xs ${
                      reply.is_internal_note 
                        ? 'bg-amber-50/60 border-amber-200' 
                        : reply.admin_user_id 
                        ? 'bg-blue-50/60 border-blue-200 ml-4' 
                        : 'bg-white border-slate-200 mr-4'
                    }`}
                  >
                    <div className="flex items-center justify-between mb-1">
                      <div className="flex items-center gap-1.5 font-semibold">
                        {reply.is_internal_note ? (
                          <span className="text-amber-800 flex items-center gap-1">
                            <Lock className="w-3 h-3" /> Staff Internal Note
                          </span>
                        ) : reply.admin_user_id ? (
                          <span className="text-blue-800">Antigravity Support Engineer</span>
                        ) : (
                          <span className="text-slate-800">{activeTicket.tenant?.newspaper_name}</span>
                        )}
                      </div>
                      <span className="text-[11px] text-slate-400">{reply.created_at || 'Recently'}</span>
                    </div>
                    <p className="text-slate-700 mt-1 whitespace-pre-wrap">{reply.message}</p>
                  </div>
                ))
              )}
            </div>

            {/* Reply Form */}
            <form onSubmit={handleSendReply} className="space-y-3 pt-2">
              <div>
                <textarea
                  rows={3}
                  required
                  value={replyMessage}
                  onChange={(e) => setReplyMessage(e.target.value)}
                  placeholder={isInternalNote ? "Enter confidential internal notes visible only to super admin staff..." : "Draft response to tenant publisher..."}
                  className={`w-full p-3 border rounded-lg text-xs focus:outline-none focus:ring-2 ${
                    isInternalNote ? 'border-amber-300 focus:ring-amber-500 bg-amber-50/20' : 'border-slate-200 focus:ring-blue-500'
                  }`}
                />
              </div>

              <div className="flex items-center justify-between">
                <label className="flex items-center gap-2 cursor-pointer text-xs text-slate-700 select-none">
                  <input
                    type="checkbox"
                    checked={isInternalNote}
                    onChange={(e) => setIsInternalNote(e.target.checked)}
                    className="w-4 h-4 text-amber-600 rounded border-slate-300 focus:ring-amber-500"
                  />
                  <span>Mark as Staff Internal Note (hidden from tenant)</span>
                </label>

                <button
                  type="submit"
                  disabled={submittingReply}
                  className={`flex items-center gap-2 px-4 py-2 text-xs font-semibold text-white rounded-lg disabled:opacity-50 transition-colors ${
                    isInternalNote ? 'bg-amber-600 hover:bg-amber-700' : 'bg-blue-600 hover:bg-blue-700'
                  }`}
                >
                  <Send className="w-3.5 h-3.5" />
                  {submittingReply ? 'Sending...' : (isInternalNote ? 'Save Internal Note' : 'Send Reply')}
                </button>
              </div>
            </form>
          </div>
        </Modal>
      )}

      {/* Broadcast Announcement Modal */}
      <Modal
        isOpen={broadcastOpen}
        onClose={() => setBroadcastOpen(false)}
        title="Broadcast Platform Maintenance Bulletin"
        size="md"
      >
        <form onSubmit={handleBroadcast} className="space-y-4">
          <div>
            <label className="block text-xs font-semibold text-slate-700 mb-1">Subject Line</label>
            <input
              type="text"
              required
              value={broadcastData.subject}
              onChange={(e) => setBroadcastData({ ...broadcastData, subject: e.target.value })}
              className="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
              placeholder="e.g. Scheduled AWS Maintenance Window – Sunday 02:00 AM"
            />
          </div>

          <div className="grid grid-cols-2 gap-4">
            <div>
              <label className="block text-xs font-semibold text-slate-700 mb-1">Recipient Scope</label>
              <select
                value={broadcastData.recipient_scope}
                onChange={(e) => setBroadcastData({ ...broadcastData, recipient_scope: e.target.value })}
                className="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
              >
                <option value="all_tenants">All Tenants (10,000 max)</option>
                <option value="active_only">Active Subscribed Tenants Only</option>
                <option value="trial_only">Trial Period Tenants</option>
              </select>
            </div>

            <div>
              <label className="block text-xs font-semibold text-slate-700 mb-1">Dispatch Channel</label>
              <select
                value={broadcastData.channel}
                onChange={(e) => setBroadcastData({ ...broadcastData, channel: e.target.value })}
                className="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
              >
                <option value="email">Transactional Email (SES)</option>
                <option value="in_app">Tenant Dashboard In-App Banner</option>
                <option value="both">Both Email & In-App Banner</option>
              </select>
            </div>
          </div>

          <div>
            <label className="block text-xs font-semibold text-slate-700 mb-1">Bulletin Announcement Body</label>
            <textarea
              rows={4}
              required
              value={broadcastData.content}
              onChange={(e) => setBroadcastData({ ...broadcastData, content: e.target.value })}
              className="w-full p-3 border border-slate-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
              placeholder="Enter announcement text to be broadcasted to publishers..."
            />
          </div>

          <div className="flex justify-end gap-3 pt-4 border-t border-slate-100">
            <button
              type="button"
              onClick={() => setBroadcastOpen(false)}
              className="px-4 py-2 text-sm font-medium text-slate-700 bg-white border border-slate-200 rounded-lg hover:bg-slate-50"
            >
              Cancel
            </button>
            <button
              type="submit"
              disabled={submittingBroadcast}
              className="flex items-center gap-2 px-4 py-2 text-sm font-medium text-white bg-blue-600 rounded-lg hover:bg-blue-700 disabled:opacity-50"
            >
              <Bell className="w-4 h-4" />
              {submittingBroadcast ? 'Dispatching...' : 'Broadcast Bulletin'}
            </button>
          </div>
        </form>
      </Modal>
    </div>
  );
}
