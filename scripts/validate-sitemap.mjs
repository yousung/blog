import fs from 'node:fs/promises';
import path from 'node:path';

const distDir = process.argv[2] ?? 'dist';
const root = process.cwd();
const distPath = path.resolve(root, distDir);

const robotsPath = path.join(distPath, 'robots.txt');
const sitemapPath = path.join(distPath, 'sitemap.xml');

function fail(message) {
  console.error(message);
  process.exit(1);
}

async function main() {
  let robots;
  let sitemap;

  try {
    robots = await fs.readFile(robotsPath, 'utf8');
  } catch {
    fail(`Missing robots.txt at ${robotsPath}`);
  }

  try {
    sitemap = await fs.readFile(sitemapPath, 'utf8');
  } catch {
    fail(`Missing sitemap.xml at ${sitemapPath}`);
  }

  if (!robots.includes('/sitemap.xml')) {
    fail('robots.txt must reference /sitemap.xml');
  }

  if (!sitemap.includes('<urlset') && !sitemap.includes('<sitemapindex')) {
    fail('sitemap.xml does not look like a valid sitemap document');
  }

  console.log(`Sitemap validation passed for ${distPath}`);
}

await main();
