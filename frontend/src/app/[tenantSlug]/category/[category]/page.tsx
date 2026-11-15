'use client';

import React, { useState, useEffect } from 'react';
import { useParams, useSearchParams } from 'next/navigation';
import { publicEditionService } from '@/modules/public-view/services/editionService';
import { PublicLayout } from '@/modules/public-view/layouts/PublicLayout';
import { CategoryPage } from '@/modules/public-view/pages/CategoryPage';
import { NotFoundPage } from '@/modules/public-view/pages/NotFoundPage';
import { PageGridSkeleton } from '@/modules/public-view/components/LoadingStates/LoadingStates';
import { DEFAULT_BRANDING, DEFAULT_PUBLICATION } from '@/modules/public-view/constants/defaultBranding';
import { PublicEditionCard, PublicCategory, PaginationMeta, PublicBranding, PublicPublication } from '@/modules/public-view/types/public.types';

export default function TenantCategoryRoute() {
  const params = useParams();
  const searchParams = useSearchParams();
  const tenantSlug = typeof params?.tenantSlug === 'string' ? params.tenantSlug : '';
  const categorySlug = typeof params?.category === 'string' ? params.category : '';
  const page = parseInt(searchParams?.get('page') || '1', 10);

  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);
  const [editions, setEditions] = useState<PublicEditionCard[]>([]);
  const [allCategories, setAllCategories] = useState<PublicCategory[]>([]);
  const [currentCategory, setCurrentCategory] = useState<PublicCategory>({
    id: 0,
    name: categorySlug,
    slug: categorySlug,
    edition_count: 0,
  });
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
    async function loadCategoryEditions() {
      try {
        setLoading(true);
        setError(null);

        const [editionsRes, homeData] = await Promise.all([
          publicEditionService.getEditions(tenantSlug, page, 12, categorySlug),
          publicEditionService.getHomePageData(tenantSlug).catch(() => null),
        ]);

        if (isMounted) {
          setEditions(editionsRes.data);
          setPagination(editionsRes.meta);
          if (homeData) {
            setBranding(homeData.branding);
            setPublication(homeData.publication);
            const cats = (homeData.categories as any[]) || [];
            setAllCategories(cats);
            const found = cats.find((c: any) =>
              typeof c === 'string'
                ? c.toLowerCase().replace(/\s+/g, '-') === categorySlug
                : c.slug === categorySlug
            );
            if (found) {
              if (typeof found === 'string') {
                setCurrentCategory({
                  id: 1,
                  name: found,
                  slug: categorySlug,
                  edition_count: editionsRes.meta?.total || 0,
                });
              } else {
                setCurrentCategory(found);
              }
            }
          }
        }
      } catch (err: any) {
        if (isMounted) {
          setError(err?.response?.data?.message || err?.message || 'Failed to load category editions');
        }
      } finally {
        if (isMounted) {
          setLoading(false);
        }
      }
    }

    if (tenantSlug && categorySlug) {
      loadCategoryEditions();
    }

    return () => {
      isMounted = false;
    };
  }, [tenantSlug, categorySlug, page]);

  if (loading) {
    return (
      <div className="min-h-screen bg-slate-50 p-6">
        <div className="mx-auto max-w-7xl space-y-6">
          <div className="h-8 w-64 bg-slate-200 rounded animate-pulse" />
          <PageGridSkeleton count={8} />
        </div>
      </div>
    );
  }

  if (error) {
    return (
      <NotFoundPage
        tenantSlug={tenantSlug}
        title="Category Not Found"
        message={error}
      />
    );
  }

  return (
    <PublicLayout
      tenantSlug={tenantSlug}
      publication={publication}
      branding={branding}
      categories={allCategories}
    >
      <CategoryPage
        tenantSlug={tenantSlug}
        category={currentCategory}
        allCategories={allCategories}
        editions={editions}
        pagination={pagination}
        primaryColor={branding.primary_color}
      />
    </PublicLayout>
  );
}
