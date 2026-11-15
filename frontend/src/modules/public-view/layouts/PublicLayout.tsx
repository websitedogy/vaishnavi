'use client';

import React from 'react';
import { Header } from '../components/Header/Header';
import { Navigation } from '../components/Navigation/Navigation';
import { Footer } from '../components/Footer/Footer';
import { PublicBranding, PublicPublication, PublicCategory } from '../types/public.types';
import '../styles/public-view.css';

interface PublicLayoutProps {
  tenantSlug: string;
  publication: PublicPublication;
  branding: PublicBranding;
  categories?: PublicCategory[];
  latestEditionCode?: string;
  publishDate?: string;
  children: React.ReactNode;
}

export const PublicLayout: React.FC<PublicLayoutProps> = ({
  tenantSlug,
  publication,
  branding,
  categories = [],
  latestEditionCode,
  publishDate,
  children,
}) => {
  // Apply dynamic tenant color tokens via CSS custom properties
  const brandStyle = {
    '--tenant-primary-color': branding.primary_color || '#1e3a8a',
    '--tenant-secondary-color': branding.secondary_color || '#3b82f6',
    '--tenant-accent-color': branding.accent_color || '#f59e0b',
  } as React.CSSProperties;

  return (
    <div
      style={brandStyle}
      className="min-h-screen flex flex-col bg-slate-50 text-slate-900 font-sans selection:bg-blue-600 selection:text-white"
    >
      {/* Masthead Header */}
      <Header
        tenantSlug={tenantSlug}
        publication={publication}
        branding={branding}
        latestEditionCode={latestEditionCode}
        publishDate={publishDate}
      />

      {/* Navigation Bar */}
      <Navigation
        tenantSlug={tenantSlug}
        primaryColor={branding.primary_color}
        categories={categories}
      />

      {/* Main Content Area */}
      <main className="flex-1 pb-16">{children}</main>

      {/* Footer */}
      <Footer
        tenantSlug={tenantSlug}
        publication={publication}
        branding={branding}
      />
    </div>
  );
};
