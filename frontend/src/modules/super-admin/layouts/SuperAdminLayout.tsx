import React, { useState, useEffect } from 'react';
import { Sidebar, NAV_ITEMS } from '../components/Sidebar';
import { Topbar } from '../components/Topbar';
import { NotificationCenter } from '../components/NotificationCenter';
import GlobalSearch from '../components/GlobalSearch';
import { SuperAdminUser, InfrastructureAlert } from '../types';
import { superAdminApi } from '../services/api';
import { ChevronRight, Home } from 'lucide-react';

interface SuperAdminLayoutProps {
  currentTab: string;
  onTabChange: (tabId: string) => void;
  user: SuperAdminUser | null;
  onLogout: () => void;
  children: React.ReactNode;
}

export const SuperAdminLayout: React.FC<SuperAdminLayoutProps> = ({
  currentTab,
  onTabChange,
  user,
  onLogout,
  children,
}) => {
  const [isCollapsed, setIsCollapsed] = useState(false);
  const [notificationsOpen, setNotificationsOpen] = useState(false);
  const [searchOpen, setSearchOpen] = useState(false);
  const [alerts, setAlerts] = useState<InfrastructureAlert[]>([]);

  // Load live alerts for notification badge
  useEffect(() => {
    const fetchAlerts = async () => {
      try {
        const res = await superAdminApi.infrastructure.getAlerts({ status: 'active' });
        setAlerts(res.data || res || []);
      } catch (err) {
        // quiet fallback
      }
    };
    fetchAlerts();
    const interval = setInterval(fetchAlerts, 30000);
    return () => clearInterval(interval);
  }, []);

  const handleAcknowledgeAlert = async (id: number) => {
    try {
      await superAdminApi.infrastructure.acknowledgeAlert(id);
      setAlerts((prev) => prev.map((a) => (a.id === id ? { ...a, status: 'acknowledged' } : a)));
    } catch (err) {
      console.error(err);
    }
  };

  const handleResolveAlert = async (id: number) => {
    try {
      await superAdminApi.infrastructure.resolveAlert(id);
      setAlerts((prev) => prev.filter((a) => a.id !== id));
    } catch (err) {
      console.error(err);
    }
  };

  const currentNav = NAV_ITEMS.find((n) => n.id === currentTab);

  return (
    <div className="flex h-screen bg-[#F8FAFC] text-slate-800 overflow-hidden font-sans">
      {/* Sidebar */}
      <Sidebar
        currentTab={currentTab}
        onTabChange={onTabChange}
        isCollapsed={isCollapsed}
        onToggleCollapse={() => setIsCollapsed(!isCollapsed)}
        onLogout={onLogout}
      />

      {/* Main Content Column */}
      <div className="flex-1 flex flex-col min-w-0 overflow-hidden">
        {/* Topbar */}
        <Topbar
          user={user}
          alertCount={alerts.filter((a) => a.status === 'active').length}
          onOpenNotifications={() => setNotificationsOpen(true)}
          onOpenSearch={() => setSearchOpen(true)}
          onNavigateProfile={() => onTabChange('profile')}
          onLogout={onLogout}
        />

        {/* Breadcrumb Navigation Bar */}
        <div className="bg-white/60 border-b border-slate-200/80 px-6 py-2 flex items-center justify-between text-xs">
          <div className="flex items-center space-x-1.5 text-slate-500">
            <span className="flex items-center gap-1 hover:text-slate-800 cursor-pointer" onClick={() => onTabChange('dashboard')}>
              <Home className="w-3.5 h-3.5" />
              <span>Super Admin</span>
            </span>
            <ChevronRight className="w-3.5 h-3.5 text-slate-300" />
            <span className="font-semibold text-navy-900">{currentNav?.name || 'Dashboard'}</span>
          </div>
          <div className="hidden md:flex items-center space-x-2 text-[11px] text-slate-400 font-mono">
            <span>DOGY ePAPER SaaS Core</span>
            <span>•</span>
            <span>Enterprise Control Center v2.4</span>
          </div>
        </div>

        {/* Scrollable Page Body */}
        <main className="flex-1 overflow-y-auto p-6">
          <div className="max-w-7xl mx-auto">{children}</div>
        </main>
      </div>

      {/* Modals & Overlays */}
      <GlobalSearch
        isOpen={searchOpen}
        onClose={() => setSearchOpen(false)}
        onNavigate={(mod) => onTabChange(mod)}
      />

      <NotificationCenter
        isOpen={notificationsOpen}
        onClose={() => setNotificationsOpen(false)}
        alerts={alerts}
        onAcknowledgeAlert={handleAcknowledgeAlert}
        onResolveAlert={handleResolveAlert}
      />
    </div>
  );
};

export default SuperAdminLayout;
