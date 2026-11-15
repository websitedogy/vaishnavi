'use client';

import React from 'react';
import { useParams } from 'next/navigation';
import { useDigitalReader } from '@/modules/public-view/hooks/useDigitalReader';
import { ReaderPage } from '@/modules/public-view/pages/ReaderPage';
import { ReaderLayout } from '@/modules/public-view/layouts/ReaderLayout';

export default function TenantSpecificReaderPage() {
  const params = useParams();
  const tenantSlug = typeof params?.tenantSlug === 'string' ? params.tenantSlug : '';
  const dateOrCode = typeof params?.dateOrCode === 'string' ? params.dateOrCode : '';

  const { payload, loading, error } = useDigitalReader(tenantSlug, dateOrCode);

  return (
    <ReaderLayout>
      <ReaderPage
        tenantSlug={tenantSlug}
        payload={payload}
        loading={loading}
        error={error}
      />
    </ReaderLayout>
  );
}
