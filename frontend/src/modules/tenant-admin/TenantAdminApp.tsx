'use client';

import React, { useState, useEffect } from 'react';
import {
  Newspaper,
  Lock,
  Mail,
  AlertCircle,
  ArrowRight,
  Shield,
  Sparkles,
  ExternalLink,
} from 'lucide-react';
import { authApi, newspaperProfileApi, notificationsApi, editionsApi } from './services/api';
import { TenantStaff, NewspaperProfile, Edition } from './types';
import { TenantAdminLayout } from './layouts/TenantAdminLayout';
import { CreateEditionModal } from './components/CreateEditionModal';

// 16 Views
import { DashboardView } from './pages/DashboardView';
import { NewspaperProfileView } from './pages/NewspaperProfileView';
import { EditionsView } from './pages/EditionsView';
import { PdfUploadView } from './pages/PdfUploadView';
import { PageManagementView } from './pages/PageManagementView';
import { PublishingView } from './pages/PublishingView';
import { MediaLibraryView } from './pages/MediaLibraryView';
import { BrandingView } from './pages/BrandingView';
import { TemplatesView } from './pages/TemplatesView';
import { StaffView } from './pages/StaffView';
import { ReportsView } from './pages/ReportsView';
import { SubscriptionUsageView } from './pages/SubscriptionUsageView';
import { NotificationsView } from './pages/NotificationsView';
import { SupportView } from './pages/SupportView';
import { SettingsView } from './pages/SettingsView';
import { MyAccountView } from './pages/MyAccountView';

