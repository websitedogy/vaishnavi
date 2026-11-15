import React from 'react';
import {
  LayoutDashboard,
  Building2,
  Globe,
  CreditCard,
  DollarSign,
  Palette,
  Newspaper,
  Server,
  ShieldCheck,
  Database,
  BarChart3,
  Headphones,
  Code2,
  Sliders,
  UserCircle,
  ChevronLeft,
  ChevronRight,
  LogOut,
} from 'lucide-react';

export interface NavItem {
  id: string;
  name: string;
  icon: React.ComponentType<{ className?: string }>;
  badge?: number | string;
}

export const NAV_ITEMS: NavItem[] = [
  { id: 'dashboard', name: 'Dashboard', icon: LayoutDashboard },
  { id: 'tenants', name: 'Tenant Management', icon: Building2 },
  { id: 'domains', name: 'Domain & SSL Management', icon: Globe },
  { id: 'subscriptions', name: 'Subscription & Packages', icon: CreditCard },
  { id: 'payments', name: 'Payments & Revenue', icon: DollarSign },
  { id: 'branding', name: 'White Label & Branding', icon: Palette },
  { id: 'operations', name: 'Newspaper Operations', icon: Newspaper },
  { id: 'infrastructure', name: 'Infrastructure & Cloud', icon: Server },
  { id: 'security', name: 'Security & Access Management', icon: ShieldCheck },
  { id: 'backups', name: 'Backup & Disaster Recovery', icon: Database },
  { id: 'reports', name: 'Reports & Analytics', icon: BarChart3 },
  { id: 'support', name: 'Support & Communication', icon: Headphones },
  { id: 'developer', name: 'Developer & Integrations', icon: Code2 },
  { id: 'settings', name: 'Platform Settings', icon: Sliders },
  { id: 'profile', name: 'My Account', icon: UserCircle },
];

interface SidebarProps {
  currentTab: string;
  onTabChange: (tabId: string) => void;
  isCollapsed: boolean;
  onToggleCollapse: () => void;
  onLogout: () => void;
}

export const Sidebar: React.FC<SidebarProps> = ({
  currentTab,
  onTabChange,
  isCollapsed,
  onToggleCollapse,
  onLogout,
}) => {
  return (
    <aside
      className={`bg-navy-900 text-slate-300 border-r border-slate-800 transition-all duration-200 flex flex-col z-30 select-none ${
        isCollapsed ? 'w-20' : 'w-64'
      }`}
    >
      {/* Brand Header */}
      <div className="h-16 flex items-center justify-between px-4 border-b border-slate-800">
        {!isCollapsed && (
          <div className="flex items-center space-x-2.5">
            <div className="w-8 h-8 rounded-lg bg-blue-600 flex items-center justify-center text-white font-black tracking-wider text-sm shadow-md shadow-blue-500/20">
              D
            </div>
            <div>
              <h1 className="text-sm font-bold text-white tracking-wide leading-tight">DOGY ePAPER</h1>
              <p className="text-[10px] font-semibold text-sky-400 uppercase tracking-widest">SUPER ADMIN</p>
            </div>
          </div>
        )}
        {isCollapsed && (
          <div className="mx-auto w-8 h-8 rounded-lg bg-blue-600 flex items-center justify-center text-white font-black text-sm">
            D
          </div>
        )}

        <button
          onClick={onToggleCollapse}
          className="text-slate-400 hover:text-white p-1 rounded-md hover:bg-slate-800/80 transition-colors"
          title={isCollapsed ? 'Expand sidebar' : 'Collapse sidebar'}
        >
          {isCollapsed ? <ChevronRight className="w-4 h-4" /> : <ChevronLeft className="w-4 h-4" />}
        </button>
      </div>

      {/* Nav List */}
      <nav className="flex-1 overflow-y-auto px-3 py-4 space-y-1 custom-scrollbar">
        {NAV_ITEMS.map((item) => {
          const Icon = item.icon;
          const isActive = currentTab === item.id;

          return (
            <button
              key={item.id}
              onClick={() => onTabChange(item.id)}
              className={`w-full flex items-center rounded-xl px-3 py-2.5 text-xs font-medium transition-all group ${
                isActive
                  ? 'bg-blue-600 text-white shadow-sm shadow-blue-600/30'
                  : 'text-slate-400 hover:text-white hover:bg-slate-800/60'
              }`}
              title={isCollapsed ? item.name : undefined}
            >
              <Icon className={`w-5 h-5 flex-shrink-0 transition-transform ${isCollapsed ? 'mx-auto' : 'mr-3'} ${isActive ? 'text-white' : 'text-slate-400 group-hover:text-white'}`} />
              
              {!isCollapsed && (
                <span className="truncate flex-1 text-left">{item.name}</span>
              )}

              {!isCollapsed && item.badge && (
                <span className="ml-auto text-[10px] px-1.5 py-0.5 rounded-full font-bold bg-blue-800/60 text-blue-200">
                  {item.badge}
                </span>
              )}
            </button>
          );
        })}
      </nav>

      {/* Footer / Logout */}
      <div className="p-3 border-t border-slate-800 space-y-1">
        <a
          href="/tenant-admin"
          className={`w-full flex items-center px-3 py-2 text-xs font-semibold text-sky-400 hover:text-white hover:bg-slate-800/60 rounded-xl transition-colors ${
            isCollapsed ? 'justify-center' : 'space-x-3'
          }`}
          title="Switch to Tenant Admin Portal"
        >
          <Newspaper className="w-5 h-5 flex-shrink-0 text-sky-400" />
          {!isCollapsed && <span>Tenant Admin &rarr;</span>}
        </a>

        <button
          onClick={onLogout}
          className={`w-full flex items-center px-3 py-2.5 text-xs font-medium text-rose-400 hover:text-rose-300 hover:bg-rose-950/40 rounded-xl transition-colors ${
            isCollapsed ? 'justify-center' : 'space-x-3'
          }`}
          title="Sign out"
        >
          <LogOut className="w-5 h-5 flex-shrink-0" />
          {!isCollapsed && <span>Sign Out</span>}
        </button>
      </div>
    </aside>
  );
};
