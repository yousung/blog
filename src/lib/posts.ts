import type { CollectionEntry } from 'astro:content';

export type BlogPost = CollectionEntry<'posts'>;

const ADSENSE_QUARANTINE_CATEGORIES = new Set(['legacy']);
const ADSENSE_QUARANTINE_TAGS = new Set([
  'coupang',
  '제품리뷰',
  '책추천',
  '생활용품',
  '아기용품',
  '주방용품',
  '분유포트',
  '에어프라이어'
]);
// Product-review quarantine is tag-led. Text matching `review`/`리뷰` would
// also quarantine legitimate technical terms such as `security-review` and
// `preview`, so retain only clear affiliate/product signals here.
const ADSENSE_QUARANTINE_PATTERN = /(?:^|[\s/_-])coupang(?:$|[\s/_-])|쿠팡|파트너스|제휴|상품|추천/i;

export function getPostDirectory(post: BlogPost): string {
  const fp = (post as unknown as { filePath?: string }).filePath;
  if (fp) {
    const match = fp.match(/\/content\/posts\/([^/]+)\//);
    if (match?.[1]) return match[1];
  }
  return post.id.split('/')[0] ?? 'unknown';
}

export function isPublishedPost(post: BlogPost): boolean {
  return post.data.status === 'published';
}

export function isAdSenseReadyPost(post: BlogPost): boolean {
  if (!isPublishedPost(post)) return false;

  const category = getPostDirectory(post);
  if (ADSENSE_QUARANTINE_CATEGORIES.has(category)) return false;

  if (post.data.tags.some((tag) => ADSENSE_QUARANTINE_TAGS.has(tag))) return false;

  const haystack = [
    post.id,
    post.data.slug,
    post.data.title,
    post.data.summary,
    post.data.oneLineSummary,
    ...post.data.tags
  ].join(' ');

  return !ADSENSE_QUARANTINE_PATTERN.test(haystack);
}

export function sortPostsByNewest(posts: BlogPost[]): BlogPost[] {
  return [...posts].sort((a, b) => b.data.date.getTime() - a.data.date.getTime());
}

export function getAdSenseReadyPosts(posts: BlogPost[]): BlogPost[] {
  return sortPostsByNewest(posts.filter(isAdSenseReadyPost));
}
