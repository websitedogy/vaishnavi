import React from 'react';

export const EditionCardSkeleton: React.FC = () => {
  return (
    <div className="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm animate-pulse">
      {/* Thumbnail Aspect Box */}
      <div className="relative aspect-[3/4] w-full bg-slate-200" />
      {/* Content */}
      <div className="p-4 space-y-2.5">
        <div className="h-4 w-3/4 rounded bg-slate-200" />
        <div className="flex items-center justify-between pt-1">
          <div className="h-3 w-1/3 rounded bg-slate-200" />
          <div className="h-3 w-1/4 rounded bg-slate-200" />
        </div>
      </div>
    </div>
  );
};

export const PageGridSkeleton: React.FC<{ count?: number }> = ({ count = 6 }) => {
  return (
    <div className="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 gap-4">
      {Array.from({ length: count }).map((_, i) => (
        <EditionCardSkeleton key={i} />
      ))}
    </div>
  );
};

export const LatestEditionHeroSkeleton: React.FC = () => {
  return (
    <div className="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm animate-pulse p-6 md:p-8">
      <div className="grid grid-cols-1 md:grid-cols-12 gap-6 items-center">
        {/* Left Column: Cover Preview Skeleton */}
        <div className="md:col-span-5 flex justify-center">
          <div className="aspect-[3/4] w-full max-w-[280px] rounded-lg bg-slate-200 shadow-inner" />
        </div>
        {/* Right Column: Metadata Skeleton */}
        <div className="md:col-span-7 space-y-4">
          <div className="h-5 w-24 rounded-full bg-slate-200" />
          <div className="h-8 w-4/5 rounded bg-slate-200" />
          <div className="h-4 w-1/2 rounded bg-slate-200" />
          <div className="grid grid-cols-2 sm:grid-cols-3 gap-3 pt-3">
            <div className="h-14 rounded-lg bg-slate-100" />
            <div className="h-14 rounded-lg bg-slate-100" />
            <div className="h-14 rounded-lg bg-slate-100" />
          </div>
          <div className="pt-4 flex gap-3">
            <div className="h-11 w-44 rounded-lg bg-slate-200" />
            <div className="h-11 w-32 rounded-lg bg-slate-100" />
          </div>
        </div>
      </div>
    </div>
  );
};

export const ReaderCanvasSkeleton: React.FC = () => {
  return (
    <div className="relative flex h-full w-full items-center justify-center bg-slate-950/90 animate-pulse">
      <div className="aspect-[3/4] max-h-[85vh] w-auto max-w-[90vw] rounded bg-slate-800 shadow-2xl flex flex-col items-center justify-center p-8">
        <div className="h-10 w-10 rounded-full border-4 border-slate-600 border-t-white animate-spin mb-4" />
        <div className="h-4 w-40 rounded bg-slate-700 mb-2" />
        <div className="h-3 w-24 rounded bg-slate-800" />
      </div>
    </div>
  );
};

export const ArchiveCalendarSkeleton: React.FC = () => {
  return (
    <div className="rounded-xl border border-slate-200 bg-white p-6 shadow-sm animate-pulse space-y-4">
      <div className="flex items-center justify-between">
        <div className="h-6 w-36 rounded bg-slate-200" />
        <div className="flex gap-2">
          <div className="h-8 w-8 rounded bg-slate-100" />
          <div className="h-8 w-8 rounded bg-slate-100" />
        </div>
      </div>
      <div className="grid grid-cols-7 gap-2 pt-2">
        {Array.from({ length: 35 }).map((_, i) => (
          <div key={i} className="aspect-square rounded-lg bg-slate-100" />
        ))}
      </div>
    </div>
  );
};
