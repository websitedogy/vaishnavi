'use client';

import React, { useRef, useState, useEffect, useCallback } from 'react';
import { DigitalReaderPage } from '@/modules/public-view/types/reader.types';
import { resolvePublicImageUrl } from '@/modules/public-view/utils/security';
import {
  Point,
  clampScale,
  clampPanOffset,
  getTouchDistance,
  getTouchCenter,
  MIN_SCALE,
  MAX_SCALE,
} from './readerUtils';
import { AlertCircle, RefreshCw } from 'lucide-react';

interface PageCanvasProps {
  page: DigitalReaderPage;
  scale: number;
  onScaleChange: (newScale: number) => void;
  nightMode?: boolean;
}

export const PageCanvas: React.FC<PageCanvasProps> = ({
  page,
  scale,
  onScaleChange,
  nightMode = false,
}) => {
  const containerRef = useRef<HTMLDivElement>(null);
  const imageRef = useRef<HTMLImageElement>(null);

  const [pan, setPan] = useState<Point>({ x: 0, y: 0 });
  const [isDragging, setIsDragging] = useState(false);
  const [dragStart, setDragStart] = useState<Point>({ x: 0, y: 0 });
  const [initialPan, setInitialPan] = useState<Point>({ x: 0, y: 0 });

  const [isLoading, setIsLoading] = useState(true);
  const [hasError, setHasError] = useState(false);

  // Touch gesture state
  const touchStateRef = useRef<{
    initialDist: number;
    initialScale: number;
    initialPan: Point;
    startPoint: Point;
  }>({
    initialDist: 0,
    initialScale: 1,
    initialPan: { x: 0, y: 0 },
    startPoint: { x: 0, y: 0 },
  });

  const imageUrl = resolvePublicImageUrl(page.image_url);

  // Reset pan and loading state when page changes
  useEffect(() => {
    setPan({ x: 0, y: 0 });
    setIsLoading(true);
    setHasError(false);
  }, [page.image_url]);

  // Ensure pan stays within bounds when scale changes
  useEffect(() => {
    if (!containerRef.current) return;
    const container = {
      width: containerRef.current.clientWidth,
      height: containerRef.current.clientHeight,
    };
    const pageDims = {
      width: page.width || 1200,
      height: page.height || 1800,
    };
    setPan((currentPan) => clampPanOffset(currentPan, container, pageDims, scale));
  }, [scale, page.width, page.height]);

  // Mouse drag handlers
  const handleMouseDown = (e: React.MouseEvent) => {
    if (e.button !== 0) return;
    setIsDragging(true);
    setDragStart({ x: e.clientX, y: e.clientY });
    setInitialPan(pan);
    e.preventDefault();
  };

  const handleMouseMove = useCallback(
    (e: MouseEvent) => {
      if (!isDragging || !containerRef.current) return;

      const deltaX = e.clientX - dragStart.x;
      const deltaY = e.clientY - dragStart.y;

      const targetPan = {
        x: initialPan.x + deltaX,
        y: initialPan.y + deltaY,
      };

      const container = {
        width: containerRef.current.clientWidth,
        height: containerRef.current.clientHeight,
      };
      const pageDims = {
        width: page.width || 1200,
        height: page.height || 1800,
      };

      setPan(clampPanOffset(targetPan, container, pageDims, scale));
    },
    [isDragging, dragStart, initialPan, scale, page.width, page.height]
  );

  const handleMouseUp = useCallback(() => {
    setIsDragging(false);
  }, []);

  useEffect(() => {
    if (isDragging) {
      window.addEventListener('mousemove', handleMouseMove);
      window.addEventListener('mouseup', handleMouseUp);
    }
    return () => {
      window.removeEventListener('mousemove', handleMouseMove);
      window.removeEventListener('mouseup', handleMouseUp);
    };
  }, [isDragging, handleMouseMove, handleMouseUp]);

  // Wheel zoom handler
  const handleWheel = (e: React.WheelEvent) => {
    if (e.ctrlKey || Math.abs(e.deltaY) > 20) {
      e.preventDefault();
      const zoomFactor = e.deltaY < 0 ? 1.15 : 0.85;
      const nextScale = clampScale(scale * zoomFactor);
      onScaleChange(nextScale);
    }
  };

  // Double click to toggle zoom (between 1.0 and 2.0)
  const handleDoubleClick = () => {
    if (scale <= 1.05) {
      onScaleChange(1.8);
    } else {
      onScaleChange(1.0);
      setPan({ x: 0, y: 0 });
    }
  };

  // Touch handlers for mobile / tablet pinch-to-zoom
  const handleTouchStart = (e: React.TouchEvent) => {
    if (e.touches.length === 2) {
      const dist = getTouchDistance(e.touches[0], e.touches[1]);
      touchStateRef.current = {
        initialDist: dist,
        initialScale: scale,
        initialPan: pan,
        startPoint: getTouchCenter(e.touches[0], e.touches[1]),
      };
    } else if (e.touches.length === 1) {
      touchStateRef.current = {
        ...touchStateRef.current,
        startPoint: { x: e.touches[0].clientX, y: e.touches[0].clientY },
        initialPan: pan,
      };
    }
  };

  const handleTouchMove = (e: React.TouchEvent) => {
    if (e.touches.length === 2) {
      e.preventDefault();
      const currentDist = getTouchDistance(e.touches[0], e.touches[1]);
      if (touchStateRef.current.initialDist > 0) {
        const factor = currentDist / touchStateRef.current.initialDist;
        const newScale = clampScale(touchStateRef.current.initialScale * factor);
        onScaleChange(newScale);
      }
    } else if (e.touches.length === 1 && scale > 1.0 && containerRef.current) {
      const deltaX = e.touches[0].clientX - touchStateRef.current.startPoint.x;
      const deltaY = e.touches[0].clientY - touchStateRef.current.startPoint.y;

      const targetPan = {
        x: touchStateRef.current.initialPan.x + deltaX,
        y: touchStateRef.current.initialPan.y + deltaY,
      };

      const container = {
        width: containerRef.current.clientWidth,
        height: containerRef.current.clientHeight,
      };
      const pageDims = {
        width: page.width || 1200,
        height: page.height || 1800,
      };

      setPan(clampPanOffset(targetPan, container, pageDims, scale));
    }
  };

  const cursorClass = scale > 1.0 ? (isDragging ? 'cursor-grabbing' : 'cursor-grab') : 'cursor-default';

  return (
    <div
      ref={containerRef}
      onMouseDown={handleMouseDown}
      onWheel={handleWheel}
      onDoubleClick={handleDoubleClick}
      onTouchStart={handleTouchStart}
      onTouchMove={handleTouchMove}
      className={`relative flex h-full w-full select-none items-center justify-center overflow-hidden bg-slate-950 p-4 ${cursorClass}`}
    >
      {/* Loading Spinner */}
      {isLoading && (
        <div className="absolute inset-0 z-10 flex flex-col items-center justify-center bg-slate-950/70 backdrop-blur-sm">
          <RefreshCw className="h-8 w-8 animate-spin text-blue-500 mb-2" />
          <span className="text-xs font-medium text-slate-300">
            Rendering Page {page.page_number}...
          </span>
        </div>
      )}

      {/* Error Fallback */}
      {hasError ? (
        <div className="flex flex-col items-center justify-center p-8 text-center text-slate-400">
          <AlertCircle className="h-10 w-10 text-red-500 mb-2" />
          <p className="text-sm font-semibold text-white">Failed to load Page {page.page_number}</p>
          <p className="text-xs text-slate-400 mt-1">Image resource may be unavailable.</p>
        </div>
      ) : (
        <div
          style={{
            transform: `translate(${pan.x}px, ${pan.y}px) scale(${scale})`,
            transformOrigin: 'center center',
            transition: isDragging ? 'none' : 'transform 0.15s ease-out',
          }}
          className="relative max-h-full max-w-full flex items-center justify-center"
        >
          <img
            ref={imageRef}
            src={imageUrl}
            alt={`Page ${page.page_number}`}
            onLoad={() => setIsLoading(false)}
            onError={() => {
              setIsLoading(false);
              setHasError(true);
            }}
            draggable={false}
            className={`max-h-[85vh] w-auto max-w-[95vw] rounded shadow-2xl object-contain transition-opacity duration-300 ${
              isLoading ? 'opacity-0' : 'opacity-100'
            } ${nightMode ? 'invert hue-rotate-180 brightness-95 contrast-125' : ''}`}
          />
        </div>
      )}
    </div>
  );
};
