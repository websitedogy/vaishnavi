'use client';

import React from 'react';
import Link from 'next/link';
import { PublicManifest } from '../../types/manifest.types';
import { formatDate } from '../../utils/formatters';
import { resolvePublicImageUrl } from '../../utils/security';
import { BookOpen, Calendar, Layers, Sparkles, ArrowRight, Clock } from 'lucide-react';

interface LatestEditionHeroProps {
  tenantSlug: string;
  manifest?: PublicManifest | null;
  latestEdition?: PublicManifest | null;
  newspaperName?: string;
  primaryColor?: string;
  secondaryColor?: string;
}

export const LatestEditionHero: React.FC<LatestEditionHeroProps> = ({
  tenantSlug,
  manifest,
  latestEdition,
  newspaperName = 'Digital ePaper',
  primaryColor = '#1E3A8A',
  secondaryColor = '#0284C7',
}) => {
  const activeManifest = manifest || latestEdition;

  if (!activeManifest) {
    return (
      <section className="relative overflow-hidden rounded-2xl border border-slate-200 bg-gradient-to-br from-slate-900 via-slate-800 to-slate-900 text-white shadow-xl p-8 md:p-12 text-center">
        <Clock className="mx-auto h-12 w-12 text-amber-400 mb-4 opacity-80" />
        <h3 className="text-xl md:text-2xl font-bold font-masthead mb-2">No Published Editions Yet</h3>
        <p className="text-xs md:text-sm text-slate-300 max-w-md mx-auto mb-6">
          {newspaperName} will publish its digital ePaper broadsheets shortly. Check back early morning for the daily issue.
        </p>
        <Link
          href={`/${tenantSlug}/archive`}
          className="inline-flex items-center gap-2 rounded-lg bg-blue-600 px-5 py-2.5 text-xs font-semibold text-white hover:bg-blue-500 transition shadow"
        >
          <Calendar className="h-4 w-4" />
          <span>Browse Publication Archive</span>
        </Link>
      </section>
    );
  }

  const edition = activeManifest.edition;
  const pages = activeManifest.pages || [];
  const frontPage = pages.find((p) => p.page_number === 1) || pages[0];
  const frontPageImage = resolvePublicImageUrl(frontPage?.image_url || frontPage?.thumbnail_url || edition.thumbnail_url);
  const formattedDate = formatDate(edition.publish_date, 'full');

  return (
    <section className="relative overflow-hidden rounded-2xl border border-slate-200 bg-gradient-to-br from-slate-900 via-slate-800 to-slate-900 text-white shadow-xl">
      {/* Decorative background glow */}
      <div
        className="absolute -top-32 -right-32 h-96 w-96 rounded-full opacity-20 blur-3xl pointer-events-none"
        style={{ backgroundColor: secondaryColor }}
      />

      <div className="relative mx-auto grid max-w-7xl grid-cols-1 gap-8 p-6 md:p-10 lg:grid-cols-12 lg:items-center">
        {/* Left Column: Edition Metadata & Actions */}
        <div className="space-y-6 lg:col-span-7">
          <div className="flex flex-wrap items-center gap-2">
            <span className="flex items-center gap-1.5 rounded-full bg-blue-500/20 border border-blue-400/30 px-3 py-1 text-xs font-semibold text-blue-300">
              <Sparkles className="h-3.5 w-3.5" />
              Latest Published Edition
            </span>
            <span className="rounded-full bg-white/10 px-3 py-1 text-xs font-medium text-slate-300">
              {edition.category || 'Main Edition'}
            </span>
            <span className="rounded-full bg-white/10 px-3 py-1 text-xs font-mono text-slate-300">
              {edition.edition_code}
            </span>
          </div>

          <div className="space-y-2">
            <h1 className="font-masthead text-3xl md:text-4xl font-extrabold tracking-tight text-white">
              {edition.edition_name}
            </h1>
            <p className="flex items-center gap-2 text-sm text-slate-300">
              <Calendar className="h-4 w-4 text-blue-400" />
              <span>{formattedDate}</span>
              <span className="text-slate-500">•</span>
              <Layers className="h-4 w-4 text-blue-400" />
              <span>{edition.total_pages} Pages Broadsheet</span>
            </p>
          </div>

          {edition.description && (
            <p className="text-xs md:text-sm text-slate-300 line-clamp-2 max-w-xl">
              {edition.description}
            </p>
          )}

          {/* Action CTAs */}
          <div className="flex flex-wrap items-center gap-3 pt-2">
            <Link
              href={`/${tenantSlug}/epaper/${edition.edition_code}`}
              className="flex items-center gap-2 rounded-xl px-6 py-3 text-sm font-bold text-white shadow-lg transition duration-200 hover:scale-105 hover:opacity-95"
              style={{ backgroundColor: primaryColor }}
            >
              <BookOpen className="h-4 w-4" />
              <span>Read Digital ePaper Now</span>
              <ArrowRight className="h-4 w-4" />
            </Link>

            <Link
              href={`/${tenantSlug}/archive`}
              className="flex items-center gap-2 rounded-xl border border-slate-700 bg-slate-800/80 px-4 py-3 text-sm font-semibold text-slate-200 backdrop-blur transition hover:bg-slate-700 hover:text-white"
            >
              <Layers className="h-4 w-4 text-slate-400" />
              <span>Browse Archive</span>
            </Link>
          </div>
        </div>

        {/* Right Column: Front Page High-Res Preview */}
        <div className="lg:col-span-5 flex justify-center">
          <Link
            href={`/${tenantSlug}/epaper/${edition.edition_code}`}
            className="group relative block w-full max-w-sm overflow-hidden rounded-xl bg-white p-2 shadow-2xl transition duration-300 hover:scale-[1.02]"
          >
            <div className="relative aspect-[3/4] w-full overflow-hidden rounded-lg bg-slate-100">
              {frontPageImage ? (
                <img
                  src={frontPageImage}
                  alt={`Front Page of ${edition.edition_name}`}
                  className="h-full w-full object-cover object-top transition duration-500 group-hover:scale-105"
                />
              ) : (
                <div className="flex h-full w-full items-center justify-center bg-slate-200 text-slate-400">
                  <BookOpen className="h-16 w-16 stroke-1 text-slate-400" />
                </div>
              )}

              {/* Hover overlay */}
              <div className="absolute inset-0 flex items-center justify-center bg-slate-900/50 opacity-0 group-hover:opacity-100 transition duration-200">
                <span className="flex items-center gap-2 rounded-full bg-white px-4 py-2 text-sm font-semibold text-slate-900 shadow">
                  <BookOpen className="h-4 w-4 text-blue-700" />
                  Click to Read
                </span>
              </div>
            </div>
            <div className="pt-2 text-center text-xs font-semibold text-slate-700">
              Page 1 • {frontPage?.page_title || 'Main Front Page'}
            </div>
          </Link>
        </div>
      </div>
    </section>
  );
};
