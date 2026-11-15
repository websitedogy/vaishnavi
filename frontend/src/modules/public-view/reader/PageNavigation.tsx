'use client';

import React, { useState, useEffect } from 'react';
import { ChevronLeft, ChevronRight, ChevronsLeft, ChevronsRight } from 'lucide-react';

interface PageNavigationProps {
  currentPage: number;
  totalPages: number;
  onPageChange: (page: number) => void;
  disabled?: boolean;
}

export const PageNavigation: React.FC<PageNavigationProps> = ({
  currentPage,
  totalPages,
  onPageChange,
  disabled = false,
}) => {
  const [inputVal, setInputVal] = useState(String(currentPage));

  useEffect(() => {
    setInputVal(String(currentPage));
  }, [currentPage]);

  const handleInputChange = (e: React.ChangeEvent<HTMLInputElement>) => {
    setInputVal(e.target.value);
  };

  const handleInputSubmit = (e: React.FormEvent) => {
    e.preventDefault();
    const pageNum = parseInt(inputVal, 10);
    if (!isNaN(pageNum) && pageNum >= 1 && pageNum <= totalPages) {
      onPageChange(pageNum);
    } else {
      setInputVal(String(currentPage));
    }
  };

  const handleInputBlur = () => {
    const pageNum = parseInt(inputVal, 10);
    if (!isNaN(pageNum) && pageNum >= 1 && pageNum <= totalPages) {
      onPageChange(pageNum);
    } else {
      setInputVal(String(currentPage));
    }
  };

  const hasPrev = currentPage > 1;
  const hasNext = currentPage < totalPages;

  return (
    <div className="flex items-center space-x-1 bg-slate-900/90 backdrop-blur rounded-lg border border-slate-800 p-1 text-slate-200 shadow-md">
      {/* First Page */}
      <button
        type="button"
        onClick={() => onPageChange(1)}
        disabled={disabled || !hasPrev}
        className="hidden sm:inline-flex rounded p-1.5 hover:bg-slate-800 hover:text-white disabled:opacity-40 disabled:hover:bg-transparent transition"
        title="First Page (Home)"
        aria-label="First Page"
      >
        <ChevronsLeft className="h-4 w-4" />
      </button>

      {/* Prev Page */}
      <button
        type="button"
        onClick={() => onPageChange(currentPage - 1)}
        disabled={disabled || !hasPrev}
        className="rounded p-1.5 hover:bg-slate-800 hover:text-white disabled:opacity-40 disabled:hover:bg-transparent transition"
        title="Previous Page (Left Arrow)"
        aria-label="Previous Page"
      >
        <ChevronLeft className="h-4 w-4" />
      </button>

      {/* Page Form Input */}
      <form onSubmit={handleInputSubmit} className="flex items-center space-x-1.5 px-2">
        <label htmlFor="reader-page-input" className="sr-only">
          Current Page
        </label>
        <input
          id="reader-page-input"
          type="text"
          value={inputVal}
          onChange={handleInputChange}
          onBlur={handleInputBlur}
          disabled={disabled}
          className="w-10 rounded border border-slate-700 bg-slate-800 px-1.5 py-0.5 text-center font-mono text-xs font-semibold text-white focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500"
        />
        <span className="font-mono text-xs text-slate-400">/</span>
        <span className="font-mono text-xs font-semibold text-slate-300">{totalPages}</span>
      </form>

      {/* Next Page */}
      <button
        type="button"
        onClick={() => onPageChange(currentPage + 1)}
        disabled={disabled || !hasNext}
        className="rounded p-1.5 hover:bg-slate-800 hover:text-white disabled:opacity-40 disabled:hover:bg-transparent transition"
        title="Next Page (Right Arrow)"
        aria-label="Next Page"
      >
        <ChevronRight className="h-4 w-4" />
      </button>

      {/* Last Page */}
      <button
        type="button"
        onClick={() => onPageChange(totalPages)}
        disabled={disabled || !hasNext}
        className="hidden sm:inline-flex rounded p-1.5 hover:bg-slate-800 hover:text-white disabled:opacity-40 disabled:hover:bg-transparent transition"
        title="Last Page (End)"
        aria-label="Last Page"
      >
        <ChevronsRight className="h-4 w-4" />
      </button>
    </div>
  );
};
