import { defineCollection, z } from 'astro:content';
import { glob } from 'astro/loaders';

const posts = defineCollection({
  loader: glob({ pattern: '**/*.md', base: 'src/content/posts' }),
  schema: z.object({
    title: z.string().min(1),
    slug: z.string().regex(/^[a-z0-9]+(?:-[a-z0-9]+)*$/),
    author: z.string().min(1),
    date: z.coerce.date(),
    summary: z.string().min(1),
    oneLineSummary: z.string().min(1),
    tags: z.array(z.string().min(1)).min(1),
    status: z.enum(['draft', 'published']),
    updatedDate: z.coerce.date().optional(),
    ogImage: z.string().optional()
  })
});

export const collections = { posts };
