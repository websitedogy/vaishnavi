'use client';

import React from 'react';
import Link from 'next/link';
import { PublicBranding, PublicPublication } from '../../types/public.types';
import { resolvePublicImageUrl } from '../../utils/security';
import { Globe, Mail, Phone, MapPin } from 'lucide-react';

interface FooterProps {
  tenantSlug: string;
  publication: PublicPublication;
  branding: PublicBranding;
}

export const Footer: React.FC<FooterProps> = ({
  tenantSlug,
  publication,
  branding,
}) => {
  const logoUrl = resolvePublicImageUrl(branding.logo_url);

  return (
    <footer className="border-t border-slate-200 bg-slate-900 text-slate-300">
      <div className="mx-auto max-w-7xl px-4 py-12 md:py-16">
        <div className="grid grid-cols-1 md:grid-cols-4 gap-8">
          {/* Col 1: Brand & About */}
          <div className="space-y-4 md:col-span-1">
            {logoUrl ? (
              <img
                src={logoUrl}
                alt={branding.newspaper_name}
                className="h-10 object-contain brightness-0 invert"
              />
            ) : (
              <span className="font-masthead text-2xl font-bold text-white">
                {branding.newspaper_name}
              </span>
            )}
            <p className="text-sm text-slate-400 leading-relaxed">
              {publication.tagline || 'Leading regional digital newspaper publishing daily broadsheet editions online.'}
            </p>
            {publication.publisher_name && (
              <p className="text-xs text-slate-500">
                <span className="font-medium text-slate-400">Publisher:</span> {publication.publisher_name}
              </p>
            )}
          </div>

          {/* Col 2: Quick Links */}
          <div>
            <h4 className="text-sm font-semibold uppercase tracking-wider text-white mb-4">
              Digital ePaper
            </h4>
            <ul className="space-y-2.5 text-sm">
              <li>
                <Link href={`/${tenantSlug}/epaper`} className="hover:text-white transition">
                  Today's ePaper
                </Link>
              </li>
              <li>
                <Link href={`/${tenantSlug}/editions`} className="hover:text-white transition">
                  Published Editions
                </Link>
              </li>
              <li>
                <Link href={`/${tenantSlug}/archive`} className="hover:text-white transition">
                  Monthly Archive
                </Link>
              </li>
              <li>
                <Link href={`/${tenantSlug}/search`} className="hover:text-white transition">
                  Search Editions
                </Link>
              </li>
            </ul>
          </div>

          {/* Col 3: Publication Info */}
          <div>
            <h4 className="text-sm font-semibold uppercase tracking-wider text-white mb-4">
              Information
            </h4>
            <ul className="space-y-2.5 text-sm">
              <li>
                <Link href={`/${tenantSlug}/about`} className="hover:text-white transition">
                  About Newspaper
                </Link>
              </li>
              <li>
                <Link href={`/${tenantSlug}/contact`} className="hover:text-white transition">
                  Contact Editorial
                </Link>
              </li>
              <li>
                <span className="text-slate-500 text-xs">
                  Language: <strong className="text-slate-400 uppercase">{publication.default_language}</strong>
                </span>
              </li>
            </ul>
          </div>

          {/* Col 4: Contact & Office */}
          <div className="space-y-3">
            <h4 className="text-sm font-semibold uppercase tracking-wider text-white mb-4">
              Editorial Office
            </h4>
            {publication.contact_email && (
              <div className="flex items-center gap-2 text-sm text-slate-400">
                <Mail className="h-4 w-4 shrink-0 text-slate-500" />
                <a href={`mailto:${publication.contact_email}`} className="hover:underline">
                  {publication.contact_email}
                </a>
              </div>
            )}
            {publication.contact_phone && (
              <div className="flex items-center gap-2 text-sm text-slate-400">
                <Phone className="h-4 w-4 shrink-0 text-slate-500" />
                <span>{publication.contact_phone}</span>
              </div>
            )}
            {publication.address && (
              <div className="flex items-start gap-2 text-sm text-slate-400">
                <MapPin className="h-4 w-4 shrink-0 mt-0.5 text-slate-500" />
                <span>{publication.address}</span>
              </div>
            )}
          </div>
        </div>

        {/* Bottom Copyright & SaaS Badge */}
        <div className="mt-12 border-t border-slate-800 pt-6 flex flex-col sm:flex-row items-center justify-between text-xs text-slate-500 gap-4">
          <p>{branding.footer_config.copyright || `© ${new Date().getFullYear()} ${branding.newspaper_name}. All rights reserved.`}</p>
          <div className="flex items-center gap-2">
            <span>Powered by</span>
            <span className="font-semibold text-slate-400">DOGY ePAPER SaaS</span>
          </div>
        </div>
      </div>
    </footer>
  );
};
