import { publicApiClient, ApiResponse } from './publicApi';
import { ReaderPayload } from '../types/reader.types';

export const readerService = {
  /**
   * Fetch digital reader payload for an edition (latest if dateOrCode is omitted).
   */
  async getReaderPayload(tenantSlug: string, dateOrCode?: string): Promise<ReaderPayload> {
    const url = dateOrCode && dateOrCode !== 'latest'
      ? `/${tenantSlug}/reader/${dateOrCode}`
      : `/${tenantSlug}/reader/latest`;
    const res = await publicApiClient.get<ApiResponse<ReaderPayload>>(url);
    const data = res.data.data;
    if (data && data.permissions && data.permissions.allow_download === undefined) {
      data.permissions.allow_download = data.permissions.allow_page_download || false;
    }
    return data;
  },
};

export const publicReaderService = readerService;
