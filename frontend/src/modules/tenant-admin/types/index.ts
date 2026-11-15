// Complete TypeScript Definitions for DOGY ePAPER Tenant Admin Control Center

export interface TenantStaff {
  id: number;
  tenant_id?: number;
  name: string;
  email: string;
  role: string;
  is_active?: boolean;
  permissions?: string[];
  created_at?: string;
}

export interface TenantInfo {
  id: number;
  name: string;
  slug?: string;
  domain?: string;
  status?: 'active' | 'suspended' | 'expired' | 'archived';
  subscription?: string;
  storage_limit_mb?: number;
  storage_used_mb?: number;
}

export interface NewspaperProfile {
  id?: number;
  tenant_id?: number;
  name?: string;
  newspaper_name?: string;
  tagline?: string;
  registration_number?: string;
  publisher_name?: string;
  editor_name?: string;
  contact_person?: string;
  email?: string;
  phone?: string;
  whatsapp?: string;
  address?: string;
  state?: string;
  district?: string;
  mandal?: string;
  pincode?: string;
  language?: string;
  logo_url?: string;
  favicon_url?: string;
  about?: string;
  about_newspaper?: string;
  social_links?: Record<string, string>;
  created_at?: string;
  updated_at?: string;
}

export interface Edition {
  id: number;
  tenant_id?: number;
  edition_code?: string;
  edition_name?: string;
  edition_title?: string;
  edition_date?: string;
  publish_date?: string;
  edition_language?: string;
  category?: string;
  description?: string;
  pages_count?: number;
  total_pages?: number;
  file_size_mb?: number;
  thumbnail_path?: string;
  original_pdf_path?: string;
  status: string;
  scheduled_at?: string;
  published_at?: string;
  archived_at?: string;
  pages?: EditionPage[];
  schedule?: PublishingSchedule;
  author?: { id: number; name: string };
  created_at?: string;
  updated_at?: string;
}

export interface EditionPage {
  id: number;
  edition_id: number;
  tenant_id?: number;
  page_number: number;
  page_title?: string;
  image_path?: string;
  thumbnail_path?: string;
  width?: number;
  height?: number;
  file_size?: number;
  rotation_angle?: number;
  rotation?: number;
  is_visible: boolean;
  sort_order?: number;
  metadata?: Record<string, any>;
  created_at?: string;
}

export interface PdfUpload {
  id: number;
  tenant_id?: number;
  edition_id?: number;
  edition?: Edition;
  uploaded_by_user_id?: number;
  file_name?: string;
  original_filename?: string;
  stored_filename?: string;
  file_path?: string;
  file_size: number;
  mime_type?: string;
  total_pages?: number;
  page_count?: number;
  checksum_sha256?: string;
  progress_percentage?: number;
  status: string;
  error_message?: string;
  created_at: string;
}

export interface PublishingSchedule {
  id: number;
  tenant_id?: number;
  edition_id: number;
  edition?: Edition;
  scheduled_at: string;
  timezone?: string;
  status: string;
  published_at?: string;
  cancelled_at?: string;
  created_at?: string;
}

export interface MediaFile {
  id: number;
  tenant_id?: number;
  user_id?: number;
  file_name?: string;
  name?: string;
  file_path: string;
  path?: string;
  disk?: string;
  category?: string;
  type?: string;
  mime_type?: string;
  file_size: number;
  size?: number;
  width?: number;
  height?: number;
  thumbnail_path?: string;
  alt_text?: string;
  created_at: string;
  user?: { id: number; name: string };
}

export interface BrandingConfiguration {
  id?: number;
  tenant_id?: number;
  primary_color?: string;
  secondary_color?: string;
  background_color?: string;
  text_color?: string;
  font_family?: string;
  typography?: string;
  header_style?: string;
  header_config?: any;
  footer_config?: any;
  social_links?: Record<string, string>;
  custom_css?: string;
  version?: number;
}

export type TenantBranding = BrandingConfiguration;

