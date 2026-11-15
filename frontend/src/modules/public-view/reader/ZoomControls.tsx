'use client';

import React from 'react';
import { ZoomIn, ZoomOut, Maximize2, Minimize2, RotateCcw } from 'lucide-react';
import { MIN_SCALE, MAX_SCALE } from './readerUtils';

interface ZoomControlsProps {
  scale: number;
  onZoomIn: () => void;
  onZoomOut: () => void;
  onFitWidth: () => void;
  onFitScreen: () => void;
  onReset: () => void;
  disabled?: boolean;
}

export const ZoomControls: React.FC<ZoomControlsProps> = ({
  scale,
  onZoomIn,
  onZoomOut,
  onFitWidth,
  onFitScreen,
  onReset,
  disabled = false,
}) => {
  const percentage = Math.round(scale * 100);

  return (
    <div className="flex items-center space-x-1 bg-slate-900/90 backdrop-blur rounded-lg border border-slate-800 p-1 text-slate-200 shadow-md">
      {/* Zoom Out */}
      <button
        type="button"
        onClick={onZoomOut}
        disabled={disabled || scale <= MIN_SCALE}
        className="rounded p-1.5 hover:bg-slate-800 hover:text-white disabled:opacity-40 disabled:hover:bg-transparent transition"
        title="Zoom Out (-)"
        aria-label="Zoom Out"
      >
        <ZoomOut className="h-4 w-4" />
      </button>

      {/* Percentage Readout / Quick 100% Reset */}
      <button
        type="button"
        onClick={onReset}
        disabled={disabled}
        className="min-w-[52px] px-1.5 py-1 text-center font-mono text-xs font-semibold text-slate-300 hover:text-white hover:bg-slate-800 rounded transition"
        title="Reset to 100% (0)"
      >
        {percentage}%
      </button>

      {/* Zoom In */}
      <button
        type="button"
        onClick={onZoomIn}
        disabled={disabled || scale >= MAX_SCALE}
        className="rounded p-1.5 hover:bg-slate-800 hover:text-white disabled:opacity-40 disabled:hover:bg-transparent transition"
        title="Zoom In (+)"
        aria-label="Zoom In"
      >
        <ZoomIn className="h-4 w-4" />
      </button>

      <div className="h-4 w-px bg-slate-800 mx-0.5" />

      {/* Fit to Width */}
      <button
        type="button"
        onClick={onFitWidth}
        disabled={disabled}
        className="rounded p-1.5 hover:bg-slate-800 hover:text-white disabled:opacity-40 transition"
        title="Fit to Width (W)"
        aria-label="Fit to Width"
      >
        <Maximize2 className="h-4 w-4 rotate-45" />
      </button>

      {/* Fit to Screen */}
      <button
        type="button"
        onClick={onFitScreen}
        disabled={disabled}
        className="rounded p-1.5 hover:bg-slate-800 hover:text-white disabled:opacity-40 transition"
        title="Fit Page to Screen (H)"
        aria-label="Fit to Screen"
      >
        <Minimize2 className="h-4 w-4" />
      </button>
    </div>
  );
};
