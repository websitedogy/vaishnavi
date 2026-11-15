# DOGY ePAPER SaaS – Tenant Admin Architecture & Workflow Guide

## 1. Executive Summary

The **Tenant Admin Control Center** is the dedicated multi-tenant digital newspaper publishing workspace within the DOGY ePAPER SaaS ecosystem. It empowers digital publishers, editors-in-chief, and desk reporters to manage their entire digital publishing lifecycle—from print PDF ingestion and local page rasterization to visual page reordering, layout curation, and automated morning releases.

---

## 2. Architecture & Strict Module Separation

The platform enforces strict boundaries across three core modules:

1. **Super Admin Module** (`backend/app/Modules/SuperAdmin` & `frontend/src/modules/super-admin`): Platform-level governance, subscription tiers, domain routing, tenant isolation oversight.
2. **Tenant Admin Module** (`backend/app/Modules/TenantAdmin` & `frontend/src/modules/tenant-admin`): Publication management, edition assembly, local rasterization engine, newsroom RBAC, and branding.
3. **Public View Module** (`backend/app/Modules/PublicView` & `frontend/src/modules/public-view`): *Reserved for future development.* High-performance public ePaper reader.

---

## 3. Server-Authoritative Multi-Tenancy & Security Isolation

- **Automatic Tenant Scoping:** Every incoming request to `/api/v1/tenant-admin/*` passes through `TenantAdminMiddleware`.
- **Bearer Token Resolution:** Resolves the authenticated `User` and binds the server-authoritative tenant context:
  ```php
  $tenantId = $request->user()->tenant_id;
  $request->attributes->set('tenant_id', $tenantId);
  ```
- **Strict Cross-Tenant Isolation:** All database queries, insertions, and updates filter strictly by `where('tenant_id', $tenantId)`. No client payload can tamper with or spoof another publication's editions or assets.
- **Suspension Enforcement:** Suspended or expired tenants are immediately rejected with an HTTP 403 Forbidden response.

---

## 4. Local High-Fidelity PDF Ingestion & Rasterization

Designed specifically for local development and high-throughput offline processing:
- **Engine Pipeline:** Managed by `LocalPdfProcessingService`.
- **SHA-256 Deduplication:** Validates incoming PDF checksums to prevent duplicate page generation.
- **GD High-Res Vector Fallback:** When Ghostscript or Imagick is not installed locally, the service renders realistic, high-resolution newspaper broadsheet layouts (header mastheads, multi-column editorial texts, and photo frames) directly in memory using PHP GD and exports optimized WebP page artwork and thumbnails.
- **Two-Pass Atomic Reordering:** Resolves SQLite/MySQL unique index constraints on `(edition_id, page_number)` by applying a temporary negative offset pass before finalizing user-requested page order.

---

## 5. 16 Core Newsroom Modules

| # | Module | Route Key | Backend Service | Functionality |
|---|---|---|---|---|
| 1 | **Dashboard** | `dashboard` | `DashboardService` | Real database telemetry, today's edition status, processing queue, quota progress. |
| 2 | **Newspaper Profile** | `profile` | `NewspaperProfileService` | RNI registration, publication name, editor-in-chief, office address, logo artwork. |
| 3 | **Editions Management** | `editions` | `EditionService` | Issue lifecycle: draft, scheduled, published, duplicate, and archive. |
| 4 | **PDF Ingestion** | `pdf-upload` | `PdfUploadService` / `LocalPdfProcessingService` | Drag-and-drop PDF ingestion, extraction status tracker, error reporting. |
| 5 | **Page Management** | `pages` | `PageManagementService` | Interactive page grid, 90° rotation, drag/arrow reordering, inspector modal with zoom. |
| 6 | **Publishing & Scheduling**| `publishing` | `PublishingService` | Instant release, unpublish retraction, automated morning release timer. |
| 7 | **Media Library** | `media` | `MediaLibraryService` | Masthead logos, sponsor advertisements, and editorial graphics storage. |
| 8 | **Branding Engine** | `branding` | `BrandingService` | Corporate color accents, typography, CSS sanitizer (strips unsafe scripts/links). |
| 9 | **Templates** | `templates` | `TemplateService` | One-click application of classic broadsheet, modern tabloid, or minimalist styles. |
| 10 | **Staff Management** | `staff` | `StaffService` | Newsroom RBAC: Admin, Editor, Reporter with granular access matrices. |
| 11 | **Reports & Analytics** | `reports` | `ReportService` | Storage breakdown by asset type, editions velocity, CSV export streaming. |
| 12 | **Subscription & Usage** | `subscription` | `UsageTrackingService` | Real disk and edition quota gauges, tier upgrade request forms. |
| 13 | **Notifications** | `notifications` | `TenantNotificationService` | Alert feed for completed conversions, publishing drops, and security events. |
| 14 | **Support Desk** | `support` | `SupportTicketService` | Direct bi-directional messaging with SaaS platform engineers. |
| 15 | **Settings** | `settings` | `TenantSettingsService` | Timezone selection, morning drop time, auto-publish switch, archival policy. |
| 16 | **My Account** | `account` | `TenantAuthService` | Personal staff profile, credentials update, password reset. |

---

## 6. Demo Test Credentials

| Newspaper Name | Account Role | Email | Password |
|---|---|---|---|
| **Telangana Samacharam** | Owner / Admin | `owner@samacharam.com` | `TenantPassword123!` |
| **Telangana Samacharam** | News Editor | `editor@samacharam.com` | `TenantPassword123!` |
| **Telangana Samacharam** | Staff Reporter | `reporter@samacharam.com` | `TenantPassword123!` |
| **Andhra Prabha Digital** | Tenant Admin | `admin@andhraprabha.com` | `TenantPassword123!` |
