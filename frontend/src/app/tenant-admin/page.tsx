import { TenantAdminApp } from '@/modules/tenant-admin/TenantAdminApp';

export const metadata = {
  title: 'Tenant Admin Control Center | DOGY ePAPER SaaS',
  description: 'Digital newspaper publishing, PDF ingestion, and layout management for tenants.',
};

export default function TenantAdminPage() {
  return <TenantAdminApp />;
}
