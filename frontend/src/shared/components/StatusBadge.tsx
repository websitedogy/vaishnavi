import React from 'react';

export type StatusType = 
  | 'active' 
  | 'healthy' 
  | 'success' 
  | 'completed'
  | 'verified' 
  | 'paid'
  | 'pending' 
  | 'provisioning' 
  | 'queued' 
  | 'processing' 
  | 'waiting_on_tenant'
  | 'warning'
  | 'trial'
  | 'suspended' 
  | 'expired' 
  | 'failed' 
  | 'critical' 
  | 'error' 
  | 'cancelled'
  | 'archived'
  | 'closed';

interface StatusBadgeProps {
  status: string;
  size?: 'sm' | 'md';
}

export const StatusBadge: React.FC<StatusBadgeProps> = ({ status, size = 'sm' }) => {
  const norm = status.toLowerCase() as StatusType;

  let colorClasses = 'bg-slate-100 text-slate-700 border-slate-200';

  if (['active', 'healthy', 'success', 'completed', 'verified', 'paid'].includes(norm)) {
    colorClasses = 'bg-emerald-50 text-emerald-700 border-emerald-200';
  } else if (['pending', 'provisioning', 'queued', 'processing', 'waiting_on_tenant', 'warning'].includes(norm)) {
    colorClasses = 'bg-amber-50 text-amber-700 border-amber-200';
  } else if (['trial'].includes(norm)) {
    colorClasses = 'bg-blue-50 text-blue-700 border-blue-200';
  } else if (['suspended', 'expired', 'failed', 'critical', 'error', 'cancelled'].includes(norm)) {
    colorClasses = 'bg-rose-50 text-rose-700 border-rose-200';
  } else if (['archived', 'closed'].includes(norm)) {
    colorClasses = 'bg-slate-100 text-slate-600 border-slate-200';
  }

  const sizeClasses = size === 'sm' ? 'px-2 py-0.5 text-xs' : 'px-2.5 py-1 text-sm';

  return (
    <span className={`inline-flex items-center font-medium capitalize rounded-full border ${sizeClasses} ${colorClasses}`}>
      <span className="w-1.5 h-1.5 mr-1.5 rounded-full bg-current opacity-75"></span>
      {status.replace(/_/g, ' ')}
    </span>
  );
};

export default StatusBadge;
