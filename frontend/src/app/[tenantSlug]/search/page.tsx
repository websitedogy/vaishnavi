'use client';

import React, { useState, useEffect } from 'react';
import { useParams, useSearchParams } from 'next/navigation';
import { publicSearchService } from '@/modules/public-view/services/searchService';
import { publicEditionService } from '@/modules/public-view/services/editionService';
import { PublicLayout } from '@/modules/public-view/layouts/PublicLayout';
import { SearchPage } from '@/modules/public-view/pages/SearchPage';
import { PageGridSkeleton } from '@/modules/public-view/components/LoadingStates/LoadingStates';
import { DEFAULT_BRANDING, DEFAULT_PUBLICATION } from '@/modules/public-view/constants/defaultBranding';
import { SearchResultData, PublicBranding, PublicPublication, PublicCategory } from '@/modules/public-view/types/public.types';

export default function TenantSearchRoute() {
  const params = useParams();
  const searchParams = useSearchParams();
  const tenantSlug = typeof params?.tenantSlug === 'string' ? params.tenantSlug : '';

  const query = searchParams?.get('q') || '';
  const category = searchParams?.get('category') || '';
  const date = searchParams?.get('date') || '';

  const [loading, setLoading] = useState(false);
  const [searchResults, setSearchResults] = useState<SearchResultData>({
    query: query,
    search_mode: 'metadata',
    results: [],
    total: 0,
    editions: [],
  });
  const [branding, setBranding] = useState<PublicBranding>(DEFAULT_BRANDING);
  const [publication, setPublication] = useState<PublicPublication>(DEFAULT_PUBLICATION);
  const [categories, setCategories] = useState<PublicCategory[]>([]);

  useEffect(() => {
    let isMounted = true;
    async function initData() {
      try {
        const homeData = await publicEditionService.getHomePageData(tenantSlug).catch(() => null);
        if (isMounted && homeData) {
          setBranding(homeData.branding);
          setPublication(homeData.publication);
          setCategories(homeData.categories as any);
        }
      } catch (err) {
        // Fallback
      }
    }
    if (tenantSlug) {
      initData();
    }
    return () => {
      isMounted = false;
    };
  }, [tenantSlug]);

  useEffect(() => {
    let isMounted = true;
    async function executeSearch() {
      if (!query && !category && !date) {
        setSearchResults({ query: '', search_mode: 'metadata', results: [], total: 0, editions: [] });
        return;
      }

      try {
        setLoading(true);
        const res = await publicSearchService.searchEditions(tenantSlug, {
          query: query || undefined,
          category: category || undefined,
          date: date || undefined,
        });

        if (isMounted) {
          setSearchResults(res);
        }
      } catch (err) {
        if (isMounted) {
          setSearchResults({ query, search_mode: 'metadata', results: [], total: 0, editions: [] });
        }
      } finally {
        if (isMounted) {
          setLoading(false);
        }
      }
    }

    if (tenantSlug) {
      executeSearch();
    }

    return () => {
      isMounted = false;
    };
  }, [tenantSlug, query, category, date]);

  return (
    <PublicLayout
      tenantSlug={tenantSlug}
      publication={publication}
      branding={branding}
      categories={categories}
    >
      {loading ? (
        <div className="mx-auto max-w-7xl px-4 py-8 space-y-6">
          <div className="h-20 w-full bg-slate-200 rounded-xl animate-pulse" />
          <PageGridSkeleton count={8} />
        </div>
      ) : (
        <SearchPage
          tenantSlug={tenantSlug}
          query={query}
          category={category}
          date={date}
          categories={categories}
          searchResults={searchResults}
          primaryColor={branding.primary_color}
        />
      )}
    </PublicLayout>
  );
}
