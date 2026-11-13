import { readFileSync } from 'node:fs';
import { fileURLToPath } from 'node:url';
import { getPublicImageMetadata } from './public-images.mjs';

// Shares only the JSON contract with responsive-images.ts (no TS import at config time).
const MAX_WIDTH = 1200;
// Article column is ~920px on desktop; mirrors the page's layout paddings below that.
const SIZES = '(min-width: 1200px) 920px, (min-width: 768px) calc(100vw - 114px), calc(100vw - 82px)';

let manifest;
function getManifest() {
  if (!manifest) {
    try {
      manifest = JSON.parse(readFileSync(fileURLToPath(new URL('../data/image-variants.json', import.meta.url)), 'utf8'));
    } catch {
      manifest = {};
    }
  }
  return manifest;
}

function buildSrcset(entry, src) {
  const variants = (entry?.variants ?? []).filter(({ w }) => w <= MAX_WIDTH);
  if (!variants.length) return undefined;
  // Keep a smaller original available for high-density screens.
  if (entry.width <= MAX_WIDTH && variants.at(-1).w < entry.width) variants.push({ w: entry.width, src });
  return variants.map((variant) => `${variant.src} ${variant.w}w`).join(', ');
}

/** Reserve image space, add responsive WebP srcset, and keep the article's first image eager. */
export default function rehypePostImages() {
  return async (tree) => {
    const images = [];
    function walk(node) {
      if (node.type === 'element' && node.tagName === 'img') images.push(node);
      for (const child of node.children ?? []) walk(child);
    }
    walk(tree);
    await Promise.all(images.map(async (node, index) => {
      const props = node.properties ??= {};
      props.decoding = 'async';
      props.loading = index === 0 ? 'eager' : 'lazy';
      if (index === 0 && props.fetchPriority == null && props.fetchpriority == null) props.fetchPriority = 'high';
      const src = props.src;
      const entry = typeof src === 'string' ? getManifest()[src] : undefined;
      const size = entry?.width && entry?.height
        ? { width: entry.width, height: entry.height }
        : await getPublicImageMetadata(src);
      if (size) {
        // Use the intrinsic ratio even if old HTML supplied only one dimension.
        props.width = size.width;
        props.height = size.height;
      }
      // External images and entries without variants keep their original markup.
      const srcset = buildSrcset(entry, src);
      if (srcset && props.srcSet == null && props.srcset == null) {
        props.srcSet = srcset;
        if (props.sizes == null) props.sizes = SIZES;
      }
    }));
  };
}
