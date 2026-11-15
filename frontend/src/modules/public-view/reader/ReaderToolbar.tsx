'use client';

import React, { useState } from 'react';
import Link from 'next/link';
import { DigitalReaderPayload } from '@/modules/public-view/types/reader.types';
import { PageNavigation } from './PageNavigation';
import { ZoomControls } from './ZoomControls';
import { FullscreenControl } from './FullscreenControl';
import { ReaderSettings } from './ReaderSettings';
import { formatDate } from '@/modules/public-view/utils/formatters';
import {
  ArrowLeft,
  Layers,
  Share2,
  Download,
  Printer,
  Check,
  Copy,
} from 'lucide-react';

interface ReaderToolbarProps {
  tenantSlug: string;
  payload: DigitalReaderPayload;
  currentPage: number;
  scale: number;
  nightMode: boolean;
  isThumbnailOpen: boolean;
  onPageChange: (page: number) => void;
  onScaleChange: (scale: number) => void;
  onZoomIn: () => void;
  onZoomOut: () => void;
  onFitWidth: () => void;
  onFitScreen: () => void;
  onReset: () => void;
  onToggleNightMode: () => void;
  onToggleThumbnails: () => void;
}

export const ReaderToolbar: React.FC<ReaderToolbarProps> = ({
  tenantSlug,
  payload,
  currentPage,
  scale,
  nightMode,
  isThumbnailOpen,
  onPageChange,
  onScaleChange,
  onZoomIn,
  onZoomOut,
  onFitWidth,
  onFitScreen,
  onReset,
  onToggleNightMode,
  onToggleThumbnails,
}) => {
  const { edition, publication, branding, permissions, pages } = payload;
  const [showShareModal, setShowShareModal] = useState(false);
  const [copied, setCopied] = useState(false);

  const handleShare = async () => {
    const currentUrl = typeof window !== 'undefined' ? window.location.href : '';
    if (navigator.share) {
      try {
        await navigator.share({
          title: `${branding?.font_family || 'ePaper'} - ${edition.edition_name}`,
          text: `Read Page ${currentPage} of ${edition.edition_name}`,
          url: currentUrl,
        });
        return;
      } catch (err) {
        // Fallback to modal if cancelled or unsupported
      }
    }
    setShowShareModal(true);
  };

  const handleCopyLink = () => {
    if (typeof window !== 'undefined') {
      navigator.clipboard.writeText(window.location.href);
      setCopied(true);
      setTimeout(() => setCopied(false), 2000);
    }
  };

  const handlePrint = () => {
    if (typeof window !== 'undefined') {
      window.print();
    }
  };

  return (
    <>
      <header className="z-30 flex h-14 w-full items-center justify-between border-b border-slate-800 bg-slate-900/95 px-3 md:px-5 backdrop-blur text-white">
        {/* Left Section: Back, Title, Thumbnails Toggle */}
        <div className="flex items-center space-x-2 md:space-x-3">
          <Link
            href={`/${tenantSlug}`}
            className="flex items-center gap-1.5 rounded-lg border border-slate-800 bg-slate-800/80 px-2.5 py-1.5 text-xs font-semibold text-slate-300 hover:bg-slate-700 hover:text-white transition"
            title="Exit Reader / Back to Newspaper"
          >
            <ArrowLeft className="h-4 w-4" />
            <span className="hidden sm:inline">Exit</span>
          </Link>

          <button
            type="button"
            onClick={onToggleThumbnails}
            className={`flex items-center gap-1.5 rounded-lg border px-2.5 py-1.5 text-xs font-semibold transition ${
              isThumbnailOpen
                ? 'border-blue-500 bg-blue-600/20 text-blue-400'
                : 'border-slate-800 bg-slate-800/80 text-slate-300 hover:bg-slate-700 hover:text-white'
            }`}
            title="Toggle Page Thumbnails"
          >
            <Layers className="h-4 w-4" />
            <span className="hidden md:inline">Pages</span>
            <span className="rounded bg-slate-800 px-1.5 py-0.2 text-[10px] text-slate-400">
              {pages.length}
            </span>
          </button>

          <div className="hidden lg:flex flex-col border-l border-slate-800 pl-3">
            <span className="text-xs font-semibold text-white truncate max-w-[200px]">
              {edition.edition_name}
            </span>
            <span className="text-[10px] text-slate-400">
              {formatDate(edition.publish_date, 'short')}
            </span>
          </div>
        </div>

        {/* Center Section: Page Navigation */}
        <div className="flex items-center">
          <PageNavigation
            currentPage={currentPage}
            totalPages={pages.length}
            onPageChange={onPageChange}
          />
        </div>

        {/* Right Section: Zoom Controls, Settings, Actions */}
        <div className="flex items-center space-x-1.5 md:space-x-2">
          {/* Zoom Controls */}
          <div className="hidden sm:flex">
            <ZoomControls
              scale={scale}
              onZoomIn={onZoomIn}
              onZoomOut={onZoomOut}
              onFitWidth={onFitWidth}
              onFitScreen={onFitScreen}
              onReset={onReset}
            />
          </div>

          {/* Share */}
          <button
            type="button"
            onClick={handleShare}
            className="rounded-lg border border-slate-800 bg-slate-900/90 p-2 text-slate-300 backdrop-blur hover:bg-slate-800 hover:text-white transition shadow-md"
            title="Share Page"
            aria-label="Share Page"
          >
            <Share2 className="h-4 w-4" />
          </button>

          {/* Print (if permitted) */}
          {permissions.allow_print && (
            <button
              type="button"
              onClick={handlePrint}
              className="hidden sm:inline-flex rounded-lg border border-slate-800 bg-slate-900/90 p-2 text-slate-300 backdrop-blur hover:bg-slate-800 hover:text-white transition shadow-md"
              title="Print Page"
              aria-label="Print Page"
            >
              <Printer className="h-4 w-4" />
            </button>
          )}

          {/* Download (if permitted) */}
          {(permissions.allow_download || permissions.allow_page_download) && (
            <a
              href={pages[currentPage - 1]?.image_url}
              download={`${edition.edition_code}-page-${currentPage}.png`}
              target="_blank"
              rel="noopener noreferrer"
              className="hidden sm:inline-flex rounded-lg border border-slate-800 bg-slate-900/90 p-2 text-slate-300 backdrop-blur hover:bg-slate-800 hover:text-white transition shadow-md"
              title="Download Page Image"
              aria-label="Download Page Image"
            >
              <Download className="h-4 w-4" />
            </a>
          )}

          {/* Reader Settings (Night mode, etc.) */}
          <ReaderSettings
            nightMode={nightMode}
            onToggleNightMode={onToggleNightMode}
          />

          {/* Fullscreen */}
          <FullscreenControl />
        </div>
      </header>

      {/* Share Modal Dialog */}
      {showShareModal && (
        <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/70 backdrop-blur-sm p-4">
          <div className="w-full max-w-sm rounded-xl border border-slate-800 bg-slate-900 p-5 text-slate-200 shadow-2xl">
            <h4 className="font-semibold text-white text-sm mb-2">Share this Page</h4>
            <p className="text-xs text-slate-400 mb-4">
              Share {edition.edition_name} - Page {currentPage} with your readers.
            </p>
            <div className="flex items-center gap-2 mb-4">
              <input
                type="text"
                readOnly
                value={typeof window !== 'undefined' ? window.location.href : ''}
                className="w-full rounded border border-slate-700 bg-slate-800 px-2.5 py-1.5 text-xs text-slate-300 select-all font-mono"
              />
              <button
                type="button"
                onClick={handleCopyLink}
                className="flex items-center gap-1 rounded bg-blue-600 px-3 py-1.5 text-xs font-semibold text-white hover:bg-blue-500 transition"
              >
                {copied ? <Check className="h-3.5 w-3.5" /> : <Copy className="h-3.5 w-3.5" />}
                <span>{copied ? 'Copied' : 'Copy'}</span>
              </button>
            </div>
            <div className="flex justify-end">
              <button
                type="button"
                onClick={() => setShowShareModal(false)}
                className="rounded px-3 py-1 text-xs text-slate-400 hover:text-white"
              >
                Close
              </button>
            </div>
          </div>
        </div>
      )}
    </>
  );
};
