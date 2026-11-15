# DOGY ePAPER SaaS – Tenant Admin REST API Reference

Base URL: `http://localhost:8000/api/v1/tenant-admin`  
Header: `Authorization: Bearer <TOKEN>`

---

## 1. Authentication Endpoints

### `POST /auth/login`
- **Body:** `{ "email": "owner@samacharam.com", "password": "TenantPassword123!" }`
- **Response:** `{ "token": "...", "user": { "id": 1, "name": "...", "role": "admin" } }`

### `GET /auth/me`
- **Response:** `{ "user": { ... }, "tenant": { ... } }`

### `POST /auth/logout`
- Revokes current personal access token.

### `PUT /auth/profile`
- **Body:** `{ "name": "Updated Name", "email": "new@domain.com" }`

### `POST /auth/change-password`
- **Body:** `{ "current_password": "...", "new_password": "...", "new_password_confirmation": "..." }`

---

## 2. Dashboard Endpoints

### `GET /dashboard`
- **Response:** Telemetry including:
  - `stats`: `total_editions`, `published_editions`, `scheduled_editions`, `draft_editions`, `total_pages`, `storage_used_mb`, `active_staff`.
  - `limits`: `plan_name`, `storage_limit_mb`, `used_storage_mb`, `max_editions_per_month`, `max_staff`.
  - `today_edition`: Details of today's newspaper issue.
  - `recent_editions`: List of recent 5 editions.
  - `upload_queue`: Active conversion items.
  - `activity_logs`: Recent audit items.

---

## 3. Editions Endpoints

### `GET /editions`
- **Query Params:** `search`, `status` (`published`, `scheduled`, `draft`, `archived`), `per_page`.
- **Response:** Paginated list of tenant editions.

### `POST /editions`
- **Body:** `{ "edition_title": "...", "edition_date": "YYYY-MM-DD", "edition_code": "...", "edition_language": "en", "category": "Main", "description": "..." }`

### `GET /editions/{id}`
- **Response:** Edition details with associated pages and publishing schedules.

### `PUT /editions/{id}`
- **Body:** Updated edition fields.

### `DELETE /editions/{id}`
- Permanently deletes edition and all associated rendered artwork.

### `POST /editions/{id}/duplicate`
- Creates a copy of the edition including metadata and page structure.

### `POST /editions/{id}/archive`
- Marks edition status as `archived`.

---

## 4. PDF Ingestion & Processing

### `GET /pdf-uploads`
- **Response:** List of PDF conversion records and progress percentages.

### `POST /editions/{editionId}/upload`
- **Form Data:** `pdf_file` (`application/pdf`, max 100MB).
- **Behavior:** Deduplicates via SHA-256, converts pages locally, stores WebP tiles, creates `edition_pages`.

### `POST /pdf-uploads/{id}/retry`
- Retries failed extraction process.

---

## 5. Page Management Endpoints

### `GET /editions/{editionId}/pages`
- **Response:** Ordered list of pages with dimensions, thumbnails, rotation angles, and visibility status.

### `PUT /editions/{editionId}/pages/reorder`
- **Body:** `{ "order": [{ "page_id": 1, "page_number": 1 }, { "page_id": 2, "page_number": 2 }] }`

### `POST /pages/{pageId}/rotate`
- **Body:** `{ "degrees": 90 }`

### `POST /pages/{pageId}/visibility`
- **Body:** `{ "is_visible": true }`

### `POST /pages/{pageId}/replace`
- **Form Data:** `page_image` (PNG/JPEG/WebP). Replaces page artwork.

---

## 6. Publishing & Automated Scheduler

### `GET /publishing/schedules`
- **Response:** List of active and historical publication drops.

### `POST /editions/{editionId}/publish`
- Immediately sets edition status to `published` and assigns `published_at = now()`.

### `POST /editions/{editionId}/unpublish`
- Reverts edition status to `draft`.

### `POST /publishing/schedule`
- **Body:** `{ "edition_id": 1, "scheduled_at": "YYYY-MM-DD HH:MM:SS", "timezone": "Asia/Kolkata" }`

### `POST /publishing/schedules/{id}/cancel`
- Cancels an upcoming scheduled drop.

---

## 7. Media Library

### `GET /media`
- **Query Params:** `category`, `search`.
- **Response:** Paginated list of uploaded tenant graphic assets.

### `POST /media`
- **Form Data:** `file`, `category`, `alt_text`.

### `DELETE /media/{id}`
- Deletes media asset file and database reference.

---

## 8. Branding & Templates

### `GET /branding`
- Returns primary/secondary colors, font family, layout style, custom CSS.

### `PUT /branding`
- Sanitizes custom CSS against script injections and updates reader stylesheet.

### `GET /templates`
- Returns list of newspaper appearance templates.

### `POST /templates/{id}/apply`
- Overwrites tenant branding with template preset configuration.

---

## 9. Staff & RBAC

### `GET /staff`
- Lists all active team users belonging to tenant.

### `POST /staff`
- **Body:** `{ "name": "...", "email": "...", "password": "...", "role": "editor|reporter|admin" }`

### `PUT /staff/{id}`
- Updates staff member name, email, or role.

### `DELETE /staff/{id}`
- Deletes staff user account.

---

## 10. Reports & Exports

### `GET /reports/overview`
- Returns storage breakdown, editions velocity, and recent audit logs.

### `GET /reports/export/csv?type=editions|activity`
- Streams real-time downloadable CSV file.

---

## 11. Subscription & Quota Tracking

### `GET /subscription`
- Returns current plan quotas, usage counters, and past upgrade request statuses.

### `POST /subscription/upgrade-request`
- **Body:** `{ "requested_plan": "enterprise", "notes": "Need 50GB storage" }`

---

## 12. Helpdesk & Notifications

### `GET /notifications`
- Returns alerts with read/unread markers.

### `POST /notifications/{id}/read`
- Marks notification as read.

### `POST /notifications/read-all`
- Marks all notifications as read.

### `GET /support/tickets`
- Returns tenant support cases.

### `POST /support/tickets`
- **Body:** `{ "subject": "...", "category": "technical", "priority": "medium", "message": "..." }`

### `POST /support/tickets/{id}/reply`
- **Body:** `{ "message": "..." }`
