'use client';

import { useState, useEffect, useCallback } from 'react';
import { publicReaderService } from '../services/readerService';
import { DigitalReaderPayload } from '../types/reader.types';

export function useDigitalReader(tenantSlug: string, dateOrCode?: string) {
  const [payload, setPayload] = useState<DigitalReaderPayload | null>(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);

  const fetchReaderData = useCallback(async () => {
    try {
      setLoading(true);
      setError(null);
      const data = await publicReaderService.getReaderPayload(tenantSlug, dateOrCode);
      setPayload(data);
    } catch (err: any) {
      setError(err?.response?.data?.message || err?.message || 'Failed to load digital reader');
    } finally {
      setLoading(false);
    }
  }, [tenantSlug, dateOrCode]);

  useEffect(() => {
    if (tenantSlug) {
      fetchReaderData();
    }
  }, [tenantSlug, dateOrCode, fetchReaderData]);

  return {
    payload,
    loading,
    error,
    refetch: fetchReaderData,
  };
}
