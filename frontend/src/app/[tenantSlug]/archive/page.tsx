'use client';

import React, { useState, useEffect } from 'react';
import { useParams, useSearchParams } from 'next/navigation';
import { publicEditionService } from '@/modules/public-view/services/editionService';
import { PublicLayout } from '@/modules/public-view/layouts/PublicLayout';
import { ArchivePage } from '@/modules/public-view/pages/ArchivePage';
import { NotFoundPage } from '@/modules/public-view/pages/NotFoundPage';
import { ArchiveCalendarSkeleton } from '@/modules/public-view/components/LoadingStates/LoadingStates';
import { DEFAULT_BRANDING, DEFAULT_PUBLICATION } from '@/modules/public-view/constants/defaultBranding';
import { ArchiveCalendarData, PublicBranding, PublicPublication, PublicCategory } from '@/modules/public-view/types/public.types';

export default function TenantArchiveRoute() {
  const params = useParams();
  const searchParams = useSearchParams();
  const tenantSlug = typeof params?.tenantSlug === 'string' ? params.tenantSlug : '';

  const now = new Date();
  const currentYear = searchParams?.get('year')
    ? parseInt(searchParams.get('year')!, 10)
    : now.getFullYear();
  const currentMonth = searchParams?.get('month')
    ? parseInt(searchParams.get('month')!, 10)
    : now.getMonth() + 1;

  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);
  const [archiveData, setArchiveData] = useState<ArchiveCalendarData>({
    year: currentYear,
    month: currentMonth,
    total_editions: 0,
    available_years: [currentYear],
    published_dates: [],
    editions: [],
  });
  const [branding, setBranding] = useState<PublicBranding>(DEFAULT_BRANDING);
  const [publication, setPublication] = useState<PublicPublication>(DEFAULT_PUBLICATION);
  const [categories, setCategories] = useState<PublicCategory[]>([]);

  useEffect(() => {
    let isMounted = true;
    async function loadArchive() {
      try {
        setLoading(true);
        setError(null);

        const [archiveRes, homeData] = await Promise.all([
          publicEditionService.getArchive(tenantSlug, currentYear, currentMonth),
          publicEditionService.getHomePageData(tenantSlug).catch(() => null),
        ]);

        if (isMounted) {
          setArchiveData(archiveRes);
          if (homeData) {
            setBranding(homeData.branding);
            setPublication(homeData.publication);
            setCategories(homeData.categories as any);
          }
        }
      } catch (err: any) {
        if (isMounted) {
          setError(err?.response?.data?.message || err?.message || 'Failed to load archive');
        }
      } finally {
        if (isMounted) {
          setLoading(false);
        }
      }
    }

    if (tenantSlug) {
      loadArchive();
    }

    return () => {
      isMounted = false;
    };
  }, [tenantSlug, currentYear, currentMonth]);

  if (loading) {
    return (
      <div className="min-h-screen bg-slate-50 p-6">
        <div className="mx-auto max-w-7xl space-y-6">
          <ArchiveCalendarSkeleton />
        </div>
      </div>
    );
  }

  if (error) {
    return (
      <NotFoundPage
        tenantSlug={tenantSlug}
        title="Unable to load archive"
        message={error}
      />
    );
  }

  return (
    <PublicLayout
      tenantSlug={tenantSlug}
      publication={publication}
      branding={branding}
      categories={categories}
    >
      <ArchivePage
        tenantSlug={tenantSlug}
        initialYear={currentYear}
        initialMonth={currentMonth}
        archiveData={archiveData}
        primaryColor={branding.primary_color}
      />
    </PublicLayout>
  );
}
