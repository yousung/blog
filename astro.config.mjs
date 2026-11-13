import { defineConfig } from 'astro/config';
import sitemap from '@astrojs/sitemap';
import rehypeRaw from 'rehype-raw';
import rehypePostImages from './src/lib/rehype-post-images.mjs';
import { readPostDates } from './src/lib/sitemap-post-dates.mjs';
import fs from 'node:fs/promises';
import path from 'node:path';
import { fileURLToPath } from 'node:url';

const postDates = await readPostDates(new URL('./src/content/posts/', import.meta.url));

export default defineConfig({
  site: 'https://blog.lovizu.com',
  output: 'static',
  integrations: [
    sitemap({
      filter: (page) => !page.includes('/search/') && !page.includes('/og/'),
      serialize: (item) => ({ ...item, lastmod: postDates.get(new URL(item.url).pathname) })
    }),
    {
      name: 'sitemap-xml-compat',
      hooks: {
        'astro:build:done': async ({ dir, logger }) => {
          const outDir = fileURLToPath(dir);
          const source = path.join(outDir, 'sitemap-index.xml');
          const target = path.join(outDir, 'sitemap.xml');
          try {
            await fs.copyFile(source, target);
            logger.info('Copied sitemap-index.xml -> sitemap.xml for crawler compatibility.');
          } catch {
            logger.warn('sitemap-index.xml was not found; skipped sitemap.xml compatibility copy.');
          }
        }
      }
    }
  ],
  markdown: {
    syntaxHighlight: 'prism',
    // Parse raw HTML images before applying the same policy as Markdown images.
    rehypePlugins: [rehypeRaw, rehypePostImages]
  },
  image: {
    service: {
      entrypoint: 'astro/assets/services/sharp'
    }
  }
});
