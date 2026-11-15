'use client';

import React from 'react';
import Link from 'next/link';
import { HomePageData } from '@/modules/public-view/types/public.types';
import { LatestEditionHero } from '@/modules/public-view/components/LatestEdition/LatestEditionHero';
import { NewspaperGrid } from '@/modules/public-view/components/NewspaperGrid/NewspaperGrid';
import { CategoryNav } from '@/modules/public-view/components/CategoryNavigation/CategoryNav';
import { SearchBar } from '@/modules/public-view/components/SearchBar/SearchBar';
import { Calendar, ArrowRight, Layers, Sparkles } from 'lucide-react';

interface HomePageProps {
  tenantSlug: string;
  data: HomePageData;
}

export const HomePage: React.FC<HomePageProps> = ({ tenantSlug, data }) => {
  const { publication, branding, latest_edition, recent_editions, categories } = data;

  return (
    <div className="space-y-8 md:space-y-12">
      {/* Category Pills Bar */}
      {categories && categories.length > 0 && (
        <CategoryNav
          tenantSlug={tenantSlug}
          categories={categories}
          primaryColor={branding.primary_color}
        />
      )}

      <div className="mx-auto max-w-7xl px-4 space-y-10">
        {/* Quick Search & Archive Shortcut Bar */}
        <section className="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
          <div className="flex flex-col md:flex-row items-center justify-between gap-4">
            <div className="w-full md:max-w-xl">
              <SearchBar
                tenantSlug={tenantSlug}
                categories={categories}
                primaryColor={branding.primary_color}
              />
            </div>
            <div className="flex items-center space-x-3 w-full md:w-auto justify-end">
              <Link
                href={`/${tenantSlug}/archive`}
                className="flex items-center gap-1.5 rounded-lg border border-slate-200 px-3 py-2 text-xs font-semibold text-slate-700 hover:bg-slate-50 transition"
              >
                <Calendar className="h-4 w-4 text-slate-500" />
                <span>Browse Calendar Archive</span>
              </Link>
            </div>
          </div>
        </section>

        {/* Hero Section: Latest Published Edition */}
        <section>
          <div className="flex items-center justify-between mb-4">
            <h2 className="flex items-center gap-2 text-lg md:text-xl font-bold text-slate-900 font-masthead">
              <Sparkles className="h-5 w-5 text-amber-500" />
              <span>Today's ePaper Edition</span>
            </h2>
            <Link
              href={`/${tenantSlug}/editions`}
              className="flex items-center gap-1 text-xs font-semibold text-blue-600 hover:text-blue-800 transition"
            >
              <span>View All Editions</span>
              <ArrowRight className="h-3.5 w-3.5" />
            </Link>
          </div>

          <LatestEditionHero
            tenantSlug={tenantSlug}
            latestEdition={latest_edition}
            newspaperName={branding.newspaper_name}
            primaryColor={branding.primary_color}
          />
        </section>

        {/* Recent Editions Grid */}
        <section className="pt-4">
          <div className="flex items-center justify-between mb-6 border-b border-slate-200 pb-3">
            <div>
              <h2 className="text-lg md:text-xl font-bold text-slate-900 font-masthead">
                Recent Publications
              </h2>
              <p className="text-xs text-slate-500 mt-0.5">
                Catch up on previous digital broadsheet editions
              </p>
            </div>
            <Link
              href={`/${tenantSlug}/editions`}
              className="flex items-center gap-1 text-xs font-semibold text-slate-600 hover:text-slate-900 transition"
            >
              <span>More Editions ({recent_editions?.length || 0})</span>
              <ArrowRight className="h-3.5 w-3.5" />
            </Link>
          </div>

          <NewspaperGrid
            tenantSlug={tenantSlug}
            editions={recent_editions || []}
            primaryColor={branding.primary_color}
            emptyMessage="No recent editions available. Check back soon for upcoming digital publications."
          />
        </section>

        {/* Publisher Information Callout */}
        <section className="rounded-2xl border border-slate-200 bg-gradient-to-r from-slate-900 to-slate-800 p-6 md:p-8 text-white shadow-sm">
          <div className="flex flex-col md:flex-row items-start md:items-center justify-between gap-6">
            <div className="space-y-2 max-w-2xl">
              <span className="text-xs font-semibold uppercase tracking-widest text-blue-400">
                Official Digital Broadsheet
              </span>
              <h3 className="text-xl md:text-2xl font-bold font-masthead">
                {branding.newspaper_name} Digital Experience
              </h3>
              <p className="text-xs md:text-sm text-slate-300 leading-relaxed">
                Experience high-fidelity newspaper reading on desktop, tablet, and mobile.
                Read crisp broadsheet pages, zoom into columns and stories, and explore historical archives.
              </p>
            </div>
            <div className="flex flex-wrap gap-3">
              <Link
                href={`/${tenantSlug}/epaper`}
                className="rounded-lg px-4 py-2.5 text-xs font-semibold text-slate-900 bg-white hover:bg-slate-100 shadow transition"
              >
                Read Latest ePaper
              </Link>
              <Link
                href={`/${tenantSlug}/about`}
                className="rounded-lg border border-slate-700 bg-slate-800/80 px-4 py-2.5 text-xs font-semibold text-slate-200 hover:bg-slate-700 hover:text-white transition"
              >
                About Publication
              </Link>
            </div>
          </div>
        </section>
      </div>
    </div>
  );
};
