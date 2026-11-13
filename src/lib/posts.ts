import type { CollectionEntry } from 'astro:content';

export type BlogPost = CollectionEntry<'posts'>;

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

export function sortPostsByNewest(posts: BlogPost[]): BlogPost[] {
  return [...posts].sort((a, b) => b.data.date.getTime() - a.data.date.getTime());
}

export function getPublishedPosts(posts: BlogPost[]): BlogPost[] {
  return sortPostsByNewest(posts.filter(isPublishedPost));
}
