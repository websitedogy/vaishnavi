import { ManifestBranding, ManifestEdition, ManifestPage, PublicManifest } from './manifest.types';

export interface PublicPublication {
  id: number;
  slug: string;
  name: string;
  newspaper_name: string;
  company_name?: string;
  tagline?: string;
  about?: string;
  publisher_name?: string;
  editor_name?: string;
  contact_email?: string;
  contact_phone?: string;
  address?: string;
  state?: string;
  district?: string;
  default_language: string;
  primary_domain?: string;
}

export interface PublicBranding {
  newspaper_name: string;
  logo_url?: string;
  favicon_url?: string;
  primary_color: string;
  secondary_color: string;
  accent_color?: string;
  typography: string;
  header_config: {
    style?: string;
    show_date?: boolean;
    show_search?: boolean;
    show_archive_btn?: boolean;
  };
  footer_config: {
    copyright?: string;
    show_powered_by?: boolean;
  };
  social_links: Record<string, string>;
  custom_css?: string;
  is_published: boolean;
}

export interface EditionCardData {
  id: number;
  edition_code: string;
  edition_title?: string;
  edition_name: string;
  category: string;
  description?: string;
  publish_date: string;
  total_pages: number;
  thumbnail_url?: string;
}

export type PublicEditionCard = EditionCardData;

export interface PublicCategory {
  id: number | string;
  name: string;
  slug: string;
  edition_count: number;
  description?: string;
}

export interface HomePageData {
  publication: PublicPublication;
  branding: PublicBranding;
  has_editions: boolean;
  latest_edition: PublicManifest | null;
  recent_editions: EditionCardData[];
  categories: string[] | PublicCategory[];
  seo: SeoMetadata;
}

export interface ArchiveCalendarData {
  year: number;
  month: number;
  total_editions: number;
  available_years: number[];
  published_dates: string[];
  editions: EditionCardData[];
}

export type ArchiveData = ArchiveCalendarData;

export interface SearchResultData {
  query: string;
  search_mode: string;
  results: EditionCardData[];
  total?: number;
  editions?: EditionCardData[];
}

export type SearchResultsData = SearchResultData;

export interface SeoMetadata {
  title: string;
  description: string;
  canonical_url: string;
  og_type: string;
  og_title: string;
  og_description: string;
  og_image?: string | null;
  twitter_card: string;
  twitter_title: string;
  twitter_description: string;
  twitter_image?: string | null;
  publisher?: string;
  language: string;
}

export interface PaginationMeta {
  current_page: number;
  last_page: number;
  per_page: number;
  total: number;
}
