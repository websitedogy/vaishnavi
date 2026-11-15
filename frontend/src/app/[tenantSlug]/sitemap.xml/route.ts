import { NextRequest, NextResponse } from 'next/server';

export async function GET(
  request: NextRequest,
  { params }: { params: { tenantSlug: string } }
) {
  const tenantSlug = params.tenantSlug;
  const backendBase = process.env.NEXT_PUBLIC_BACKEND_URL || 'http://localhost:8000';

  try {
    const res = await fetch(`${backendBase}/api/v1/public/${tenantSlug}/sitemap.xml`, {
      headers: {
        'Accept': 'application/xml',
      },
      next: { revalidate: 3600 },
    });

    if (!res.ok) {
      return new NextResponse('<error>Sitemap not available</error>', {
        status: res.status,
        headers: { 'Content-Type': 'application/xml' },
      });
    }

    const xml = await res.text();
    return new NextResponse(xml, {
      status: 200,
      headers: {
        'Content-Type': 'application/xml; charset=utf-8',
        'Cache-Control': 'public, max-age=3600, s-maxage=3600',
      },
    });
  } catch (err) {
    return new NextResponse('<error>Sitemap error</error>', {
      status: 500,
      headers: { 'Content-Type': 'application/xml' },
    });
  }
}
