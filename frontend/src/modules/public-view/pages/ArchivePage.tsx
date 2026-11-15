'use client';

import React, { useState } from 'react';
import Link from 'next/link';
import { Breadcrumb } from '@/modules/public-view/components/Breadcrumb/Breadcrumb';
import { ArchiveData, PublicEditionCard } from '@/modules/public-view/types/public.types';
import { EditionCard } from '@/modules/public-view/components/EditionCard/EditionCard';
import {
  Calendar as CalendarIcon,
  ChevronLeft,
  ChevronRight,
  BookOpen,
  Layers,
} from 'lucide-react';
import { formatDate } from '@/modules/public-view/utils/formatters';

interface ArchivePageProps {
  tenantSlug: string;
  initialYear: number;
  initialMonth: number;
  archiveData: ArchiveData;
  primaryColor?: string;
}

const MONTH_NAMES = [
  'January',
  'February',
  'March',
  'April',
  'May',
  'June',
  'July',
  'August',
  'September',
  'October',
  'November',
  'December',
];

const WEEKDAY_NAMES = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];

export const ArchivePage: React.FC<ArchivePageProps> = ({
  tenantSlug,
  initialYear,
  initialMonth,
  archiveData,
  primaryColor = '#1e3a8a',
}) => {
  const [selectedYear, setSelectedYear] = useState(initialYear);
  const [selectedMonth, setSelectedMonth] = useState(initialMonth);

  // Map calendar days
  const daysInMonth = new Date(selectedYear, selectedMonth, 0).getDate();
  const firstDayWeekday = new Date(selectedYear, selectedMonth - 1, 1).getDay();

  // Create lookup dictionary for dates with published editions
  const activeDatesMap: Record<string, PublicEditionCard[]> = {};
  archiveData.editions.forEach((ed: PublicEditionCard) => {
    const d = ed.publish_date;
    if (!activeDatesMap[d]) {
      activeDatesMap[d] = [];
    }
    activeDatesMap[d].push(ed);
  });

  const handlePrevMonth = () => {
    if (selectedMonth === 1) {
      setSelectedYear((y) => y - 1);
      setSelectedMonth(12);
    } else {
      setSelectedMonth((m) => m - 1);
    }
  };

  const handleNextMonth = () => {
    if (selectedMonth === 12) {
      setSelectedYear((y) => y + 1);
      setSelectedMonth(1);
    } else {
      setSelectedMonth((m) => m + 1);
    }
  };

  const breadcrumbItems = [
    { label: 'Edition Archive', active: true },
  ];

  return (
    <div className="space-y-6">
      <Breadcrumb tenantSlug={tenantSlug} items={breadcrumbItems} />

      <div className="mx-auto max-w-7xl px-4 space-y-8">
        {/* Header */}
        <div className="border-b border-slate-200 pb-4">
          <h1 className="text-xl md:text-2xl font-bold text-slate-900 font-masthead flex items-center gap-2">
            <CalendarIcon className="h-6 w-6 text-blue-600" />
            <span>Digital ePaper Archive</span>
          </h1>
          <p className="text-xs text-slate-500 mt-1">
            Browse published historical editions by calendar date or month
          </p>
        </div>

        {/* Calendar Navigator Card */}
        <div className="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
          {/* Calendar Header with Controls */}
          <div className="flex items-center justify-between border-b border-slate-100 pb-4 mb-4">
            <h2 className="text-base md:text-lg font-bold text-slate-800">
              {MONTH_NAMES[selectedMonth - 1]} {selectedYear}
            </h2>

            <div className="flex items-center space-x-2">
              <button
                type="button"
                onClick={handlePrevMonth}
                className="rounded-lg border border-slate-200 p-2 text-slate-600 hover:bg-slate-50 hover:text-slate-900 transition"
                aria-label="Previous Month"
              >
                <ChevronLeft className="h-4 w-4" />
              </button>
              <button
                type="button"
                onClick={handleNextMonth}
                className="rounded-lg border border-slate-200 p-2 text-slate-600 hover:bg-slate-50 hover:text-slate-900 transition"
                aria-label="Next Month"
              >
                <ChevronRight className="h-4 w-4" />
              </button>
            </div>
          </div>

          {/* Weekdays Bar */}
          <div className="grid grid-cols-7 gap-1 text-center font-semibold text-xs text-slate-500 mb-2">
            {WEEKDAY_NAMES.map((d) => (
              <div key={d} className="py-1">
                {d}
              </div>
            ))}
          </div>

          {/* Month Days Grid */}
          <div className="grid grid-cols-7 gap-1 sm:gap-2">
            {/* Blank offset tiles for first day of month */}
            {Array.from({ length: firstDayWeekday }).map((_, i) => (
              <div key={`blank-${i}`} className="aspect-square rounded-lg bg-slate-50/50" />
            ))}

            {/* Days 1 through daysInMonth */}
            {Array.from({ length: daysInMonth }).map((_, i) => {
              const dayNum = i + 1;
              const dateStr = `${selectedYear}-${String(selectedMonth).padStart(2, '0')}-${String(
                dayNum
              ).padStart(2, '0')}`;
              const dayEditions = activeDatesMap[dateStr] || [];
              const hasEditions = dayEditions.length > 0;

              return (
                <div
                  key={dateStr}
                  className={`relative flex flex-col justify-between aspect-square rounded-lg p-1.5 sm:p-2 border transition ${
                    hasEditions
                      ? 'border-blue-300 bg-blue-50/60 hover:bg-blue-100/80 shadow-sm'
                      : 'border-slate-100 bg-white text-slate-400'
                  }`}
                >
                  <div className="flex items-center justify-between">
                    <span
                      className={`text-xs sm:text-sm font-semibold ${
                        hasEditions ? 'text-blue-900' : 'text-slate-500'
                      }`}
                    >
                      {dayNum}
                    </span>
                    {hasEditions && (
                      <span className="rounded-full bg-blue-600 px-1.5 py-0.2 text-[9px] font-bold text-white">
                        {dayEditions.length}
                      </span>
                    )}
                  </div>

                  {hasEditions && (
                    <div className="mt-auto">
                      <Link
                        href={`/${tenantSlug}/epaper/${dayEditions[0].edition_code}`}
                        className="flex items-center justify-center gap-1 w-full rounded bg-blue-600 py-1 text-[10px] font-semibold text-white hover:bg-blue-700 transition"
                        title={`Read ${dayEditions[0].edition_name}`}
                      >
                        <BookOpen className="h-3 w-3" />
                        <span className="hidden sm:inline">Read</span>
                      </Link>
                    </div>
                  )}
                </div>
              );
            })}
          </div>
        </div>

        {/* Editions List for Selected Month */}
        <div className="space-y-4">
          <div className="flex items-center justify-between border-b border-slate-200 pb-2">
            <h3 className="text-base font-bold text-slate-800 flex items-center gap-2">
              <Layers className="h-4 w-4 text-slate-500" />
              <span>Editions Published in {MONTH_NAMES[selectedMonth - 1]} {selectedYear}</span>
            </h3>
            <span className="text-xs text-slate-500 font-medium">
              {archiveData.editions.length} Editions
            </span>
          </div>

          {archiveData.editions.length > 0 ? (
            <div className="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 gap-4">
              {archiveData.editions.map((ed: PublicEditionCard) => (
                <EditionCard
                  key={ed.id}
                  tenantSlug={tenantSlug}
                  edition={ed}
                  primaryColor={primaryColor}
                />
              ))}
            </div>
          ) : (
            <div className="rounded-xl border border-slate-200 bg-white p-8 text-center text-slate-500">
              <p className="text-sm">No editions were published in this month.</p>
            </div>
          )}
        </div>
      </div>
    </div>
  );
};
