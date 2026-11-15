import axios from 'axios';
import {
  DashboardOverview,
  DashboardStats,
  NewspaperProfile,
  Edition,
  EditionPage,
  PdfUpload,
  MediaFile,
  BrandingConfiguration,
  TenantBranding,
  NewspaperTemplate,
  TenantStaff,
  SubscriptionUsage,
  SubscriptionUsageData,
  TenantNotification,
  SupportTicket,
  SupportTicketReply,
  TenantSettings,
  ReportOverview,
} from '../types';

const API_BASE_URL = process.env.NEXT_PUBLIC_API_URL 
  ? process.env.NEXT_PUBLIC_API_URL.replace('/v1/super-admin', '/v1/tenant-admin')
  : 'http://localhost:8000/api/v1/tenant-admin';

export const tenantApiClient = axios.create({
  baseURL: API_BASE_URL,
  headers: {
    'Content-Type': 'application/json',
    'Accept': 'application/json',
  },
});

// Auto-inject bearer token
tenantApiClient.interceptors.request.use((config) => {
  if (typeof window !== 'undefined') {
    const token = localStorage.getItem('tenant_token') || localStorage.getItem('tenant_admin_token');
    if (token) {
      config.headers.Authorization = `Bearer ${token}`;
    }
  }
  return config;
});

// 0. Auth API
export const authApi = {
  login: async (credentials: { email: string; password: string }) => {
    const res = await tenantApiClient.post('/auth/login', credentials);
    return res.data;
  },
  me: async () => {
    const res = await tenantApiClient.get('/auth/me');
    return res.data;
  },
  logout: async () => {
    const res = await tenantApiClient.post('/auth/logout');
    return res.data;
  },
  updateProfile: async (data: { name: string; email: string }) => {
    const res = await tenantApiClient.put('/auth/profile', data);
    return res.data;
  },
  changePassword: async (data: { current_password: string; new_password: string; new_password_confirmation?: string }) => {
    const res = await tenantApiClient.post('/auth/change-password', data);
    return res.data;
  },
};

// 1. Dashboard API
export const dashboardApi = {
  getOverview: async (): Promise<{ data: DashboardOverview }> => {
    const res = await tenantApiClient.get('/dashboard');
    return res.data;
  },
  getStats: async (): Promise<DashboardStats> => {
    const res = await tenantApiClient.get('/dashboard');
    return res.data.data;
  },
};

// 2. Newspaper Profile API
export const newspaperProfileApi = {
  get: async (): Promise<{ data: NewspaperProfile }> => {
    const res = await tenantApiClient.get('/newspaper-profile');
    return res.data;
  },
  update: async (data: Partial<NewspaperProfile>): Promise<{ data: NewspaperProfile }> => {
    const res = await tenantApiClient.put('/newspaper-profile', data);
    return res.data;
  },
  uploadLogo: async (file: File): Promise<string> => {
    const formData = new FormData();
    formData.append('logo', file);
    const res = await tenantApiClient.post('/newspaper-profile/logo', formData, {
      headers: { 'Content-Type': 'multipart/form-data' },
    });
    return res.data.data?.logo_url || res.data.logo_url;
  },
  uploadFavicon: async (file: File): Promise<string> => {
    const formData = new FormData();
    formData.append('favicon', file);
    const res = await tenantApiClient.post('/newspaper-profile/favicon', formData, {
      headers: { 'Content-Type': 'multipart/form-data' },
    });
    return res.data.data?.favicon_url || res.data.favicon_url;
  },
};

// 3. Editions API
export const editionsApi = {
  getAll: async (params?: Record<string, any>): Promise<{ data: any }> => {
    const res = await tenantApiClient.get('/editions', { params });
    return res.data;
  },
  list: async (params?: Record<string, any>) => {
    const res = await tenantApiClient.get('/editions', { params });
    return res.data;
  },
  getById: async (id: number): Promise<{ data: Edition }> => {
    const res = await tenantApiClient.get(`/editions/${id}`);
    return res.data;
  },
  get: async (id: number): Promise<Edition> => {
    const res = await tenantApiClient.get(`/editions/${id}`);
    return res.data.data;
  },
  create: async (data: Partial<Edition>): Promise<{ data: Edition }> => {
    const res = await tenantApiClient.post('/editions', data);
    return res.data;
  },
  update: async (id: number, data: Partial<Edition>): Promise<{ data: Edition }> => {
    const res = await tenantApiClient.put(`/editions/${id}`, data);
    return res.data;
  },
  delete: async (id: number) => {
    const res = await tenantApiClient.delete(`/editions/${id}`);
    return res.data;
  },
  duplicate: async (id: number): Promise<{ data: Edition }> => {
    const res = await tenantApiClient.post(`/editions/${id}/duplicate`);
    return res.data;
  },
  archive: async (id: number): Promise<{ data: Edition }> => {
    const res = await tenantApiClient.post(`/editions/${id}/archive`);
    return res.data;
  },
};

