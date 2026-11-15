'use client';

import React from 'react';
import { useParams } from 'next/navigation';
import { usePublicTenant } from '@/modules/public-view/hooks/usePublicTenant';
import { PublicLayout } from '@/modules/public-view/layouts/PublicLayout';
import { HomePage } from '@/modules/public-view/pages/HomePage';
import { NotFoundPage } from '@/modules/public-view/pages/NotFoundPage';
import {
  LatestEditionHeroSkeleton,
  PageGridSkeleton,
} from '@/modules/public-view/components/LoadingStates/LoadingStates';

export default function TenantPublicHomePage() {
  const params = useParams();
  const tenantSlug = typeof params?.tenantSlug === 'string' ? params.tenantSlug : '';

  const { data, loading, error } = usePublicTenant(tenantSlug);

  if (loading) {
    return (
      <div className="min-h-screen bg-slate-50 p-6">
        <div className="mx-auto max-w-7xl space-y-6">
          <LatestEditionHeroSkeleton />
          <PageGridSkeleton count={8} />
        </div>
      </div>
    );
  }

  if (error || !data) {
    return (
      <NotFoundPage
        tenantSlug={tenantSlug}
        title="Publication Not Found"
        message={error || 'This digital newspaper publication is currently inactive or does not exist.'}
      />
    );
  }

  return (
    <PublicLayout
      tenantSlug={tenantSlug}
      publication={data.publication}
      branding={data.branding}
      categories={data.categories as any}
      latestEditionCode={data.latest_edition?.edition?.edition_code}
      publishDate={data.latest_edition?.edition?.publish_date}
    >
      <HomePage tenantSlug={tenantSlug} data={data} />
    </PublicLayout>
  );
}
