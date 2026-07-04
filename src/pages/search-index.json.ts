import { getCollection } from 'astro:content';
import { getAdSenseReadyPosts } from '../lib/posts';

export async function GET() {
  const posts = getAdSenseReadyPosts(await getCollection('posts'))
    .map((p) => ({
      slug: p.data.slug,
      title: p.data.title,
      summary: p.data.summary,
      oneLineSummary: p.data.oneLineSummary,
      tags: p.data.tags,
      date: p.data.date.toISOString().slice(0, 10)
    }));

  return new Response(JSON.stringify(posts, null, 2), {
    headers: { 'Content-Type': 'application/json' }
  });
}
