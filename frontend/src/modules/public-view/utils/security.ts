export function resolvePublicImageUrl(url?: string | null): string {
  if (!url) return '';

  // Prohibit private storage paths
  if (url.startsWith('private/') || url.startsWith('/private/')) {
    return '';
  }

  // If already absolute URL
  if (url.startsWith('http://') || url.startsWith('https://')) {
    return url;
  }

  // Prepend backend storage URL if relative path
  const backendUrl = process.env.NEXT_PUBLIC_API_URL || 'http://localhost:8000';
  const cleanPath = url.startsWith('/') ? url : `/${url}`;

  // If path doesn't start with /storage, prepend /storage
  if (!cleanPath.startsWith('/storage/')) {
    return `${backendUrl}/storage${cleanPath}`;
  }

  return `${backendUrl}${cleanPath}`;
}
