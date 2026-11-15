'use client';

import React from 'react';
import '../styles/public-view.css';

interface ReaderLayoutProps {
  children: React.ReactNode;
}

export const ReaderLayout: React.FC<ReaderLayoutProps> = ({ children }) => {
  return (
    <div className="h-[100dvh] w-full bg-slate-950 text-white overflow-hidden select-none">
      {children}
    </div>
  );
};
