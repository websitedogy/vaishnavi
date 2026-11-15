'use client';

import React from 'react';
import Link from 'next/link';
import { PublicCategory } from '../../types/public.types';
import { Tag } from 'lucide-react';

interface CategoryNavProps {
  tenantSlug: string;
  categories: (string | PublicCategory)[];
  activeCategorySlug?: string;
  primaryColor?: string;
  showAllOption?: boolean;
}

export const CategoryNav: React.FC<CategoryNavProps> = ({
  tenantSlug,
  categories,
  activeCategorySlug,
  primaryColor = '#1e3a8a',
  showAllOption = true,
}) => {
  if (!categories || categories.length === 0) {
    return null;
  }

  const normalizedCategories: PublicCategory[] = categories.map((cat, idx) => {
    if (typeof cat === 'string') {
      return {
        id: idx + 1,
        name: cat,
        slug: cat.toLowerCase().replace(/\s+/g, '-'),
        edition_count: 0,
      };
    }
    return cat;
  });

  const isAllActive = !activeCategorySlug;

  return (
    <div className="border-b border-slate-200 bg-slate-50/70 py-2.5">
      <div className="mx-auto flex max-w-7xl items-center px-4">
        <div className="mr-3 flex items-center gap-1.5 text-xs font-semibold uppercase tracking-wider text-slate-500">
          <Tag className="h-3.5 w-3.5" />
          <span>Categories:</span>
        </div>

        <div className="flex flex-1 items-center space-x-2 overflow-x-auto scrollbar-none py-0.5">
          {showAllOption && (
            <Link
              href={`/${tenantSlug}/editions`}
              className={`rounded-full px-3.5 py-1 text-xs font-medium transition ${
                isAllActive
                  ? 'bg-slate-900 text-white shadow-sm'
                  : 'bg-white text-slate-700 border border-slate-200 hover:bg-slate-100 hover:text-slate-900'
              }`}
            >
              All Editions
            </Link>
          )}

          {normalizedCategories.map((category) => {
            const isActive = activeCategorySlug === category.slug;
            return (
              <Link
                key={category.id}
                href={`/${tenantSlug}/category/${category.slug}`}
                className={`flex items-center gap-1.5 whitespace-nowrap rounded-full px-3.5 py-1 text-xs font-medium transition ${
                  isActive
                    ? 'text-white shadow-sm'
                    : 'bg-white text-slate-700 border border-slate-200 hover:bg-slate-100 hover:text-slate-900'
                }`}
                style={isActive ? { backgroundColor: primaryColor } : undefined}
              >
                <span>{category.name}</span>
                {category.edition_count > 0 && (
                  <span
                    className={`rounded-full px-1.5 py-0.2 text-[10px] font-semibold ${
                      isActive ? 'bg-white/20 text-white' : 'bg-slate-100 text-slate-600'
                    }`}
                  >
                    {category.edition_count}
                  </span>
                )}
              </Link>
            );
          })}
        </div>
      </div>
    </div>
  );
};
