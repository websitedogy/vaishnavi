import React from 'react';

interface StatCardProps {
  title: string;
  value: string | number;
  icon: React.ReactNode;
  change?: string;
  trend?: 'up' | 'down' | 'neutral';
  subtitle?: string;
  subValue?: string;
  isPositive?: boolean;
}

export const StatCard: React.FC<StatCardProps> = ({
  title,
  value,
  icon,
  change,
  trend = 'neutral',
  subtitle,
  subValue,
  isPositive,
}) => {
  const isUp = isPositive !== undefined ? isPositive : trend === 'up';

  return (
    <div className="bg-white border border-slate-200/90 rounded-2xl p-5 shadow-xs hover:border-primary-200 transition-all flex flex-col justify-between">
      <div className="flex items-start justify-between">
        <div>
          <p className="text-xs font-semibold text-slate-500 uppercase tracking-wider">{title}</p>
          <h3 className="text-2xl font-bold text-navy-900 mt-1 tracking-tight">{value}</h3>
          {(subValue || subtitle) && (
            <p className="text-[11px] text-slate-400 mt-0.5">{subValue || subtitle}</p>
          )}
        </div>
        <div className="p-2.5 rounded-xl bg-slate-50 border border-slate-100 flex items-center justify-center shrink-0">
          {icon}
        </div>
      </div>

      {change && (
        <div className="mt-3 pt-3 border-t border-slate-100 flex items-center justify-between text-xs">
          <span
            className={`font-semibold text-[11px] ${
              isUp
                ? 'text-emerald-600'
                : trend === 'down'
                ? 'text-rose-600'
                : 'text-slate-500'
            }`}
          >
            {change}
          </span>
        </div>
      )}
    </div>
  );
};
