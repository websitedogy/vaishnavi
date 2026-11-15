import React, { useState } from 'react';
import { TenantSidebar, TENANT_NAV_ITEMS } from './TenantSidebar';
import { TenantNavbar } from './TenantNavbar';
import { TenantStaff, TenantInfo } from '../types';
import { ChevronRight, Home, Newspaper } from 'lucide-react';

export interface TenantAdminLayoutProps {
  currentTab?: string;
  activeModule?: string;
  onTabChange?: (tabId: string) => void;
  onNavigate?: (mod: string) => void;
  user?: TenantStaff | null;
  staffUser?: TenantStaff | null;
  tenant?: TenantInfo | null;
  newspaperName?: string;
  unreadNotifications?: number;
  unreadNotificationsCount?: number;
  onLogout: () => void;
  children: React.ReactNode;
}

export const TenantAdminLayout: React.FC<TenantAdminLayoutProps> = ({
  currentTab,
  activeModule,
  onTabChange,
  onNavigate,
  user,
  staffUser,
  tenant,
  newspaperName,
  unreadNotifications,
  unreadNotificationsCount,
  onLogout,
  children,
}) => {
  const [isCollapsed, setIsCollapsed] = useState(false);

  const activeTab = activeModule || currentTab || 'dashboard';
  const handleTabChange = onNavigate || onTabChange || (() => {});
  const activeUser = staffUser !== undefined ? staffUser : (user || null);
  const activeUnread = unreadNotificationsCount !== undefined ? unreadNotificationsCount : (unreadNotifications || 0);
  const activeTenant: TenantInfo = tenant || {
    id: 1,
    name: newspaperName || 'Telangana Samacharam',
    slug: 'samacharam',
    status: 'active',
  };

  const activeNav = TENANT_NAV_ITEMS.find((n) => n.id === activeTab);

  return (
    <div className="flex h-screen bg-[#F8FAFC] text-slate-800 overflow-hidden font-sans">
      {/* Sidebar */}
      <TenantSidebar
        currentTab={activeTab}
        onTabChange={handleTabChange}
        isCollapsed={isCollapsed}
        onToggleCollapse={() => setIsCollapsed(!isCollapsed)}
        onLogout={onLogout}
        newspaperName={newspaperName || activeTenant?.name}
        unreadNotifications={activeUnread}
      />

      {/* Main Viewport */}
      <div className="flex-1 flex flex-col min-w-0 overflow-hidden">
        {/* Top Navbar */}
        <TenantNavbar
          user={activeUser}
          tenant={activeTenant}
          unreadCount={activeUnread}
          onOpenNotifications={() => handleTabChange('notifications')}
          onNavigateTab={handleTabChange}
          onLogout={onLogout}
        />

        {/* Breadcrumb Navigation Bar */}
        <div className="bg-white/70 border-b border-slate-200/80 px-6 py-2 flex items-center justify-between text-xs">
          <div className="flex items-center space-x-1.5 text-slate-500">
            <span
              className="flex items-center gap-1 hover:text-navy-900 cursor-pointer"
              onClick={() => handleTabChange('dashboard')}
            >
              <Home className="w-3.5 h-3.5" />
              <span>Workspace</span>
            </span>
            <ChevronRight className="w-3.5 h-3.5 text-slate-300" />
            <span className="font-semibold text-navy-900">{activeNav?.name || 'Dashboard'}</span>
          </div>

          <div className="hidden md:flex items-center space-x-2 text-[11px] text-slate-400 font-mono">
            <span>DOGY ePAPER • Publisher Console</span>
            <span>•</span>
            <span className="text-emerald-600 font-bold uppercase tracking-wider">
              {newspaperName || activeTenant?.name}
            </span>
          </div>
        </div>

        {/* Scrollable Page Body */}
        <main className="flex-1 overflow-y-auto p-6 md:p-8">
          <div className="max-w-7xl mx-auto space-y-6">{children}</div>
        </main>
      </div>
    </div>
  );
};
