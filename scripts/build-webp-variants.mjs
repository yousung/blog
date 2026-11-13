/**
 * Usage: node scripts/build-webp-variants.mjs
 * Uses the installed sharp dependency; no package installation is needed.
 * Creates 400/800/1200px WebP siblings at quality 75 without upscaling.
 * Existing variants are skipped; originals are never written or removed.
 * Writes src/data/image-variants.json, including originals smaller than 400px
 * (those entries have an empty variants array).
 */
import { readdir, mkdir, readFile, stat, writeFile } from 'node:fs/promises';
import path from 'node:path';
import { fileURLToPath } from 'node:url';
import sharp from 'sharp';

const root = fileURLToPath(new URL('../', import.meta.url));
const publicDir = path.join(root, 'public');
const manifestPath = path.join(root, 'src/data/image-variants.json');
const widths = [400, 800, 1200];

async function collectImages(directory) {
  const images = [];
  for (const entry of await readdir(directory, { withFileTypes: true })) {
    const filename = path.join(directory, entry.name);
    if (entry.isDirectory()) images.push(...await collectImages(filename));
    else if (/\.(jpe?g|png|gif)$/i.test(entry.name)) images.push(filename);
  }
  return images;
}

const tistoryDir = path.join(publicDir, 'images/tistory');
const prioritySources = [
  path.join(tistoryDir, 'ai-junior-developer-cliff-debate/1.jpg'),
  path.join(tistoryDir, 'homeplanet-metal-laptop-stand-review/1.jpg'),
];
const tistorySources = (await collectImages(tistoryDir))
  .filter(filename => !prioritySources.includes(filename))
  .sort();
const sources = [
  ...prioritySources,
  ...await collectImages(path.join(publicDir, 'images/posts')),
  path.join(publicDir, 'images/logo.png'),
  ...tistorySources,
];
let manifest = {};
try {
  manifest = JSON.parse(await readFile(manifestPath, 'utf8'));
} catch (error) {
  if (error.code !== 'ENOENT') throw error;
}
let generated = 0;
let skipped = 0;
let originalBytes = 0;
let variantBytes = 0;

for (const filename of sources) {
  const { width, height } = await sharp(filename).metadata();
  if (!width || !height) throw new Error(`Missing dimensions: ${filename}`);
  originalBytes += (await stat(filename)).size;
  const src = '/' + path.relative(publicDir, filename).split(path.sep).join('/');
  const variants = [];
  for (const w of widths.filter(w => w <= width)) {
    const output = filename.replace(/\.[^.]+$/, `-${w}.webp`);
    try {
      await stat(output);
      skipped++;
    } catch (error) {
      if (error.code !== 'ENOENT') throw error;
      const buffer = await sharp(filename)
        .resize({ width: w, withoutEnlargement: true })
        .webp({ quality: 75 })
        .toBuffer();
      await writeFile(output, buffer, { flag: 'wx' });
      generated++;
    }
    variantBytes += (await stat(output)).size;
    variants.push({ w, src: src.replace(/\.[^.]+$/, `-${w}.webp`) });
  }
  manifest[src] = { width, height, variants };
}

await mkdir(path.dirname(manifestPath), { recursive: true });
const json = JSON.stringify(manifest, null, 2) + '\n';
let previous;
try {
  previous = await readFile(manifestPath, 'utf8');
} catch (error) {
  if (error.code !== 'ENOENT') throw error;
}
if (previous !== json) await writeFile(manifestPath, json);
console.log(JSON.stringify({
  sources: sources.length,
  generated,
  skipped,
  variants: generated + skipped,
  originalBytes,
  variantBytes,
  manifest: path.relative(root, manifestPath),
}, null, 2));
