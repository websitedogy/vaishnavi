'use client';

import React from 'react';
import Link from 'next/link';
import { ChevronRight, Home } from 'lucide-react';

export interface BreadcrumbItem {
  label: string;
  href?: string;
  active?: boolean;
}

interface BreadcrumbProps {
  tenantSlug: string;
  items: BreadcrumbItem[];
}

export const Breadcrumb: React.FC<BreadcrumbProps> = ({ tenantSlug, items }) => {
  return (
    <nav aria-label="Breadcrumb" className="py-2.5 px-4 text-xs text-slate-500">
      <div className="mx-auto flex max-w-7xl items-center space-x-1.5 overflow-x-auto scrollbar-none">
        <Link
          href={`/${tenantSlug}`}
          className="flex items-center gap-1 text-slate-600 hover:text-slate-900 transition"
        >
          <Home className="h-3.5 w-3.5" />
          <span className="sr-only">Home</span>
        </Link>

        {items.map((item, index) => {
          const isLast = index === items.length - 1;
          return (
            <React.Fragment key={index}>
              <ChevronRight className="h-3.5 w-3.5 text-slate-400 shrink-0" />
              {item.href && !isLast ? (
                <Link
                  href={item.href}
                  className="whitespace-nowrap text-slate-600 hover:text-slate-900 transition"
                >
                  {item.label}
                </Link>
              ) : (
                <span
                  className="whitespace-nowrap font-medium text-slate-900"
                  aria-current={isLast ? 'page' : undefined}
                >
                  {item.label}
                </span>
              )}
            </React.Fragment>
          );
        })}
      </div>
    </nav>
  );
};
