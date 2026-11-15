import React from 'react';
import {
  LayoutDashboard,
  Newspaper,
  Layers,
  UploadCloud,
  FileText,
  Calendar,
  Image,
  Palette,
  LayoutTemplate,
  Users,
  BarChart3,
  CreditCard,
  Bell,
  LifeBuoy,
  Settings,
  UserCircle,
  ChevronLeft,
  ChevronRight,
  LogOut,
} from 'lucide-react';

export interface TenantNavItem {
  id: string;
  name: string;
  icon: React.ComponentType<{ className?: string }>;
  badge?: number | string;
}

export const TENANT_NAV_ITEMS: TenantNavItem[] = [
  { id: 'dashboard', name: 'Dashboard', icon: LayoutDashboard },
  { id: 'profile', name: 'Newspaper Profile', icon: Newspaper },
  { id: 'editions', name: 'Editions Management', icon: Layers },
  { id: 'upload', name: 'PDF Upload & Queue', icon: UploadCloud },
  { id: 'pages', name: 'Page Management', icon: FileText },
  { id: 'publishing', name: 'Publishing & Calendar', icon: Calendar },
  { id: 'media', name: 'Media Library', icon: Image },
  { id: 'branding', name: 'Branding & Theme', icon: Palette },
  { id: 'templates', name: 'Template Management', icon: LayoutTemplate },
  { id: 'staff', name: 'Staff & Roles', icon: Users },
  { id: 'reports', name: 'Reports & Analytics', icon: BarChart3 },
  { id: 'subscription', name: 'Subscription & Quotas', icon: CreditCard },
  { id: 'notifications', name: 'Notifications', icon: Bell },
  { id: 'support', name: 'Support & Helpdesk', icon: LifeBuoy },
  { id: 'settings', name: 'Publishing Settings', icon: Settings },
  { id: 'account', name: 'My Account', icon: UserCircle },
];

interface TenantSidebarProps {
  currentTab: string;
  onTabChange: (tabId: string) => void;
  isCollapsed: boolean;
  onToggleCollapse: () => void;
  onLogout: () => void;
  newspaperName?: string;
  unreadNotifications?: number;
}

export const TenantSidebar: React.FC<TenantSidebarProps> = ({
  currentTab,
  onTabChange,
  isCollapsed,
  onToggleCollapse,
  onLogout,
  newspaperName = 'Newspaper Publisher',
  unreadNotifications = 0,
}) => {
  return (
    <aside
      className={`h-screen bg-[#0F172A] text-slate-300 flex flex-col transition-all duration-300 ease-in-out border-r border-slate-800 shrink-0 z-30 ${
        isCollapsed ? 'w-20' : 'w-64'
      }`}
    >
      {/* Brand Header */}
      <div className="h-16 px-4 flex items-center justify-between border-b border-slate-800/80">
        {!isCollapsed && (
          <div className="flex items-center gap-2.5 overflow-hidden">
            <div className="w-8 h-8 rounded-lg bg-blue-600 text-white flex items-center justify-center font-bold text-sm shadow-sm shrink-0">
              <Newspaper className="w-4 h-4" />
            </div>
            <div className="min-w-0">
              <span className="font-bold text-white tracking-tight text-sm block truncate">
                {newspaperName}
              </span>
              <span className="text-[10px] text-blue-400 font-semibold uppercase tracking-wider block">
                Publisher Workspace
              </span>
            </div>
          </div>
        )}

        {isCollapsed && (
          <div className="w-full flex justify-center">
            <div className="w-8 h-8 rounded-lg bg-blue-600 text-white flex items-center justify-center font-bold text-sm shadow-sm">
              <Newspaper className="w-4 h-4" />
            </div>
          </div>
        )}

        <button
          onClick={onToggleCollapse}
          className="p-1.5 rounded-lg text-slate-400 hover:text-white hover:bg-slate-800 transition-colors"
          title={isCollapsed ? 'Expand Sidebar' : 'Collapse Sidebar'}
        >
          {isCollapsed ? <ChevronRight className="w-4 h-4" /> : <ChevronLeft className="w-4 h-4" />}
        </button>
      </div>

      {/* Navigation List */}
      <nav className="flex-1 overflow-y-auto px-3 py-4 space-y-1">
        {TENANT_NAV_ITEMS.map((item) => {
          const Icon = item.icon;
          const isActive = currentTab === item.id;
          const showBadge = item.id === 'notifications' && unreadNotifications > 0;

          return (
            <button
              key={item.id}
              onClick={() => onTabChange(item.id)}
              className={`w-full flex items-center gap-3 px-3 py-2.5 rounded-xl text-xs font-medium transition-all duration-150 group relative ${
                isActive
                  ? 'bg-blue-600 text-white shadow-sm shadow-blue-500/25 font-semibold'
                  : 'text-slate-400 hover:text-slate-100 hover:bg-slate-800/60'
              } ${isCollapsed ? 'justify-center' : ''}`}
              title={isCollapsed ? item.name : undefined}
            >
              <Icon
                className={`w-4 h-4 shrink-0 transition-colors ${
                  isActive ? 'text-white' : 'text-slate-400 group-hover:text-slate-200'
                }`}
              />

              {!isCollapsed && <span className="truncate text-left flex-1">{item.name}</span>}

              {!isCollapsed && showBadge && (
                <span className="px-1.5 py-0.5 rounded-full text-[10px] font-bold bg-rose-500 text-white shadow-xs">
                  {unreadNotifications}
                </span>
              )}

              {isCollapsed && showBadge && (
                <span className="absolute top-1 right-2 w-2 h-2 rounded-full bg-rose-500 animate-pulse" />
              )}
            </button>
          );
        })}
      </nav>

      {/* Sign Out Action */}
      <div className="p-3 border-t border-slate-800/80">
        <button
          onClick={onLogout}
          className={`w-full flex items-center gap-3 px-3 py-2 rounded-xl text-xs font-medium text-slate-400 hover:text-rose-400 hover:bg-rose-500/10 transition-colors ${
            isCollapsed ? 'justify-center' : ''
          }`}
          title="Sign Out"
        >
          <LogOut className="w-4 h-4 shrink-0" />
          {!isCollapsed && <span>Sign Out</span>}
        </button>
      </div>
    </aside>
  );
};
