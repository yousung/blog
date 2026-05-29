import fs from 'node:fs/promises';
import path from 'node:path';
import { chromium } from 'playwright';

const out = process.argv[2];
const browser = await chromium.launch({ headless: true });
const targets = [
  {
    name: 'configured',
    htmlPath: path.resolve(out, 'configured/dist/posts/what-this-blog-covers/index.html')
  },
  {
    name: 'missing',
    htmlPath: path.resolve(out, 'missing/dist/posts/what-this-blog-covers/index.html')
  }
];

const results = [];
for (const t of targets) {
  const context = await browser.newContext({ viewport: { width: 1440, height: 900 } });
  const page = await context.newPage();
  await page.goto(`file://${t.htmlPath}`, { waitUntil: 'domcontentloaded' });
  await page.waitForTimeout(800);
  const evald = await page.evaluate(() => {
    const giscusScript = document.querySelector('script[src="https://giscus.app/client.js"]');
    const fallback = document.querySelector('.comments-fallback');
    return {
      title: document.title,
      hasGiscusScript: !!giscusScript,
      giscusRepo: giscusScript?.getAttribute('data-repo') || null,
      giscusRepoId: giscusScript?.getAttribute('data-repo-id') || null,
      giscusCategory: giscusScript?.getAttribute('data-category') || null,
      giscusCategoryId: giscusScript?.getAttribute('data-category-id') || null,
      hasFallback: !!fallback,
      fallbackText: fallback?.textContent?.replace(/\s+/g, ' ').trim() || null,
      hasCommentsHeading: Array.from(document.querySelectorAll('h2')).some((h) => h.textContent?.trim() === '댓글')
    };
  });
  const screenshot = path.resolve(out, `${t.name}-post.png`);
  await page.screenshot({ path: screenshot, fullPage: true });
  results.push({ case: t.name, htmlPath: t.htmlPath, screenshot, ...evald });
  await context.close();
}

await browser.close();
await fs.writeFile(path.resolve(out, 'results.json'), JSON.stringify({ generatedAt: new Date().toISOString(), results }, null, 2));
console.log(JSON.stringify({ out, results }, null, 2));
