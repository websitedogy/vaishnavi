'use client';

import React from 'react';
import Link from 'next/link';
import { Newspaper, Home, ArrowLeft } from 'lucide-react';

interface NotFoundPageProps {
  tenantSlug?: string;
  title?: string;
  message?: string;
}

export const NotFoundPage: React.FC<NotFoundPageProps> = ({
  tenantSlug,
  title = 'Page Not Found',
  message = 'The digital edition or page you are looking for does not exist or may have been archived.',
}) => {
  return (
    <div className="flex min-h-[70vh] flex-col items-center justify-center px-4 py-16 text-center">
      <div className="max-w-md space-y-4">
        <div className="mx-auto flex h-16 w-16 items-center justify-center rounded-2xl bg-slate-100 text-slate-500">
          <Newspaper className="h-8 w-8 text-slate-400" />
        </div>
        <span className="font-mono text-xs font-bold uppercase tracking-widest text-blue-600">
          404 Error
        </span>
        <h1 className="text-2xl md:text-3xl font-extrabold text-slate-900 font-masthead">
          {title}
        </h1>
        <p className="text-xs md:text-sm text-slate-500 leading-relaxed">{message}</p>
        <div className="pt-4 flex items-center justify-center gap-3">
          {tenantSlug ? (
            <Link
              href={`/${tenantSlug}`}
              className="inline-flex items-center gap-2 rounded-lg bg-blue-600 px-4 py-2 text-xs font-semibold text-white hover:bg-blue-700 transition shadow-sm"
            >
              <Home className="h-4 w-4" />
              <span>Return to Newspaper Home</span>
            </Link>
          ) : (
            <Link
              href="/"
              className="inline-flex items-center gap-2 rounded-lg bg-blue-600 px-4 py-2 text-xs font-semibold text-white hover:bg-blue-700 transition shadow-sm"
            >
              <Home className="h-4 w-4" />
              <span>Return Home</span>
            </Link>
          )}
        </div>
      </div>
    </div>
  );
};
