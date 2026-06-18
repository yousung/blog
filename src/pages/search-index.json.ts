import { getCollection } from 'astro:content';

export async function GET() {
  const posts = (await getCollection('posts'))
    .filter((p) => p.data.status === 'published')
    .sort((a, b) => b.data.date.getTime() - a.data.date.getTime())
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
