import { NextResponse } from 'next/server';
import type { NextRequest } from 'next/server';

export function middleware(request: NextRequest) {
  const { pathname } = request.nextUrl;
  const host = request.headers.get('host') || '';

  // Skip static files, Next.js internal paths, and API routes
  if (
    pathname.startsWith('/_next') ||
    pathname.startsWith('/api') ||
    pathname.startsWith('/static') ||
    pathname.includes('.')
  ) {
    return NextResponse.next();
  }

  // Preserve Super Admin and Tenant Admin paths
  if (pathname === '/' || pathname.startsWith('/tenant-admin') || pathname.startsWith('/admin')) {
    return NextResponse.next();
  }

  // Handle Subdomain or Custom Domain Routing
  // e.g., 'samacharam.dogyepaper.com' -> subdomain 'samacharam'
  // e.g., 'epaper.samacharam.com' -> custom verified domain
  const cleanHost = host.split(':')[0].toLowerCase();
  const isLocalhost = cleanHost === 'localhost' || cleanHost === '127.0.0.1';
  const isMainDomain = cleanHost === 'dogyepaper.com' || cleanHost === 'www.dogyepaper.com';

  if (!isLocalhost && !isMainDomain) {
    // If it is a subdomain of dogyepaper.com (e.g. samacharam.dogyepaper.com)
    if (cleanHost.endsWith('.dogyepaper.com')) {
      const subdomain = cleanHost.replace('.dogyepaper.com', '');
      if (subdomain && subdomain !== 'app' && subdomain !== 'admin') {
        const url = request.nextUrl.clone();
        url.pathname = `/${subdomain}${pathname}`;
        return NextResponse.rewrite(url);
      }
    } else {
      // Verified custom domain: pass domain host header as tenant identifier rewrite
      const url = request.nextUrl.clone();
      url.pathname = `/${cleanHost}${pathname}`;
      return NextResponse.rewrite(url);
    }
  }

  return NextResponse.next();
}

export const config = {
  matcher: [
    /*
     * Match all request paths except for the ones starting with:
     * - _next/static (static files)
     * - _next/image (image optimization files)
     * - favicon.ico (favicon file)
     */
    '/((?!_next/static|_next/image|favicon.ico).*)',
  ],
};
