import { SITE_URL } from '../consts';

export const prerender = true;

const siteUrl = SITE_URL.endsWith('/') ? SITE_URL.slice(0, -1) : SITE_URL;

export function GET() {
  return new Response(`User-agent: *
Allow: /

Sitemap: ${siteUrl}/sitemap.xml
`, {
    headers: {
      'Content-Type': 'text/plain; charset=utf-8',
      'Cache-Control': 'public, max-age=3600'
    }
  });
}