// 4. PDF Upload & Processing API
export const pdfUploadApi = {
  getUploads: async (params?: Record<string, any>): Promise<{ data: any }> => {
    const res = await tenantApiClient.get('/pdf-uploads', { params });
    return res.data;
  },
  upload: async (editionId: number, file: File, onProgress?: (percent: number) => void) => {
    const formData = new FormData();
    formData.append('pdf_file', file);
    const res = await tenantApiClient.post(`/editions/${editionId}/upload`, formData, {
      headers: { 'Content-Type': 'multipart/form-data' },
      onUploadProgress: (progressEvent) => {
        if (progressEvent.total && onProgress) {
          const percent = Math.round((progressEvent.loaded * 100) / progressEvent.total);
          onProgress(percent);
        }
      },
    });
    return res.data;
  },
  status: async (editionId: number) => {
    const res = await tenantApiClient.get(`/editions/${editionId}/processing-status`);
    return res.data;
  },
  retry: async (uploadId: number) => {
    const res = await tenantApiClient.post(`/pdf-uploads/${uploadId}/retry`);
    return res.data;
  },
};

// 5. Page Management API
export const pagesApi = {
  getByEdition: async (editionId: number): Promise<{ data: any }> => {
    const res = await tenantApiClient.get(`/editions/${editionId}/pages`);
    return res.data;
  },
  list: async (editionId: number): Promise<EditionPage[]> => {
    const res = await tenantApiClient.get(`/editions/${editionId}/pages`);
    return res.data.data;
  },
  reorder: async (editionId: number, order: any): Promise<any> => {
    const res = await tenantApiClient.put(`/editions/${editionId}/pages/reorder`, { order });
    return res.data;
  },
  rotate: async (pageId: number, degrees: number = 90): Promise<any> => {
    const res = await tenantApiClient.post(`/pages/${pageId}/rotate`, { degrees });
    return res.data;
  },
  toggleVisibility: async (pageId: number, isVisible: boolean): Promise<any> => {
    const res = await tenantApiClient.post(`/pages/${pageId}/visibility`, { is_visible: isVisible });
    return res.data;
  },
  replaceImage: async (pageId: number, file: File): Promise<any> => {
    const formData = new FormData();
    formData.append('page_image', file);
    const res = await tenantApiClient.post(`/pages/${pageId}/replace`, formData, {
      headers: { 'Content-Type': 'multipart/form-data' },
    });
    return res.data;
  },
};

// 6. Publishing API
export const publishingApi = {
  getSchedules: async (): Promise<{ data: any }> => {
    const res = await tenantApiClient.get('/publishing/schedules');
    return res.data;
  },
  schedule: async (data: { edition_id: number; scheduled_at: string; timezone?: string }) => {
    const res = await tenantApiClient.post(`/editions/${data.edition_id}/schedule`, data);
    return res.data;
  },
  publishNow: async (editionId: number) => {
    const res = await tenantApiClient.post(`/editions/${editionId}/publish`);
    return res.data;
  },
  unpublish: async (editionId: number) => {
    const res = await tenantApiClient.post(`/editions/${editionId}/unpublish`);
    return res.data;
  },
  cancelSchedule: async (scheduleId: number) => {
    const res = await tenantApiClient.post(`/publishing/schedules/${scheduleId}/cancel`);
    return res.data;
  },
};

// 7. Media Library API
export const mediaLibraryApi = {
  getAll: async (params?: Record<string, any>): Promise<{ data: any }> => {
    const res = await tenantApiClient.get('/media', { params });
    return res.data;
  },
  upload: async (file: File, category?: string, altText?: string) => {
    const formData = new FormData();
    formData.append('file', file);
    if (category) formData.append('category', category);
    if (altText) formData.append('alt_text', altText);
    const res = await tenantApiClient.post('/media', formData, {
      headers: { 'Content-Type': 'multipart/form-data' },
    });
    return res.data;
  },
  update: async (id: number, data: any) => {
    const res = await tenantApiClient.put(`/media/${id}`, data);
    return res.data;
  },
  delete: async (id: number) => {
    const res = await tenantApiClient.delete(`/media/${id}`);
    return res.data;
  },
};

