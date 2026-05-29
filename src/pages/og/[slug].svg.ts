import type { APIRoute } from 'astro';
import { getCollection } from 'astro:content';
import { SITE_DESCRIPTION, SITE_TITLE } from '../../consts';

export const prerender = true;

export async function getStaticPaths() {
  const posts = await getCollection('posts', ({ data }) => data.status === 'published');
  return [
    {
      params: { slug: 'site' },
      props: { title: SITE_TITLE, description: SITE_DESCRIPTION }
    },
    ...posts.map((post) => ({
      params: { slug: post.data.slug },
      props: { title: post.data.title, description: post.data.oneLineSummary ?? post.data.summary }
    }))
  ];
}

const escapeXml = (value: string) =>
  value
    .replaceAll('&', '&amp;')
    .replaceAll('<', '&lt;')
    .replaceAll('>', '&gt;')
    .replaceAll('"', '&quot;')
    .replaceAll("'", '&apos;');
const truncate = (value: string, maxLength: number) =>
  value.length > maxLength ? `${value.slice(0, maxLength - 3)}...` : value;

export const GET: APIRoute = ({ params, props }) => {
  const slug = params.slug ?? 'post';
  const fallbackTitle = slug
    .split('-')
    .filter(Boolean)
    .map((word) => word[0]?.toUpperCase() + word.slice(1))
    .join(' ');
  const title = truncate(typeof props.title === 'string' ? props.title : fallbackTitle, 34);
  const description = truncate(typeof props.description === 'string' ? props.description : SITE_DESCRIPTION, 58);

  const svg = `<svg xmlns="http://www.w3.org/2000/svg" width="1200" height="630" viewBox="0 0 1200 630" role="img" aria-label="${escapeXml(
    title
  )}">
  <defs>
    <linearGradient id="bg" x1="0" y1="0" x2="1" y2="1">
      <stop offset="0%" stop-color="#0f172a" />
      <stop offset="100%" stop-color="#334155" />
    </linearGradient>
  </defs>
  <rect width="1200" height="630" fill="url(#bg)" />
  <text x="80" y="180" fill="#94a3b8" font-size="36" font-family="Pretendard, Noto Sans KR, sans-serif">${escapeXml(
    SITE_TITLE
  )}</text>
  <text x="80" y="330" fill="#f8fafc" font-size="68" font-weight="700" font-family="Pretendard, Noto Sans KR, sans-serif">${escapeXml(
    title || 'Post'
  )}</text>
  <text x="80" y="420" fill="#cbd5e1" font-size="34" font-family="Pretendard, Noto Sans KR, sans-serif">${escapeXml(
    description
  )}</text>
</svg>`;

  return new Response(svg, {
    headers: {
      'Content-Type': 'image/svg+xml; charset=utf-8',
      'Cache-Control': 'public, max-age=31536000, immutable'
    }
  });
};
