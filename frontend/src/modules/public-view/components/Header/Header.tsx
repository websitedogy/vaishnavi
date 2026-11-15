'use client';

import React, { useState } from 'react';
import Link from 'next/link';
import { PublicBranding, PublicPublication } from '../../types/public.types';
import { formatDate, formatTeluguDate } from '../../utils/formatters';
import { resolvePublicImageUrl } from '../../utils/security';
import { Search, Calendar, BookOpen, Menu, X, ArrowRight } from 'lucide-react';

interface HeaderProps {
  tenantSlug: string;
  publication: PublicPublication;
  branding: PublicBranding;
  latestEditionCode?: string;
  publishDate?: string;
}

export const Header: React.FC<HeaderProps> = ({
  tenantSlug,
  publication,
  branding,
  latestEditionCode,
  publishDate,
}) => {
  const [mobileMenuOpen, setMobileMenuOpen] = useState(false);
  const logoUrl = resolvePublicImageUrl(branding.logo_url);
  const formattedDate = formatDate(publishDate || new Date().toISOString(), 'full');
  const teluguDate = formatTeluguDate(publishDate || new Date().toISOString());

  return (
    <header className="border-b border-slate-200 bg-white">
      {/* Top Utility Bar (Date, Tagline, Quick Actions) */}
      <div className="border-b border-slate-100 bg-slate-50 py-1.5 px-4 text-xs text-slate-600">
        <div className="mx-auto flex max-w-7xl items-center justify-between">
          <div className="flex items-center space-x-3">
            <span className="flex items-center gap-1 font-medium text-slate-700">
              <Calendar className="h-3.5 w-3.5 text-slate-500" />
              {formattedDate}
            </span>
            {publication.default_language === 'te' && (
              <span className="hidden sm:inline border-l border-slate-200 pl-3 text-slate-500 font-medium">
                {teluguDate}
              </span>
            )}
          </div>
          <div className="flex items-center space-x-4">
            <span className="hidden md:inline text-slate-500 italic">
              {publication.tagline || 'Leading Digital Newspaper'}
            </span>
            <Link
              href={`/${tenantSlug}/archive`}
              className="font-medium text-slate-700 hover:text-blue-700 transition"
            >
              Edition Archive
            </Link>
          </div>
        </div>
      </div>

      {/* Main Masthead Banner */}
      <div className="mx-auto flex max-w-7xl items-center justify-between px-4 py-4 md:py-6">
        {/* Brand / Logo */}
        <Link href={`/${tenantSlug}`} className="flex items-center gap-3 group">
          {logoUrl ? (
            <img
              src={logoUrl}
              alt={branding.newspaper_name}
              className="h-12 md:h-16 max-w-[240px] object-contain"
            />
          ) : (
            <div className="flex flex-col">
              <span
                className="font-masthead text-2xl md:text-4xl font-extrabold tracking-tight group-hover:opacity-90 transition"
                style={{ color: branding.primary_color }}
              >
                {branding.newspaper_name}
              </span>
              <span className="text-[10px] md:text-xs font-semibold uppercase tracking-widest text-slate-500">
                Digital ePaper Edition
              </span>
            </div>
          )}
        </Link>

        {/* Desktop Actions */}
        <div className="hidden md:flex items-center gap-3">
          <Link
            href={`/${tenantSlug}/search`}
            className="flex items-center gap-2 rounded-lg border border-slate-200 px-3 py-2 text-sm text-slate-600 hover:bg-slate-50 transition"
          >
            <Search className="h-4 w-4 text-slate-400" />
            <span>Search Editions</span>
          </Link>

          <Link
            href={`/${tenantSlug}/epaper`}
            className="flex items-center gap-2 rounded-lg px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:opacity-95"
            style={{ backgroundColor: branding.primary_color }}
          >
            <BookOpen className="h-4 w-4" />
            <span>Read Today's ePaper</span>
            <ArrowRight className="h-3.5 w-3.5" />
          </Link>
        </div>

        {/* Mobile Menu Button */}
        <button
          type="button"
          onClick={() => setMobileMenuOpen(!mobileMenuOpen)}
          className="md:hidden rounded-lg p-2 text-slate-600 hover:bg-slate-100"
          aria-label="Toggle navigation menu"
        >
          {mobileMenuOpen ? <X className="h-6 w-6" /> : <Menu className="h-6 w-6" />}
        </button>
      </div>

      {/* Desktop Main Navigation Bar */}
      <nav className="border-t border-slate-100 bg-white">
        <div className="mx-auto flex max-w-7xl items-center justify-between px-4">
          <ul className="flex items-center space-x-1 md:space-x-6 text-sm font-medium text-slate-700 py-2.5">
            <li>
              <Link href={`/${tenantSlug}`} className="hover:text-blue-700 transition">
                Home
              </Link>
            </li>
            <li>
              <Link href={`/${tenantSlug}/epaper`} className="hover:text-blue-700 transition">
                ePaper Reader
              </Link>
            </li>
            <li>
              <Link href={`/${tenantSlug}/editions`} className="hover:text-blue-700 transition">
                Editions
              </Link>
            </li>
            <li>
              <Link href={`/${tenantSlug}/archive`} className="hover:text-blue-700 transition">
                Archive
              </Link>
            </li>
            <li>
              <Link href={`/${tenantSlug}/about`} className="hover:text-blue-700 transition">
                About
              </Link>
            </li>
            <li>
              <Link href={`/${tenantSlug}/contact`} className="hover:text-blue-700 transition">
                Contact
              </Link>
            </li>
          </ul>

          <div className="hidden lg:flex items-center text-xs text-slate-500 font-medium">
            <span>Edition: </span>
            <span className="ml-1 font-semibold text-slate-700">
              {latestEditionCode || 'Main Daily'}
            </span>
          </div>
        </div>
      </nav>

      {/* Mobile Drawer */}
      {mobileMenuOpen && (
        <div className="md:hidden border-t border-slate-200 bg-white px-4 py-4 space-y-3">
          <Link
            href={`/${tenantSlug}/epaper`}
            onClick={() => setMobileMenuOpen(false)}
            className="flex w-full items-center justify-center gap-2 rounded-lg py-2.5 text-sm font-semibold text-white shadow"
            style={{ backgroundColor: branding.primary_color }}
          >
            <BookOpen className="h-4 w-4" />
            <span>Open Today's ePaper</span>
          </Link>

          <Link
            href={`/${tenantSlug}/search`}
            onClick={() => setMobileMenuOpen(false)}
            className="flex items-center gap-2 rounded-lg border border-slate-200 px-3 py-2 text-sm text-slate-700 hover:bg-slate-50"
          >
            <Search className="h-4 w-4 text-slate-400" />
            <span>Search Editions</span>
          </Link>

          <div className="border-t border-slate-100 pt-2 space-y-2 text-sm font-medium text-slate-700">
            <Link
              href={`/${tenantSlug}`}
              onClick={() => setMobileMenuOpen(false)}
              className="block py-1 hover:text-blue-700"
            >
              Home
            </Link>
            <Link
              href={`/${tenantSlug}/editions`}
              onClick={() => setMobileMenuOpen(false)}
              className="block py-1 hover:text-blue-700"
            >
              All Editions
            </Link>
            <Link
              href={`/${tenantSlug}/archive`}
              onClick={() => setMobileMenuOpen(false)}
              className="block py-1 hover:text-blue-700"
            >
              Archive & Calendar
            </Link>
            <Link
              href={`/${tenantSlug}/about`}
              onClick={() => setMobileMenuOpen(false)}
              className="block py-1 hover:text-blue-700"
            >
              About Newspaper
            </Link>
            <Link
              href={`/${tenantSlug}/contact`}
              onClick={() => setMobileMenuOpen(false)}
              className="block py-1 hover:text-blue-700"
            >
              Contact Us
            </Link>
          </div>
        </div>
      )}
    </header>
  );
};
