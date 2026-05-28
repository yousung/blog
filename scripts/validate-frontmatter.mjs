#!/usr/bin/env node
import fs from 'node:fs';
import path from 'node:path';

const postsDir = process.argv[2] || 'content/posts';

const requiredFields = [
  'title',
  'slug',
  'author',
  'date',
  'summary',
  'tags',
  'status',
];

function walkMarkdownFiles(dir) {
  const entries = fs.readdirSync(dir, { withFileTypes: true });
  const files = [];
  for (const entry of entries) {
    const full = path.join(dir, entry.name);
    if (entry.isDirectory()) {
      files.push(...walkMarkdownFiles(full));
      continue;
    }
    if (entry.isFile() && full.endsWith('.md')) files.push(full);
  }
  return files;
}

function parseFrontmatter(content) {
  const match = content.match(/^---\n([\s\S]*?)\n---\n?/);
  if (!match) return null;

  const data = {};
  for (const rawLine of match[1].split('\n')) {
    const line = rawLine.trim();
    if (!line || line.startsWith('#')) continue;

    const idx = line.indexOf(':');
    if (idx <= 0) continue;

    const key = line.slice(0, idx).trim();
    let value = line.slice(idx + 1).trim();

    if ((value.startsWith('"') && value.endsWith('"')) || (value.startsWith("'") && value.endsWith("'"))) {
      value = value.slice(1, -1);
    }

    if (value.startsWith('[') && value.endsWith(']')) {
      data[key] = value
        .slice(1, -1)
        .split(',')
        .map((v) => v.trim().replace(/^['\"]|['\"]$/g, ''))
        .filter(Boolean);
    } else {
      data[key] = value;
    }
  }

  return data;
}

function isISODate(value) {
  if (!/^\d{4}-\d{2}-\d{2}$/.test(value)) return false;
  const date = new Date(`${value}T00:00:00Z`);
  return !Number.isNaN(date.getTime());
}

let hasError = false;

if (!fs.existsSync(postsDir)) {
  console.error(`::error title=Posts directory missing::${postsDir} does not exist`);
  process.exit(1);
}

const files = walkMarkdownFiles(postsDir);
if (files.length === 0) {
  console.error(`::error title=No posts found::No markdown files found in ${postsDir}`);
  process.exit(1);
}

for (const file of files) {
  const raw = fs.readFileSync(file, 'utf8');
  const frontmatter = parseFrontmatter(raw);

  if (!frontmatter) {
    console.error(`::error file=${file},title=Frontmatter missing::Expected YAML frontmatter bounded by ---`);
    hasError = true;
    continue;
  }

  for (const field of requiredFields) {
    if (!(field in frontmatter) || frontmatter[field] === '' || (Array.isArray(frontmatter[field]) && frontmatter[field].length === 0)) {
      console.error(`::error file=${file},title=Frontmatter schema violation::Missing required field \"${field}\"`);
      hasError = true;
    }
  }

  if (frontmatter.date && !isISODate(frontmatter.date)) {
    console.error(`::error file=${file},title=Frontmatter schema violation::date must be YYYY-MM-DD`);
    hasError = true;
  }

  if (frontmatter.slug && !/^[a-z0-9]+(?:-[a-z0-9]+)*$/.test(frontmatter.slug)) {
    console.error(`::error file=${file},title=Frontmatter schema violation::slug must be kebab-case`);
    hasError = true;
  }

  if (frontmatter.status && !['draft', 'published'].includes(frontmatter.status)) {
    console.error(`::error file=${file},title=Frontmatter schema violation::status must be draft or published`);
    hasError = true;
  }

  if (frontmatter.tags && !Array.isArray(frontmatter.tags)) {
    console.error(`::error file=${file},title=Frontmatter schema violation::tags must be an inline array, e.g. [ai, release]`);
    hasError = true;
  }
}

if (hasError) {
  console.error('::error title=Frontmatter validation failed::Fix schema violations before deploy');
  process.exit(1);
}

console.log(`Validated ${files.length} post(s) in ${postsDir}`);
