'use client';

import { useState, useEffect } from 'react';
import { publicEditionService } from '../services/editionService';
import { HomePageData } from '../types/public.types';
import { DEFAULT_BRANDING, DEFAULT_PUBLICATION } from '../constants/defaultBranding';

export function usePublicTenant(tenantSlug: string) {
  const [data, setData] = useState<HomePageData>({
    publication: DEFAULT_PUBLICATION,
    branding: DEFAULT_BRANDING,
    has_editions: false,
    latest_edition: null,
    recent_editions: [],
    categories: [],
    seo: {
      title: 'Digital ePaper',
      description: 'Read high-resolution digital broadsheets online',
      canonical_url: '',
      og_type: 'website',
      og_title: 'Digital ePaper',
      og_description: 'Read high-resolution digital broadsheets online',
      twitter_card: 'summary_large_image',
      twitter_title: 'Digital ePaper',
      twitter_description: 'Read high-resolution digital broadsheets online',
      language: 'en',
    },
  });
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);

  useEffect(() => {
    let isMounted = true;

    async function fetchData() {
      try {
        setLoading(true);
        setError(null);
        const homeData = await publicEditionService.getHomePageData(tenantSlug);
        if (isMounted) {
          setData(homeData);
        }
      } catch (err: any) {
        if (isMounted) {
          setError(err?.response?.data?.message || err?.message || 'Failed to load publication');
        }
      } finally {
        if (isMounted) {
          setLoading(false);
        }
      }
    }

    if (tenantSlug) {
      fetchData();
    }

    return () => {
      isMounted = false;
    };
  }, [tenantSlug]);

  return { data, loading, error };
}
