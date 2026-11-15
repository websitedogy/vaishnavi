'use client';

import React from 'react';
import { DigitalReaderPage } from '@/modules/public-view/types/reader.types';
import { resolvePublicImageUrl } from '@/modules/public-view/utils/security';
import { X, Layers } from 'lucide-react';

interface PageThumbnailPanelProps {
  pages: DigitalReaderPage[];
  currentPage: number;
  isOpen: boolean;
  onClose: () => void;
  onSelectPage: (pageNumber: number) => void;
  primaryColor?: string;
}

export const PageThumbnailPanel: React.FC<PageThumbnailPanelProps> = ({
  pages,
  currentPage,
  isOpen,
  onClose,
  onSelectPage,
  primaryColor = '#2563eb',
}) => {
  if (!isOpen) return null;

  return (
    <aside
      className="fixed inset-y-0 left-0 z-40 flex w-72 flex-col border-r border-slate-800 bg-slate-900/95 text-white shadow-2xl backdrop-blur transition-transform duration-200 ease-in-out"
      aria-label="Page Thumbnails"
    >
      {/* Header */}
      <div className="flex items-center justify-between border-b border-slate-800 px-4 py-3">
        <div className="flex items-center space-x-2">
          <Layers className="h-4 w-4 text-slate-400" />
          <h3 className="text-sm font-semibold text-slate-200">All Pages ({pages.length})</h3>
        </div>
        <button
          type="button"
          onClick={onClose}
          className="rounded p-1 text-slate-400 hover:bg-slate-800 hover:text-white transition"
          aria-label="Close thumbnail drawer"
        >
          <X className="h-4 w-4" />
        </button>
      </div>

      {/* Thumbnail List */}
      <div className="flex-1 overflow-y-auto p-4 space-y-4">
        {pages.map((page) => {
          const isActive = page.page_number === currentPage;
          const thumbUrl = resolvePublicImageUrl(page.thumbnail_url || page.image_url);

          return (
            <div
              key={page.page_number}
              onClick={() => {
                onSelectPage(page.page_number);
              }}
              className={`group relative cursor-pointer overflow-hidden rounded-lg border-2 transition ${
                isActive
                  ? 'border-blue-500 shadow-md ring-2 ring-blue-500/30'
                  : 'border-slate-800 hover:border-slate-600'
              }`}
            >
              <div className="relative aspect-[3/4] w-full bg-slate-950">
                {thumbUrl ? (
                  <img
                    src={thumbUrl}
                    alt={`Page ${page.page_number} thumbnail`}
                    className="h-full w-full object-cover transition group-hover:opacity-90"
                    loading="lazy"
                  />
                ) : (
                  <div className="flex h-full w-full items-center justify-center text-xs text-slate-500 font-mono">
                    Page {page.page_number}
                  </div>
                )}
                {/* Page Badge */}
                <div
                  className={`absolute bottom-2 left-2 rounded px-2 py-0.5 text-xs font-semibold shadow ${
                    isActive ? 'bg-blue-600 text-white' : 'bg-slate-900/80 text-slate-300'
                  }`}
                >
                  Page {page.page_number}
                </div>
              </div>
            </div>
          );
        })}
      </div>
    </aside>
  );
};
