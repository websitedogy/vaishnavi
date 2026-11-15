import React, { useState } from 'react';
import { Search, Bell, User, ChevronDown, CheckCircle, ExternalLink, Shield } from 'lucide-react';
import { TenantStaff, TenantInfo } from '../types';

interface TenantNavbarProps {
  user: TenantStaff | null;
  tenant: TenantInfo | null;
  unreadCount: number;
  onOpenNotifications: () => void;
  onNavigateTab: (tab: string) => void;
  onLogout: () => void;
}

export const TenantNavbar: React.FC<TenantNavbarProps> = ({
  user,
  tenant,
  unreadCount,
  onOpenNotifications,
  onNavigateTab,
  onLogout,
}) => {
  const [profileOpen, setProfileOpen] = useState(false);

  return (
    <header className="h-16 bg-white border-b border-slate-200 px-6 flex items-center justify-between z-20">
      {/* Left: Newspaper Publication Header */}
      <div className="flex items-center gap-4">
        <div>
          <div className="flex items-center gap-2">
            <h1 className="text-sm font-bold text-navy-900 truncate">
              {tenant?.name || 'Newspaper Publisher'}
            </h1>
            <span className="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200">
              <span className="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
              {tenant?.status?.toUpperCase() || 'ACTIVE'}
            </span>
          </div>
          <p className="text-[11px] text-slate-500 font-mono">
            {tenant?.domain || `${tenant?.slug || 'publisher'}.dogyepaper.com`}
          </p>
        </div>
      </div>

      {/* Right Actions */}
      <div className="flex items-center gap-4">
        {/* Notifications Bell */}
        <button
          onClick={onOpenNotifications}
          className="relative p-2 text-slate-500 hover:text-navy-900 hover:bg-slate-100 rounded-xl transition-colors"
          title="Notifications"
        >
          <Bell className="w-5 h-5" />
          {unreadCount > 0 && (
            <span className="absolute top-1.5 right-1.5 w-4 h-4 rounded-full bg-rose-600 text-[10px] font-bold text-white flex items-center justify-center shadow-xs">
              {unreadCount > 9 ? '9+' : unreadCount}
            </span>
          )}
        </button>

        {/* Staff User Menu */}
        <div className="relative">
          <button
            onClick={() => setProfileOpen(!profileOpen)}
            className="flex items-center gap-3 p-1.5 pl-2.5 rounded-xl hover:bg-slate-100 transition-colors"
          >
            <div className="text-right hidden sm:block">
              <p className="text-xs font-semibold text-navy-900 leading-tight">{user?.name || 'Staff User'}</p>
              <p className="text-[10px] uppercase font-bold text-blue-600">{user?.role || 'Admin'}</p>
            </div>
            <div className="w-8 h-8 rounded-lg bg-blue-600 text-white font-bold text-xs flex items-center justify-center shadow-xs">
              {user?.name ? user.name.substring(0, 2).toUpperCase() : 'TU'}
            </div>
            <ChevronDown className="w-3.5 h-3.5 text-slate-400" />
          </button>

          {profileOpen && (
            <div
              className="absolute right-0 mt-2 w-56 bg-white rounded-xl shadow-xl border border-slate-200 py-1.5 text-xs text-slate-700 z-50 animate-in fade-in zoom-in-95 duration-100"
              onClick={() => setProfileOpen(false)}
            >
              <div className="px-3 py-2 border-b border-slate-100">
                <p className="font-semibold text-navy-900">{user?.name}</p>
                <p className="text-slate-500 text-[11px] truncate">{user?.email}</p>
              </div>

              <button
                onClick={() => onNavigateTab('account')}
                className="w-full text-left px-3 py-2 hover:bg-slate-50 flex items-center gap-2 text-slate-700"
              >
                <User className="w-3.5 h-3.5 text-slate-400" />
                <span>My Profile & Password</span>
              </button>

              <button
                onClick={() => onNavigateTab('subscription')}
                className="w-full text-left px-3 py-2 hover:bg-slate-50 flex items-center gap-2 text-slate-700"
              >
                <Shield className="w-3.5 h-3.5 text-slate-400" />
                <span>Plan Quotas & Usage</span>
              </button>

              <div className="border-t border-slate-100 mt-1 pt-1">
                <button
                  onClick={onLogout}
                  className="w-full text-left px-3 py-2 hover:bg-rose-50 text-rose-600 flex items-center gap-2 font-medium"
                >
                  <span>Sign Out</span>
                </button>
              </div>
            </div>
          )}
        </div>
      </div>
    </header>
  );
};
