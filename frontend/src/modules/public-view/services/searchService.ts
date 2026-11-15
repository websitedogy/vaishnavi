import { publicApiClient, ApiResponse } from './publicApi';
import { SearchResultData } from '../types/public.types';

export const searchService = {
  /**
   * Search published editions using verified edition metadata.
   */
  async searchEditions(
    tenantSlug: string,
    params: { query?: string; q?: string; category?: string; from_date?: string; to_date?: string; date?: string; page?: number; per_page?: number } = {}
  ): Promise<SearchResultData> {
    const qParams: Record<string, any> = { ...params };
    if (params.query && !params.q) {
      qParams.q = params.query;
    }
    if (params.date && !params.from_date) {
      qParams.from_date = params.date;
      qParams.to_date = params.date;
    }

    const res = await publicApiClient.get<ApiResponse<SearchResultData>>(`/${tenantSlug}/search`, { params: qParams });
    const data = res.data.data;
    if (data) {
      data.total = data.results ? data.results.length : 0;
      data.editions = data.results || [];
    }
    return data;
  },
};

export const publicSearchService = searchService;
