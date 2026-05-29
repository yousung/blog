#!/usr/bin/env node
import fs from 'node:fs';
import path from 'node:path';
import { execSync } from 'node:child_process';
import { z } from 'zod';

const args = process.argv.slice(2);
const getArg = (name, fallback = null) => {
  const idx = args.indexOf(name);
  return idx >= 0 ? args[idx + 1] : fallback;
};

const inputPath = getArg('--input');
const repoRoot = path.resolve(getArg('--repo-root', process.cwd()));
const skipPush = args.includes('--skip-push');

if (!inputPath) {
  console.error('Missing required argument: --input <payload.json>');
  process.exit(1);
}

const postSchema = z.object({
  title: z.string().min(1),
  description: z.string().min(1),
  pubDate: z.string().datetime({ offset: true }).or(z.string().regex(/^\d{4}-\d{2}-\d{2}$/)),
  updatedDate: z.string().datetime({ offset: true }).or(z.string().regex(/^\d{4}-\d{2}-\d{2}$/)).optional(),
  draft: z.boolean().default(false),
  tags: z.array(z.string()).default([]),
  ogImage: z.string().url().optional(),
});

const payloadSchema = z.object({
  frontmatter: postSchema.extend({
    category: z.string().min(1).regex(/^[a-z0-9]+(?:-[a-z0-9]+)*$/),
    slug: z.string().min(1).regex(/^[a-z0-9]+(?:-[a-z0-9]+)*$/),
    publishedAt: z.string().regex(/^\d{4}-\d{2}-\d{2}$/),
  }),
  body: z.string().min(1),
  imagePaths: z.array(z.string()).default([]),
});

const yamlScalar = (value) => {
  if (typeof value === 'boolean') return value ? 'true' : 'false';
  return JSON.stringify(String(value));
};

const makeFrontmatter = (f) => {
  const lines = [
    '---',
    `title: ${yamlScalar(f.title)}`,
    `description: ${yamlScalar(f.description)}`,
    `pubDate: ${yamlScalar(f.pubDate)}`,
  ];

  if (f.updatedDate) lines.push(`updatedDate: ${yamlScalar(f.updatedDate)}`);
  lines.push(`draft: ${f.draft ? 'true' : 'false'}`);
  lines.push('tags:');
  for (const tag of f.tags) lines.push(`  - ${yamlScalar(tag)}`);
  if (f.ogImage) lines.push(`ogImage: ${yamlScalar(f.ogImage)}`);
  lines.push('---');
  return lines.join('\n');
};

const runGit = (cmd, retries = 1) => {
  let lastErr;
  for (let i = 1; i <= retries; i += 1) {
    try {
      return execSync(cmd, { cwd: repoRoot, stdio: ['pipe', 'pipe', 'pipe'] }).toString().trim();
    } catch (err) {
      lastErr = err;
      if (i < retries) {
        const delayMs = i * 700;
        Atomics.wait(new Int32Array(new SharedArrayBuffer(4)), 0, 0, delayMs);
      }
    }
  }
  throw lastErr;
};

const workflowUrlFromRemote = () => {
  const remote = runGit('git remote get-url origin');
  const normalized = remote
    .replace(/^git@github.com:/, 'https://github.com/')
    .replace(/\.git$/, '');
  return `${normalized}/actions/workflows/blog-publish.yml`;
};

const payloadRaw = fs.readFileSync(path.resolve(inputPath), 'utf8');
const parsed = payloadSchema.safeParse(JSON.parse(payloadRaw));
if (!parsed.success) {
  console.error('Schema violation:');
  console.error(JSON.stringify(parsed.error.flatten(), null, 2));
  console.error('Push skipped due to schema violation.');
  process.exit(2);
}

const payload = parsed.data;
const postDir = path.join(repoRoot, 'src/content/posts', payload.frontmatter.category);
fs.mkdirSync(postDir, { recursive: true });

const matching = fs.readdirSync(postDir)
  .filter((f) => f.endsWith(`-${payload.frontmatter.slug}.md`))
  .sort();

const targetName = matching[0] || `${payload.frontmatter.publishedAt}-${payload.frontmatter.slug}.md`;
const targetPath = path.join(postDir, targetName);
const mode = matching.length > 0 ? 'update_duplicate_slug' : 'create';

if (mode === 'update_duplicate_slug') {
  payload.frontmatter.updatedDate = new Date().toISOString();
}

const markdown = `${makeFrontmatter(payload.frontmatter)}\n\n${payload.body.trim()}\n`;
fs.writeFileSync(targetPath, markdown, 'utf8');

const imageDir = path.join(postDir, 'images');
fs.mkdirSync(imageDir, { recursive: true });
for (const imagePath of payload.imagePaths) {
  const from = path.resolve(repoRoot, imagePath);
  if (!fs.existsSync(from)) {
    console.error(`Image not found: ${imagePath}`);
    process.exit(3);
  }
  const dest = path.join(imageDir, path.basename(from));
  fs.copyFileSync(from, dest);
}

runGit(`git add ${JSON.stringify(path.relative(repoRoot, targetPath))}`);
if (payload.imagePaths.length > 0) {
  runGit(`git add ${JSON.stringify(path.relative(repoRoot, imageDir))}`);
}

const commitMessage = `publish: ${payload.frontmatter.category}/${payload.frontmatter.slug}`;
runGit(`git commit -m ${JSON.stringify(commitMessage)}`);
const commitHash = runGit('git rev-parse HEAD');

if (!skipPush) {
  const pat = process.env.PAPERCLIP_GITHUB_PAT || process.env.GITHUB_PAT;
  if (!pat) {
    console.error('Missing Paperclip Secret: set PAPERCLIP_GITHUB_PAT or GITHUB_PAT');
    process.exit(4);
  }

  const remote = runGit('git remote get-url origin');
  if (/^https:\/\//.test(remote) && !remote.includes('@')) {
    const withToken = remote.replace('https://', `https://${pat}@`);
    runGit(`git remote set-url origin ${JSON.stringify(withToken)}`);
  }
  runGit('git push origin HEAD', 3);
}

console.log(JSON.stringify({
  status: 'ok',
  mode,
  path: path.relative(repoRoot, targetPath),
  commitHash,
  workflowUrl: workflowUrlFromRemote(),
}, null, 2));
