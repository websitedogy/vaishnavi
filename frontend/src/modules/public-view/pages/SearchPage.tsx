'use client';

import React from 'react';
import { Breadcrumb } from '@/modules/public-view/components/Breadcrumb/Breadcrumb';
import { SearchBar } from '@/modules/public-view/components/SearchBar/SearchBar';
import { NewspaperGrid } from '@/modules/public-view/components/NewspaperGrid/NewspaperGrid';
import { SearchResultsData, PublicCategory } from '@/modules/public-view/types/public.types';
import { Search as SearchIcon, Info, SlidersHorizontal } from 'lucide-react';

interface SearchPageProps {
  tenantSlug: string;
  query?: string;
  category?: string;
  date?: string;
  categories: (string | PublicCategory)[];
  searchResults: SearchResultsData;
  primaryColor?: string;
}

export const SearchPage: React.FC<SearchPageProps> = ({
  tenantSlug,
  query = '',
  category = '',
  date = '',
  categories,
  searchResults,
  primaryColor = '#1e3a8a',
}) => {
  const breadcrumbItems = [
    { label: 'Search Editions', active: true },
  ];

  const hasSearched = Boolean(query || category || date);
  const editionsList = searchResults.editions || searchResults.results || [];
  const totalCount = searchResults.total ?? editionsList.length;

  return (
    <div className="space-y-6">
      <Breadcrumb tenantSlug={tenantSlug} items={breadcrumbItems} />

      <div className="mx-auto max-w-7xl px-4 space-y-8">
        {/* Header */}
        <div className="border-b border-slate-200 pb-4">
          <h1 className="text-xl md:text-2xl font-bold text-slate-900 font-masthead flex items-center gap-2">
            <SearchIcon className="h-6 w-6 text-blue-600" />
            <span>Search Newspaper Editions</span>
          </h1>
          <p className="text-xs text-slate-500 mt-1">
            Search published ePaper editions by title, edition code, category, or publication date
          </p>
        </div>

        {/* Search Bar & Filters Card */}
        <div className="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
          <SearchBar
            tenantSlug={tenantSlug}
            initialQuery={query}
            initialCategory={category}
            initialDate={date}
            categories={categories}
            primaryColor={primaryColor}
            inline
          />

          {/* Technical Search Notice */}
          <div className="mt-4 flex items-start gap-2 rounded-lg bg-slate-50 p-3 text-xs text-slate-600 border border-slate-200">
            <Info className="h-4 w-4 text-slate-400 shrink-0 mt-0.5" />
            <p>
              Metadata Search: Searches published edition titles, edition identifiers, publication dates,
              and categories. Page broadsheet images are preserved in high-resolution vector/raster formats.
            </p>
          </div>
        </div>

        {/* Results Section */}
        {hasSearched ? (
          <div className="space-y-4">
            <div className="flex items-center justify-between border-b border-slate-200 pb-2">
              <h2 className="text-sm font-bold text-slate-800">
                Found {totalCount} matching edition{totalCount === 1 ? '' : 's'}
              </h2>
              {query && (
                <span className="text-xs text-slate-500">
                  for query: <strong className="text-slate-800">"{query}"</strong>
                </span>
              )}
            </div>

            <NewspaperGrid
              tenantSlug={tenantSlug}
              editions={editionsList}
              primaryColor={primaryColor}
              emptyMessage={`No editions matched your search criteria. Try broadening your keywords or clearing the category and date filters.`}
            />
          </div>
        ) : (
          <div className="rounded-xl border border-slate-200 bg-white p-12 text-center text-slate-500">
            <SlidersHorizontal className="mx-auto h-10 w-10 text-slate-300 mb-3" />
            <p className="text-sm font-semibold text-slate-700">Enter a keyword or choose a date to search</p>
            <p className="text-xs text-slate-400 mt-1">
              You can search by edition name (e.g. "Main", "District", "Sunday") or date.
            </p>
          </div>
        )}
      </div>
    </div>
  );
};
