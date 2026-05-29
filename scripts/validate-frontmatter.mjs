#!/usr/bin/env node
import fs from 'node:fs';
import path from 'node:path';

const CANONICAL_POSTS_DIR = 'src/content/posts';
const LEGACY_POSTS_DIR = 'content/posts';

function resolvePostsDir(inputDir) {
  if (!inputDir) return CANONICAL_POSTS_DIR;
  if (inputDir === CANONICAL_POSTS_DIR) return CANONICAL_POSTS_DIR;
  if (inputDir === LEGACY_POSTS_DIR) return CANONICAL_POSTS_DIR;
  return inputDir;
}

const requestedPostsDir = process.argv[2];
const postsDir = resolvePostsDir(requestedPostsDir);
const taxonomyPath = 'src/content/taxonomy.json';

const requiredFields = ['title', 'slug', 'author', 'date', 'summary', 'oneLineSummary', 'tags', 'status'];

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

if (!fs.existsSync(taxonomyPath)) {
  console.error(`::error title=Taxonomy missing::${taxonomyPath} does not exist`);
  process.exit(1);
}

const taxonomyRaw = fs.readFileSync(taxonomyPath, 'utf8');
let taxonomy;
try {
  taxonomy = JSON.parse(taxonomyRaw);
} catch (error) {
  console.error(`::error title=Taxonomy invalid::${taxonomyPath} is not valid JSON`);
  process.exit(1);
}
const allowedCategories = new Set(Array.isArray(taxonomy.categories) ? taxonomy.categories : []);
const allowedTags = new Set(Array.isArray(taxonomy.allowedTags) ? taxonomy.allowedTags : []);

if (allowedCategories.size === 0) {
  console.error(`::error title=Taxonomy invalid::categories must contain at least one value in ${taxonomyPath}`);
  process.exit(1);
}

if (allowedTags.size === 0) {
  console.error(`::error title=Taxonomy invalid::allowedTags must contain at least one value in ${taxonomyPath}`);
  process.exit(1);
}

if (!fs.existsSync(postsDir)) {
  console.error(`::error title=Posts directory missing::${postsDir} does not exist`);
  process.exit(1);
}

if (requestedPostsDir === LEGACY_POSTS_DIR) {
  console.warn(`::warning title=Normalized posts path::Using ${CANONICAL_POSTS_DIR} instead of legacy ${LEGACY_POSTS_DIR}`);
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

  const relativePath = path.relative(postsDir, file);
  const category = relativePath.split(path.sep)[0];
  if (!allowedCategories.has(category)) {
    console.error(`::error file=${file},title=Category violation::category \"${category}\" is not allowed (update ${taxonomyPath} first)`);
    hasError = true;
  }

  if (Array.isArray(frontmatter.tags)) {
    for (const tag of frontmatter.tags) {
      if (!allowedTags.has(tag)) {
        console.error(`::error file=${file},title=Tag violation::tag \"${tag}\" is not allowed (update ${taxonomyPath} first)`);
        hasError = true;
      }
    }
  }
}

if (hasError) {
  console.error('::error title=Frontmatter validation failed::Fix schema violations before deploy');
  process.exit(1);
}

console.log(`Validated ${files.length} post(s) in ${postsDir}`);