export const TenantAdminApp: React.FC = () => {
  const [token, setToken] = useState<string | null>(null);
  const [staff, setStaff] = useState<TenantStaff | null>(null);
  const [profile, setProfile] = useState<NewspaperProfile | null>(null);
  const [unreadCount, setUnreadCount] = useState<number>(0);
  const [activeModule, setActiveModule] = useState<string>('dashboard');
  const [pageManagementEditionId, setPageManagementEditionId] = useState<number | undefined>();
  const [pdfUploadEditionId, setPdfUploadEditionId] = useState<number | undefined>();

  // Global Create Edition Modal
  const [isCreateEditionOpen, setIsCreateEditionOpen] = useState(false);

  // Login form state
  const [loginEmail, setLoginEmail] = useState('owner@samacharam.com');
  const [loginPassword, setLoginPassword] = useState('TenantPassword123!');
  const [loginLoading, setLoginLoading] = useState(false);
  const [loginError, setLoginError] = useState<string | null>(null);

  // Initialize auth token from storage
  useEffect(() => {
    const savedToken = localStorage.getItem('tenant_token');
    if (savedToken) {
      setToken(savedToken);
    }
  }, []);

  // Fetch session profile when token is set
  useEffect(() => {
    if (!token) return;

    const initWorkspace = async () => {
      try {
        const [meRes, profRes, notifRes] = await Promise.all([
          authApi.me(),
          newspaperProfileApi.get().catch(() => ({ data: null })),
          notificationsApi.getAll().catch(() => ({ data: [] })),
        ]);
        setStaff(meRes.data?.user || meRes.data);
        if (profRes.data) setProfile(profRes.data);
        const notifs = notifRes.data?.data || notifRes.data || [];
        setUnreadCount(notifs.filter((n: any) => !n.is_read).length);
      } catch (err: any) {
        console.error('Session expired or invalid:', err);
        handleLogout();
      }
    };

    initWorkspace();
  }, [token]);

  const handleLogin = async (e: React.FormEvent) => {
    e.preventDefault();
    setLoginLoading(true);
    setLoginError(null);
    try {
      const res = await authApi.login({
        email: loginEmail,
        password: loginPassword,
      });
      const receivedToken = res.data?.token || res.data?.access_token;
      if (receivedToken) {
        localStorage.setItem('tenant_token', receivedToken);
        setToken(receivedToken);
        setStaff(res.data?.user);
      }
    } catch (err: any) {
      setLoginError(err?.response?.data?.message || 'Invalid tenant email or password.');
    } finally {
      setLoginLoading(false);
    }
  };

  const handleLogout = () => {
    localStorage.removeItem('tenant_token');
    setToken(null);
    setStaff(null);
    setProfile(null);
  };

  const handleQuickLogin = (email: string) => {
    setLoginEmail(email);
    setLoginPassword('TenantPassword123!');
  };

  const handleCreateEditionSubmit = async (data: Partial<Edition>) => {
    const res = await editionsApi.create(data);
    const created = res.data;
    if (created && created.id) {
      setPageManagementEditionId(created.id);
      setActiveModule('pages');
    }
  };

  // If not authenticated, show professional Login Screen
  if (!token) {
    return (
      <div className="min-h-screen bg-slate-950 flex flex-col justify-center py-12 sm:px-6 lg:px-8 relative overflow-hidden font-sans">
        {/* Background Gradients */}
        <div className="absolute top-0 left-1/2 -translate-x-1/2 w-[800px] h-[400px] bg-primary-600/15 blur-[120px] rounded-full pointer-events-none" />
        <div className="absolute bottom-0 right-10 w-[400px] h-[300px] bg-sky-500/10 blur-[100px] rounded-full pointer-events-none" />

        <div className="sm:mx-auto sm:w-full sm:max-w-md relative z-10 text-center">
          <div className="inline-flex items-center justify-center w-14 h-14 rounded-2xl bg-gradient-to-tr from-primary-600 to-sky-500 text-white shadow-xl shadow-primary-500/25 mb-4 border border-white/20">
            <Newspaper className="w-7 h-7" />
          </div>
          <h2 className="text-2xl font-black tracking-tight text-white">
            DOGY ePAPER SaaS
          </h2>
          <p className="text-sm font-semibold text-primary-400 mt-1 uppercase tracking-wider">
            Tenant Admin Control Center
          </p>
          <p className="text-xs text-slate-400 mt-2">
            Secure editorial workspace for digital newspaper publishers
          </p>
        </div>

        <div className="mt-8 sm:mx-auto sm:w-full sm:max-w-md relative z-10">
          <div className="bg-slate-900/90 backdrop-blur-xl py-8 px-6 sm:px-10 rounded-3xl border border-slate-800 shadow-2xl">
            {loginError && (
              <div className="mb-6 p-3.5 bg-rose-500/10 border border-rose-500/20 text-rose-300 text-xs font-medium rounded-xl flex items-center gap-2">
                <AlertCircle className="w-4 h-4 text-rose-400 flex-shrink-0" />
                {loginError}
              </div>
            )}

            <form onSubmit={handleLogin} className="space-y-4">
              <div>
                <label className="block text-xs font-bold text-slate-300 uppercase tracking-wider mb-1.5">
                  Tenant Staff Email
                </label>
                <div className="relative">
                  <input
                    type="email"
                    required
                    value={loginEmail}
                    onChange={(e) => setLoginEmail(e.target.value)}
                    placeholder="editor@newspaper.com"
                    className="w-full pl-9 pr-3.5 py-2.5 bg-slate-800/80 border border-slate-700 rounded-xl text-sm text-white placeholder-slate-500 focus:outline-hidden focus:ring-2 focus:ring-primary-500 focus:border-transparent transition"
                  />
                  <Mail className="w-4 h-4 text-slate-400 absolute left-3 top-3" />
                </div>
              </div>

              <div>
                <label className="block text-xs font-bold text-slate-300 uppercase tracking-wider mb-1.5">
                  Password
                </label>
                <div className="relative">
                  <input
                    type="password"
                    required
                    value={loginPassword}
                    onChange={(e) => setLoginPassword(e.target.value)}
                    placeholder="••••••••••••"
                    className="w-full pl-9 pr-3.5 py-2.5 bg-slate-800/80 border border-slate-700 rounded-xl text-sm text-white placeholder-slate-500 focus:outline-hidden focus:ring-2 focus:ring-primary-500 focus:border-transparent transition"
                  />
                  <Lock className="w-4 h-4 text-slate-400 absolute left-3 top-3" />
                </div>
              </div>

              <div className="pt-2">
                <button
                  type="submit"
                  disabled={loginLoading}
                  className="w-full py-3 px-4 bg-gradient-to-r from-primary-600 to-primary-700 hover:from-primary-500 hover:to-primary-600 text-white rounded-xl text-xs font-bold uppercase tracking-wider shadow-lg shadow-primary-600/30 transition flex items-center justify-center gap-2 disabled:opacity-50"
                >
                  {loginLoading ? (
                    <div className="w-4 h-4 border-2 border-white/30 border-t-white rounded-full animate-spin" />
                  ) : (
                    <>
                      Enter Workspace
                      <ArrowRight className="w-4 h-4" />
                    </>
                  )}
                </button>
              </div>
            </form>

            {/* Demo Quick Fills - Development Only */}
            {process.env.NODE_ENV !== 'production' && (
              <div className="mt-6 pt-6 border-t border-slate-800 space-y-3">
                <span className="text-[11px] font-bold text-slate-400 uppercase tracking-wider block text-center">
                  Instant Demo Credentials (Development Only)
                </span>
                <div className="grid grid-cols-2 gap-2">
                  <button
                    type="button"
                    onClick={() => handleQuickLogin('owner@samacharam.com')}
                    className="p-2.5 rounded-xl bg-slate-800/60 hover:bg-slate-800 text-left border border-slate-700/60 transition group"
                  >
                    <div className="text-[11px] font-bold text-white group-hover:text-primary-400">
                      Tenant 1 (Samacharam)
                    </div>
                    <div className="text-[10px] text-slate-400">Owner Account</div>
                  </button>

                  <button
                    type="button"
                    onClick={() => handleQuickLogin('admin@andhraprabha.com')}
                    className="p-2.5 rounded-xl bg-slate-800/60 hover:bg-slate-800 text-left border border-slate-700/60 transition group"
                  >
                    <div className="text-[11px] font-bold text-white group-hover:text-primary-400">
                      Tenant 2 (Andhra Prabha)
                    </div>
                    <div className="text-[10px] text-slate-400">Admin Account</div>
                  </button>
                </div>
              </div>
            )}

            {/* Portal Switch Link */}
            <div className="mt-4 pt-4 text-center">
              <a
                href="/"
                className="text-xs text-slate-400 hover:text-white inline-flex items-center gap-1 transition"
              >
                Switch to Super Admin Portal &rarr;
              </a>
            </div>
          </div>
        </div>
      </div>
    );
  }

  // Render Module Content
  const renderModuleContent = () => {
    switch (activeModule) {
      case 'dashboard':
        return (
          <DashboardView
            onNavigate={(mod) => setActiveModule(mod)}
            onOpenCreateEdition={() => setIsCreateEditionOpen(true)}
          />
        );
      case 'profile':
        return <NewspaperProfileView />;
      case 'editions':
        return (
          <EditionsView
            onNavigateToPages={(edId) => {
              setPageManagementEditionId(edId);
              setActiveModule('pages');
            }}
            onNavigateToUpload={(edId) => {
              setPdfUploadEditionId(edId);
              setActiveModule('pdf-upload');
            }}
          />
        );
      case 'pdf-upload':
        return (
          <PdfUploadView
            onNavigateToPages={(edId) => {
              setPageManagementEditionId(edId);
              setActiveModule('pages');
            }}
            targetEditionId={pdfUploadEditionId}
          />
        );
      case 'pages':
        return <PageManagementView initialEditionId={pageManagementEditionId} />;
      case 'publishing':
        return <PublishingView />;
      case 'media':
        return <MediaLibraryView />;
      case 'branding':
        return <BrandingView />;
      case 'templates':
        return (
          <TemplatesView
            onNavigateToBranding={() => setActiveModule('branding')}
          />
        );
      case 'staff':
        return <StaffView />;
      case 'reports':
        return <ReportsView />;
      case 'subscription':
        return <SubscriptionUsageView />;
      case 'notifications':
        return <NotificationsView />;
      case 'support':
        return <SupportView />;
      case 'settings':
        return <SettingsView />;
      case 'account':
        return <MyAccountView onLogout={handleLogout} />;
      default:
        return (
          <DashboardView
            onNavigate={(mod) => setActiveModule(mod)}
            onOpenCreateEdition={() => setIsCreateEditionOpen(true)}
          />
        );
    }
  };

  return (
    <TenantAdminLayout
      activeModule={activeModule}
      onNavigate={(mod) => setActiveModule(mod)}
      newspaperName={profile?.name || 'Telangana Samacharam'}
      staffUser={staff}
      unreadNotificationsCount={unreadCount}
      onLogout={handleLogout}
    >
      {renderModuleContent()}

      {/* Global Quick Create Edition Modal */}
      <CreateEditionModal
        isOpen={isCreateEditionOpen}
        onClose={() => setIsCreateEditionOpen(false)}
        onSubmit={handleCreateEditionSubmit}
      />
    </TenantAdminLayout>
  );
};
