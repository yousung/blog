import { getPublicImageMetadata } from './public-images.mjs';

/** Reserve image space, while keeping the article's first image eager. */
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
      const size = await getPublicImageMetadata(props.src);
      if (size) {
        // Use the intrinsic ratio even if old HTML supplied only one dimension.
        props.width = size.width;
        props.height = size.height;
      }
    }));
  };
}
