'use client';

import React from 'react';
import Link from 'next/link';
import { usePathname } from 'next/navigation';
import { BookOpen, Calendar, Archive, Search, Info, Phone, Newspaper, Layers } from 'lucide-react';
import { PublicCategory } from '../../types/public.types';

interface NavigationProps {
  tenantSlug: string;
  primaryColor?: string;
  categories?: (string | PublicCategory)[];
}

export const Navigation: React.FC<NavigationProps> = ({
  tenantSlug,
  primaryColor = '#1e3a8a',
  categories = [],
}) => {
  const pathname = usePathname() || '';

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

  const navItems = [
    { label: 'Home', href: `/${tenantSlug}`, icon: Newspaper, exact: true },
    { label: "Today's ePaper", href: `/${tenantSlug}/epaper`, icon: BookOpen },
    { label: 'All Editions', href: `/${tenantSlug}/editions`, icon: Layers },
    { label: 'Archive', href: `/${tenantSlug}/archive`, icon: Archive },
    { label: 'Search', href: `/${tenantSlug}/search`, icon: Search },
  ];

  const isActive = (href: string, exact: boolean = false) => {
    if (exact) {
      return pathname === href;
    }
    return pathname.startsWith(href);
  };

  return (
    <nav className="border-b border-slate-200 bg-white">
      <div className="mx-auto flex max-w-7xl items-center justify-between px-4">
        {/* Primary Navigation Links */}
        <div className="flex items-center space-x-1 overflow-x-auto py-1 scrollbar-none">
          {navItems.map((item) => {
            const active = isActive(item.href, item.exact);
            const Icon = item.icon;
            return (
              <Link
                key={item.href}
                href={item.href}
                className={`flex items-center gap-1.5 whitespace-nowrap rounded-md px-3 py-2 text-sm font-medium transition ${
                  active
                    ? 'font-semibold text-white'
                    : 'text-slate-700 hover:bg-slate-100 hover:text-slate-900'
                }`}
                style={active ? { backgroundColor: primaryColor } : undefined}
              >
                <Icon className="h-4 w-4" />
                <span>{item.label}</span>
              </Link>
            );
          })}

          {/* Dynamic Top Categories */}
          {normalizedCategories.slice(0, 4).map((cat) => {
            const catHref = `/${tenantSlug}/category/${cat.slug}`;
            const active = pathname === catHref;
            return (
              <Link
                key={cat.id}
                href={catHref}
                className={`hidden md:flex items-center gap-1.5 whitespace-nowrap rounded-md px-3 py-2 text-sm font-medium transition ${
                  active
                    ? 'font-semibold text-white'
                    : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900'
                }`}
                style={active ? { backgroundColor: primaryColor } : undefined}
              >
                <span>{cat.name}</span>
                {cat.edition_count > 0 && (
                  <span className="rounded-full bg-slate-200/60 px-1.5 py-0.2 text-[10px] text-slate-700">
                    {cat.edition_count}
                  </span>
                )}
              </Link>
            );
          })}
        </div>

        {/* Secondary Links: About & Contact */}
        <div className="hidden lg:flex items-center space-x-2 text-xs text-slate-500">
          <Link
            href={`/${tenantSlug}/about`}
            className="flex items-center gap-1 px-2 py-1.5 hover:text-slate-900 transition"
          >
            <Info className="h-3.5 w-3.5" />
            <span>About</span>
          </Link>
          <span className="text-slate-300">|</span>
          <Link
            href={`/${tenantSlug}/contact`}
            className="flex items-center gap-1 px-2 py-1.5 hover:text-slate-900 transition"
          >
            <Phone className="h-3.5 w-3.5" />
            <span>Contact</span>
          </Link>
        </div>
      </div>
    </nav>
  );
};
