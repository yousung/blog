import fs from 'node:fs/promises';
import path from 'node:path';
import { chromium } from 'playwright';

const base = 'http://127.0.0.1:4173';
const outDir = 'docs/qa-evidence/bloa-98-2026-05-28';
const cases = [
  { name: 'desktop-post', width: 1440, height: 900, pathname: '/posts/blog-publish-sample/' },
  { name: 'tablet-post', width: 768, height: 1024, pathname: '/posts/blog-publish-sample/' },
  { name: 'mobile-post', width: 390, height: 844, pathname: '/posts/blog-publish-sample/' },
  { name: 'desktop-home', width: 1440, height: 900, pathname: '/' }
];

const browser = await chromium.launch({ headless: true });
const results = [];

for (const c of cases) {
  const context = await browser.newContext({ viewport: { width: c.width, height: c.height }, colorScheme: 'light' });
  const page = await context.newPage();
  await page.addInitScript(() => {
    window.__qaCls = 0;
    new PerformanceObserver((list) => {
      for (const e of list.getEntries()) {
        if (!e.hadRecentInput) window.__qaCls += e.value;
      }
    }).observe({ type: 'layout-shift', buffered: true });
  });

  await page.goto(`${base}${c.pathname}`, { waitUntil: 'networkidle' });
  await page.waitForTimeout(1200);
  await page.evaluate(() => window.scrollTo(0, document.body.scrollHeight));
  await page.waitForTimeout(500);
  await page.evaluate(() => window.scrollTo(0, 0));
  await page.waitForTimeout(300);

  const slots = await page.$$eval('.ad-slot', (els) =>
    els.map((el) => {
      const r = el.getBoundingClientRect();
      return {
        classes: el.className,
        width: Math.round(r.width),
        height: Math.round(r.height),
        y: Math.round(r.y)
      };
    })
  );

  const sidebars = await page.$$eval('.ad-slot--sidebar', (els) =>
    els.map((el) => {
      const r = el.getBoundingClientRect();
      const style = window.getComputedStyle(el);
      return {
        width: Math.round(r.width),
        height: Math.round(r.height),
        y: Math.round(r.y),
        display: style.display,
        visibility: style.visibility
      };
    })
  );

  const cls = await page.evaluate(() => Number((window.__qaCls || 0).toFixed(4)));
  const screenshot = path.resolve(outDir, `${c.name}.png`);
  await page.screenshot({ path: screenshot, fullPage: true });

  results.push({ ...c, cls, slots, sidebars, screenshot });
  await context.close();
}

const darkContext = await browser.newContext({ viewport: { width: 1440, height: 900 }, colorScheme: 'dark' });
const darkPage = await darkContext.newPage();
await darkPage.goto(`${base}/posts/blog-publish-sample/`, { waitUntil: 'networkidle' });
await darkPage.waitForTimeout(1200);
const darkEvidence = await darkPage.evaluate(() => {
  const htmlTheme = document.documentElement.dataset.theme;
  const hasGiscusIframe = !!document.querySelector('iframe.giscus-frame');
  const fallback = document.querySelector('.comments-fallback');
  const fallbackStyle = fallback ? window.getComputedStyle(fallback) : null;
  return {
    htmlTheme,
    hasGiscusIframe,
    commentsFallback: !!fallback,
    commentsFallbackBg: fallbackStyle?.backgroundColor || null,
    commentsFallbackColor: fallbackStyle?.color || null
  };
});
const darkScreenshot = path.resolve(outDir, 'desktop-post-dark.png');
await darkPage.screenshot({ path: darkScreenshot, fullPage: true });
await darkContext.close();

await browser.close();

const payload = { generatedAt: new Date().toISOString(), results, darkEvidence, darkScreenshot };
await fs.writeFile(path.resolve(outDir, 'results.json'), JSON.stringify(payload, null, 2));
console.log(JSON.stringify(payload, null, 2));
