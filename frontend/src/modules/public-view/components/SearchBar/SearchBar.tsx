'use client';

import React, { useState } from 'react';
import { useRouter } from 'next/navigation';
import { Search, X, Calendar, Filter } from 'lucide-react';
import { PublicCategory } from '../../types/public.types';

interface SearchBarProps {
  tenantSlug: string;
  initialQuery?: string;
  initialCategory?: string;
  initialDate?: string;
  categories?: (string | PublicCategory)[];
  primaryColor?: string;
  onSearch?: (params: { query: string; category?: string; date?: string }) => void;
  inline?: boolean;
}

export const SearchBar: React.FC<SearchBarProps> = ({
  tenantSlug,
  initialQuery = '',
  initialCategory = '',
  initialDate = '',
  categories = [],
  primaryColor = '#1e3a8a',
  onSearch,
  inline = false,
}) => {
  const router = useRouter();
  const [query, setQuery] = useState(initialQuery);
  const [category, setCategory] = useState(initialCategory);
  const [date, setDate] = useState(initialDate);
  const [showFilters, setShowFilters] = useState(Boolean(initialCategory || initialDate));

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

  const handleSubmit = (e: React.FormEvent) => {
    e.preventDefault();
    if (onSearch) {
      onSearch({ query: query.trim(), category: category || undefined, date: date || undefined });
    } else {
      const params = new URLSearchParams();
      if (query.trim()) params.set('q', query.trim());
      if (category) params.set('category', category);
      if (date) params.set('date', date);
      router.push(`/${tenantSlug}/search?${params.toString()}`);
    }
  };

  const handleClear = () => {
    setQuery('');
    setCategory('');
    setDate('');
    if (onSearch) {
      onSearch({ query: '', category: undefined, date: undefined });
    }
  };

  return (
    <form onSubmit={handleSubmit} className="w-full">
      <div className="relative flex flex-col sm:flex-row items-center gap-2">
        {/* Main Search Input */}
        <div className="relative w-full flex-1">
          <Search className="absolute left-3.5 top-1/2 -translate-y-1/2 h-5 w-5 text-slate-400" />
          <input
            type="text"
            value={query}
            onChange={(e) => setQuery(e.target.value)}
            placeholder="Search editions by title, edition code, or keywords..."
            className="w-full rounded-lg border border-slate-300 bg-white py-2.5 pl-11 pr-10 text-sm text-slate-900 placeholder-slate-400 shadow-sm transition focus:border-blue-600 focus:outline-none focus:ring-1 focus:ring-blue-600"
          />
          {query && (
            <button
              type="button"
              onClick={() => setQuery('')}
              className="absolute right-3 top-1/2 -translate-y-1/2 rounded p-1 text-slate-400 hover:text-slate-600 transition"
              aria-label="Clear search"
            >
              <X className="h-4 w-4" />
            </button>
          )}
        </div>

        {/* Filter Toggle Button */}
        <button
          type="button"
          onClick={() => setShowFilters(!showFilters)}
          className={`flex items-center gap-1.5 rounded-lg border px-3.5 py-2.5 text-sm font-medium transition ${
            showFilters || category || date
              ? 'border-blue-300 bg-blue-50 text-blue-700'
              : 'border-slate-300 bg-white text-slate-700 hover:bg-slate-50'
          }`}
        >
          <Filter className="h-4 w-4" />
          <span className="hidden sm:inline">Filters</span>
          {(category || date) && (
            <span className="ml-1 rounded-full bg-blue-600 px-1.5 py-0.2 text-[10px] font-bold text-white">
              {(category ? 1 : 0) + (date ? 1 : 0)}
            </span>
          )}
        </button>

        {/* Search Submit Button */}
        <button
          type="submit"
          className="w-full sm:w-auto rounded-lg px-6 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:opacity-95"
          style={{ backgroundColor: primaryColor }}
        >
          Search
        </button>
      </div>

      {/* Expandable Filters Tray */}
      {showFilters && (
        <div className="mt-3 grid grid-cols-1 sm:grid-cols-2 gap-3 rounded-lg border border-slate-200 bg-slate-50 p-3.5">
          {/* Category Filter */}
          <div>
            <label className="block text-xs font-semibold text-slate-700 mb-1">Category</label>
            <select
              value={category}
              onChange={(e) => setCategory(e.target.value)}
              className="w-full rounded-md border border-slate-300 bg-white py-1.5 px-3 text-sm text-slate-700 focus:border-blue-600 focus:outline-none"
            >
              <option value="">All Categories</option>
              {normalizedCategories.map((cat) => (
                <option key={cat.id} value={cat.slug}>
                  {cat.name} {cat.edition_count > 0 ? `(${cat.edition_count})` : ''}
                </option>
              ))}
            </select>
          </div>

          {/* Publication Date Filter */}
          <div>
            <label className="block text-xs font-semibold text-slate-700 mb-1">
              Publication Date
            </label>
            <div className="relative">
              <input
                type="date"
                value={date}
                onChange={(e) => setDate(e.target.value)}
                className="w-full rounded-md border border-slate-300 bg-white py-1.5 px-3 text-sm text-slate-700 focus:border-blue-600 focus:outline-none"
              />
            </div>
          </div>

          {(category || date) && (
            <div className="sm:col-span-2 flex justify-end">
              <button
                type="button"
                onClick={handleClear}
                className="text-xs font-medium text-slate-500 hover:text-red-600 transition"
              >
                Reset Filters
              </button>
            </div>
          )}
        </div>
      )}
    </form>
  );
};
