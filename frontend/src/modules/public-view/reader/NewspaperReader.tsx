'use client';

import React, { useState, useEffect, useCallback, useRef } from 'react';
import { useRouter, useSearchParams } from 'next/navigation';
import { DigitalReaderPayload } from '@/modules/public-view/types/reader.types';
import { ReaderToolbar } from './ReaderToolbar';
import { PageCanvas } from './PageCanvas';
import { PageThumbnailPanel } from './PageThumbnailPanel';
import { MobileReader } from './MobileReader';
import {
  clampScale,
  SCALE_STEP,
  calculateFitToWidth,
  calculateFitToScreen,
} from './readerUtils';

interface NewspaperReaderProps {
  tenantSlug: string;
  payload: DigitalReaderPayload;
  initialPage?: number;
}

export const NewspaperReader: React.FC<NewspaperReaderProps> = ({
  tenantSlug,
  payload,
  initialPage = 1,
}) => {
  const router = useRouter();
  const searchParams = useSearchParams();

  // Read initial page from URL query param if present
  const queryPage = searchParams ? parseInt(searchParams.get('page') || '0', 10) : 0;
  const startPage = queryPage > 0 && queryPage <= payload.pages.length ? queryPage : initialPage;

  const [currentPage, setCurrentPage] = useState(startPage);
  const [scale, setScale] = useState(1.0);
  const [nightMode, setNightMode] = useState(false);
  const [isThumbnailOpen, setIsThumbnailOpen] = useState(false);
  const [isMobile, setIsMobile] = useState(false);

  const containerRef = useRef<HTMLDivElement>(null);

  // Responsive screen detection
  useEffect(() => {
    const checkMobile = () => {
      setIsMobile(window.innerWidth < 768);
    };
    checkMobile();
    window.addEventListener('resize', checkMobile);
    return () => window.removeEventListener('resize', checkMobile);
  }, []);

  // Update URL query param when page changes (shallow)
  const changePage = useCallback(
    (pageNum: number) => {
      const target = Math.max(1, Math.min(payload.pages.length, pageNum));
      setCurrentPage(target);

      // Update URL query param without full page reload
      if (typeof window !== 'undefined') {
        const url = new URL(window.location.href);
        url.searchParams.set('page', String(target));
        window.history.replaceState({}, '', url.toString());
      }
    },
    [payload.pages.length]
  );

  // Zoom helpers
  const handleZoomIn = () => {
    setScale((prev) => clampScale(prev + SCALE_STEP));
  };

  const handleZoomOut = () => {
    setScale((prev) => clampScale(prev - SCALE_STEP));
  };

  const handleReset = () => {
    setScale(1.0);
  };

  const activePage = payload.pages[currentPage - 1] || payload.pages[0];

  const handleFitWidth = () => {
    if (!containerRef.current || !activePage) return;
    const container = {
      width: containerRef.current.clientWidth,
      height: containerRef.current.clientHeight,
    };
    const pageDims = {
      width: activePage.width || 1200,
      height: activePage.height || 1800,
    };
    setScale(calculateFitToWidth(container, pageDims));
  };

  const handleFitScreen = () => {
    if (!containerRef.current || !activePage) return;
    const container = {
      width: containerRef.current.clientWidth,
      height: containerRef.current.clientHeight,
    };
    const pageDims = {
      width: activePage.width || 1200,
      height: activePage.height || 1800,
    };
    setScale(calculateFitToScreen(container, pageDims));
  };

  // Keyboard shortcut listener
  useEffect(() => {
    const handleKeyDown = (e: KeyboardEvent) => {
      if (
        e.target instanceof HTMLInputElement ||
        e.target instanceof HTMLTextAreaElement ||
        e.target instanceof HTMLSelectElement
      ) {
        return;
      }

      switch (e.key) {
        case 'ArrowLeft':
        case 'PageUp':
          e.preventDefault();
          changePage(currentPage - 1);
          break;
        case 'ArrowRight':
        case 'PageDown':
          e.preventDefault();
          changePage(currentPage + 1);
          break;
        case 'Home':
          e.preventDefault();
          changePage(1);
          break;
        case 'End':
          e.preventDefault();
          changePage(payload.pages.length);
          break;
        case '+':
        case '=':
          e.preventDefault();
          handleZoomIn();
          break;
        case '-':
        case '_':
          e.preventDefault();
          handleZoomOut();
          break;
        case '0':
          e.preventDefault();
          handleReset();
          break;
        case 'w':
        case 'W':
          e.preventDefault();
          handleFitWidth();
          break;
        case 'h':
        case 'H':
          e.preventDefault();
          handleFitScreen();
          break;
        case 't':
        case 'T':
          e.preventDefault();
          setIsThumbnailOpen((prev) => !prev);
          break;
        default:
          break;
      }
    };

    window.addEventListener('keydown', handleKeyDown);
    return () => window.removeEventListener('keydown', handleKeyDown);
  }, [currentPage, payload.pages.length, changePage]);

  // Render Mobile Reader when viewport is small
  if (isMobile) {
    return (
      <MobileReader
        tenantSlug={tenantSlug}
        payload={payload}
        currentPage={currentPage}
        scale={scale}
        nightMode={nightMode}
        onPageChange={changePage}
        onScaleChange={setScale}
        onZoomIn={handleZoomIn}
        onZoomOut={handleZoomOut}
        onFitWidth={handleFitWidth}
        onFitScreen={handleFitScreen}
        onReset={handleReset}
        onToggleNightMode={() => setNightMode(!nightMode)}
      />
    );
  }

  // Desktop Immersive Reader
  return (
    <div
      ref={containerRef}
      className="relative flex h-[100dvh] w-full flex-col bg-slate-950 text-white overflow-hidden"
    >
      {/* Top Reader Toolbar */}
      <ReaderToolbar
        tenantSlug={tenantSlug}
        payload={payload}
        currentPage={currentPage}
        scale={scale}
        nightMode={nightMode}
        isThumbnailOpen={isThumbnailOpen}
        onPageChange={changePage}
        onScaleChange={setScale}
        onZoomIn={handleZoomIn}
        onZoomOut={handleZoomOut}
        onFitWidth={handleFitWidth}
        onFitScreen={handleFitScreen}
        onReset={handleReset}
        onToggleNightMode={() => setNightMode(!nightMode)}
        onToggleThumbnails={() => setIsThumbnailOpen(!isThumbnailOpen)}
      />

      {/* Main Reading Canvas Viewport */}
      <main className="relative flex-1 overflow-hidden">
        {activePage && (
          <PageCanvas
            page={activePage}
            scale={scale}
            onScaleChange={setScale}
            nightMode={nightMode}
          />
        )}
      </main>

      {/* Collapsible Left Thumbnail Tray */}
      <PageThumbnailPanel
        pages={payload.pages}
        currentPage={currentPage}
        isOpen={isThumbnailOpen}
        onClose={() => setIsThumbnailOpen(false)}
        onSelectPage={(pageNum) => {
          changePage(pageNum);
          setIsThumbnailOpen(false);
        }}
        primaryColor={payload.branding?.primary_color}
      />
    </div>
  );
};
