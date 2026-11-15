'use client';

import React, { useState } from 'react';
import { Settings, Moon, Sun, HelpCircle, X } from 'lucide-react';

interface ReaderSettingsProps {
  nightMode: boolean;
  onToggleNightMode: () => void;
}

export const ReaderSettings: React.FC<ReaderSettingsProps> = ({
  nightMode,
  onToggleNightMode,
}) => {
  const [isOpen, setIsOpen] = useState(false);
  const [showShortcuts, setShowShortcuts] = useState(false);

  return (
    <div className="relative">
      <button
        type="button"
        onClick={() => setIsOpen(!isOpen)}
        className="rounded-lg border border-slate-800 bg-slate-900/90 p-2 text-slate-300 backdrop-blur hover:bg-slate-800 hover:text-white transition shadow-md"
        title="Reader Settings"
        aria-label="Reader Settings"
      >
        <Settings className="h-4 w-4" />
      </button>

      {isOpen && (
        <div className="absolute right-0 bottom-12 sm:bottom-auto sm:top-12 z-50 w-56 rounded-xl border border-slate-800 bg-slate-900 p-3 shadow-2xl text-slate-200 text-xs">
          <div className="flex items-center justify-between border-b border-slate-800 pb-2 mb-2">
            <span className="font-semibold text-white">Reader Settings</span>
            <button
              onClick={() => setIsOpen(false)}
              className="text-slate-400 hover:text-white"
            >
              <X className="h-3.5 w-3.5" />
            </button>
          </div>

          {/* Night Mode Toggle */}
          <div className="flex items-center justify-between py-1.5">
            <span className="flex items-center gap-2">
              {nightMode ? <Sun className="h-3.5 w-3.5 text-amber-400" /> : <Moon className="h-3.5 w-3.5 text-slate-400" />}
              <span>Invert / Night Mode</span>
            </span>
            <input
              type="checkbox"
              checked={nightMode}
              onChange={onToggleNightMode}
              className="h-4 w-4 rounded border-slate-700 bg-slate-800 text-blue-600 focus:ring-0"
            />
          </div>

          {/* Keyboard Shortcuts Info */}
          <div className="border-t border-slate-800 pt-2 mt-2">
            <button
              type="button"
              onClick={() => setShowShortcuts(true)}
              className="flex w-full items-center justify-between rounded px-2 py-1 hover:bg-slate-800 text-slate-300 transition"
            >
              <span className="flex items-center gap-1.5">
                <HelpCircle className="h-3.5 w-3.5 text-slate-400" />
                <span>Keyboard Shortcuts</span>
              </span>
              <span className="text-[10px] text-slate-500 font-mono">?</span>
            </button>
          </div>
        </div>
      )}

      {/* Keyboard Shortcuts Dialog */}
      {showShortcuts && (
        <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/60 backdrop-blur-sm p-4">
          <div className="w-full max-w-sm rounded-xl border border-slate-800 bg-slate-900 p-5 text-slate-200 shadow-2xl">
            <div className="flex items-center justify-between border-b border-slate-800 pb-3 mb-3">
              <h4 className="font-semibold text-white text-sm">Keyboard Shortcuts</h4>
              <button
                onClick={() => setShowShortcuts(false)}
                className="text-slate-400 hover:text-white"
              >
                <X className="h-4 w-4" />
              </button>
            </div>
            <ul className="space-y-2 text-xs">
              <li className="flex justify-between">
                <span className="text-slate-400">Next / Prev Page</span>
                <kbd className="rounded bg-slate-800 px-1.5 py-0.5 font-mono text-[11px] text-slate-300">
                  → / ←
                </kbd>
              </li>
              <li className="flex justify-between">
                <span className="text-slate-400">First / Last Page</span>
                <kbd className="rounded bg-slate-800 px-1.5 py-0.5 font-mono text-[11px] text-slate-300">
                  Home / End
                </kbd>
              </li>
              <li className="flex justify-between">
                <span className="text-slate-400">Zoom In / Out</span>
                <kbd className="rounded bg-slate-800 px-1.5 py-0.5 font-mono text-[11px] text-slate-300">
                  + / -
                </kbd>
              </li>
              <li className="flex justify-between">
                <span className="text-slate-400">Reset Zoom (100%)</span>
                <kbd className="rounded bg-slate-800 px-1.5 py-0.5 font-mono text-[11px] text-slate-300">
                  0
                </kbd>
              </li>
              <li className="flex justify-between">
                <span className="text-slate-400">Toggle Fullscreen</span>
                <kbd className="rounded bg-slate-800 px-1.5 py-0.5 font-mono text-[11px] text-slate-300">
                  F
                </kbd>
              </li>
              <li className="flex justify-between">
                <span className="text-slate-400">Pan / Drag</span>
                <span className="text-slate-400 italic">Click + Drag</span>
              </li>
            </ul>
          </div>
        </div>
      )}
    </div>
  );
};