export interface NewspaperTemplate {
  id: number;
  tenant_id?: number;
  name: string;
  slug?: string;
  description?: string;
  thumbnail_url?: string;
  version?: string;
  is_active?: boolean;
  is_default?: boolean;
  configuration?: any;
  layout_config?: Record<string, any>;
  color_palette?: {
    primary?: string;
    secondary?: string;
    background?: string;
    surface?: string;
  };
}

export interface TenantNotification {
  id: number;
  tenant_id?: number;
  user_id?: number;
  type: string;
  title: string;
  message: string;
  data?: Record<string, any>;
  is_read: boolean;
  read_at?: string;
  created_at: string;
}

export interface SupportTicket {
  id: number;
  tenant_id?: number;
  ticket_number?: string;
  subject: string;
  category?: string;
  priority: string;
  status: string;
  message?: string;
  description?: string;
  last_reply_at?: string;
  created_at: string;
  replies?: SupportTicketReply[];
}

export interface SupportTicketReply {
  id: number;
  ticket_id: number;
  user_name?: string;
  user_id?: number;
  admin_user_id?: number;
  is_admin_reply?: boolean;
  message: string;
  is_internal_note?: boolean;
  created_at: string;
}

export interface TenantSettings {
  default_timezone?: string;
  default_language?: string;
  default_publish_time?: string;
  auto_publish_on_upload?: boolean;
  enable_watermark?: boolean;
  watermark_text?: string;
  retention_days?: number;
}

export interface DashboardOverview {
  stats: {
    total_editions: number;
    published_editions: number;
    scheduled_editions: number;
    draft_editions: number;
    total_pages: number;
    storage_used_mb: number;
    active_staff: number;
    unread_notifications: number;
  };
  limits: {
    plan_name: string;
    storage_limit_mb: number;
    used_storage_mb: number;
    max_editions_per_month: number;
    max_staff: number;
  };
  today_edition?: Edition | null;
  recent_editions: Edition[];
  upload_queue: PdfUpload[];
  activity_logs: Array<{
    id: number;
    action: string;
    description: string;
    created_at: string;
  }>;
}

export interface DashboardStats extends DashboardOverview {
  kpis?: any;
  latest_uploads?: PdfUpload[];
  processing_queue?: Edition[];
  upcoming_scheduled?: Edition[];
  recent_activities?: any[];
  publishing_trajectory?: Array<{
    date: string;
    day_name: string;
    published_count: number;
  }>;
}

export interface ReportOverview {
  editions_breakdown?: {
    published: number;
    scheduled: number;
    draft: number;
    archived: number;
  };
  storage_breakdown?: {
    original_pdfs_mb: string | number;
    page_images_mb: string | number;
    media_library_mb: string | number;
  };
  activity_logs?: Array<{
    id: number;
    action: string;
    description: string;
    ip_address?: string;
    created_at: string;
  }>;
}

export interface SubscriptionUsage {
  plan?: {
    name: string;
    storage_limit_mb: number;
    max_editions_per_month: number;
    max_staff: number;
  };
  usage?: {
    used_storage_mb: number;
    editions_count: number;
    staff_count: number;
  };
  upgrade_requests?: Array<{
    id: number;
    requested_plan: string;
    notes?: string;
    status: string;
    created_at: string;
  }>;
}

export interface SubscriptionUsageData extends SubscriptionUsage {
  subscription?: {
    package_name: string;
    package_slug: string;
    status: string;
    billing_cycle: string;
    amount: number;
    starts_at: string;
    expires_at: string;
  };
  quotas?: {
    storage: {
      used_mb: number;
      limit_mb: number;
      used_gb: number;
      limit_gb: number;
      percent: number;
    };
    monthly_editions: {
      used: number;
      limit: number;
      percent: number;
    };
    staff_users: {
      used: number;
      limit: number;
      percent: number;
    };
    custom_domains: {
      used: number;
      limit: number;
    };
    pdf_upload_limit_mb: number;
  };
  available_packages?: Array<{
    id: number;
    name: string;
    slug: string;
    price: number;
    billing_cycle: string;
    description?: string;
  }>;
}
