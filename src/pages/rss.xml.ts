import rss from '@astrojs/rss';
import { getCollection } from 'astro:content';
import { SITE_DESCRIPTION, SITE_TITLE } from '../consts';
import { sortPostsByUpdatedDesc } from '../utils/posts';

export async function GET(context: { site: URL }) {
  const basePath = import.meta.env.BASE_URL.endsWith('/')
    ? import.meta.env.BASE_URL.slice(0, -1)
    : import.meta.env.BASE_URL;
  const posts = (await getCollection('posts'))
    .filter((entry) => entry.data.status === 'published')
    .sort(sortPostsByUpdatedDesc);

  return rss({
    title: SITE_TITLE,
    description: SITE_DESCRIPTION,
    site: context.site,
    items: posts.map((post) => ({
      title: post.data.title,
      description: post.data.summary,
      pubDate: post.data.date,
      link: `${basePath}/posts/${post.data.slug}/`
    }))
  });
}