// 8. Branding API
export const brandingApi = {
  get: async (): Promise<{ data: BrandingConfiguration }> => {
    const res = await tenantApiClient.get('/branding');
    return res.data;
  },
  update: async (data: Partial<BrandingConfiguration>): Promise<{ data: BrandingConfiguration }> => {
    const res = await tenantApiClient.put('/branding', data);
    return res.data;
  },
  reset: async () => {
    const res = await tenantApiClient.post('/branding/reset');
    return res.data;
  },
};

// 9. Templates API
export const templatesApi = {
  getAll: async (): Promise<{ data: any }> => {
    const res = await tenantApiClient.get('/templates');
    return res.data;
  },
  apply: async (id: number) => {
    const res = await tenantApiClient.post(`/templates/${id}/apply`);
    return res.data;
  },
};

// 10. Staff & Roles API
export const staffApi = {
  getAll: async (): Promise<{ data: any }> => {
    const res = await tenantApiClient.get('/staff');
    return res.data;
  },
  create: async (data: any) => {
    const res = await tenantApiClient.post('/staff', data);
    return res.data;
  },
  update: async (id: number, data: any) => {
    const res = await tenantApiClient.put(`/staff/${id}`, data);
    return res.data;
  },
  delete: async (id: number) => {
    const res = await tenantApiClient.delete(`/staff/${id}`);
    return res.data;
  },
};

export const rolesApi = {
  getAll: async (): Promise<{ data: any }> => {
    const res = await tenantApiClient.get('/roles');
    return res.data;
  },
};

// 11. Reports API
export const reportsApi = {
  getOverview: async (): Promise<{ data: ReportOverview }> => {
    const res = await tenantApiClient.get('/reports/overview');
    return res.data;
  },
  downloadCsv: async (type: string) => {
    const res = await tenantApiClient.get(`/reports/export/csv?type=${type}`, {
      responseType: 'blob',
    });
    return res;
  },
};

// 12. Subscription & Usage API
export const subscriptionApi = {
  getUsage: async (): Promise<{ data: SubscriptionUsage }> => {
    const res = await tenantApiClient.get('/subscription');
    return res.data;
  },
  requestUpgrade: async (data: { requested_plan: string; notes?: string }) => {
    const res = await tenantApiClient.post('/subscription/upgrade-request', data);
    return res.data;
  },
};

// 13. Notifications API
export const notificationsApi = {
  getAll: async (params?: any): Promise<{ data: any }> => {
    const res = await tenantApiClient.get('/notifications', { params });
    return res.data;
  },
  markAsRead: async (id: number) => {
    const res = await tenantApiClient.post(`/notifications/${id}/read`);
    return res.data;
  },
  markAllAsRead: async () => {
    const res = await tenantApiClient.post('/notifications/read-all');
    return res.data;
  },
  delete: async (id: number) => {
    const res = await tenantApiClient.delete(`/notifications/${id}`);
    return res.data;
  },
};

// 14. Support API
export const supportApi = {
  getTickets: async (params?: any): Promise<{ data: any }> => {
    const res = await tenantApiClient.get('/support/tickets', { params });
    return res.data;
  },
  createTicket: async (data: { subject: string; category?: string; priority?: string; message: string }) => {
    const res = await tenantApiClient.post('/support/tickets', data);
    return res.data;
  },
  getTicket: async (id: number): Promise<{ data: SupportTicket }> => {
    const res = await tenantApiClient.get(`/support/tickets/${id}`);
    return res.data;
  },
  replyTicket: async (id: number, message: string): Promise<{ data: SupportTicket }> => {
    const res = await tenantApiClient.post(`/support/tickets/${id}/reply`, { message });
    return res.data;
  },
};

// 15. Settings API
export const settingsApi = {
  get: async (): Promise<{ data: TenantSettings }> => {
    const res = await tenantApiClient.get('/settings');
    return res.data;
  },
  update: async (data: Partial<TenantSettings>): Promise<{ data: TenantSettings }> => {
    const res = await tenantApiClient.post('/settings', data);
    return res.data;
  },
};

// Compatibility aggregated object
export const tenantAdminApi = {
  auth: authApi,
  dashboard: dashboardApi,
  profile: newspaperProfileApi,
  editions: editionsApi,
  pdf: pdfUploadApi,
  pages: pagesApi,
  publishing: publishingApi,
  media: mediaLibraryApi,
  branding: brandingApi,
  templates: templatesApi,
  staff: staffApi,
  roles: rolesApi,
  reports: reportsApi,
  subscription: subscriptionApi,
  notifications: notificationsApi,
  support: supportApi,
  settings: settingsApi,
};
