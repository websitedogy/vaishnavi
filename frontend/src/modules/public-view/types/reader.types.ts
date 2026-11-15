import { ManifestBranding, ManifestEdition, ManifestPage, ManifestPublication } from './manifest.types';

export interface AdjacentEdition {
  id: number;
  edition_code: string;
  edition_title?: string;
  edition_name: string;
  publish_date: string;
}

export interface ReaderNavigation {
  current_edition_code: string;
  previous_edition?: AdjacentEdition | null;
  next_edition?: AdjacentEdition | null;
}

export interface ReaderPermissions {
  allow_page_download: boolean;
  allow_pdf_download: boolean;
  allow_share: boolean;
  allow_print: boolean;
  allow_download?: boolean;
}

export interface ReaderPayload {
  edition: ManifestEdition;
  publication: ManifestPublication;
  branding: ManifestBranding;
  pages: ManifestPage[];
  navigation: ReaderNavigation;
  permissions: ReaderPermissions;
  compiled_at: string;
}

export type DigitalReaderPayload = ReaderPayload;
export type DigitalReaderPage = ManifestPage;
export type DigitalReaderPermissions = ReaderPermissions;
export type DigitalReaderNavigation = ReaderNavigation;

export type ZoomMode = 'fit-width' | 'fit-height' | 'actual' | 'custom';

export interface ZoomState {
  scale: number;
  mode: ZoomMode;
  translateX: number;
  translateY: number;
  isDragging: boolean;
}
