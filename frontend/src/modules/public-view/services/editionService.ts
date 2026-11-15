import { publicApiClient, ApiResponse } from './publicApi';
import { HomePageData, EditionCardData, ArchiveCalendarData, PublicCategory } from '../types/public.types';
import { PublicManifest } from '../types/manifest.types';

export const editionService = {
  /**
   * Fetch complete homepage payload for the tenant.
   */
  async getHomePageData(tenantSlug: string): Promise<HomePageData> {
    const res = await publicApiClient.get<ApiResponse<HomePageData>>(`/${tenantSlug}/home`);
    const data = res.data.data;
    // Normalize categories to PublicCategory objects if they are strings
    if (data && Array.isArray(data.categories)) {
      data.categories = data.categories.map((c: any, idx: number) => {
        if (typeof c === 'string') {
          return {
            id: idx + 1,
            name: c,
            slug: c.toLowerCase().replace(/\s+/g, '-'),
            edition_count: 0,
          };
        }
        return c;
      });
    }
    return data;
  },

  /**
   * Fetch latest published edition manifest.
   */
  async getLatestEdition(tenantSlug: string): Promise<PublicManifest> {
    const res = await publicApiClient.get<ApiResponse<PublicManifest>>(`/${tenantSlug}/latest`);
    return res.data.data;
  },

  /**
   * Fetch specific published edition manifest by date or edition code.
   */
  async getEdition(tenantSlug: string, dateOrCode: string): Promise<PublicManifest> {
    const res = await publicApiClient.get<ApiResponse<PublicManifest>>(`/${tenantSlug}/editions/${dateOrCode}`);
    return res.data.data;
  },

  /**
   * Fetch paginated list of published editions.
   */
  async getEditions(
    tenantSlug: string,
    page: number = 1,
    perPage: number = 12,
    category?: string
  ): Promise<{ data: EditionCardData[]; meta: any }> {
    const params: Record<string, any> = { page, per_page: perPage };
    if (category) params.category = category;

    const res = await publicApiClient.get<ApiResponse<EditionCardData[]>>(`/${tenantSlug}/editions`, { params });
    return {
      data: res.data.data,
      meta: res.data.meta || { current_page: page, last_page: 1, per_page: perPage, total: res.data.data.length },
    };
  },

  /**
   * Fetch archive calendar and editions for year/month.
   */
  async getArchive(tenantSlug: string, year?: number, month?: number): Promise<ArchiveCalendarData> {
    const params: Record<string, number> = {};
    if (year) params.year = year;
    if (month) params.month = month;

    const res = await publicApiClient.get<ApiResponse<EditionCardData[]>>(`/${tenantSlug}/archive`, { params });
    return {
      year: res.data.calendar?.year || (year ?? new Date().getFullYear()),
      month: res.data.calendar?.month || (month ?? new Date().getMonth() + 1),
      total_editions: res.data.calendar?.total_editions || res.data.data.length,
      available_years: res.data.calendar?.available_years || [new Date().getFullYear()],
      published_dates: res.data.calendar?.published_dates || [],
      editions: res.data.data,
    };
  },

  /**
   * Fetch distinct published categories for tenant.
   */
  async getCategories(tenantSlug: string): Promise<PublicCategory[]> {
    const res = await publicApiClient.get<ApiResponse<string[]>>(`/${tenantSlug}/categories`);
    return (res.data.data || []).map((catName: string, index: number) => ({
      id: index + 1,
      name: catName,
      slug: catName.toLowerCase().replace(/\s+/g, '-'),
      edition_count: 0,
    }));
  },
};

export const publicEditionService = editionService;
