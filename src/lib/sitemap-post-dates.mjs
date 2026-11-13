import fs from 'node:fs/promises';
import { parseFrontmatter } from '@astrojs/markdown-remark';

/** Re-read frontmatter for every build, including builds in a long-lived process. */
export async function readPostDates(directory) {
  const dates = new Map();
  async function walk(dir) {
    for (const entry of await fs.readdir(dir, { withFileTypes: true })) {
      const location = new URL(entry.name + (entry.isDirectory() ? '/' : ''), dir);
      if (entry.isDirectory()) await walk(location);
      else if (entry.name.endsWith('.md')) {
        const { frontmatter } = parseFrontmatter(await fs.readFile(location, 'utf8'));
        if (frontmatter.status !== 'published') continue;
        const date = new Date(frontmatter.updatedDate ?? frontmatter.date);
        if (!Number.isNaN(date.getTime())) dates.set(`/posts/${frontmatter.slug}/`, date.toISOString());
      }
    }
  }
  await walk(directory);
  return dates;
}
