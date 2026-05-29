import fs from 'node:fs/promises';
import { chromium } from 'playwright';

const base = 'https://yousung.github.io/blog';
const outDir = process.argv[2];
const browser = await chromium.launch({ headless: true });
const cases = [
  {name:'desktop-home', path:'/', viewport:{width:1440,height:900}},
  {name:'mobile-home', path:'/', viewport:{width:390,height:844}},
  {name:'desktop-post', path:'/posts/what-this-blog-covers/', viewport:{width:1440,height:900}},
  {name:'mobile-post', path:'/posts/what-this-blog-covers/', viewport:{width:390,height:844}},
  {name:'desktop-privacy', path:'/privacy/', viewport:{width:1440,height:900}}
];
const results=[];
for (const c of cases) {
  const context = await browser.newContext({viewport:c.viewport,colorScheme:'light'});
  const page = await context.newPage();
  const consoleEntries = [];
  page.on('console', m => consoleEntries.push({type:m.type(),text:m.text()}));
  await page.goto(base + c.path, {waitUntil:'networkidle'});
  await page.waitForTimeout(1200);
  const evald = await page.evaluate(() => {
    const h1 = document.querySelector('h1')?.textContent?.trim() || null;
    const hasCommentsHeading = Array.from(document.querySelectorAll('h2')).some(h => h.textContent?.trim() === '댓글');
    const hasCommentsIframe = !!document.querySelector('iframe.giscus-frame');
    const fallback = document.querySelector('.comments-fallback');
    const fallbackText = fallback?.textContent?.replace(/\s+/g,' ').trim() || null;
    const overflow = document.documentElement.scrollWidth > document.documentElement.clientWidth;
    return {h1,hasCommentsHeading,hasCommentsIframe,hasCommentsFallback:!!fallback,fallbackText,overflow};
  });
  const screenshot = `${outDir}/${c.name}.png`;
  await page.screenshot({path:screenshot, fullPage:true});
  results.push({case:c.name,url:base+c.path,viewport:c.viewport,consoleEntries,...evald,screenshot});
  await context.close();
}
await browser.close();
await fs.writeFile(`${outDir}/results.json`, JSON.stringify({generatedAt:new Date().toISOString(), base, results}, null, 2));
console.log(JSON.stringify({outDir, checks: results.map(r=>({case:r.case,h1:r.h1,overflow:r.overflow,hasCommentsIframe:r.hasCommentsIframe,hasCommentsFallback:r.hasCommentsFallback,consoleErrors:r.consoleEntries.filter(e=>e.type==='error').length}))}, null, 2));
