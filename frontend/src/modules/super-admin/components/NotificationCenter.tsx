import React from 'react';
import { X, AlertCircle, CheckCircle, Clock } from 'lucide-react';
import { InfrastructureAlert } from '../types';

interface NotificationCenterProps {
  isOpen: boolean;
  onClose: () => void;
  alerts: InfrastructureAlert[];
  onAcknowledgeAlert: (id: number) => void;
  onResolveAlert: (id: number) => void;
}

export const NotificationCenter: React.FC<NotificationCenterProps> = ({
  isOpen,
  onClose,
  alerts,
  onAcknowledgeAlert,
  onResolveAlert,
}) => {
  if (!isOpen) return null;

  return (
    <div className="fixed inset-0 z-50 overflow-hidden">
      <div className="absolute inset-0 bg-slate-900/40 backdrop-blur-xs transition-opacity" onClick={onClose} />

      <div className="fixed inset-y-0 right-0 max-w-full flex pl-10">
        <div className="w-screen max-w-md bg-white border-l border-slate-200 shadow-2xl flex flex-col">
          {/* Header */}
          <div className="px-6 py-4 bg-slate-50 border-b border-slate-200 flex items-center justify-between">
            <div>
              <h2 className="text-base font-bold text-slate-900">Platform Notification Center</h2>
              <p className="text-xs text-slate-500 mt-0.5">Live infrastructure alerts & platform status</p>
            </div>
            <button
              onClick={onClose}
              className="p-1.5 text-slate-400 hover:text-slate-600 rounded-lg hover:bg-slate-200/60 transition-colors"
            >
              <X className="w-5 h-5" />
            </button>
          </div>

          {/* List */}
          <div className="flex-1 overflow-y-auto p-4 space-y-3">
            {alerts.length === 0 ? (
              <div className="text-center py-16 text-slate-400">
                <CheckCircle className="w-12 h-12 text-emerald-500 mx-auto mb-2 opacity-80" />
                <p className="text-sm font-semibold text-slate-700">All systems operational</p>
                <p className="text-xs text-slate-500 mt-1">No active infrastructure alerts</p>
              </div>
            ) : (
              alerts.map((alert) => (
                <div
                  key={alert.id}
                  className={`p-4 rounded-xl border text-xs space-y-2 transition-shadow shadow-2xs ${
                    alert.severity === 'critical'
                      ? 'bg-rose-50/70 border-rose-200 text-rose-900'
                      : alert.severity === 'high'
                      ? 'bg-amber-50/70 border-amber-200 text-amber-900'
                      : 'bg-blue-50/70 border-blue-200 text-blue-900'
                  }`}
                >
                  <div className="flex items-start justify-between">
                    <div className="flex items-center space-x-1.5 font-bold uppercase tracking-wider text-[10px]">
                      <AlertCircle className="w-3.5 h-3.5 flex-shrink-0" />
                      <span>{alert.severity} • {alert.service?.service_name || 'SYSTEM'}</span>
                    </div>
                    <span className="text-[10px] text-slate-400 font-mono">
                      {new Date(alert.triggered_at).toLocaleTimeString()}
                    </span>
                  </div>

                  <h4 className="font-semibold text-sm leading-snug">{alert.title}</h4>
                  {alert.description && <p className="text-slate-600 text-xs">{alert.description}</p>}

                  <div className="flex items-center justify-end space-x-2 pt-2 border-t border-slate-200/60">
                    {alert.status === 'active' && (
                      <button
                        onClick={() => onAcknowledgeAlert(alert.id)}
                        className="px-2.5 py-1 text-[11px] font-semibold bg-white border border-slate-300 rounded-md text-slate-700 hover:bg-slate-50 shadow-2xs"
                      >
                        Acknowledge
                      </button>
                    )}
                    <button
                      onClick={() => onResolveAlert(alert.id)}
                      className="px-2.5 py-1 text-[11px] font-semibold bg-emerald-600 text-white rounded-md hover:bg-emerald-700 shadow-2xs"
                    >
                      Resolve Alert
                    </button>
                  </div>
                </div>
              ))
            )}
          </div>
        </div>
      </div>
    </div>
  );
};
