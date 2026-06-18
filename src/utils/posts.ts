import type { CollectionEntry } from 'astro:content';

export type PostEntry = CollectionEntry<'posts'>;

export const getPostSortDate = (post: PostEntry) => post.data.updatedDate ?? post.data.date;

export const sortPostsByUpdatedDesc = (a: PostEntry, b: PostEntry) =>
  getPostSortDate(b).getTime() - getPostSortDate(a).getTime();

export const getPostDisplayDate = (post: PostEntry) => {
  const date = post.data.updatedDate ?? post.data.date;

  return {
    date,
    label: post.data.updatedDate ? '수정일' : '생성일',
    text: date.toISOString().slice(0, 10).replace(/-/g, '.'),
  };
};
