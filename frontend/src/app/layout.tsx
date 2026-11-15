import type { Metadata } from 'next';
import './globals.css';

export const metadata: Metadata = {
  title: 'DOGY ePAPER SaaS – Enterprise Super Admin Control Center',
  description: 'Multi-Tenant Digital Newspaper Publishing Cloud Platform - Super Admin Control Center',
};

export default function RootLayout({
  children,
}: {
  children: React.ReactNode;
}) {
  return (
    <html lang="en">
      <body className="bg-slate-50 text-slate-900 antialiased">{children}</body>
    </html>
  );
}
