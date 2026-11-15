export interface SuperAdminUser {
  id: number;
  name: string;
  email: string;
  phone?: string;
  avatar?: string;
  role: any;
  role_slug?: string;
  permissions?: string[];
  mfa_enabled?: boolean;
  status?: string;
  two_factor_enabled?: boolean;
  last_login_at?: string;
}

export interface SuperAdminRole {
  id: number;
  name: string;
  slug: string;
  description?: string;
  is_system: boolean;
  permissions?: SuperAdminPermission[];
}

export interface SuperAdminPermission {
  id: number;
  name: string;
  slug: string;
  module: string;
  description?: string;
}

export interface Tenant {
  id: number;
  name: string;
  newspaper_name?: string;
  company_name?: string;
  contact?: string;
  contact_person?: string;
  slug: string;
  email: string;
  phone?: string;
  domain?: string;
  address?: string;
  state?: string;
  district?: string;
  mandal?: string;
  logo?: string;
  subscription: string;
  package_id?: number;
  subscription_amount: number;
  billing_cycle: string;
  storage_limit_mb: number;
  storage_used_mb: number;
  default_language: string;
  timezone: string;
  database_name?: string;
  status: 'active' | 'trial' | 'pending' | 'suspended' | 'expired' | 'archived';
  trial_ends_at?: string;
  subscription_ends_at?: string;
  suspended_at?: string;
  created_at: string;
  primary_domain?: TenantDomain;
  package?: SubscriptionPackage;
}

export interface TenantDomain {
  id: number;
  tenant_id: number;
  type: 'custom' | 'subdomain';
  host: string;
  is_primary: boolean;
  verification_status: 'pending' | 'verified' | 'failed';
  verification_token?: string;
  txt_record_host?: string;
  txt_record_value?: string;
  cname_host?: string;
  cname_target?: string;
  ssl_status: 'pending' | 'provisioning' | 'active' | 'failed' | 'expired';
  ssl_auto: boolean;
  acm_certificate_arn?: string;
  cloudfront_distribution_id?: string;
  ssl_issued_at?: string;
  ssl_expires_at?: string;
  health_status: 'healthy' | 'warning' | 'error' | 'pending_dns';
  dns_checked_at?: string;
  dns_error?: string;
  tenant?: Tenant;
  dns_records?: DomainDnsRecord[];
  ssl_certificates?: DomainSslCertificate[];
}

export interface DomainDnsRecord {
  id: number;
  domain_id: number;
  record_type: 'TXT' | 'CNAME' | 'A';
  host: string;
  expected_value: string;
  current_value?: string;
  status: 'pending' | 'matched' | 'mismatched' | 'error';
  last_verified_at?: string;
}

export interface DomainSslCertificate {
  id: number;
  domain_id: number;
  certificate_arn?: string;
  issuer: string;
  domain_name: string;
  sans?: string[];
  status: string;
  valid_from?: string;
  valid_to?: string;
  auto_renew: boolean;
}

export interface SubscriptionPackage {
  id: number;
  name: string;
  slug: string;
  description?: string;
  billing_cycle: 'monthly' | 'quarterly' | 'half_yearly' | 'yearly' | 'custom';
  price: number;
  currency: string;
  trial_days: number;
  is_active: boolean;
  is_popular: boolean;
  is_custom: boolean;
  version: number;
  entitlements?: PackageEntitlement;
}

export interface PackageEntitlement {
  id: number;
  package_id: number;
  max_domains: number;
  storage_limit_gb: number;
  pdf_upload_limit_mb: number;
  monthly_edition_limit: number;
  admin_user_limit: number;
  custom_branding_enabled: boolean;
  priority_support_enabled: boolean;
  analytics_enabled: boolean;
  custom_entitlements?: Record<string, any>;
}

export interface TenantSubscription {
  id: number;
  tenant_id: number;
  package_id: number;
  status: string;
  billing_cycle: string;
  amount: number;
  currency: string;
  current_period_start?: string;
  current_period_end?: string;
  trial_ends_at?: string;
  auto_renew: boolean;
  tenant?: Tenant;
  package?: SubscriptionPackage;
}

export interface SubscriptionInvoice {
  id: number;
  tenant_id: number;
  subscription_id?: number;
  invoice_number: string;
  amount: number;
  tax_amount: number;
  currency: string;
  status: 'draft' | 'pending' | 'paid' | 'void' | 'refunded';
  due_date?: string;
  paid_at?: string;
  tenant?: Tenant;
}

export interface Payment {
  id: number;
  tenant_id: number;
  invoice_id?: number;
  transaction_reference: string;
  gateway: 'razorpay' | 'stripe' | 'paypal' | 'manual';
  amount: number;
  tax: number;
  fee: number;
  currency: string;
  status: 'pending' | 'success' | 'failed' | 'refunded';
  payment_method?: string;
  created_at: string;
  tenant?: Tenant;
  invoice?: SubscriptionInvoice;
}

export interface Refund {
  id: number;
  payment_id: number;
  tenant_id: number;
  refund_reference: string;
  amount: number;
  reason?: string;
  status: 'pending' | 'completed' | 'failed';
  created_at: string;
}

