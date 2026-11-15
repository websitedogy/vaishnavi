import React from 'react';

interface StatusBadgeProps {
  status: string;
}

export const StatusBadge: React.FC<StatusBadgeProps> = ({ status }) => {
  const normalized = status ? status.toLowerCase() : 'unknown';

  let colorClasses = 'bg-slate-100 text-slate-700 border-slate-200';
  let label = status;

  switch (normalized) {
    case 'published':
      colorClasses = 'bg-emerald-50 text-emerald-700 border-emerald-200';
      label = 'Published';
      break;
    case 'scheduled':
      colorClasses = 'bg-blue-50 text-blue-700 border-blue-200';
      label = 'Scheduled';
      break;
    case 'ready_for_review':
    case 'ready':
      colorClasses = 'bg-indigo-50 text-indigo-700 border-indigo-200';
      label = 'Ready for Review';
      break;
    case 'draft':
      colorClasses = 'bg-slate-100 text-slate-700 border-slate-200';
      label = 'Draft';
      break;
    case 'processing':
    case 'uploaded':
      colorClasses = 'bg-amber-50 text-amber-700 border-amber-200';
      label = 'Processing';
      break;
    case 'processing_failed':
    case 'failed':
      colorClasses = 'bg-rose-50 text-rose-700 border-rose-200';
      label = 'Processing Failed';
      break;
    case 'archived':
      colorClasses = 'bg-purple-50 text-purple-700 border-purple-200';
      label = 'Archived';
      break;
    case 'active':
      colorClasses = 'bg-emerald-50 text-emerald-700 border-emerald-200';
      label = 'Active';
      break;
    case 'open':
      colorClasses = 'bg-blue-50 text-blue-700 border-blue-200';
      label = 'Open';
      break;
    case 'closed':
    case 'resolved':
      colorClasses = 'bg-slate-100 text-slate-600 border-slate-200';
      label = 'Resolved';
      break;
  }

  return (
    <span
      className={`inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold border ${colorClasses} capitalize`}
    >
      {label}
    </span>
  );
};

export default StatusBadge;
