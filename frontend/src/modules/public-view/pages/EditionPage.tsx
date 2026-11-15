'use client';

import React from 'react';
import Link from 'next/link';
import { PublicEditionCard, PublicCategory, PaginationMeta } from '@/modules/public-view/types/public.types';
import { NewspaperGrid } from '@/modules/public-view/components/NewspaperGrid/NewspaperGrid';
import { CategoryNav } from '@/modules/public-view/components/CategoryNavigation/CategoryNav';
import { Breadcrumb } from '@/modules/public-view/components/Breadcrumb/Breadcrumb';
import { ChevronLeft, ChevronRight, Layers } from 'lucide-react';

interface EditionPageProps {
  tenantSlug: string;
  editions: PublicEditionCard[];
  pagination: PaginationMeta;
  categories: (string | PublicCategory)[];
  primaryColor?: string;
  activeCategory?: string;
}

export const EditionPage: React.FC<EditionPageProps> = ({
  tenantSlug,
  editions,
  pagination,
  categories,
  primaryColor = '#1e3a8a',
  activeCategory,
}) => {
  const breadcrumbItems = [
    { label: 'All Editions', href: `/${tenantSlug}/editions`, active: !activeCategory },
    ...(activeCategory
      ? [{ label: activeCategory, active: true }]
      : []),
  ];

  const hasPrev = pagination.current_page > 1;
  const hasNext = pagination.current_page < pagination.last_page;

  return (
    <div className="space-y-6">
      <Breadcrumb tenantSlug={tenantSlug} items={breadcrumbItems} />

      {/* Category Pills Navigation */}
      {categories.length > 0 && (
        <CategoryNav
          tenantSlug={tenantSlug}
          categories={categories}
          activeCategorySlug={activeCategory}
          primaryColor={primaryColor}
        />
      )}

      <div className="mx-auto max-w-7xl px-4 space-y-6">
        {/* Page Header */}
        <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-2 border-b border-slate-200 pb-4">
          <div>
            <h1 className="text-xl md:text-2xl font-bold text-slate-900 font-masthead flex items-center gap-2">
              <Layers className="h-6 w-6 text-blue-600" />
              <span>Published Newspaper Editions</span>
            </h1>
            <p className="text-xs text-slate-500 mt-1">
              Showing {editions.length} of {pagination.total} published digital editions
            </p>
          </div>
        </div>

        {/* Editions Grid */}
        <NewspaperGrid
          tenantSlug={tenantSlug}
          editions={editions}
          primaryColor={primaryColor}
          emptyMessage="No editions found in this section."
        />

        {/* Pagination Bar */}
        {pagination.last_page > 1 && (
          <div className="flex items-center justify-between border-t border-slate-200 pt-6">
            <div className="text-xs text-slate-500">
              Page <span className="font-semibold text-slate-900">{pagination.current_page}</span> of{' '}
              <span className="font-semibold text-slate-900">{pagination.last_page}</span>
            </div>

            <div className="flex items-center space-x-2">
              <Link
                href={`/${tenantSlug}/editions?page=${pagination.current_page - 1}${
                  activeCategory ? `&category=${activeCategory}` : ''
                }`}
                aria-disabled={!hasPrev}
                className={`flex items-center gap-1 rounded-lg border border-slate-200 px-3 py-1.5 text-xs font-semibold transition ${
                  hasPrev
                    ? 'bg-white text-slate-700 hover:bg-slate-50 shadow-sm'
                    : 'pointer-events-none opacity-40 bg-slate-100 text-slate-400'
                }`}
              >
                <ChevronLeft className="h-4 w-4" />
                <span>Previous</span>
              </Link>

              <Link
                href={`/${tenantSlug}/editions?page=${pagination.current_page + 1}${
                  activeCategory ? `&category=${activeCategory}` : ''
                }`}
                aria-disabled={!hasNext}
                className={`flex items-center gap-1 rounded-lg border border-slate-200 px-3 py-1.5 text-xs font-semibold transition ${
                  hasNext
                    ? 'bg-white text-slate-700 hover:bg-slate-50 shadow-sm'
                    : 'pointer-events-none opacity-40 bg-slate-100 text-slate-400'
                }`}
              >
                <span>Next</span>
                <ChevronRight className="h-4 w-4" />
              </Link>
            </div>
          </div>
        )}
      </div>
    </div>
  );
};
