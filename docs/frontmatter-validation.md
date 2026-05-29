# Frontmatter Validation

Astro Content Collections validates required frontmatter at build time using the schema in `src/content.config.ts`.

Required fields for posts:

- `title`
- `slug` (kebab-case)
- `author`
- `date` (ISO date)
- `summary`
- `tags` (at least one)
- `status` (`draft` or `published`)

## Reproduce build failure

1. Create a markdown post in `src/content/posts/` with one required field removed (for example `summary`).
2. Run `npm run build`.
3. Confirm build fails with a Content Collections schema validation error.

This is intentional to prevent invalid content from being deployed.
