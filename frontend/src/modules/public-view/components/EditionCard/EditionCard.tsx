'use client';

import React from 'react';
import Link from 'next/link';
import { EditionCardData } from '../../types/public.types';
import { formatDate } from '../../utils/formatters';
import { resolvePublicImageUrl } from '../../utils/security';
import { BookOpen, Calendar, FileText } from 'lucide-react';

interface EditionCardProps {
  tenantSlug: string;
  edition: EditionCardData;
  primaryColor?: string;
}

export const EditionCard: React.FC<EditionCardProps> = ({
  tenantSlug,
  edition,
  primaryColor = '#1E3A8A',
}) => {
  const thumbUrl = resolvePublicImageUrl(edition.thumbnail_url);
  const formattedDate = formatDate(edition.publish_date, 'short');

  return (
    <article className="group flex flex-col rounded-xl border border-slate-200 bg-white overflow-hidden shadow-sm hover:shadow-md transition duration-200">
      {/* Thumbnail Aspect Ratio Box */}
      <Link
        href={`/${tenantSlug}/epaper/${edition.edition_code}`}
        className="relative block aspect-[3/4] w-full overflow-hidden bg-slate-100"
      >
        {thumbUrl ? (
          <img
            src={thumbUrl}
            alt={edition.edition_name}
            loading="lazy"
            className="h-full w-full object-cover object-top transition duration-300 group-hover:scale-105"
          />
        ) : (
          <div className="flex h-full w-full flex-col items-center justify-center p-4 text-center text-slate-400">
            <FileText className="h-12 w-12 stroke-[1.2] text-slate-300 mb-2" />
            <span className="text-xs font-medium">Page Preview</span>
          </div>
        )}

        {/* Category Badge overlay */}
        <div className="absolute top-3 left-3">
          <span className="rounded-full bg-slate-900/80 backdrop-blur-sm px-2.5 py-1 text-[11px] font-semibold text-white shadow">
            {edition.category || 'Main Edition'}
          </span>
        </div>

        {/* Page Count overlay */}
        <div className="absolute bottom-3 right-3">
          <span className="flex items-center gap-1 rounded bg-white/90 backdrop-blur-sm px-2 py-0.5 text-[11px] font-bold text-slate-800 shadow">
            <FileText className="h-3 w-3 text-slate-500" />
            {edition.total_pages} {edition.total_pages === 1 ? 'Page' : 'Pages'}
          </span>
        </div>
      </Link>

      {/* Details Box */}
      <div className="flex flex-1 flex-col p-4 justify-between">
        <div>
          <div className="flex items-center gap-1.5 text-xs text-slate-500 mb-1.5">
            <Calendar className="h-3.5 w-3.5 text-slate-400" />
            <span>{formattedDate}</span>
          </div>

          <h3 className="font-semibold text-slate-900 text-base leading-snug group-hover:text-blue-700 transition line-clamp-2">
            <Link href={`/${tenantSlug}/epaper/${edition.edition_code}`}>
              {edition.edition_title || edition.edition_name}
            </Link>
          </h3>
        </div>

        {/* Read Button */}
        <div className="mt-4 pt-3 border-t border-slate-100 flex items-center justify-between">
          <span className="text-xs font-mono font-medium text-slate-400">
            {edition.edition_code}
          </span>

          <Link
            href={`/${tenantSlug}/epaper/${edition.edition_code}`}
            className="flex items-center gap-1.5 text-xs font-semibold text-blue-700 hover:text-blue-900 transition"
          >
            <BookOpen className="h-3.5 w-3.5" />
            <span>Read Edition</span>
          </Link>
        </div>
      </div>
    </article>
  );
};
