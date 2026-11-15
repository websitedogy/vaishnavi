'use client';

import React from 'react';
import { Breadcrumb } from '@/modules/public-view/components/Breadcrumb/Breadcrumb';
import { PublicBranding, PublicPublication } from '@/modules/public-view/types/public.types';
import { Newspaper, Info, ShieldCheck, Award } from 'lucide-react';

interface AboutPageProps {
  tenantSlug: string;
  publication: PublicPublication;
  branding: PublicBranding;
}

export const AboutPage: React.FC<AboutPageProps> = ({
  tenantSlug,
  publication,
  branding,
}) => {
  const breadcrumbItems = [
    { label: 'About Publication', active: true },
  ];

  return (
    <div className="space-y-6">
      <Breadcrumb tenantSlug={tenantSlug} items={breadcrumbItems} />

      <div className="mx-auto max-w-4xl px-4 space-y-8">
        {/* Masthead Header */}
        <div className="text-center border-b border-slate-200 pb-8 space-y-2">
          <span className="text-xs font-semibold uppercase tracking-widest text-slate-500">
            About the Publication
          </span>
          <h1
            className="text-2xl md:text-4xl font-extrabold font-masthead"
            style={{ color: branding.primary_color }}
          >
            {branding.newspaper_name}
          </h1>
          {publication.tagline && (
            <p className="text-sm md:text-base text-slate-600 italic">
              "{publication.tagline}"
            </p>
          )}
        </div>

        {/* Overview Card */}
        <div className="rounded-2xl border border-slate-200 bg-white p-6 md:p-8 shadow-sm space-y-6">
          <div className="space-y-4 text-sm text-slate-700 leading-relaxed">
            <h2 className="text-lg font-bold text-slate-900 font-masthead">
              Editorial Mission & Vision
            </h2>
            <p>
              {publication.about ||
                `${branding.newspaper_name} is dedicated to independent, verified, and timely journalism. Our digital broadsheet edition brings the authentic printed newspaper experience into the modern digital age, preserving complete page layouts, headlines, photographs, and regional coverage.`}
            </p>
            <p>
              Published daily, our digital edition serves readers with comprehensive news, editorial analysis,
              district reports, cultural features, sports, and business intelligence.
            </p>
          </div>

          <div className="grid grid-cols-1 md:grid-cols-3 gap-4 pt-4 border-t border-slate-100">
            <div className="rounded-xl bg-slate-50 p-4 border border-slate-200/60">
              <ShieldCheck className="h-5 w-5 text-blue-600 mb-2" />
              <h3 className="font-semibold text-slate-900 text-xs">Authentic Layout</h3>
              <p className="text-xs text-slate-500 mt-1">
                Exact replica of the daily printed edition with pristine high-resolution rendering.
              </p>
            </div>
            <div className="rounded-xl bg-slate-50 p-4 border border-slate-200/60">
              <Award className="h-5 w-5 text-amber-500 mb-2" />
              <h3 className="font-semibold text-slate-900 text-xs">Multi-Platform Access</h3>
              <p className="text-xs text-slate-500 mt-1">
                Optimized for touchscreens, desktop monitors, tablets, and smartphones.
              </p>
            </div>
            <div className="rounded-xl bg-slate-50 p-4 border border-slate-200/60">
              <Newspaper className="h-5 w-5 text-emerald-600 mb-2" />
              <h3 className="font-semibold text-slate-900 text-xs">Digital Archive</h3>
              <p className="text-xs text-slate-500 mt-1">
                Browse back-issues and historical publications easily with our calendar archive.
              </p>
            </div>
          </div>
        </div>

        {/* Publication Details */}
        <div className="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
          <h2 className="text-base font-bold text-slate-900 mb-4">Publication Credentials</h2>
          <dl className="grid grid-cols-1 sm:grid-cols-2 gap-x-6 gap-y-3 text-xs">
            <div>
              <dt className="text-slate-500">Newspaper Name:</dt>
              <dd className="font-semibold text-slate-800">{branding.newspaper_name}</dd>
            </div>
            <div>
              <dt className="text-slate-500">Primary Language:</dt>
              <dd className="font-semibold text-slate-800 uppercase">
                {publication.default_language}
              </dd>
            </div>
            <div>
              <dt className="text-slate-500">Publication Frequency:</dt>
              <dd className="font-semibold text-slate-800">Daily Morning Edition</dd>
            </div>
            <div>
              <dt className="text-slate-500">Digital Platform:</dt>
              <dd className="font-semibold text-slate-800">DOGY ePAPER Cloud SaaS</dd>
            </div>
          </dl>
        </div>
      </div>
    </div>
  );
};
