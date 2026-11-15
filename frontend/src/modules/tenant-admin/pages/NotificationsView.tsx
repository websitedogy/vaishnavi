import React, { useEffect, useState } from 'react';
import {
  Bell,
  CheckCheck,
  Check,
  Trash2,
  RefreshCw,
  Info,
  AlertTriangle,
  Send,
  ShieldAlert,
} from 'lucide-react';
import { notificationsApi } from '../services/api';
import { TenantNotification } from '../types';

export const NotificationsView: React.FC = () => {
  const [notifications, setNotifications] = useState<TenantNotification[]>([]);
  const [loading, setLoading] = useState(true);
  const [filter, setFilter] = useState<'all' | 'unread'>('all');
  const [actionLoading, setActionLoading] = useState(false);

  const fetchNotifications = async () => {
    setLoading(true);
    try {
      const res = await notificationsApi.getAll();
      setNotifications(res.data?.data || res.data || []);
    } catch (err: any) {
      console.error('Failed to load notifications:', err);
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => {
    fetchNotifications();
  }, []);

  const handleMarkAsRead = async (id: number) => {
    try {
      await notificationsApi.markAsRead(id);
      setNotifications((prev) =>
        prev.map((n) => (n.id === id ? { ...n, is_read: true } : n))
      );
    } catch (err: any) {
      console.error('Failed to mark notification as read:', err);
    }
  };

  const handleMarkAllAsRead = async () => {
    setActionLoading(true);
    try {
      await notificationsApi.markAllAsRead();
      setNotifications((prev) => prev.map((n) => ({ ...n, is_read: true })));
    } catch (err: any) {
      alert('Failed to mark all as read.');
    } finally {
      setActionLoading(false);
    }
  };

  const handleDelete = async (id: number) => {
    try {
      await notificationsApi.delete(id);
      setNotifications((prev) => prev.filter((n) => n.id !== id));
    } catch (err: any) {
      alert('Failed to delete notification.');
    }
  };

  const displayedList = filter === 'unread' ? notifications.filter((n) => !n.is_read) : notifications;
  const unreadCount = notifications.filter((n) => !n.is_read).length;

  const getTypeIcon = (type: string) => {
    switch (type) {
      case 'publishing':
        return <Send className="w-4 h-4 text-emerald-600" />;
      case 'alert':
      case 'warning':
        return <AlertTriangle className="w-4 h-4 text-amber-600" />;
      case 'security':
        return <ShieldAlert className="w-4 h-4 text-rose-600" />;
      default:
        return <Info className="w-4 h-4 text-primary-600" />;
    }
  };

  return (
    <div className="max-w-4xl mx-auto space-y-6">
      {/* Header */}
      <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
          <h1 className="text-xl font-bold text-navy-950 flex items-center gap-2">
            <Bell className="w-6 h-6 text-primary-600" />
            Notifications & System Alerts
          </h1>
          <p className="text-xs text-slate-500 mt-1">
            Stay updated with PDF conversion completions, morning automated releases, and editorial messages
          </p>
        </div>

        <div className="flex items-center gap-3">
          <button
            onClick={() => setFilter(filter === 'all' ? 'unread' : 'all')}
            className={`px-3 py-2 rounded-xl text-xs font-semibold border transition ${
              filter === 'unread'
                ? 'bg-primary-50 text-primary-700 border-primary-200'
                : 'bg-white text-slate-600 border-slate-200 hover:bg-slate-50'
            }`}
          >
            {filter === 'unread' ? 'Showing Unread Only' : `Show Unread (${unreadCount})`}
          </button>
          {unreadCount > 0 && (
            <button
              onClick={handleMarkAllAsRead}
              disabled={actionLoading}
              className="px-4 py-2 bg-primary-600 hover:bg-primary-700 text-white rounded-xl text-xs font-semibold shadow-xs transition flex items-center gap-1.5"
            >
              <CheckCheck className="w-4 h-4" />
              Mark All Read
            </button>
          )}
        </div>
      </div>

      {/* Notifications List */}
      <div className="bg-white rounded-2xl border border-slate-200/80 shadow-xs divide-y divide-slate-100 overflow-hidden">
        {loading ? (
          <div className="py-16 text-center">
            <RefreshCw className="w-6 h-6 text-primary-600 animate-spin mx-auto mb-2" />
            <p className="text-xs font-medium text-slate-500">Checking notifications...</p>
          </div>
        ) : displayedList.length > 0 ? (
          displayedList.map((notif) => (
            <div
              key={notif.id}
              className={`p-4 transition-colors flex items-start justify-between gap-4 ${
                !notif.is_read ? 'bg-primary-50/20' : 'hover:bg-slate-50/60'
              }`}
            >
              <div className="flex items-start gap-3 min-w-0">
                <div className="p-2 rounded-xl bg-slate-100 mt-0.5 flex-shrink-0">
                  {getTypeIcon(notif.type)}
                </div>

                <div className="min-w-0">
                  <div className="flex items-center gap-2">
                    <h4 className="font-bold text-navy-950 text-xs">
                      {notif.title}
                    </h4>
                    {!notif.is_read && (
                      <span className="w-2 h-2 rounded-full bg-primary-600 flex-shrink-0" />
                    )}
                  </div>
                  <p className="text-xs text-slate-600 mt-0.5 leading-relaxed">
                    {notif.message}
                  </p>
                  <span className="text-[10px] text-slate-400 font-mono mt-1 block">
                    {new Date(notif.created_at).toLocaleString()}
                  </span>
                </div>
              </div>

              <div className="flex items-center gap-1 flex-shrink-0">
                {!notif.is_read && (
                  <button
                    onClick={() => handleMarkAsRead(notif.id)}
                    className="p-1.5 rounded-lg text-slate-400 hover:text-primary-600 hover:bg-primary-50 transition"
                    title="Mark as Read"
                  >
                    <Check className="w-4 h-4" />
                  </button>
                )}
                <button
                  onClick={() => handleDelete(notif.id)}
                  className="p-1.5 rounded-lg text-slate-400 hover:text-rose-600 hover:bg-rose-50 transition"
                  title="Delete"
                >
                  <Trash2 className="w-4 h-4" />
                </button>
              </div>
            </div>
          ))
        ) : (
          <div className="py-16 text-center text-slate-400">
            <Bell className="w-10 h-10 text-slate-300 mx-auto mb-2" />
            <p className="text-xs font-semibold text-navy-900">No notifications to display</p>
            <p className="text-[11px] text-slate-500 mt-0.5">You're all caught up!</p>
          </div>
        )}
      </div>
    </div>
  );
};
