interface ImageVariants {
  width: number;
  height: number;
  variants: { w: number; src: string }[];
}

// Missing entries (including newly authored images) keep their original URL.
const manifests = import.meta.glob<Record<string, ImageVariants>>('../data/image-variants.json', {
  eager: true,
  import: 'default'
});
const manifest = Object.values(manifests)[0] ?? {};

export function getResponsiveImage(src: string, maxWidth = 1200, basePath = '/') {
  const isExternal = /^https?:\/\//.test(src);
  const withBase = (value: string) => /^https?:\/\//.test(value) ? value : `${basePath}${value.replace(/^\//, '')}`;
  // Remote images and unlisted local images keep their original source.
  const entry = isExternal ? undefined : manifest[src];
  const variants = entry?.variants.filter(({ w }) => w <= maxWidth) ?? [];
  // Keep smaller originals available for high-density screens (for example the 600px logo).
  if (entry && variants.length && entry.width <= maxWidth && variants.at(-1)!.w < entry.width) {
    variants.push({ w: entry.width, src });
  }
  return {
    src: withBase(variants.at(-1)?.src ?? src),
    srcset: variants.length ? variants.map(({ src, w }) => `${withBase(src)} ${w}w`).join(', ') : undefined,
    width: entry?.width,
    height: entry?.height
  };
}
