import { PublicBranding, PublicPublication } from '../types/public.types';

export const DEFAULT_BRANDING: PublicBranding = {
  newspaper_name: 'DOGY ePaper',
  primary_color: '#1E3A8A',
  secondary_color: '#0284C7',
  typography: 'Inter',
  header_config: {
    style: 'standard',
    show_date: true,
    show_search: true,
    show_archive_btn: true,
  },
  footer_config: {
    copyright: `© ${new Date().getFullYear()} DOGY ePAPER. All rights reserved.`,
    show_powered_by: true,
  },
  social_links: {},
  custom_css: '',
  is_published: true,
};

export const DEFAULT_PUBLICATION: PublicPublication = {
  id: 0,
  slug: 'default',
  name: 'DOGY ePaper Publication',
  newspaper_name: 'DOGY ePaper',
  tagline: 'Leading Digital Newspaper Platform',
  default_language: 'te',
};
