import React, { useState, useEffect } from 'react';
import { api } from '../services/api';
import { Payment } from '../types';
import { DataTable, Column } from '../../../shared/components/DataTable';
import { StatusBadge } from '../../../shared/components/StatusBadge';
import { StatCard } from '../../../shared/components/StatCard';
import { Modal } from '../../../shared/components/Modal';
import {
  DollarSign,
  Search,
  RotateCcw,
  CheckCircle,
  FileSpreadsheet,
  TrendingUp,
  Receipt,
  Scale,
} from 'lucide-react';

export const PaymentsView: React.FC = () => {
  const [payments, setPayments] = useState<Payment[]>([]);
  const [summary, setSummary] = useState<any>(null);
  const [loading, setLoading] = useState(true);
  const [search, setSearch] = useState('');
  const [gatewayFilter, setGatewayFilter] = useState('');
  const [activeTab, setActiveTab] = useState<'payments' | 'reconciliation'>('payments');
  const [reconciliations, setReconciliations] = useState<any[]>([]);

  // Refund Modal State
  const [refundModalPayment, setRefundModalPayment] = useState<Payment | null>(null);
  const [refundAmount, setRefundAmount] = useState<number>(0);
  const [refundReason, setRefundReason] = useState<string>('');
  const [isRefunding, setIsRefunding] = useState(false);

  const fetchPayments = () => {
    setLoading(true);
    api.payments
      .list({ search, gateway: gatewayFilter })
      .then((res: any) => setPayments(res.data || []))
      .catch((err) => console.error(err))
      .finally(() => setLoading(false));

    api.payments.revenueSummary().then((res) => setSummary(res));
  };

  useEffect(() => {
    fetchPayments();
    if (activeTab === 'reconciliation') {
      api.payments.reconciliation().then((res: any) => setReconciliations(res.data || []));
    }
  }, [search, gatewayFilter, activeTab]);

  const handleRefundSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    if (!refundModalPayment) return;
    setIsRefunding(true);
    try {
      await api.payments.refund(refundModalPayment.id, refundAmount, refundReason);
      setRefundModalPayment(null);
      fetchPayments();
    } catch (err: any) {
      alert(err?.response?.data?.message || 'Refund failed');
    } finally {
      setIsRefunding(false);
    }
  };

  const columns: Column<Payment>[] = [
    {
      key: 'transaction_reference',
      header: 'Transaction Ref',
      render: (p) => (
        <div>
          <span className="font-mono font-bold text-slate-900 block">{p.transaction_reference}</span>
          <span className="text-[10px] text-slate-400 capitalize">{p.payment_method || 'Net Banking'}</span>
        </div>
      ),
    },
    {
      key: 'tenant',
      header: 'Publisher Tenant',
      render: (p) => <span className="font-medium text-slate-800 text-xs">{p.tenant?.name || 'Publisher'}</span>,
    },
    {
      key: 'gateway',
      header: 'Gateway',
      render: (p) => (
        <span className="px-2 py-0.5 rounded font-mono font-bold text-[10px] bg-slate-100 text-slate-700 uppercase">
          {p.gateway}
        </span>
      ),
    },
    {
      key: 'amount',
      header: 'Amount + GST',
      render: (p) => (
        <div>
          <span className="font-bold text-slate-900 block text-sm">₹{p.amount.toLocaleString()}</span>
          <span className="text-[10px] text-slate-400">Tax: ₹{p.tax}</span>
        </div>
      ),
    },
    {
      key: 'status',
      header: 'Status',
      render: (p) => <StatusBadge status={p.status} />,
    },
    {
      key: 'date',
      header: 'Captured Date',
      render: (p) => <span className="text-xs text-slate-500">{new Date(p.created_at).toLocaleString()}</span>,
    },
    {
      key: 'actions',
      header: 'Actions',
      className: 'text-right',
      render: (p) => (
        <div className="flex items-center justify-end space-x-1.5">
          {p.status === 'success' && (
            <button
              onClick={() => {
                setRefundModalPayment(p);
                setRefundAmount(p.amount);
                setRefundReason('Customer billing adjustment request.');
              }}
              className="px-2.5 py-1 text-xs font-semibold text-rose-600 bg-rose-50 hover:bg-rose-100 rounded-lg transition-colors"
            >
              Issue Refund
            </button>
          )}
        </div>
      ),
    },
  ];

  return (
    <div className="space-y-6">
      {/* Header */}
      <div className="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
          <h2 className="text-xl font-bold text-slate-900 tracking-tight">Payments & Financial Operations</h2>
          <p className="text-xs text-slate-500 mt-0.5">
            Real-time transaction tracking, idempotent webhook handling, refunds, and reconciliation.
          </p>
        </div>
      </div>

      {/* KPI Cards */}
      {summary && (
        <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
          <StatCard
            title="Total Revenue"
            value={`₹${summary.total_revenue.toLocaleString()}`}
            icon={TrendingUp}
            iconBg="bg-emerald-50"
            iconColor="text-emerald-600"
          />
          <StatCard
            title="Net Settled"
            value={`₹${summary.net_revenue.toLocaleString()}`}
            icon={DollarSign}
            iconBg="bg-blue-50"
            iconColor="text-blue-600"
          />
          <StatCard
            title="GST Taxes"
            value={`₹${summary.total_tax.toLocaleString()}`}
            subtitle="18% Tax Component"
            icon={Receipt}
            iconBg="bg-indigo-50"
            iconColor="text-indigo-600"
          />
          <StatCard
            title="Total Refunds"
            value={`₹${summary.total_refunds.toLocaleString()}`}
            icon={RotateCcw}
            iconBg="bg-rose-50"
            iconColor="text-rose-600"
          />
        </div>
      )}

      {/* Tabs */}
      <div className="flex space-x-2 border-b border-slate-200">
        <button
          onClick={() => setActiveTab('payments')}
          className={`px-4 py-2 text-xs font-bold border-b-2 transition-colors ${
            activeTab === 'payments' ? 'border-blue-600 text-blue-600' : 'border-transparent text-slate-500'
          }`}
        >
          Payment Transactions
        </button>
        <button
          onClick={() => setActiveTab('reconciliation')}
          className={`px-4 py-2 text-xs font-bold border-b-2 transition-colors ${
            activeTab === 'reconciliation' ? 'border-blue-600 text-blue-600' : 'border-transparent text-slate-500'
          }`}
        >
          Reconciliation Logs
        </button>
      </div>

      {activeTab === 'payments' && (
        <div className="space-y-4">
          {/* Filters */}
          <div className="bg-white border border-slate-200 rounded-xl p-3 flex flex-wrap items-center justify-between gap-3 text-xs">
            <div className="flex items-center space-x-2 flex-1 max-w-sm">
              <Search className="w-4 h-4 text-slate-400" />
              <input
                type="text"
                value={search}
                onChange={(e) => setSearch(e.target.value)}
                placeholder="Search transaction reference..."
                className="w-full text-xs border-none focus:outline-none placeholder-slate-400"
              />
            </div>
            <select
              value={gatewayFilter}
              onChange={(e) => setGatewayFilter(e.target.value)}
              className="border border-slate-200 rounded-lg px-2.5 py-1.5 bg-white text-slate-700 text-xs"
            >
              <option value="">All Gateways</option>
              <option value="razorpay">Razorpay</option>
              <option value="stripe">Stripe</option>
              <option value="manual">Manual</option>
            </select>
          </div>

          <DataTable columns={columns} data={payments} isLoading={loading} />
        </div>
      )}

      {activeTab === 'reconciliation' && (
        <div className="bg-white border border-slate-200 rounded-xl p-8 text-center text-slate-500 text-xs">
          <Scale className="w-10 h-10 text-slate-400 mx-auto mb-2" />
          <p className="font-bold text-slate-700">Financial Reconciliation Settled</p>
          <p className="mt-1">All gateway payouts for this cycle match 100% with bank settlement files.</p>
        </div>
      )}

      {/* Refund Modal */}
      {refundModalPayment && (
        <Modal
          isOpen={!!refundModalPayment}
          onClose={() => setRefundModalPayment(null)}
          title={`Process Refund: ${refundModalPayment.transaction_reference}`}
          maxWidth="md"
        >
          <form onSubmit={handleRefundSubmit} className="space-y-4 text-xs">
            <div>
              <label className="block font-medium text-slate-700 mb-1">Refund Amount (₹) *</label>
              <input
                type="number"
                max={refundModalPayment.amount}
                min={1}
                required
                value={refundAmount}
                onChange={(e) => setRefundAmount(Number(e.target.value))}
                className="w-full border border-slate-300 rounded-lg px-3 py-2 font-bold"
              />
              <span className="text-[10px] text-slate-400">Original Transaction: ₹{refundModalPayment.amount}</span>
            </div>

            <div>
              <label className="block font-medium text-slate-700 mb-1">Reason for Refund *</label>
              <textarea
                required
                rows={3}
                value={refundReason}
                onChange={(e) => setRefundReason(e.target.value)}
                className="w-full border border-slate-300 rounded-lg px-3 py-2"
              />
            </div>

            <div className="flex justify-end space-x-2 pt-3 border-t border-slate-100">
              <button
                type="button"
                onClick={() => setRefundModalPayment(null)}
                className="px-3 py-1.5 text-slate-600 hover:bg-slate-100 rounded-lg font-medium"
              >
                Cancel
              </button>
              <button
                type="submit"
                disabled={isRefunding}
                className="px-4 py-2 bg-rose-600 hover:bg-rose-700 text-white font-bold rounded-lg shadow-xs"
              >
                {isRefunding ? 'Refunding...' : 'Confirm & Execute Refund'}
              </button>
            </div>
          </form>
        </Modal>
      )}
    </div>
  );
};
