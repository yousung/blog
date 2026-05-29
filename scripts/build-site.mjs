#!/usr/bin/env node
import fs from 'node:fs';
import path from 'node:path';

const postsDir = 'src/content/posts';
const distDir = 'dist';

function ensureDir(dir) {
  fs.mkdirSync(dir, { recursive: true });
}

function parseFrontmatter(content) {
  const match = content.match(/^---\n([\s\S]*?)\n---\n?/);
  if (!match) return null;
  const data = {};
  for (const line of match[1].split('\n')) {
    const idx = line.indexOf(':');
    if (idx <= 0) continue;
    const key = line.slice(0, idx).trim();
    let value = line.slice(idx + 1).trim();
    if ((value.startsWith('"') && value.endsWith('"')) || (value.startsWith("'") && value.endsWith("'"))) {
      value = value.slice(1, -1);
    }
    data[key] = value;
  }
  return data;
}

function walkMarkdownFiles(dir) {
  const out = [];
  for (const entry of fs.readdirSync(dir, { withFileTypes: true })) {
    const full = path.join(dir, entry.name);
    if (entry.isDirectory()) out.push(...walkMarkdownFiles(full));
    if (entry.isFile() && full.endsWith('.md')) out.push(full);
  }
  return out;
}

ensureDir(distDir);
const posts = walkMarkdownFiles(postsDir)
  .map((file) => {
    const raw = fs.readFileSync(file, 'utf8');
    const frontmatter = parseFrontmatter(raw) || {};
    return {
      title: frontmatter.title || path.basename(file),
      date: frontmatter.date || '',
      summary: frontmatter.summary || '',
      oneLineSummary: frontmatter.oneLineSummary || frontmatter.summary || '',
      relPath: file,
    };
  })
  .sort((a, b) => String(b.date).localeCompare(String(a.date)));

const items = posts
  .map(
    (p) => `<article><h2>${p.title}</h2><p>${p.oneLineSummary}</p><p><small>${p.date}</small></p><p><code>${p.relPath}</code></p></article>`
  )
  .join('\n');

const html = `<!doctype html>
<html lang="ko">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <title>BLOA Blog</title>
  <style>
    body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif; margin: 2rem auto; max-width: 760px; line-height: 1.6; padding: 0 1rem; }
    h1 { margin-bottom: 0.5rem; }
    article { padding: 1rem 0; border-top: 1px solid #ddd; }
  </style>
</head>
<body>
  <h1>BLOA Blog</h1>
  <p>자동 발행 워크플로우 빌드 결과</p>
  ${items}
</body>
</html>`;

fs.writeFileSync(path.join(distDir, 'index.html'), html);
console.log(`Built site with ${posts.length} post(s) -> ${path.join(distDir, 'index.html')}`);
