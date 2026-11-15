'use client';

import React, { useState, useEffect } from 'react';
import { useParams } from 'next/navigation';
import { publicEditionService } from '@/modules/public-view/services/editionService';
import { PublicLayout } from '@/modules/public-view/layouts/PublicLayout';
import { AboutPage } from '@/modules/public-view/pages/AboutPage';
import { NotFoundPage } from '@/modules/public-view/pages/NotFoundPage';
import { DEFAULT_BRANDING, DEFAULT_PUBLICATION } from '@/modules/public-view/constants/defaultBranding';
import { PublicBranding, PublicPublication, PublicCategory } from '@/modules/public-view/types/public.types';

export default function TenantAboutRoute() {
  const params = useParams();
  const tenantSlug = typeof params?.tenantSlug === 'string' ? params.tenantSlug : '';

  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);
  const [branding, setBranding] = useState<PublicBranding>(DEFAULT_BRANDING);
  const [publication, setPublication] = useState<PublicPublication>(DEFAULT_PUBLICATION);
  const [categories, setCategories] = useState<PublicCategory[]>([]);

  useEffect(() => {
    let isMounted = true;
    async function loadData() {
      try {
        setLoading(true);
        const homeData = await publicEditionService.getHomePageData(tenantSlug);
        if (isMounted) {
          setBranding(homeData.branding);
          setPublication(homeData.publication);
          setCategories(homeData.categories as any);
        }
      } catch (err: any) {
        if (isMounted) {
          setError(err?.message || 'Failed to load publication');
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
  }, [tenantSlug]);

  if (loading) {
    return (
      <div className="min-h-screen bg-slate-50 p-6">
        <div className="mx-auto max-w-4xl space-y-6">
          <div className="h-10 w-64 bg-slate-200 rounded animate-pulse mx-auto" />
          <div className="h-64 bg-slate-200 rounded-2xl animate-pulse" />
        </div>
      </div>
    );
  }

  if (error) {
    return (
      <NotFoundPage
        tenantSlug={tenantSlug}
        title="Publication Not Found"
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
      <AboutPage
        tenantSlug={tenantSlug}
        publication={publication}
        branding={branding}
      />
    </PublicLayout>
  );
}
