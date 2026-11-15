import axios, { AxiosInstance, AxiosRequestConfig } from 'axios';
import {
  DashboardStats,
  Tenant,
  TenantDomain,
  SubscriptionPackage,
  TenantSubscription,
  SubscriptionInvoice,
  Payment,
  PlatformBranding,
  TenantBranding,
  NewspaperEdition,
  PdfProcessingJob,
  InfrastructureService,
  InfrastructureAlert,
  BackupRecord,
  SupportTicket,
  ApiKey,
  WebhookEndpoint,
  ReportJob,
  SuperAdminUser,
  SuperAdminRole,
  SuperAdminPermission,
  AuditLog,
} from '../types';

const API_BASE_URL = process.env.NEXT_PUBLIC_API_URL || 'http://127.0.0.1:8000/api/v1/super-admin';

export const apiClient: AxiosInstance = axios.create({
  baseURL: API_BASE_URL,
  headers: {
    'Content-Type': 'application/json',
    Accept: 'application/json',
  },
});

// Automatic Bearer Token Interceptor
apiClient.interceptors.request.use((config) => {
  if (typeof window !== 'undefined') {
    const token = localStorage.getItem('super_admin_token') || 'superadmin-master-session-token';
    if (token) {
      config.headers.Authorization = `Bearer ${token}`;
    }
  }
  return config;
});

