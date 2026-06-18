import { defineConfig } from 'astro/config';
import sitemap from '@astrojs/sitemap';
import fs from 'node:fs/promises';
import path from 'node:path';
import { fileURLToPath } from 'node:url';

export default defineConfig({
  site: 'https://blog.lovizu.com',
  output: 'static',
  integrations: [
    sitemap({
      filter: (page) => !page.includes('/search/') && !page.includes('/og/') && !page.endsWith('/privacy/')
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
    syntaxHighlight: 'prism'
  },
  image: {
    service: {
      entrypoint: 'astro/assets/services/sharp'
    }
  }
});
