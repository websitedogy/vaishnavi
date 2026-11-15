import React, { useEffect, useState } from 'react';
import {
  HelpCircle,
  Plus,
  MessageSquare,
  Send,
  AlertCircle,
  CheckCircle2,
  RefreshCw,
  Clock,
  ChevronRight,
  ArrowLeft,
} from 'lucide-react';
import { supportApi } from '../services/api';
import { SupportTicket } from '../types';
import { Modal } from '../components/Modal';
import { StatusBadge } from '../components/StatusBadge';

export const SupportView: React.FC = () => {
  const [tickets, setTickets] = useState<SupportTicket[]>([]);
  const [loading, setLoading] = useState(true);
  const [selectedTicket, setSelectedTicket] = useState<SupportTicket | null>(null);
  const [replyMessage, setReplyMessage] = useState('');
  const [sendingReply, setSendingReply] = useState(false);

  // New ticket modal
  const [isOpenNew, setIsOpenNew] = useState(false);
  const [newSubject, setNewSubject] = useState('');
  const [newCategory, setNewCategory] = useState('technical');
  const [newPriority, setNewPriority] = useState('medium');
  const [newMessage, setNewMessage] = useState('');
  const [submitting, setSubmitting] = useState(false);
  const [success, setSuccess] = useState<string | null>(null);

  const fetchTickets = async () => {
    setLoading(true);
    try {
      const res = await supportApi.getTickets();
      setTickets(res.data?.data || res.data || []);
    } catch (err: any) {
      console.error('Failed to load support tickets:', err);
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => {
    fetchTickets();
  }, []);

  const handleCreateTicket = async (e: React.FormEvent) => {
    e.preventDefault();
    setSubmitting(true);
    try {
      await supportApi.createTicket({
        subject: newSubject,
        category: newCategory,
        priority: newPriority,
        message: newMessage,
      });
      setIsOpenNew(false);
      setNewSubject('');
      setNewMessage('');
      setSuccess('Support ticket created. Our platform operations team has been notified.');
      setTimeout(() => setSuccess(null), 5000);
      await fetchTickets();
    } catch (err: any) {
      alert(err?.response?.data?.message || 'Failed to submit support ticket.');
    } finally {
      setSubmitting(false);
    }
  };

  const handleSelectTicket = async (ticket: SupportTicket) => {
    setSelectedTicket(ticket);
    try {
      const res = await supportApi.getTicket(ticket.id);
      if (res.data) setSelectedTicket(res.data);
    } catch (err) {
      console.error(err);
    }
  };

  const handleSendReply = async (e: React.FormEvent) => {
    e.preventDefault();
    if (!selectedTicket || !replyMessage.trim()) return;
    setSendingReply(true);
    try {
      const res = await supportApi.replyTicket(selectedTicket.id, replyMessage);
      setReplyMessage('');
      if (res.data) setSelectedTicket(res.data);
      await fetchTickets();
    } catch (err: any) {
      alert(err?.response?.data?.message || 'Failed to send reply.');
    } finally {
      setSendingReply(false);
    }
  };

  return (
    <div className="space-y-6">
      {/* Header */}
      <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
          <h1 className="text-xl font-bold text-navy-950 flex items-center gap-2">
            <HelpCircle className="w-6 h-6 text-primary-600" />
            Support & Helpdesk Desk
          </h1>
          <p className="text-xs text-slate-500 mt-1">
            Direct communication channel with Dogy SaaS platform infrastructure engineers and account managers
          </p>
        </div>

        {!selectedTicket && (
          <button
            onClick={() => setIsOpenNew(true)}
            className="px-4 py-2.5 bg-primary-600 hover:bg-primary-700 text-white rounded-xl text-xs font-semibold shadow-xs transition flex items-center gap-2"
          >
            <Plus className="w-4 h-4" />
            Open Support Ticket
          </button>
        )}
      </div>

      {success && (
        <div className="p-3.5 bg-emerald-50 border border-emerald-200 text-emerald-800 text-xs font-medium rounded-xl flex items-center gap-2">
          <CheckCircle2 className="w-4 h-4 text-emerald-600 flex-shrink-0" />
          {success}
        </div>
      )}

      {selectedTicket ? (
        /* Conversation Thread View */
        <div className="bg-white rounded-2xl border border-slate-200/80 shadow-xs overflow-hidden space-y-4">
          {/* Thread Header */}
          <div className="px-6 py-4 border-b border-slate-100 flex items-center justify-between bg-slate-50/50">
            <div className="flex items-center gap-3">
              <button
                onClick={() => setSelectedTicket(null)}
                className="p-1.5 rounded-lg border border-slate-200 hover:bg-slate-100 text-slate-600"
              >
                <ArrowLeft className="w-4 h-4" />
              </button>
              <div>
                <h3 className="font-bold text-navy-950 text-sm">
                  #{selectedTicket.id}: {selectedTicket.subject}
                </h3>
                <span className="text-[11px] text-slate-500 capitalize">
                  Category: {selectedTicket.category || 'General'} • Priority: {selectedTicket.priority}
                </span>
              </div>
            </div>

            <StatusBadge status={selectedTicket.status} />
          </div>

          {/* Conversation history */}
          <div className="p-6 space-y-4 max-h-[450px] overflow-y-auto">
            {/* Original message */}
            <div className="p-4 rounded-xl bg-slate-50 border border-slate-200/80 space-y-2">
              <div className="flex items-center justify-between text-xs text-slate-500">
                <span className="font-bold text-navy-900">Initial Request</span>
                <span className="font-mono text-[10px]">
                  {new Date(selectedTicket.created_at).toLocaleString()}
                </span>
              </div>
              <p className="text-xs text-slate-700 leading-relaxed whitespace-pre-wrap">
                {selectedTicket.message}
              </p>
            </div>

            {/* Replies */}
            {selectedTicket.replies && selectedTicket.replies.map((reply: any, idx: number) => (
              <div
                key={idx}
                className={`p-4 rounded-xl border space-y-2 ${
                  reply.is_admin_reply
                    ? 'bg-primary-50/40 border-primary-100 ml-6'
                    : 'bg-white border-slate-200 mr-6'
                }`}
              >
                <div className="flex items-center justify-between text-xs">
                  <span className="font-bold text-navy-900">
                    {reply.user_name || (reply.is_admin_reply ? 'Platform Engineer' : 'Tenant Staff')}
                  </span>
                  <span className="font-mono text-[10px] text-slate-400">
                    {new Date(reply.created_at).toLocaleString()}
                  </span>
                </div>
                <p className="text-xs text-slate-700 leading-relaxed whitespace-pre-wrap">
                  {reply.message}
                </p>
              </div>
            ))}
          </div>

          {/* Reply Form */}
          {selectedTicket.status !== 'closed' ? (
            <form onSubmit={handleSendReply} className="p-6 pt-0 space-y-3 border-t border-slate-100">
              <textarea
                rows={3}
                required
                value={replyMessage}
                onChange={(e) => setReplyMessage(e.target.value)}
                placeholder="Type your message reply..."
                className="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs font-medium text-navy-950 focus:outline-hidden focus:ring-2 focus:ring-primary-500 resize-none"
              />
              <div className="flex justify-end">
                <button
                  type="submit"
                  disabled={sendingReply || !replyMessage.trim()}
                  className="px-4 py-2 bg-primary-600 hover:bg-primary-700 text-white rounded-xl text-xs font-semibold shadow-xs transition flex items-center gap-1.5 disabled:opacity-50"
                >
                  <Send className="w-3.5 h-3.5" />
                  {sendingReply ? 'Sending...' : 'Send Reply'}
                </button>
              </div>
            </form>
          ) : (
            <div className="p-4 bg-slate-50 text-center text-xs text-slate-500 font-medium">
              This ticket has been marked as resolved and closed.
            </div>
          )}
        </div>
      ) : (
        /* Tickets Table */
        <div className="bg-white rounded-2xl border border-slate-200/80 shadow-xs overflow-hidden">
          <div className="px-6 py-4 border-b border-slate-100 flex items-center justify-between">
            <h3 className="font-bold text-navy-950 text-sm">Your Support Cases</h3>
            <button
              onClick={fetchTickets}
              className="p-1.5 rounded-lg border border-slate-200 hover:bg-slate-100 text-slate-500"
              title="Refresh"
            >
              <RefreshCw className={`w-3.5 h-3.5 ${loading ? 'animate-spin' : ''}`} />
            </button>
          </div>

          <div className="overflow-x-auto">
            <table className="w-full text-left border-collapse text-xs">
              <thead>
                <tr className="bg-slate-50/80 text-[11px] font-bold text-slate-500 uppercase tracking-wider border-b border-slate-100">
                  <th className="py-3 px-6">Ticket ID & Subject</th>
                  <th className="py-3 px-4">Category</th>
                  <th className="py-3 px-4">Priority</th>
                  <th className="py-3 px-4">Status</th>
                  <th className="py-3 px-4">Created Date</th>
                  <th className="py-3 px-6 text-right">Action</th>
                </tr>
              </thead>
              <tbody className="divide-y divide-slate-100">
                {tickets.length > 0 ? (
                  tickets.map((t) => (
                    <tr
                      key={t.id}
                      onClick={() => handleSelectTicket(t)}
                      className="hover:bg-slate-50/60 transition cursor-pointer"
                    >
                      <td className="py-3.5 px-6 font-bold text-navy-950">
                        #{t.id} - {t.subject}
                      </td>
                      <td className="py-3.5 px-4 capitalize text-slate-600 font-medium">
                        {t.category || 'Technical'}
                      </td>
                      <td className="py-3.5 px-4 capitalize font-semibold">
                        <span
                          className={`px-2 py-0.5 rounded-full text-[10px] ${
                            t.priority === 'high'
                              ? 'bg-rose-100 text-rose-800'
                              : t.priority === 'urgent'
                              ? 'bg-red-100 text-red-900 font-black'
                              : 'bg-slate-100 text-slate-700'
                          }`}
                        >
                          {t.priority}
                        </span>
                      </td>
                      <td className="py-3.5 px-4">
                        <StatusBadge status={t.status} />
                      </td>
                      <td className="py-3.5 px-4 text-slate-500 font-mono text-[11px]">
                        {new Date(t.created_at).toLocaleDateString()}
                      </td>
                      <td className="py-3.5 px-6 text-right">
                        <ChevronRight className="w-4 h-4 text-slate-400 inline" />
                      </td>
                    </tr>
                  ))
                ) : (
                  <tr>
                    <td colSpan={6} className="py-12 text-center text-slate-400">
                      <HelpCircle className="w-10 h-10 text-slate-300 mx-auto mb-2" />
                      <p className="text-xs font-semibold text-navy-900">No support tickets opened</p>
                      <p className="text-[11px] text-slate-500 mt-0.5">Need help? Open a case to connect with platform engineers</p>
                    </td>
                  </tr>
                )}
              </tbody>
            </table>
          </div>
        </div>
      )}

      {/* New Ticket Modal */}
      <Modal
        isOpen={isOpenNew}
        onClose={() => setIsOpenNew(false)}
        title="Open Support Ticket"
        size="md"
      >
        <form onSubmit={handleCreateTicket} className="space-y-4">
          <div>
            <label className="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1.5">
              Subject *
            </label>
            <input
              type="text"
              required
              placeholder="e.g. Question regarding PDF page rasterization speed"
              value={newSubject}
              onChange={(e) => setNewSubject(e.target.value)}
              className="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs font-medium text-navy-950 focus:outline-hidden focus:ring-2 focus:ring-primary-500"
            />
          </div>

          <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
              <label className="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1.5">
                Category
              </label>
              <select
                value={newCategory}
                onChange={(e) => setNewCategory(e.target.value)}
                className="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs font-medium text-navy-950 focus:outline-hidden focus:ring-2 focus:ring-primary-500"
              >
                <option value="technical">Technical / PDF Engine</option>
                <option value="billing">Billing & Limits</option>
                <option value="feature">Feature Request</option>
                <option value="other">Other Inquiry</option>
              </select>
            </div>

            <div>
              <label className="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1.5">
                Priority
              </label>
              <select
                value={newPriority}
                onChange={(e) => setNewPriority(e.target.value)}
                className="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs font-medium text-navy-950 focus:outline-hidden focus:ring-2 focus:ring-primary-500"
              >
                <option value="low">Low</option>
                <option value="medium">Medium</option>
                <option value="high">High</option>
                <option value="urgent">Urgent</option>
              </select>
            </div>
          </div>

          <div>
            <label className="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1.5">
              Message / Issue Description *
            </label>
            <textarea
              rows={4}
              required
              value={newMessage}
              onChange={(e) => setNewMessage(e.target.value)}
              placeholder="Describe what occurred, any error messages, or assistance needed..."
              className="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs font-medium text-navy-950 focus:outline-hidden focus:ring-2 focus:ring-primary-500 resize-none"
            />
          </div>

          <div className="flex items-center justify-end gap-3 pt-3 border-t border-slate-100">
            <button
              type="button"
              onClick={() => setIsOpenNew(false)}
              className="px-4 py-2 text-xs font-semibold text-slate-600 bg-white border border-slate-200 rounded-xl hover:bg-slate-50"
            >
              Cancel
            </button>
            <button
              type="submit"
              disabled={submitting}
              className="px-5 py-2 text-xs font-semibold text-white bg-primary-600 hover:bg-primary-700 rounded-xl shadow-xs transition"
            >
              {submitting ? 'Submitting...' : 'Submit Ticket'}
            </button>
          </div>
        </form>
      </Modal>
    </div>
  );
};