export const api = {
  // Auth
  auth: {
    login: async (email: string, password: string, twoFactorCode?: string) => {
      const res = await apiClient.post('/auth/login', { email, password, two_factor_code: twoFactorCode });
      return res.data;
    },
    logout: async () => {
      const res = await apiClient.post('/auth/logout');
      return res.data;
    },
    me: async () => {
      const res = await apiClient.get('/auth/me');
      return res.data.data as SuperAdminUser;
    },
    getMe: async () => {
      const res = await apiClient.get('/auth/me');
      return res.data.data as SuperAdminUser;
    },
  },

  // 1. Dashboard
  dashboard: {
    getStats: async () => {
      const res = await apiClient.get('/dashboard/stats');
      return res.data.data as DashboardStats;
    },
  },

  // 2. Tenant Management
  tenants: {
    list: async (params?: Record<string, any>) => {
      const res = await apiClient.get('/tenants', { params });
      return res.data.data;
    },
    get: async (id: number) => {
      const res = await apiClient.get(`/tenants/${id}`);
      return res.data.data;
    },
    create: async (data: Record<string, any>) => {
      const res = await apiClient.post('/tenants', data);
      return res.data.data as Tenant;
    },
    update: async (id: number, data: Record<string, any>) => {
      const res = await apiClient.put(`/tenants/${id}`, data);
      return res.data.data as Tenant;
    },
    activate: async (id: number) => {
      const res = await apiClient.post(`/tenants/${id}/activate`);
      return res.data.data as Tenant;
    },
    suspend: async (id: number, reason: string) => {
      const res = await apiClient.post(`/tenants/${id}/suspend`, { reason });
      return res.data.data as Tenant;
    },
    reactivate: async (id: number) => {
      const res = await apiClient.post(`/tenants/${id}/reactivate`);
      return res.data.data as Tenant;
    },
    renew: async (id: number, months: number = 1) => {
      const res = await apiClient.post(`/tenants/${id}/renew`, { months });
      return res.data.data as Tenant;
    },
    archive: async (id: number) => {
      const res = await apiClient.post(`/tenants/${id}/archive`);
      return res.data.data as Tenant;
    },
    delete: async (id: number, confirmationCode: string) => {
      const res = await apiClient.delete(`/tenants/${id}`, { data: { confirmation_code: confirmationCode } });
      return res.data;
    },
  },

  // 3. Domain & SSL Management
  domains: {
    list: async (params?: Record<string, any>) => {
      const res = await apiClient.get('/domains', { params });
      return res.data.data;
    },
    get: async (id: number) => {
      const res = await apiClient.get(`/domains/${id}`);
      return res.data.data;
    },
    create: async (data: { tenant_id: number; host: string; type?: string; is_primary?: boolean }) => {
      const res = await apiClient.post('/domains', data);
      return res.data.data as TenantDomain;
    },
    verify: async (id: number, simulateSuccess?: boolean) => {
      const res = await apiClient.post(`/domains/${id}/verify${simulateSuccess ? '?simulate_success=1' : ''}`);
      return res.data.data;
    },
    provisionSsl: async (id: number) => {
      const res = await apiClient.post(`/domains/${id}/provision-ssl`);
      return res.data.data as TenantDomain;
    },
    delete: async (id: number) => {
      const res = await apiClient.delete(`/domains/${id}`);
      return res.data;
    },
  },

  // 4. Subscription & Packages
  subscriptions: {
    getPackages: async () => {
      const res = await apiClient.get('/subscriptions/packages');
      return res.data.data as SubscriptionPackage[];
    },
    createPackage: async (data: Record<string, any>) => {
      const res = await apiClient.post('/subscriptions/packages', data);
      return res.data.data as SubscriptionPackage;
    },
    updatePackage: async (id: number, data: Record<string, any>) => {
      const res = await apiClient.put(`/subscriptions/packages/${id}`, data);
      return res.data.data as SubscriptionPackage;
    },
    list: async (params?: Record<string, any>) => {
      const res = await apiClient.get('/subscriptions/list', { params });
      return res.data.data;
    },
    changePackage: async (tenantId: number, newPackageId: number) => {
      const res = await apiClient.post(`/subscriptions/tenant/${tenantId}/change-package`, { new_package_id: newPackageId });
      return res.data.data as TenantSubscription;
    },
    invoices: async (params?: Record<string, any>) => {
      const res = await apiClient.get('/subscriptions/invoices', { params });
      return res.data.data;
    },
  },

  // 5. Payments & Revenue
  payments: {
    list: async (params?: Record<string, any>) => {
      const res = await apiClient.get('/payments', { params });
      return res.data.data;
    },
    get: async (id: number) => {
      const res = await apiClient.get(`/payments/${id}`);
      return res.data.data;
    },
    refund: async (id: number, amount: number, reason: string) => {
      const res = await apiClient.post(`/payments/${id}/refund`, { amount, reason });
      return res.data.data;
    },
    reconciliation: async () => {
      const res = await apiClient.get('/payments/reconciliation');
      return res.data.data;
    },
    revenueSummary: async () => {
      const res = await apiClient.get('/payments/revenue-summary');
      return res.data.data;
    },
  },

  // 6. White Label & Branding
  branding: {
    getPlatform: async () => {
      const res = await apiClient.get('/branding/platform');
      return res.data.data as PlatformBranding;
    },
    updatePlatform: async (data: Partial<PlatformBranding>) => {
      const res = await apiClient.put('/branding/platform', data);
      return res.data.data as PlatformBranding;
    },
    getTenant: async (tenantId: number) => {
      const res = await apiClient.get(`/branding/tenant/${tenantId}`);
      return res.data.data;
    },
    updateTenant: async (tenantId: number, data: Partial<TenantBranding>) => {
      const res = await apiClient.put(`/branding/tenant/${tenantId}`, data);
      return res.data.data as TenantBranding;
    },
    rollbackTenant: async (tenantId: number, version: number) => {
      const res = await apiClient.post(`/branding/tenant/${tenantId}/rollback/${version}`);
      return res.data.data as TenantBranding;
    },
  },

  // 7. Newspaper Operations
  operations: {
    getStats: async () => {
      const res = await apiClient.get('/operations/stats');
      return res.data.data;
    },
    getOverview: async () => {
      const res = await apiClient.get('/operations/stats');
      return res.data.data;
    },
    getEditions: async (params?: Record<string, any>) => {
      const res = await apiClient.get('/operations/editions', { params });
      return res.data.data;
    },
    getJobs: async (params?: Record<string, any>) => {
      const res = await apiClient.get('/operations/jobs', { params });
      return res.data.data;
    },
    retryJob: async (id: number) => {
      const res = await apiClient.post(`/operations/jobs/${id}/retry`);
      return res.data.data as PdfProcessingJob;
    },
    purgeDlq: async () => {
      const res = await apiClient.post('/operations/purge-dlq');
      return res.data.data;
    },
    getStorageUsage: async () => {
      const res = await apiClient.get('/operations/storage-usage');
      return res.data.data;
    },
  },

  // 8. Infrastructure & Cloud
  infrastructure: {
    getOverview: async () => {
      const res = await apiClient.get('/infrastructure/overview');
      return res.data.data;
    },
    getServices: async () => {
      const res = await apiClient.get('/infrastructure/services');
      return res.data.data as InfrastructureService[];
    },
    getAlerts: async (params?: Record<string, any>) => {
      const res = await apiClient.get('/infrastructure/alerts', { params });
      return res.data.data;
    },
    acknowledgeAlert: async (id: number) => {
      const res = await apiClient.post(`/infrastructure/alerts/${id}/acknowledge`);
      return res.data.data as InfrastructureAlert;
    },
    resolveAlert: async (id: number) => {
      const res = await apiClient.post(`/infrastructure/alerts/${id}/resolve`);
      return res.data.data as InfrastructureAlert;
    },
    getDeployments: async () => {
      const res = await apiClient.get('/infrastructure/deployments');
      return res.data.data;
    },
  },

  // 9. Security & Access
  security: {
    getAdmins: async (params?: Record<string, any>) => {
      const res = await apiClient.get('/security/admins', { params });
      return res.data.data;
    },
    createAdmin: async (data: Record<string, any>) => {
      const res = await apiClient.post('/security/admins', data);
      return res.data.data as SuperAdminUser;
    },
    updateAdmin: async (id: number, data: Record<string, any>) => {
      const res = await apiClient.put(`/security/admins/${id}`, data);
      return res.data.data as SuperAdminUser;
    },
    getRoles: async () => {
      const res = await apiClient.get('/security/roles');
      return res.data.data as SuperAdminRole[];
    },
    getPermissions: async () => {
      const res = await apiClient.get('/security/permissions');
      return res.data.data as Record<string, SuperAdminPermission[]>;
    },
    getSessions: async () => {
      const res = await apiClient.get('/security/sessions');
      return res.data.data;
    },
    revokeSession: async (id: number) => {
      const res = await apiClient.delete(`/security/sessions/${id}`);
      return res.data;
    },
    getAuditLogs: async (params?: Record<string, any>) => {
      const res = await apiClient.get('/security/audit-logs', { params });
      return res.data.data;
    },
    getLoginLogs: async (params?: Record<string, any>) => {
      const res = await apiClient.get('/security/login-logs', { params });
      return res.data.data;
    },
  },

  // 10. Backup & Recovery
  backups: {
    list: async (params?: Record<string, any>) => {
      const res = await apiClient.get('/backups', { params });
      return res.data.data;
    },
    trigger: async (backupType: string, notes?: string) => {
      const res = await apiClient.post('/backups/trigger', { backup_type: backupType, notes });
      return res.data.data as BackupRecord;
    },
    testRestore: async (id: number) => {
      const res = await apiClient.post(`/backups/${id}/test-restore`);
      return res.data.data;
    },
  },

  // 11. Reports & Analytics
  reports: {
    list: async (params?: Record<string, any>) => {
      const res = await apiClient.get('/reports', { params });
      return res.data.data;
    },
    generate: async (reportType: string, format: string, filters?: Record<string, any>) => {
      const res = await apiClient.post('/reports/generate', { report_type: reportType, format, filters });
      return res.data.data as ReportJob;
    },
    downloadUrl: (id: number) => `${API_BASE_URL}/reports/${id}/download`,
  },

  // 12. Support & Communication
  support: {
    getTickets: async (params?: Record<string, any>) => {
      const res = await apiClient.get('/support/tickets', { params });
      return res.data.data;
    },
    getTicket: async (id: number) => {
      const res = await apiClient.get(`/support/tickets/${id}`);
      return res.data.data as SupportTicket;
    },
    replyTicket: async (id: number, message: string, isInternalNote: boolean = false) => {
      const res = await apiClient.post(`/support/tickets/${id}/reply`, { message, is_internal_note: isInternalNote });
      return res.data.data;
    },
    updateTicketStatus: async (id: number, status: string, priority?: string) => {
      const res = await apiClient.put(`/support/tickets/${id}/status`, { status, priority });
      return res.data.data as SupportTicket;
    },
    getTemplates: async () => {
      const res = await apiClient.get('/support/templates');
      return res.data.data;
    },
    sendAnnouncement: async (data: { subject: string; channel: string; content: string; recipient_scope: string }) => {
      const res = await apiClient.post('/support/announcements', data);
      return res.data;
    },
  },

  // 13. Developer & Integrations
  developer: {
    getApiKeys: async () => {
      const res = await apiClient.get('/developer/api-keys');
      return res.data.data as ApiKey[];
    },
    createApiKey: async (data: Record<string, any>) => {
      const res = await apiClient.post('/developer/api-keys', data);
      return res.data.data;
    },
    revokeApiKey: async (id: number) => {
      const res = await apiClient.delete(`/developer/api-keys/${id}`);
      return res.data;
    },
    getWebhooks: async () => {
      const res = await apiClient.get('/developer/webhooks');
      return res.data.data as WebhookEndpoint[];
    },
    createWebhook: async (data: { name: string; target_url: string; events: string[] }) => {
      const res = await apiClient.post('/developer/webhooks', data);
      return res.data.data as WebhookEndpoint;
    },
    getWebhookLogs: async (params?: Record<string, any>) => {
      const res = await apiClient.get('/developer/webhook-logs', { params });
      return res.data.data;
    },
    retryWebhook: async (id: number) => {
      const res = await apiClient.post(`/developer/webhook-logs/${id}/retry`);
      return res.data.data;
    },
  },

  // 14. Platform Settings
  settings: {
    getSettings: async () => {
      const res = await apiClient.get('/settings');
      return res.data.data as Record<string, string>;
    },
    updateSettings: async (settings: Record<string, any>) => {
      const res = await apiClient.post('/settings', settings);
      return res.data.data;
    },
  },

  // 15. My Account
  profile: {
    getProfile: async () => {
      const res = await apiClient.get('/profile');
      return res.data.data;
    },
    updateProfile: async (data: { name: string; phone?: string; avatar?: string }) => {
      const res = await apiClient.put('/profile', data);
      return res.data.data as SuperAdminUser;
    },
    changePassword: async (currentPassword: string, newPassword: string) => {
      const res = await apiClient.post('/profile/change-password', {
        current_password: currentPassword,
        new_password: newPassword,
      });
      return res.data;
    },
  },
};

export const superAdminApi = api;
export default api;
