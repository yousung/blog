# blog-publish Skill

`blog-publish`는 초안 payload를 받아 블로그 포스트 파일/이미지를 생성 또는 갱신하고, git commit/push까지 자동화하는 실행 스킬입니다.

## Input Contract

`node scripts/blog-publish.mjs --input <payload.json>`

payload 스키마:

```json
{
  "frontmatter": {
    "title": "string",
    "description": "string",
    "pubDate": "YYYY-MM-DD 또는 ISO datetime",
    "updatedDate": "optional, YYYY-MM-DD 또는 ISO datetime",
    "draft": false,
    "tags": ["string"],
    "ogImage": "optional, https://...",
    "category": "kebab-case",
    "slug": "kebab-case",
    "publishedAt": "YYYY-MM-DD"
  },
  "body": "markdown string",
  "imagePaths": ["repo-relative/file/path"]
}
```

## Output Contract

성공 시 JSON 출력:

```json
{
  "status": "ok",
  "mode": "create | update_duplicate_slug",
  "path": "src/content/posts/{category}/{publishedAt}-{slug}.md",
  "commitHash": "<git-hash>",
  "workflowUrl": "https://github.com/{owner}/{repo}/actions/workflows/blog-publish.yml"
}
```

## Behavior

1. `frontmatter`는 `src/content.config.ts`의 Zod 스키마(`title`, `description`, `pubDate`, `updatedDate`, `draft`, `tags`, `ogImage`)와 동일 계약으로 사전 검증됩니다.
2. 추가 필드 `category`, `slug`, `publishedAt`를 검증하고 최종 파일 경로를 결정합니다.
3. 파일 저장 경로:
   - 신규: `src/content/posts/{category}/{publishedAt}-{slug}.md`
   - duplicate slug 발견 시: 기존 `{date}-{slug}.md` 파일 갱신 + `updatedDate` 자동 주입
4. 이미지 복사 경로: `src/content/posts/{category}/images/*`
5. git 자동화:
   - `git add` → `git commit -m "publish: {category}/{slug}"` → `git push origin HEAD`
   - push 인증은 `PAPERCLIP_GITHUB_PAT` (fallback: `GITHUB_PAT`) 사용
   - push는 네트워크/인증 오류 시 최대 3회 재시도

## Errors

- `exit 2`: schema violation (fail-fast, push 미수행)
- `exit 3`: image path 누락
- `exit 4`: PAT secret 누락
- 기타 git 실패: stderr와 함께 즉시 실패

## Usage Examples

성공(로컬 검증용 push 생략):

```bash
node scripts/blog-publish.mjs \
  --input docs/blog-publish-fixtures/payload-valid.json \
  --skip-push
```

스키마 실패 확인:

```bash
node scripts/blog-publish.mjs \
  --input docs/blog-publish-fixtures/payload-invalid.json \
  --skip-push
```

실제 발행(push 포함):

```bash
PAPERCLIP_GITHUB_PAT=*** node scripts/blog-publish.mjs \
  --input <payload.json>
```
