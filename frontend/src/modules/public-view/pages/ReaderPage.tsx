'use client';

import React from 'react';
import { DigitalReaderPayload } from '@/modules/public-view/types/reader.types';
import { NewspaperReader } from '@/modules/public-view/reader/NewspaperReader';
import { ReaderCanvasSkeleton } from '@/modules/public-view/components/LoadingStates/LoadingStates';
import Link from 'next/link';
import { AlertCircle, ArrowLeft } from 'lucide-react';

interface ReaderPageProps {
  tenantSlug: string;
  payload: DigitalReaderPayload | null;
  loading?: boolean;
  error?: string | null;
  initialPage?: number;
}

export const ReaderPage: React.FC<ReaderPageProps> = ({
  tenantSlug,
  payload,
  loading = false,
  error = null,
  initialPage = 1,
}) => {
  if (loading) {
    return (
      <div className="h-[100dvh] w-full bg-slate-950">
        <ReaderCanvasSkeleton />
      </div>
    );
  }

  if (error || !payload || !payload.pages || payload.pages.length === 0) {
    return (
      <div className="flex h-[100dvh] w-full flex-col items-center justify-center bg-slate-950 p-6 text-center text-white">
        <div className="max-w-md space-y-4 rounded-2xl border border-slate-800 bg-slate-900/90 p-8 shadow-2xl backdrop-blur">
          <AlertCircle className="mx-auto h-12 w-12 text-red-400" />
          <h2 className="text-xl font-bold font-masthead">Edition Unavailable</h2>
          <p className="text-xs text-slate-400 leading-relaxed">
            {error || 'This digital newspaper edition is not published, has expired, or is currently undergoing rasterization.'}
          </p>
          <div className="pt-2">
            <Link
              href={`/${tenantSlug}`}
              className="inline-flex items-center gap-2 rounded-lg bg-blue-600 px-4 py-2 text-xs font-semibold text-white hover:bg-blue-500 transition shadow"
            >
              <ArrowLeft className="h-4 w-4" />
              <span>Return to Newspaper Home</span>
            </Link>
          </div>
        </div>
      </div>
    );
  }

  return (
    <NewspaperReader
      tenantSlug={tenantSlug}
      payload={payload}
      initialPage={initialPage}
    />
  );
};
