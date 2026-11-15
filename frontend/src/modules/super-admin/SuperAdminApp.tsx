'use client';

import React, { useState, useEffect } from 'react';
import SuperAdminLayout from './layouts/SuperAdminLayout';
import { DashboardView } from './pages/DashboardView';
import { TenantsView } from './pages/TenantsView';
import { DomainsView } from './pages/DomainsView';
import { SubscriptionsView } from './pages/SubscriptionsView';
import { PaymentsView } from './pages/PaymentsView';
import { BrandingView } from './pages/BrandingView';
import OperationsView from './pages/OperationsView';
import InfrastructureView from './pages/InfrastructureView';
import SecurityView from './pages/SecurityView';
import BackupsView from './pages/BackupsView';
import ReportsView from './pages/ReportsView';
import SupportView from './pages/SupportView';
import DeveloperView from './pages/DeveloperView';
import SettingsView from './pages/SettingsView';
import MyAccountView from './pages/MyAccountView';

import { TenantWizardModal } from './components/TenantWizardModal';
import { TenantDetailsModal } from './components/TenantDetailsModal';
import { superAdminApi } from './services/api';
import { SuperAdminUser, SubscriptionPackage } from './types';
import { Shield, Lock, Newspaper, ArrowRight, AlertCircle, CheckCircle2 } from 'lucide-react';

