'use client';

import React, { useState } from 'react';
import Link from 'next/link';
import { DigitalReaderPayload } from '@/modules/public-view/types/reader.types';
import { PageCanvas } from './PageCanvas';
import { PageThumbnailPanel } from './PageThumbnailPanel';
import { ZoomControls } from './ZoomControls';
import {
  ArrowLeft,
  ChevronLeft,
  ChevronRight,
  Layers,
  ZoomIn,
  Share2,
  Moon,
  Sun,
} from 'lucide-react';

interface MobileReaderProps {
  tenantSlug: string;
  payload: DigitalReaderPayload;
  currentPage: number;
  scale: number;
  nightMode: boolean;
  onPageChange: (page: number) => void;
  onScaleChange: (scale: number) => void;
  onZoomIn: () => void;
  onZoomOut: () => void;
  onFitWidth: () => void;
  onFitScreen: () => void;
  onReset: () => void;
  onToggleNightMode: () => void;
}

export const MobileReader: React.FC<MobileReaderProps> = ({
  tenantSlug,
  payload,
  currentPage,
  scale,
  nightMode,
  onPageChange,
  onScaleChange,
  onZoomIn,
  onZoomOut,
  onFitWidth,
  onFitScreen,
  onReset,
  onToggleNightMode,
}) => {
  const { edition, branding, pages } = payload;
  const [isThumbnailOpen, setIsThumbnailOpen] = useState(false);
  const [showZoomBar, setShowZoomBar] = useState(false);

  const activePage = pages[currentPage - 1] || pages[0];
  const hasPrev = currentPage > 1;
  const hasNext = currentPage < pages.length;

  const handleShare = async () => {
    if (typeof window !== 'undefined' && navigator.share) {
      try {
        await navigator.share({
          title: `${edition.edition_name} - Page ${currentPage}`,
          url: window.location.href,
        });
      } catch (err) {
        // User cancelled
      }
    }
  };

  return (
    <div className="relative flex h-[100dvh] w-full flex-col bg-slate-950 text-white overflow-hidden">
      {/* Top Mobile Bar */}
      <div className="z-30 flex h-12 w-full items-center justify-between border-b border-slate-800 bg-slate-900/90 px-3 backdrop-blur">
        <Link
          href={`/${tenantSlug}`}
          className="flex items-center gap-1 text-slate-300 hover:text-white"
        >
          <ArrowLeft className="h-5 w-5" />
          <span className="text-xs font-semibold truncate max-w-[140px]">
            {edition.edition_name}
          </span>
        </Link>

        {/* Page Badge */}
        <div className="rounded-full bg-slate-800 px-3 py-1 font-mono text-xs font-bold text-slate-200">
          {currentPage} / {pages.length}
        </div>

        {/* Top Right Quick Actions */}
        <div className="flex items-center space-x-1">
          <button
            type="button"
            onClick={onToggleNightMode}
            className="rounded p-1.5 text-slate-400 hover:text-white"
            aria-label="Night Mode"
          >
            {nightMode ? <Sun className="h-4 w-4 text-amber-400" /> : <Moon className="h-4 w-4" />}
          </button>
          <button
            type="button"
            onClick={handleShare}
            className="rounded p-1.5 text-slate-400 hover:text-white"
            aria-label="Share"
          >
            <Share2 className="h-4 w-4" />
          </button>
        </div>
      </div>

      {/* Main Canvas Viewport */}
      <div className="relative flex-1 overflow-hidden">
        {activePage && (
          <PageCanvas
            page={activePage}
            scale={scale}
            onScaleChange={onScaleChange}
            nightMode={nightMode}
          />
        )}
      </div>

      {/* Floating Zoom Controls Bar (collapsible) */}
      {showZoomBar && (
        <div className="absolute bottom-16 left-1/2 -translate-x-1/2 z-30">
          <ZoomControls
            scale={scale}
            onZoomIn={onZoomIn}
            onZoomOut={onZoomOut}
            onFitWidth={onFitWidth}
            onFitScreen={onFitScreen}
            onReset={onReset}
          />
        </div>
      )}

      {/* Bottom Floating Navigation Bar */}
      <div className="z-30 flex h-14 w-full items-center justify-between border-t border-slate-800 bg-slate-900/95 px-4 backdrop-blur">
        {/* Prev Page Button */}
        <button
          type="button"
          onClick={() => onPageChange(currentPage - 1)}
          disabled={!hasPrev}
          className="flex items-center gap-1 rounded-lg border border-slate-800 bg-slate-800/80 px-3 py-2 text-xs font-semibold text-slate-200 disabled:opacity-40"
        >
          <ChevronLeft className="h-4 w-4" />
          <span>Prev</span>
        </button>

        {/* Thumbnails Drawer Toggle */}
        <button
          type="button"
          onClick={() => setIsThumbnailOpen(true)}
          className="flex items-center gap-1.5 rounded-lg border border-slate-800 bg-slate-800/80 px-3 py-2 text-xs font-semibold text-slate-200"
        >
          <Layers className="h-4 w-4 text-blue-400" />
          <span>Pages</span>
        </button>

        {/* Zoom Controls Toggle */}
        <button
          type="button"
          onClick={() => setShowZoomBar(!showZoomBar)}
          className={`flex items-center gap-1 rounded-lg border px-3 py-2 text-xs font-semibold transition ${
            showZoomBar
              ? 'border-blue-500 bg-blue-600/20 text-blue-400'
              : 'border-slate-800 bg-slate-800/80 text-slate-200'
          }`}
        >
          <ZoomIn className="h-4 w-4" />
          <span>{Math.round(scale * 100)}%</span>
        </button>

        {/* Next Page Button */}
        <button
          type="button"
          onClick={() => onPageChange(currentPage + 1)}
          disabled={!hasNext}
          className="flex items-center gap-1 rounded-lg border border-slate-800 bg-slate-800/80 px-3 py-2 text-xs font-semibold text-slate-200 disabled:opacity-40"
        >
          <span>Next</span>
          <ChevronRight className="h-4 w-4" />
        </button>
      </div>

      {/* Thumbnail Drawer */}
      <PageThumbnailPanel
        pages={pages}
        currentPage={currentPage}
        isOpen={isThumbnailOpen}
        onClose={() => setIsThumbnailOpen(false)}
        onSelectPage={(num) => {
          onPageChange(num);
          setIsThumbnailOpen(false);
        }}
      />
    </div>
  );
};
