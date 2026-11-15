import axios, { AxiosInstance, AxiosResponse } from 'axios';

const API_BASE_URL = process.env.NEXT_PUBLIC_API_URL || 'http://localhost:8000';

export const publicApiClient: AxiosInstance = axios.create({
  baseURL: `${API_BASE_URL}/api/v1/public`,
  headers: {
    'Accept': 'application/json',
    'Content-Type': 'application/json',
  },
  timeout: 15000,
});

export interface ApiResponse<T> {
  success: boolean;
  message?: string;
  data: T;
  meta?: {
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
  };
  calendar?: {
    year: number;
    month: number;
    total_editions: number;
    available_years: number[];
    published_dates: string[];
  };
}

export function handleApiError(error: any): string {
  if (axios.isAxiosError(error)) {
    if (error.response?.status === 403) {
      return 'This newspaper publication is currently suspended or inactive.';
    }
    if (error.response?.status === 404) {
      return error.response.data?.message || 'The requested newspaper edition or publication was not found.';
    }
    return error.response?.data?.message || error.message || 'An unexpected network error occurred.';
  }
  return error?.message || 'Network request failed.';
}
