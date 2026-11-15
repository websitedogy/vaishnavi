import React, { useState } from 'react';
import { Search, Bell, Shield, ChevronDown, CheckCircle2 } from 'lucide-react';
import { SuperAdminUser } from '../types';

interface TopbarProps {
  user: SuperAdminUser | null;
  alertCount: number;
  onOpenNotifications: () => void;
  onOpenSearch: () => void;
  onNavigateProfile: () => void;
  onLogout: () => void;
}

export const Topbar: React.FC<TopbarProps> = ({
  user,
  alertCount,
  onOpenNotifications,
  onOpenSearch,
  onNavigateProfile,
  onLogout,
}) => {
  const [profileDropdownOpen, setProfileDropdownOpen] = useState(false);

  return (
    <header className="h-16 bg-white border-b border-slate-200 px-6 flex items-center justify-between z-20">
      {/* Search Bar Button */}
      <div className="flex items-center space-x-3 flex-1 max-w-md">
        <button
          onClick={onOpenSearch}
          className="w-full flex items-center space-x-2.5 px-3.5 py-2 text-xs text-slate-400 bg-slate-50 hover:bg-slate-100/80 border border-slate-200 rounded-xl transition-colors text-left"
        >
          <Search className="w-4 h-4 text-slate-400" />
          <span className="flex-1">Search tenants, domains, tickets, subscriptions...</span>
          <kbd className="hidden sm:inline-block px-1.5 py-0.5 text-[10px] font-semibold text-slate-500 bg-white border border-slate-200 rounded shadow-2xs">
            ⌘K
          </kbd>
        </button>
      </div>

      {/* Right Actions */}
      <div className="flex items-center space-x-4">
        {/* Environment Status Badge */}
        <div className="hidden lg:flex items-center space-x-1.5 px-2.5 py-1 rounded-full bg-blue-50 border border-blue-200 text-blue-700 text-xs font-medium">
          <span className="w-2 h-2 rounded-full bg-blue-600 animate-pulse"></span>
          <span>AWS Multi-Tenant • 10K Target Fleet</span>
        </div>

        {/* Notifications Bell */}
        <button
          onClick={onOpenNotifications}
          className="relative p-2 text-slate-500 hover:text-slate-800 hover:bg-slate-100 rounded-xl transition-colors"
          title="Notification Center"
        >
          <Bell className="w-5 h-5" />
          {alertCount > 0 && (
            <span className="absolute top-1.5 right-1.5 w-4 h-4 rounded-full bg-rose-600 text-[10px] font-bold text-white flex items-center justify-center shadow-xs">
              {alertCount > 9 ? '9+' : alertCount}
            </span>
          )}
        </button>

        {/* Profile Dropdown */}
        <div className="relative">
          <button
            onClick={() => setProfileDropdownOpen(!profileDropdownOpen)}
            className="flex items-center space-x-3 p-1.5 pr-2.5 rounded-xl hover:bg-slate-50 border border-transparent hover:border-slate-200 transition-colors"
          >
            <div className="w-8 h-8 rounded-lg bg-navy-900 text-white flex items-center justify-center font-bold text-xs uppercase shadow-xs">
              {user ? user.name.charAt(0) : 'A'}
            </div>
            <div className="hidden md:block text-left">
              <p className="text-xs font-semibold text-slate-800 leading-tight">{user ? user.name : 'Super Admin'}</p>
              <p className="text-[10px] font-medium text-slate-500">{user?.role || 'Platform Owner'}</p>
            </div>
            <ChevronDown className="w-3.5 h-3.5 text-slate-400" />
          </button>

          {profileDropdownOpen && (
            <div className="absolute right-0 mt-2 w-56 bg-white rounded-xl shadow-xl border border-slate-200 py-1.5 z-50 text-xs divide-y divide-slate-100 animate-in fade-in zoom-in-95 duration-100">
              <div className="px-4 py-2.5">
                <p className="font-semibold text-slate-900">{user?.name}</p>
                <p className="text-slate-500 truncate text-[11px] mt-0.5">{user?.email}</p>
                <div className="mt-2 inline-flex items-center space-x-1 text-[10px] font-semibold text-emerald-700 bg-emerald-50 px-2 py-0.5 rounded border border-emerald-200">
                  <CheckCircle2 className="w-3 h-3 text-emerald-600" />
                  <span>MFA Protected</span>
                </div>
              </div>

              <div className="py-1">
                <button
                  onClick={() => {
                    setProfileDropdownOpen(false);
                    onNavigateProfile();
                  }}
                  className="w-full text-left px-4 py-2 text-slate-700 hover:bg-slate-50 font-medium"
                >
                  My Account & Security
                </button>
              </div>

              <div className="py-1">
                <button
                  onClick={() => {
                    setProfileDropdownOpen(false);
                    onLogout();
                  }}
                  className="w-full text-left px-4 py-2 text-rose-600 hover:bg-rose-50 font-medium"
                >
                  Sign Out
                </button>
              </div>
            </div>
          )}
        </div>
      </div>
    </header>
  );
};