export interface PlatformBranding {
  id: number;
  platform_name: string;
  tag_line?: string;
  logo_url?: string;
  favicon_url?: string;
  primary_color: string;
  secondary_color: string;
  header_config?: Record<string, any>;
  footer_config?: Record<string, any>;
  custom_css?: string;
}

export interface TenantBranding {
  id: number;
  tenant_id: number;
  newspaper_name: string;
  logo_url?: string;
  favicon_url?: string;
  primary_color: string;
  secondary_color: string;
  typography: string;
  header_config?: Record<string, any>;
  footer_config?: Record<string, any>;
  social_links?: Record<string, any>;
  custom_css?: string;
  is_published: boolean;
  version: number;
}

export interface NewspaperEdition {
  id: number;
  tenant_id: number;
  edition_code: string;
  edition_name: string;
  publish_date: string;
  total_pages: number;
  file_size_mb: number;
  pdf_s3_path?: string;
  status: 'pending' | 'processing' | 'completed' | 'failed';
  tenant?: Tenant;
  created_at: string;
}

export interface PdfProcessingJob {
  id: number;
  edition_id: number;
  tenant_id: number;
  job_uuid: string;
  status: 'queued' | 'processing' | 'completed' | 'failed' | 'retrying' | 'dead_letter';
  current_page: number;
  total_pages: number;
  worker_id?: string;
  attempts: number;
  error_message?: string;
  started_at?: string;
  completed_at?: string;
  edition?: NewspaperEdition;
  tenant?: Tenant;
  created_at: string;
}

export interface InfrastructureService {
  id: number;
  service_name: string;
  service_type: string;
  aws_region: string;
  resource_arn?: string;
  status: 'healthy' | 'degraded' | 'critical' | 'maintenance';
  cpu_percent: number;
  memory_percent: number;
  latency_ms: number;
  last_checked_at?: string;
}

export interface InfrastructureAlert {
  id: number;
  service_id?: number;
  severity: 'low' | 'medium' | 'high' | 'critical';
  title: string;
  description?: string;
  status: 'active' | 'acknowledged' | 'resolved';
  triggered_at: string;
  resolved_at?: string;
  service?: InfrastructureService;
}

export interface BackupRecord {
  id: number;
  type: string;
  file_path?: string;
  file_size: number;
  status: string;
  backup_taken_at: string;
  notes?: string;
  backup_type: string;
  aws_backup_arn?: string;
  encryption_status: string;
  retention_days: number;
  rpo_minutes: number;
  rto_minutes: number;
}

export interface SupportTicket {
  id: number;
  tenant_id: number;
  ticket_number?: string;
  subject: string;
  category: string;
  priority: 'low' | 'medium' | 'high' | 'urgent';
  status: 'open' | 'in_progress' | 'waiting_on_tenant' | 'resolved' | 'closed';
  assigned_admin_id?: number;
  last_reply_at?: string;
  created_at: string;
  tenant?: Tenant;
  assigned_admin?: SuperAdminUser;
}

export interface ApiKey {
  id: number;
  name: string;
  key_prefix: string;
  permissions?: string[];
  rate_limit_per_minute: number;
  is_active: boolean;
  last_used_at?: string;
  expires_at?: string;
  created_at: string;
}

export interface WebhookEndpoint {
  id: number;
  name: string;
  target_url: string;
  events: string[];
  is_active: boolean;
  failure_count: number;
  created_at: string;
}

export interface ReportJob {
  id: number;
  report_type: string;
  format: 'csv' | 'excel' | 'pdf';
  status: 'queued' | 'processing' | 'completed' | 'failed';
  file_path?: string;
  file_size_kb: number;
  completed_at?: string;
  created_at: string;
}

export interface AuditLog {
  id: number;
  super_admin_id?: number;
  actor_name?: string;
  actor_email?: string;
  action: string;
  entity_type?: string;
  entity_id?: number;
  summary?: string;
  changes?: any;
  ip_address?: string;
  performed_at: string;
}

export interface DashboardStats {
  kpis: {
    total_tenants: number;
    active_tenants: number;
    trial_tenants: number;
    suspended_tenants: number;
    expired_tenants: number;
    pending_tenants: number;
    total_domains: number;
    verified_domains: number;
    active_ssl_certificates: number;
    total_subscriptions: number;
    monthly_recurring_revenue: number;
    annual_recurring_revenue: number;
    total_payments_revenue: number;
    failed_payments: number;
    total_storage_used_mb: number;
    total_storage_limit_mb: number;
    pdf_processing_queue: number;
  };
  charts: {
    tenant_growth: Array<{ month: string; count: number }>;
    monthly_revenue: Array<{ month: string; revenue: number }>;
    subscription_distribution: Array<{ name: string; subscriber_count: number }>;
  };
  infrastructure: {
    total_services: number;
    healthy_services: number;
    critical_alerts: number;
    avg_api_latency_ms: number;
  };
  recent_tenants: Tenant[];
  recent_payments: Payment[];
  recent_activities: AuditLog[];
  infrastructure_alerts: InfrastructureAlert[];
}
