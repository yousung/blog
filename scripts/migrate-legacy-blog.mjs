#!/usr/bin/env node
import fs from 'node:fs';
import path from 'node:path';

const ROOT = process.cwd();
const OUTPUT_DIR = path.join(ROOT, 'src/content/posts/legacy');
const REPORT_PATH = path.join(ROOT, 'docs/migration-report-bloa-154.json');
const SITEMAP_URL = 'https://blog.lovizu.com/sitemap.xml';
const AUTHOR = 'Lovizu';
const DEFAULT_TAGS = ['legacy', 'legacy-migration'];

function decodeHtmlEntities(str) {
  return str
    .replace(/&lt;/g, '<')
    .replace(/&gt;/g, '>')
    .replace(/&quot;/g, '"')
    .replace(/&#39;/g, "'")
    .replace(/&amp;/g, '&');
}

function stripTags(str) {
  return str.replace(/<[^>]+>/g, ' ').replace(/\s+/g, ' ').trim();
}

function escapeYaml(str) {
  return String(str)
    .replace(/[\u0000-\u001F\u007F]/g, ' ')
    .replace(/\s+/g, ' ')
    .trim();
}

function yamlQuote(str) {
  return `'${escapeYaml(str).replace(/'/g, "''")}'`;
}

function slugify(raw, fallback) {
  const decoded = decodeURIComponent(raw);
  const ascii = decoded
    .normalize('NFKD')
    .replace(/[\u0300-\u036f]/g, '')
    .toLowerCase()
    .replace(/[^a-z0-9]+/g, '-')
    .replace(/^-+|-+$/g, '');
  return ascii || fallback;
}

function getMeta(html, property, attr = 'property') {
  const tags = html.match(/<meta\b[^>]*>/gi) || [];
  for (const tag of tags) {
    if (!new RegExp(`${attr}=["']${property}["']`, 'i').test(tag)) continue;
    const contentMatch = tag.match(/content=["']([^"']*)["']/i);
    if (contentMatch?.[1]) return decodeHtmlEntities(contentMatch[1]).trim();
  }
  return '';
}

function getArticleHtml(html) {
  const between = (startRegex, endRegex) => {
    const start = html.search(startRegex);
    if (start < 0) return '';
    const startTagEnd = html.indexOf('>', start);
    if (startTagEnd < 0) return '';
    const rest = html.slice(startTagEnd + 1);
    const endMatch = rest.match(endRegex);
    const endIndex = endMatch?.index ?? -1;
    if (endIndex < 0) return '';
    return rest.slice(0, endIndex).trim();
  };

  const contentsStyleMatch = html.match(/<div class=\"contents_style\"[^>]*>([\s\S]*?)<\/div>\s*(?:<!-- System - START -->|<div class=\"container_postbtn\"|$)/i);
  if (contentsStyleMatch) return contentsStyleMatch[1].trim();

  const articleMatch = html.match(/<article[^>]*>[\s\S]*?<\/article>/i);
  if (articleMatch) return articleMatch[0].trim();

  const commonBlockMatch = html.match(/<div class="tt_article_useless_p_margin[^"]*"[^>]*>([\s\S]*?)<\/div>\s*<div class="container_postbtn"/i);
  if (commonBlockMatch) return commonBlockMatch[1].trim();

  const contentsSlice = between(/<div class=\"contents_style\"[^>]*>/i, /(?:<!-- System - START -->|<div class=\"container_postbtn\"|<div class=\"another_category\"|<script\b)/i);
  if (contentsSlice) return contentsSlice;

  const ttSlice = between(/<div class=\"tt_article_useless_p_margin[^"]*\"[^>]*>/i, /(?:<div class=\"container_postbtn\"|<div class=\"another_category\"|<script\b)/i);
  if (ttSlice) return ttSlice;

  const repMatch = html.match(/<div class="article_rep_desc"[^>]*>([\s\S]*?)<\/div>/i);
  if (repMatch) return repMatch[1].trim();

  return '';
}

function normalizeLegacyDate(raw) {
  if (!raw) return '';
  if (/^\d{14}$/.test(raw)) {
    return `${raw.slice(0, 4)}-${raw.slice(4, 6)}-${raw.slice(6, 8)}T${raw.slice(8, 10)}:${raw.slice(10, 12)}:${raw.slice(12, 14)}+09:00`;
  }
  const visible = raw.match(/(\d{4})\.\s*(\d{1,2})\.\s*(\d{1,2})\./);
  if (visible) {
    const [, y, m, d] = visible;
    return `${y}-${m.padStart(2, '0')}-${d.padStart(2, '0')}T00:00:00+09:00`;
  }
  return raw;
}

function buildFrontmatter(data) {
  return `---\ntitle: ${yamlQuote(data.title)}\nslug: ${yamlQuote(data.slug)}\nauthor: ${yamlQuote(data.author)}\ndate: ${yamlQuote(data.date)}\nsummary: ${yamlQuote(data.summary)}\noneLineSummary: ${yamlQuote(data.oneLineSummary ?? data.summary)}\ntags: [${data.tags.map((t) => yamlQuote(t)).join(', ')}]\nstatus: 'published'\n${data.updatedDate ? `updatedDate: ${yamlQuote(data.updatedDate)}\n` : ''}---`;
}

async function main() {
  fs.mkdirSync(OUTPUT_DIR, { recursive: true });
  for (const file of fs.readdirSync(OUTPUT_DIR)) {
    if (file.endsWith('.md')) fs.unlinkSync(path.join(OUTPUT_DIR, file));
  }

  const sitemapXml = await fetch(SITEMAP_URL).then((r) => r.text());
  const urls = [...sitemapXml.matchAll(/<loc>(https:\/\/blog\.lovizu\.com\/entry\/[^<]+)<\/loc>/g)].map((m) => m[1]);
  const uniqueUrls = [...new Set(urls)];

  const usedSlugs = new Set();

  const report = {
    source: SITEMAP_URL,
    discoveredEntries: uniqueUrls.length,
    migrated: 0,
    updated: 0,
    failed: 0,
    failureByReason: {
      access_unavailable: 0,
      template_mismatch: 0,
      parsing_error: 0
    },
    failures: [],
    sampleChecks: []
  };

  for (const entryUrl of uniqueUrls) {
    try {
      const fetchUrl = entryUrl.replace('https://blog.lovizu.com/', 'https://lovizu.tistory.com/');
      const response = await fetch(fetchUrl);
      if (!response.ok) {
        const accessError = new Error(`http ${response.status}`);
        accessError.reason = 'access_unavailable';
        throw accessError;
      }
      const html = await response.text();
      const title = getMeta(html, 'og:title') || stripTags((html.match(/<title>([\s\S]*?)<\/title>/i)?.[1] || 'Untitled'));
      const description = getMeta(html, 'og:description', 'property') || getMeta(html, 'description', 'name');
      const published = normalizeLegacyDate(
        getMeta(html, 'article:published_time') ||
        getMeta(html, 'og:regDate') ||
        stripTags(html.match(/<span class="txt_detail[^"]*">([\s\S]*?)<\/span>/i)?.[1] || '')
      );
      const modified = normalizeLegacyDate(getMeta(html, 'article:modified_time')) || published;
      const rawBody = getArticleHtml(html);

      if (!title || !published) {
        const parseError = new Error('missing required metadata fields');
        parseError.reason = 'parsing_error';
        throw parseError;
      }
      if (!rawBody) {
        const templateError = new Error('missing article body for known templates');
        templateError.reason = 'template_mismatch';
        throw templateError;
      }

      const sourceSlug = entryUrl.split('/entry/')[1] || '';
      const fallbackSlug = `legacy-${published.slice(0, 10)}-${Math.abs(sourceSlug.length)}`.toLowerCase();
      let slug = slugify(sourceSlug, fallbackSlug);
      const baseSlug = slug;
      let collisionCount = 1;
      while (usedSlugs.has(slug)) {
        slug = `${baseSlug}-${collisionCount}`;
        collisionCount += 1;
      }

      const dateOnly = new Date(published).toISOString().slice(0, 10);
      const summary = stripTags(description || rawBody).slice(0, 180) || title;
      const oneLineSummary = summary.slice(0, 72);
      const updatedDateOnly = modified ? new Date(modified).toISOString().slice(0, 10) : '';

      const frontmatter = buildFrontmatter({
        title,
        slug,
        author: AUTHOR,
        date: dateOnly,
        summary,
        oneLineSummary,
        tags: DEFAULT_TAGS,
        updatedDate: updatedDateOnly && updatedDateOnly !== dateOnly ? updatedDateOnly : ''
      });

      const markdown = `${frontmatter}\n\n<!-- source: ${entryUrl} -->\n\n${rawBody.trim()}\n`;
      const fileName = `${dateOnly}-${slug}.md`;
      const filePath = path.join(OUTPUT_DIR, fileName);

      fs.writeFileSync(filePath, markdown, 'utf8');
      report.migrated += 1;
      usedSlugs.add(slug);

      if (report.sampleChecks.length < 8) {
        const hasImage = /<img\b/i.test(rawBody);
        const hasCode = /<(pre|code)\b/i.test(rawBody);
        report.sampleChecks.push({
          entryUrl,
          file: path.relative(ROOT, filePath),
          hasImage,
          hasCode,
          hasText: stripTags(rawBody).length > 120
        });
      }
    } catch (error) {
      const reason = error.reason || (
        /http\s(401|403|404|410)/i.test(String(error.message || ''))
          ? 'access_unavailable'
          : 'parsing_error'
      );
      report.failed += 1;
      report.failureByReason[reason] += 1;
      report.failures.push({
        entryUrl,
        reason,
        error: String(error.message || error)
      });
    }
  }

  fs.mkdirSync(path.dirname(REPORT_PATH), { recursive: true });
  fs.writeFileSync(REPORT_PATH, `${JSON.stringify(report, null, 2)}\n`, 'utf8');
  console.log(JSON.stringify(report, null, 2));
}

main().catch((error) => {
  console.error(error);
  process.exit(1);
});
