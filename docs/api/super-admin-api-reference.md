# DOGY ePAPER SaaS – Super Admin API Reference Guide

## 1. Authentication & Protocols

- **Base URL**: `https://api.dogyepaper.com/api/v1/super-admin`
- **Authentication**: Bearer Token in HTTP Authorization Header:
  ```http
  Authorization: Bearer <SUPER_ADMIN_TOKEN>
  ```
- **Response Standard**: JSON format adhering to standard REST guidelines:
  ```json
  {
    "status": "success",
    "data": { ... },
    "message": "Operation completed successfully"
  }
  ```
- **Error Standard**: RFC 7807 compliant error responses:
  ```json
  {
    "status": "error",
    "code": "VALIDATION_FAILED",
    "message": "The given data was invalid.",
    "errors": {
      "email": ["The email has already been taken."]
    }
  }
  ```

---

## 2. API Endpoints Catalog (82 Endpoints Across 15 Modules)

### Module 1: Dashboard (`/dashboard`)
| Method | Endpoint | Description |
|---|---|---|
| `GET` | `/dashboard/stats` | Aggregated platform KPIs (tenants, revenue, storage, queue depth) |
| `GET` | `/dashboard/charts` | Revenue trajectory and tenant growth time-series data |
| `GET` | `/dashboard/recent-activity` | Chronological activity feed across all tenants |

### Module 2: Tenant Management (`/tenants`)
| Method | Endpoint | Description |
|---|---|---|
| `GET` | `/tenants` | Paginated tenant list with search, status, and state filters |
| `POST` | `/tenants` | Atomic 7-step tenant registration wizard submission |
| `GET` | `/tenants/{id}` | Full tenant record with all 10 module relations |
| `PUT` | `/tenants/{id}` | Update publisher information and metadata |
| `POST` | `/tenants/{id}/activate` | Activate pending or trial tenant |
| `POST` | `/tenants/{id}/suspend` | Suspend tenant access with audit reason |
| `POST` | `/tenants/{id}/reactivate`| Restore tenant to active state |
| `POST` | `/tenants/{id}/archive` | Archive inactive tenant assets |
| `DELETE` | `/tenants/{id}` | Controlled deletion workflow requiring confirmation code |

### Module 3: Domain & SSL Management (`/domains`)
| Method | Endpoint | Description |
|---|---|---|
| `GET` | `/domains` | List all tenant custom domains and verification status |
| `POST` | `/domains` | Register new custom domain for tenant |
| `GET` | `/domains/{id}` | Detailed DNS challenge tokens and SSL status |
| `POST` | `/domains/{id}/verify` | Execute live DNS TXT/CNAME query challenge check |
| `POST` | `/domains/{id}/provision-ssl` | Request automated AWS Certificate Manager (ACM) SSL certificate |
| `DELETE` | `/domains/{id}` | Remove custom domain binding |

### Module 4: Subscription & Packages (`/subscriptions`)
| Method | Endpoint | Description |
|---|---|---|
| `GET` | `/subscriptions/packages` | List all active subscription tiers with entitlements |
| `POST` | `/subscriptions/packages` | Create new versioned subscription package |
| `PUT` | `/subscriptions/packages/{id}` | Update pricing and quota entitlements |
| `GET` | `/subscriptions/tenants` | List all active tenant subscription plans |
| `POST` | `/subscriptions/tenants/{id}/change-package` | Upgrade/downgrade tenant plan with prorated billing |
| `GET` | `/subscriptions/invoices` | List platform billing statements |

### Module 5: Payments & Revenue (`/payments`)
| Method | Endpoint | Description |
|---|---|---|
| `GET` | `/payments/stats` | Monthly recurring revenue (MRR), ARR, and transaction volume |
| `GET` | `/payments/transactions` | Paginated transaction ledger with gateway references |
| `POST` | `/payments/{id}/refund` | Process gateway refund with ledger adjustment |
| `POST` | `/payments/webhook/{gateway}`| Idempotent webhook processor for Razorpay / Stripe |

### Module 6: White Label & Branding (`/branding`)
| Method | Endpoint | Description |
|---|---|---|
| `GET` | `/branding/platform` | Global SaaS platform logos, favicon, and brand colors |
| `POST` | `/branding/platform` | Update global branding assets |
| `GET` | `/branding/tenants/{tenant_id}` | Tenant-specific white-label configuration |
| `POST` | `/branding/tenants/{tenant_id}` | Publish sanitized CSS, logos, and color palette |
| `POST` | `/branding/tenants/{tenant_id}/rollback` | Rollback to previous version snapshot |

