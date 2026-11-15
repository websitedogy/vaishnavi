export interface ManifestPage {
  page_number: number;
  page_title: string;
  image_url: string;
  thumbnail_url?: string;
  width: number;
  height: number;
  rotation: number;
  is_visible: boolean;
}

export interface ManifestEdition {
  id: number;
  edition_code: string;
  edition_title?: string;
  edition_name: string;
  edition_language: string;
  category: string;
  description?: string;
  publish_date: string;
  total_pages: number;
  thumbnail_url?: string;
  checksum_sha256?: string;
  published_at?: string;
}

export interface ManifestPublication {
  name: string;
  slug: string;
  domain?: string;
  tagline?: string;
  publisher_name?: string;
  editor_name?: string;
  contact_email?: string;
  contact_phone?: string;
  logo_url?: string;
  favicon_url?: string;
}

export interface ManifestBranding {
  primary_color: string;
  secondary_color: string;
  font_family: string;
  header_style?: string;
  custom_css?: string;
}

export interface PublicManifest {
  schema_version: string;
  edition: ManifestEdition;
  publication: ManifestPublication;
  branding?: ManifestBranding;
  pages: ManifestPage[];
  compiled_at: string;
}
