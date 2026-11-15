'use client';

import React from 'react';
import { EditionCardData } from '../../types/public.types';
import { EditionCard } from '../EditionCard/EditionCard';
import { Newspaper } from 'lucide-react';

interface NewspaperGridProps {
  tenantSlug: string;
  editions: EditionCardData[];
  primaryColor?: string;
  title?: string;
  subtitle?: string;
  emptyMessage?: string;
}

export const NewspaperGrid: React.FC<NewspaperGridProps> = ({
  tenantSlug,
  editions,
  primaryColor,
  title,
  subtitle,
  emptyMessage = 'No published newspaper editions match this criteria. Please check back later or browse the archive.',
}) => {
  if (!editions || editions.length === 0) {
    return (
      <div className="flex flex-col items-center justify-center rounded-2xl border border-dashed border-slate-300 bg-slate-50 p-12 text-center">
        <Newspaper className="h-12 w-12 text-slate-400 mb-3 stroke-1" />
        <h3 className="text-base font-semibold text-slate-800">No Editions Available</h3>
        <p className="text-sm text-slate-500 mt-1 max-w-sm">{emptyMessage}</p>
      </div>
    );
  }

  return (
    <div className="space-y-6">
      {title && (
        <div className="flex flex-col sm:flex-row sm:items-end sm:justify-between gap-2 border-b border-slate-200 pb-4">
          <div>
            <h2 className="font-masthead text-2xl md:text-3xl font-bold text-slate-900">{title}</h2>
            {subtitle && <p className="text-sm text-slate-500 mt-1">{subtitle}</p>}
          </div>
          <span className="text-xs font-semibold text-slate-500">
            Showing {editions.length} {editions.length === 1 ? 'Edition' : 'Editions'}
          </span>
        </div>
      )}

      <div className="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-6">
        {editions.map((edition) => (
          <EditionCard
            key={edition.id}
            tenantSlug={tenantSlug}
            edition={edition}
            primaryColor={primaryColor}
          />
        ))}
      </div>
    </div>
  );
};