### Module 7: Newspaper Operations (`/operations`)
| Method | Endpoint | Description |
|---|---|---|
| `GET` | `/operations/overview` | Ghostscript worker status, SQS queue depth, DLQ count |
| `GET` | `/operations/editions` | Master catalog of published newspaper editions |
| `GET` | `/operations/jobs` | Real-time PDF page conversion jobs |
| `POST` | `/operations/jobs/{id}/retry` | Re-queue failed or dead-letter job into SQS |
| `POST` | `/operations/purge-dlq` | Purge unrecoverable dead-letter queue items |
| `GET` | `/operations/storage-usage`| Multi-tenant storage consumption breakdown |

### Module 8: Infrastructure & Cloud (`/infrastructure`)
| Method | Endpoint | Description |
|---|---|---|
| `GET` | `/infrastructure/overview` | Consolidated AWS telemetry (ECS, RDS, S3, Redis, CDN) |
| `GET` | `/infrastructure/services` | Health, CPU, memory, and latency per subsystem |
| `GET` | `/infrastructure/alerts` | CloudWatch triggered alarms |
| `POST` | `/infrastructure/alerts/{id}/acknowledge` | Mark alarm as acknowledged by engineer |
| `POST` | `/infrastructure/alerts/{id}/resolve` | Mark alarm as resolved |
| `GET` | `/infrastructure/deployments`| Blue/Green canary deployment history |

### Module 9: Security & Access Management (`/security`)
| Method | Endpoint | Description |
|---|---|---|
| `GET` | `/security/admins` | List Super Admin staff users |
| `POST` | `/security/admins` | Provision new administrator account |
| `PUT` | `/security/admins/{id}` | Update staff permissions and roles |
| `GET` | `/security/roles` | RBAC hierarchy definitions |
| `GET` | `/security/permissions` | Atomic system permissions grouped by module |
| `GET` | `/security/sessions` | Active super admin browser sessions |
| `DELETE` | `/security/sessions/{id}` | Remotely terminate untrusted session |
| `GET` | `/security/audit-logs` | Immutable audit trail query with date/action filters |
| `GET` | `/security/login-logs` | Authentication attempt history with IP tracking |

### Module 10: Backup & Disaster Recovery (`/backups`)
| Method | Endpoint | Description |
|---|---|---|
| `GET` | `/backups` | Historical database snapshots and S3 manifests |
| `POST` | `/backups/trigger` | Trigger immediate on-demand AWS RDS snapshot |
| `POST` | `/backups/{id}/test-restore` | Automated disaster recovery drill simulation |

### Module 11: Reports & Analytics (`/reports`)
| Method | Endpoint | Description |
|---|---|---|
| `GET` | `/reports` | Archive of exported analytical reports |
| `POST` | `/reports/generate` | Dispatch asynchronous report export (CSV / XLSX / PDF) |
| `GET` | `/reports/{id}/download` | Stream generated report file |

### Module 12: Support & Communication (`/support`)
| Method | Endpoint | Description |
|---|---|---|
| `GET` | `/support/tickets` | Tenant technical inquiry tickets |
| `GET` | `/support/tickets/{id}` | Ticket details and thread history |
| `POST` | `/support/tickets/{id}/reply` | Post public reply or private staff note |
| `PUT` | `/support/tickets/{id}/status` | Update ticket status and priority |
| `POST` | `/support/announcements` | Broadcast maintenance bulletin to tenants |

### Module 13: Developer & Integrations (`/developer`)
| Method | Endpoint | Description |
|---|---|---|
| `GET` | `/developer/api-keys` | Active programmatic API keys |
| `POST` | `/developer/api-keys` | Provision API key with secret token generation |
| `DELETE` | `/developer/api-keys/{id}` | Revoke API key |
| `GET` | `/developer/webhooks` | Registered outbound webhook subscribers |
| `POST` | `/developer/webhooks` | Register new webhook endpoint |
| `GET` | `/developer/webhook-logs` | Webhook dispatch delivery logs |
| `POST` | `/developer/webhook-logs/{id}/retry` | Re-deliver failed webhook payload |

### Module 14: Platform Settings (`/settings`)
| Method | Endpoint | Description |
|---|---|---|
| `GET` | `/settings` | Retrieve global SaaS configuration dictionary |
| `POST` | `/settings` | Batch update platform configuration keys |

### Module 15: My Account (`/profile`)
| Method | Endpoint | Description |
|---|---|---|
| `GET` | `/profile` | Current authenticated super admin profile |
| `PUT` | `/profile` | Update personal profile details |
| `POST` | `/profile/change-password` | Change master account password |
