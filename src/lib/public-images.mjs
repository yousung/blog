import path from 'node:path';
import { fileURLToPath } from 'node:url';
import sharp from 'sharp';

const publicDir = path.resolve(fileURLToPath(new URL('../../public/', import.meta.url)));
const dimensions = new Map();

/** Read local public assets only; external URLs never trigger build-time requests. */
export function getPublicImageMetadata(src) {
  if (typeof src !== 'string' || !src.startsWith('/') || src.startsWith('//')) return undefined;
  let filename;
  try {
    filename = path.resolve(publicDir, `.${decodeURIComponent(src.split(/[?#]/)[0])}`);
  } catch {
    return undefined;
  }
  if (!filename.startsWith(`${publicDir}${path.sep}`)) return undefined;
  if (!dimensions.has(filename)) {
    dimensions.set(filename, sharp(filename).metadata().then(({ width, height, orientation }) => {
      if (!width || !height) return undefined;
      return orientation >= 5 ? { width: height, height: width } : { width, height };
    }).catch(() => undefined));
  }
  return dimensions.get(filename);
}
