import React, { useEffect, useState } from 'react';
import {
  CreditCard,
  HardDrive,
  Users,
  Newspaper,
  CheckCircle2,
  AlertCircle,
  ArrowUpRight,
  RefreshCw,
  Zap,
  Shield,
} from 'lucide-react';
import { subscriptionApi } from '../services/api';
import { SubscriptionUsage } from '../types';
import { Modal } from '../components/Modal';

export const SubscriptionUsageView: React.FC = () => {
  const [data, setData] = useState<SubscriptionUsage | null>(null);
  const [loading, setLoading] = useState(true);
  const [isUpgradeOpen, setIsUpgradeOpen] = useState(false);
  const [requestedPlan, setRequestedPlan] = useState('enterprise');
  const [notes, setNotes] = useState('');
  const [submitting, setSubmitting] = useState(false);
  const [success, setSuccess] = useState<string | null>(null);

  const fetchUsage = async () => {
    setLoading(true);
    try {
      const res = await subscriptionApi.getUsage();
      setData(res.data);
    } catch (err: any) {
      console.error('Failed to load subscription usage:', err);
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => {
    fetchUsage();
  }, []);

  const handleUpgradeSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    setSubmitting(true);
    try {
      await subscriptionApi.requestUpgrade({
        requested_plan: requestedPlan,
        notes,
      });
      setIsUpgradeOpen(false);
      setSuccess('Upgrade request submitted to Super Admin platform team. You will be notified upon review!');
      setNotes('');
      setTimeout(() => setSuccess(null), 5000);
      await fetchUsage();
    } catch (err: any) {
      alert(err?.response?.data?.message || 'Failed to submit upgrade request.');
    } finally {
      setSubmitting(false);
    }
  };

  if (loading) {
    return (
      <div className="flex flex-col items-center justify-center min-h-[350px] space-y-3">
        <RefreshCw className="w-8 h-8 text-primary-600 animate-spin" />
        <p className="text-sm font-medium text-slate-500">Checking subscription quotas...</p>
      </div>
    );
  }

  const { plan, usage, upgrade_requests } = data || {};
  const storagePercent = plan ? Math.min(Math.round(((usage?.used_storage_mb || 0) / (plan.storage_limit_mb || 1)) * 100), 100) : 0;
  const editionsPercent = plan ? Math.min(Math.round(((usage?.editions_count || 0) / (plan.max_editions_per_month || 1)) * 100), 100) : 0;
  const staffPercent = plan ? Math.min(Math.round(((usage?.staff_count || 0) / (plan.max_staff || 1)) * 100), 100) : 0;

  return (
    <div className="space-y-6">
      {/* Header */}
      <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
          <h1 className="text-xl font-bold text-navy-950 flex items-center gap-2">
            <CreditCard className="w-6 h-6 text-primary-600" />
            Subscription & Resource Quotas
          </h1>
          <p className="text-xs text-slate-500 mt-1">
            Monitor allocated publication capacity, storage boundaries, and request platform quota tier upgrades
          </p>
        </div>

        <button
          onClick={() => setIsUpgradeOpen(true)}
          className="px-4 py-2.5 bg-gradient-to-r from-primary-600 to-primary-700 hover:from-primary-500 hover:to-primary-600 text-white rounded-xl text-xs font-semibold shadow-md shadow-primary-500/20 transition flex items-center gap-2"
        >
          <Zap className="w-4 h-4" />
          Request Plan Upgrade
        </button>
      </div>

      {success && (
        <div className="p-3.5 bg-emerald-50 border border-emerald-200 text-emerald-800 text-xs font-medium rounded-xl flex items-center gap-2">
          <CheckCircle2 className="w-4 h-4 text-emerald-600 flex-shrink-0" />
          {success}
        </div>
      )}

      {/* Plan Details Card */}
      <div className="p-6 rounded-2xl bg-white border border-slate-200/80 shadow-xs flex flex-col md:flex-row md:items-center justify-between gap-6">
        <div>
          <div className="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-primary-50 text-primary-700 text-xs font-bold border border-primary-200 mb-2">
            <Shield className="w-3.5 h-3.5" />
            ACTIVE PLAN
          </div>
          <h2 className="text-2xl font-black text-navy-950">
            {plan?.name || 'Professional Publisher Plan'}
          </h2>
          <p className="text-xs text-slate-500 mt-1 max-w-xl leading-relaxed">
            Allocated multi-tenant workspace with dedicated newspaper subdomain, white-label branding, and local rasterization pipeline.
          </p>
        </div>

        <div className="flex flex-col items-start md:items-end justify-center">
          <div className="text-sm font-semibold text-slate-500">Subscription Status</div>
          <div className="text-lg font-bold text-emerald-600 flex items-center gap-1.5 mt-0.5">
            <span className="w-2.5 h-2.5 rounded-full bg-emerald-500" />
            Active & Verified
          </div>
        </div>
      </div>

      {/* Resource Progress Grid */}
      <div className="grid grid-cols-1 md:grid-cols-3 gap-6">
        {/* Storage */}
        <div className="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-xs space-y-3">
          <div className="flex items-center justify-between">
            <span className="text-xs font-bold text-slate-600 flex items-center gap-1.5">
              <HardDrive className="w-4 h-4 text-primary-600" />
              Storage Limit
            </span>
            <span className="text-xs font-mono font-bold text-navy-950">
              {storagePercent}%
            </span>
          </div>

          <div className="w-full h-2.5 bg-slate-100 rounded-full overflow-hidden">
            <div
              className={`h-full rounded-full transition-all duration-300 ${
                storagePercent > 85 ? 'bg-rose-500' : 'bg-primary-600'
              }`}
              style={{ width: `${storagePercent}%` }}
            />
          </div>

          <div className="text-xs text-slate-500 flex justify-between font-medium">
            <span>{usage?.used_storage_mb || 0} MB used</span>
            <span>{plan?.storage_limit_mb || 1024} MB cap</span>
          </div>
        </div>

        {/* Monthly Editions */}
        <div className="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-xs space-y-3">
          <div className="flex items-center justify-between">
            <span className="text-xs font-bold text-slate-600 flex items-center gap-1.5">
              <Newspaper className="w-4 h-4 text-sky-600" />
              Monthly Editions
            </span>
            <span className="text-xs font-mono font-bold text-navy-950">
              {editionsPercent}%
            </span>
          </div>

          <div className="w-full h-2.5 bg-slate-100 rounded-full overflow-hidden">
            <div
              className="h-full bg-sky-500 rounded-full transition-all duration-300"
              style={{ width: `${editionsPercent}%` }}
            />
          </div>

          <div className="text-xs text-slate-500 flex justify-between font-medium">
            <span>{usage?.editions_count || 0} editions</span>
            <span>{plan?.max_editions_per_month || 100} / mo</span>
          </div>
        </div>

        {/* Staff Seats */}
        <div className="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-xs space-y-3">
          <div className="flex items-center justify-between">
            <span className="text-xs font-bold text-slate-600 flex items-center gap-1.5">
              <Users className="w-4 h-4 text-indigo-600" />
              Editorial Seats
            </span>
            <span className="text-xs font-mono font-bold text-navy-950">
              {staffPercent}%
            </span>
          </div>

          <div className="w-full h-2.5 bg-slate-100 rounded-full overflow-hidden">
            <div
              className="h-full bg-indigo-500 rounded-full transition-all duration-300"
              style={{ width: `${staffPercent}%` }}
            />
          </div>

          <div className="text-xs text-slate-500 flex justify-between font-medium">
            <span>{usage?.staff_count || 0} active</span>
            <span>{plan?.max_staff || 5} seats cap</span>
          </div>
        </div>
      </div>

      {/* Upgrade Requests History */}
      <div className="bg-white rounded-2xl border border-slate-200/80 shadow-xs overflow-hidden">
        <div className="px-6 py-4 border-b border-slate-100 flex items-center justify-between">
          <h3 className="font-bold text-navy-950 text-sm">Tier Upgrade Request History</h3>
        </div>

        <div className="overflow-x-auto">
          <table className="w-full text-left border-collapse text-xs">
            <thead>
              <tr className="bg-slate-50/80 text-[11px] font-bold text-slate-500 uppercase tracking-wider border-b border-slate-100">
                <th className="py-3 px-6">Requested Tier</th>
                <th className="py-3 px-4">Request Notes</th>
                <th className="py-3 px-4">Submitted Date</th>
                <th className="py-3 px-6 text-right">Status</th>
              </tr>
            </thead>
            <tbody className="divide-y divide-slate-100">
              {upgrade_requests && upgrade_requests.length > 0 ? (
                upgrade_requests.map((req) => (
                  <tr key={req.id} className="hover:bg-slate-50/60 transition">
                    <td className="py-3.5 px-6 font-bold text-navy-950 capitalize">
                      {req.requested_plan}
                    </td>
                    <td className="py-3.5 px-4 text-slate-600 max-w-sm truncate">
                      {req.notes || 'Standard tier increase requested'}
                    </td>
                    <td className="py-3.5 px-4 text-slate-500 font-mono text-[11px]">
                      {new Date(req.created_at).toLocaleDateString()}
                    </td>
                    <td className="py-3.5 px-6 text-right">
                      <span
                        className={`inline-block px-2.5 py-0.5 rounded-full text-[10px] font-bold uppercase ${
                          req.status === 'approved'
                            ? 'bg-emerald-100 text-emerald-800'
                            : req.status === 'rejected'
                            ? 'bg-rose-100 text-rose-800'
                            : 'bg-amber-100 text-amber-800'
                        }`}
                      >
                        {req.status}
                      </span>
                    </td>
                  </tr>
                ))
              ) : (
                <tr>
                  <td colSpan={4} className="py-8 text-center text-slate-400">
                    No upgrade requests submitted yet.
                  </td>
                </tr>
              )}
            </tbody>
          </table>
        </div>
      </div>

      {/* Upgrade Request Modal */}
      <Modal
        isOpen={isUpgradeOpen}
        onClose={() => setIsUpgradeOpen(false)}
        title="Request Subscription Tier Upgrade"
        size="md"
      >
        <form onSubmit={handleUpgradeSubmit} className="space-y-4">
          <div>
            <label className="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1.5">
              Target Plan Tier *
            </label>
            <select
              value={requestedPlan}
              onChange={(e) => setRequestedPlan(e.target.value)}
              className="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs font-medium text-navy-950 focus:outline-hidden focus:ring-2 focus:ring-primary-500"
            >
              <option value="professional">Professional (5,000 MB Storage, 250 Editions/mo, 10 Staff)</option>
              <option value="enterprise">Enterprise (50,000 MB Storage, Unlimited Editions, 50 Staff, Custom Domain)</option>
            </select>
          </div>

          <div>
            <label className="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1.5">
              Specific Requirements / Editorial Notes
            </label>
            <textarea
              rows={3}
              value={notes}
              onChange={(e) => setNotes(e.target.value)}
              placeholder="State any specific storage or volume requirements for the platform team..."
              className="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs font-medium text-navy-950 focus:outline-hidden focus:ring-2 focus:ring-primary-500 resize-none"
            />
          </div>

          <div className="flex items-center justify-end gap-3 pt-3 border-t border-slate-100">
            <button
              type="button"
              onClick={() => setIsUpgradeOpen(false)}
              className="px-4 py-2 text-xs font-semibold text-slate-600 bg-white border border-slate-200 rounded-xl hover:bg-slate-50"
            >
              Cancel
            </button>
            <button
              type="submit"
              disabled={submitting}
              className="px-5 py-2 text-xs font-semibold text-white bg-primary-600 hover:bg-primary-700 rounded-xl shadow-xs transition"
            >
              {submitting ? 'Submitting...' : 'Submit Request'}
            </button>
          </div>
        </form>
      </Modal>
    </div>
  );
};
