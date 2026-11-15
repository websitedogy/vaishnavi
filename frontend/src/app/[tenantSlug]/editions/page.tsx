'use client';

import React, { useState, useEffect } from 'react';
import { useParams, useSearchParams } from 'next/navigation';
import { publicEditionService } from '@/modules/public-view/services/editionService';
import { PublicLayout } from '@/modules/public-view/layouts/PublicLayout';
import { EditionPage } from '@/modules/public-view/pages/EditionPage';
import { NotFoundPage } from '@/modules/public-view/pages/NotFoundPage';
import { PageGridSkeleton } from '@/modules/public-view/components/LoadingStates/LoadingStates';
import { DEFAULT_BRANDING, DEFAULT_PUBLICATION } from '@/modules/public-view/constants/defaultBranding';
import { PublicEditionCard, PublicCategory, PaginationMeta, PublicBranding, PublicPublication } from '@/modules/public-view/types/public.types';

export default function TenantEditionsRoute() {
  const params = useParams();
  const searchParams = useSearchParams();
  const tenantSlug = typeof params?.tenantSlug === 'string' ? params.tenantSlug : '';
  const page = parseInt(searchParams?.get('page') || '1', 10);
  const category = searchParams?.get('category') || undefined;

  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);
  const [editions, setEditions] = useState<PublicEditionCard[]>([]);
  const [categories, setCategories] = useState<PublicCategory[]>([]);
  const [pagination, setPagination] = useState<PaginationMeta>({
    current_page: 1,
    last_page: 1,
    per_page: 12,
    total: 0,
  });
  const [branding, setBranding] = useState<PublicBranding>(DEFAULT_BRANDING);
  const [publication, setPublication] = useState<PublicPublication>(DEFAULT_PUBLICATION);

  useEffect(() => {
    let isMounted = true;
    async function loadData() {
      try {
        setLoading(true);
        setError(null);

        const [editionsRes, homeData] = await Promise.all([
          publicEditionService.getEditions(tenantSlug, page, 12, category),
          publicEditionService.getHomePageData(tenantSlug).catch(() => null),
        ]);

        if (isMounted) {
          setEditions(editionsRes.data);
          setPagination(editionsRes.meta);
          if (homeData) {
            setBranding(homeData.branding);
            setPublication(homeData.publication);
            setCategories((homeData.categories as any) || []);
          }
        }
      } catch (err: any) {
        if (isMounted) {
          setError(err?.response?.data?.message || err?.message || 'Failed to load editions');
        }
      } finally {
        if (isMounted) {
          setLoading(false);
        }
      }
    }

    if (tenantSlug) {
      loadData();
    }

    return () => {
      isMounted = false;
    };
  }, [tenantSlug, page, category]);

  if (loading) {
    return (
      <div className="min-h-screen bg-slate-50 p-6">
        <div className="mx-auto max-w-7xl space-y-6">
          <div className="h-8 w-64 bg-slate-200 rounded animate-pulse" />
          <PageGridSkeleton count={10} />
        </div>
      </div>
    );
  }

  if (error) {
    return (
      <NotFoundPage
        tenantSlug={tenantSlug}
        title="Unable to load editions"
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
      <EditionPage
        tenantSlug={tenantSlug}
        editions={editions}
        pagination={pagination}
        categories={categories}
        primaryColor={branding.primary_color}
        activeCategory={category}
      />
    </PublicLayout>
  );
}