export default function SuperAdminApp() {
  const [isAuthenticated, setIsAuthenticated] = useState<boolean>(false);
  const [user, setUser] = useState<SuperAdminUser | null>(null);
  const [currentTab, setCurrentTab] = useState<string>('dashboard');
  const [initialLoading, setInitialLoading] = useState<boolean>(true);

  // Tenant modals
  const [isWizardOpen, setIsWizardOpen] = useState(false);
  const [selectedTenantDetailsId, setSelectedTenantDetailsId] = useState<number | null>(null);
  const [packages, setPackages] = useState<SubscriptionPackage[]>([]);

  // Login form state
  const [email, setEmail] = useState('admin@dogyepaper.com');
  const [password, setPassword] = useState('SuperAdminPassword123!');
  const [twoFactorCode, setTwoFactorCode] = useState('');
  const [loginError, setLoginError] = useState<string | null>(null);
  const [loggingIn, setLoggingIn] = useState(false);

  useEffect(() => {
    const token = typeof window !== 'undefined' ? localStorage.getItem('super_admin_token') : null;
    if (token) {
      setIsAuthenticated(true);
      superAdminApi.auth
        .getMe()
        .then((userData: any) => setUser(userData))
        .catch(() => {
          localStorage.removeItem('super_admin_token');
          setIsAuthenticated(false);
        })
        .finally(() => setInitialLoading(false));
    } else {
      setInitialLoading(false);
    }
  }, []);

  useEffect(() => {
    if (isAuthenticated) {
      superAdminApi.subscriptions
        .getPackages()
        .then((res: any) => setPackages(res.data || res || []))
        .catch(() => {});
    }
  }, [isAuthenticated]);

  const handleLogin = async (e: React.FormEvent) => {
    e.preventDefault();
    setLoginError(null);
    setLoggingIn(true);
    try {
      const res = await superAdminApi.auth.login(email, password, twoFactorCode);
      if (res.token) {
        localStorage.setItem('super_admin_token', res.token);
        setUser(res.user);
        setIsAuthenticated(true);
      }
    } catch (err: any) {
      setLoginError(err.response?.data?.message || 'Authentication failed. Please verify credentials.');
    } finally {
      setLoggingIn(false);
    }
  };

  const handleLogout = async () => {
    try {
      await superAdminApi.auth.logout();
    } catch (err) {}
    localStorage.removeItem('super_admin_token');
    setIsAuthenticated(false);
    setUser(null);
  };

  const handleCreateTenantSubmit = async (formData: any) => {
    const created = await superAdminApi.tenants.create(formData);
    setIsWizardOpen(false);
    setCurrentTab('tenants');
    return created;
  };

  if (initialLoading) {
    return (
      <div className="min-h-screen bg-[#F8FAFC] flex items-center justify-center">
        <div className="flex flex-col items-center gap-3">
          <div className="w-8 h-8 border-4 border-blue-600 border-t-transparent rounded-full animate-spin"></div>
          <p className="text-xs font-semibold text-slate-500 font-mono tracking-wider">
            INITIALIZING DOGY EPAPER CONTROL CENTER...
          </p>
        </div>
      </div>
    );
  }

  // Login Screen if not authenticated
  if (!isAuthenticated) {
    return (
      <div className="min-h-screen bg-[#F8FAFC] flex flex-col justify-center py-12 sm:px-6 lg:px-8">
        <div className="sm:mx-auto sm:w-full sm:max-w-md">
          {/* Logo & Header */}
          <div className="flex justify-center items-center gap-3 mb-2">
            <div className="w-12 h-12 rounded-xl bg-blue-600 flex items-center justify-center text-white shadow-lg shadow-blue-500/20">
              <Newspaper className="w-6 h-6" />
            </div>
          </div>
          <h1 className="text-center text-2xl font-black text-navy-900 tracking-tight">
            DOGY ePAPER SaaS
          </h1>
          <p className="text-center text-xs font-semibold text-blue-600 uppercase tracking-widest mt-1">
            Enterprise Super Admin Control Center
          </p>
          <p className="text-center text-xs text-slate-500 mt-2">
            Targeting up to 10,000 newspaper publishing tenants across AWS
          </p>
        </div>

        <div className="mt-8 sm:mx-auto sm:w-full sm:max-w-md">
          <div className="bg-white py-8 px-6 shadow-xl border border-slate-200/80 rounded-2xl sm:px-10">
            <form onSubmit={handleLogin} className="space-y-4">
              {loginError && (
                <div className="p-3 bg-rose-50 border border-rose-200 rounded-xl text-rose-700 text-xs flex items-center gap-2">
                  <AlertCircle className="w-4 h-4 shrink-0 text-rose-600" />
                  <span>{loginError}</span>
                </div>
              )}

              <div>
                <label className="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">
                  Master Email Address
                </label>
                <input
                  type="email"
                  required
                  value={email}
                  onChange={(e) => setEmail(e.target.value)}
                  className="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:bg-white transition-all text-slate-900 font-medium"
                  placeholder="admin@dogyepaper.com"
                />
              </div>

              <div>
                <label className="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">
                  Super Admin Password
                </label>
                <input
                  type="password"
                  required
                  value={password}
                  onChange={(e) => setPassword(e.target.value)}
                  className="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:bg-white transition-all text-slate-900 font-medium"
                />
              </div>

              <div>
                <label className="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">
                  2FA Security Code <span className="text-slate-400 font-normal lowercase">(optional for demo)</span>
                </label>
                <input
                  type="text"
                  maxLength={6}
                  value={twoFactorCode}
                  onChange={(e) => setTwoFactorCode(e.target.value)}
                  placeholder="e.g. 123456"
                  className="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm font-mono tracking-widest focus:outline-none focus:ring-2 focus:ring-blue-500 focus:bg-white transition-all text-slate-900"
                />
              </div>

              <div className="pt-2">
                <button
                  type="submit"
                  disabled={loggingIn}
                  className="w-full flex items-center justify-center gap-2 py-3 px-4 rounded-xl text-sm font-bold text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 shadow-md shadow-blue-500/25 transition-all disabled:opacity-50"
                >
                  <Lock className="w-4 h-4" />
                  {loggingIn ? 'Authenticating...' : 'Sign In to Control Center'}
                  <ArrowRight className="w-4 h-4 ml-1" />
                </button>
              </div>
            </form>

            <div className="mt-6 pt-4 border-t border-slate-100 text-center">
              <div className="flex items-center justify-center gap-1.5 text-xs text-slate-500">
                <Shield className="w-3.5 h-3.5 text-emerald-600" />
                <span>Protected by AWS WAF & Hardware Token 2FA</span>
              </div>
            </div>
          </div>
        </div>
      </div>
    );
  }

  // Authenticated Super Admin Dashboard Shell
  return (
    <SuperAdminLayout
      currentTab={currentTab}
      onTabChange={(tabId) => setCurrentTab(tabId)}
      user={user}
      onLogout={handleLogout}
    >
      {/* Dynamic View rendering based on active sidebar tab */}
      {currentTab === 'dashboard' && (
        <DashboardView
          onNavigate={(tab) => setCurrentTab(tab)}
          onOpenCreateTenant={() => setIsWizardOpen(true)}
        />
      )}

      {currentTab === 'tenants' && (
        <TenantsView
          onOpenCreateWizard={() => setIsWizardOpen(true)}
          onOpenDetails={(id) => setSelectedTenantDetailsId(id)}
        />
      )}

      {currentTab === 'domains' && <DomainsView />}

      {currentTab === 'subscriptions' && <SubscriptionsView />}

      {currentTab === 'payments' && <PaymentsView />}

      {currentTab === 'branding' && <BrandingView />}

      {currentTab === 'operations' && <OperationsView />}

      {currentTab === 'infrastructure' && <InfrastructureView />}

      {currentTab === 'security' && <SecurityView />}

      {currentTab === 'backups' && <BackupsView />}

      {currentTab === 'reports' && <ReportsView />}

      {currentTab === 'support' && <SupportView />}

      {currentTab === 'developer' && <DeveloperView />}

      {currentTab === 'settings' && <SettingsView />}

      {currentTab === 'profile' && <MyAccountView />}

      {/* Persistent Global Modals */}
      <TenantWizardModal
        isOpen={isWizardOpen}
        onClose={() => setIsWizardOpen(false)}
        packages={packages}
        onSubmit={handleCreateTenantSubmit}
      />

      <TenantDetailsModal
        tenantId={selectedTenantDetailsId}
        isOpen={selectedTenantDetailsId !== null}
        onClose={() => setSelectedTenantDetailsId(null)}
      />
    </SuperAdminLayout>
  );
}
