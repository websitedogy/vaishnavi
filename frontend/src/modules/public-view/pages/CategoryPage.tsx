'use client';

import React from 'react';
import { Breadcrumb } from '@/modules/public-view/components/Breadcrumb/Breadcrumb';
import { NewspaperGrid } from '@/modules/public-view/components/NewspaperGrid/NewspaperGrid';
import { CategoryNav } from '@/modules/public-view/components/CategoryNavigation/CategoryNav';
import { PublicCategory, PublicEditionCard, PaginationMeta } from '@/modules/public-view/types/public.types';
import { Tag } from 'lucide-react';
import Link from 'next/link';
import { ChevronLeft, ChevronRight } from 'lucide-react';

interface CategoryPageProps {
  tenantSlug: string;
  category: PublicCategory;
  allCategories: (string | PublicCategory)[];
  editions: PublicEditionCard[];
  pagination: PaginationMeta;
  primaryColor?: string;
}

export const CategoryPage: React.FC<CategoryPageProps> = ({
  tenantSlug,
  category,
  allCategories,
  editions,
  pagination,
  primaryColor = '#1e3a8a',
}) => {
  const breadcrumbItems = [
    { label: 'All Editions', href: `/${tenantSlug}/editions` },
    { label: category.name, active: true },
  ];

  const hasPrev = pagination.current_page > 1;
  const hasNext = pagination.current_page < pagination.last_page;

  return (
    <div className="space-y-6">
      <Breadcrumb tenantSlug={tenantSlug} items={breadcrumbItems} />

      {/* Category Navigation Pills */}
      <CategoryNav
        tenantSlug={tenantSlug}
        categories={allCategories}
        activeCategorySlug={category.slug}
        primaryColor={primaryColor}
      />

      <div className="mx-auto max-w-7xl px-4 space-y-6">
        {/* Category Header */}
        <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-2 border-b border-slate-200 pb-4">
          <div>
            <div className="flex items-center gap-2">
              <span
                className="rounded-lg p-2 text-white"
                style={{ backgroundColor: primaryColor }}
              >
                <Tag className="h-5 w-5" />
              </span>
              <div>
                <h1 className="text-xl md:text-2xl font-bold text-slate-900 font-masthead">
                  {category.name} Editions
                </h1>
                <p className="text-xs text-slate-500">
                  {category.description || `Browse digital editions published under ${category.name}.`}
                </p>
              </div>
            </div>
          </div>
          <div className="text-xs text-slate-500 font-medium">
            {pagination.total} Published Edition{pagination.total === 1 ? '' : 's'}
          </div>
        </div>

        {/* Editions Grid */}
        <NewspaperGrid
          tenantSlug={tenantSlug}
          editions={editions}
          primaryColor={primaryColor}
          emptyMessage={`No published editions found in ${category.name}.`}
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
                href={`/${tenantSlug}/category/${category.slug}?page=${pagination.current_page - 1}`}
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
                href={`/${tenantSlug}/category/${category.slug}?page=${pagination.current_page + 1}`}
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
