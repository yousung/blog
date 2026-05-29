import fs from 'node:fs/promises';
import path from 'node:path';
import { chromium } from 'playwright';

const base = 'https://blog.lovizu.com';
const samplePostPath = '/posts/what-this-blog-covers/';
const runStamp = new Date().toISOString().replace(/[:.]/g, '-');
const outDir = path.resolve('docs/qa-evidence', `bloa-100-${runStamp}`);

const cases = [
  { name: 'desktop-home', width: 1440, height: 900, pathname: '/' },
  { name: 'mobile-home', width: 390, height: 844, pathname: '/' },
  { name: 'desktop-post', width: 1440, height: 900, pathname: samplePostPath },
  { name: 'desktop-privacy', width: 1440, height: 900, pathname: '/privacy/' }
];

await fs.mkdir(outDir, { recursive: true });
const browser = await chromium.launch({ headless: true });
const results = [];

for (const c of cases) {
  const context = await browser.newContext({ viewport: { width: c.width, height: c.height }, colorScheme: 'light' });
  const page = await context.newPage();
  const response = await page.goto(`${base}${c.pathname}`, { waitUntil: 'networkidle' });
  await page.waitForTimeout(1200);

  const evidence = await page.evaluate(() => {
    const adSlots = Array.from(document.querySelectorAll('.ad-slot')).map((el) => {
      const r = el.getBoundingClientRect();
      return {
        classes: el.className,
        width: Math.round(r.width),
        height: Math.round(r.height),
        y: Math.round(r.y)
      };
    });

    const commentsSection = document.querySelector('#comments');
    const commentsFallback = document.querySelector('.comments-fallback');
    const commentsStyle = commentsFallback ? window.getComputedStyle(commentsFallback) : null;
    const nav = document.querySelector('.site-header nav, .site-nav, header nav');

    return {
      title: document.title,
      hasNav: !!nav,
      adSlotCount: adSlots.length,
      adSlots,
      hasCommentsSection: !!commentsSection,
      hasCommentsIframe: !!document.querySelector('iframe.giscus-frame'),
      hasCommentsFallback: !!commentsFallback,
      commentsFallbackBg: commentsStyle?.backgroundColor || null,
      commentsFallbackColor: commentsStyle?.color || null,
      horizontalOverflow: document.documentElement.scrollWidth > document.documentElement.clientWidth
    };
  });

  const screenshot = path.resolve(outDir, `${c.name}.png`);
  await page.screenshot({ path: screenshot, fullPage: true });
  results.push({
    ...c,
    url: `${base}${c.pathname}`,
    httpStatus: response?.status() ?? null,
    httpOk: response?.ok() ?? null,
    screenshot,
    ...evidence
  });
  await context.close();
}

const darkContext = await browser.newContext({ viewport: { width: 1440, height: 900 }, colorScheme: 'dark' });
const darkPage = await darkContext.newPage();
await darkPage.goto(`${base}${samplePostPath}`, { waitUntil: 'networkidle' });
await darkPage.waitForTimeout(1200);
const darkEvidence = await darkPage.evaluate(() => {
  const rootStyle = window.getComputedStyle(document.documentElement);
  const fallback = document.querySelector('.comments-fallback');
  const fallbackStyle = fallback ? window.getComputedStyle(fallback) : null;
  return {
    backgroundColor: rootStyle.backgroundColor,
    textColor: rootStyle.color,
    hasCommentsFallback: !!fallback,
    commentsFallbackBg: fallbackStyle?.backgroundColor || null,
    commentsFallbackColor: fallbackStyle?.color || null,
    horizontalOverflow: document.documentElement.scrollWidth > document.documentElement.clientWidth
  };
});
const darkScreenshot = path.resolve(outDir, 'desktop-post-dark.png');
await darkPage.screenshot({ path: darkScreenshot, fullPage: true });
await darkContext.close();
await browser.close();

const payload = {
  generatedAt: new Date().toISOString(),
  base,
  results,
  darkEvidence,
  darkScreenshot
};

const resultPath = path.resolve(outDir, 'results.json');
await fs.writeFile(resultPath, JSON.stringify(payload, null, 2));
console.log(JSON.stringify({ outDir, resultPath, generatedAt: payload.generatedAt }, null, 2));
