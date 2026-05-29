# 태그/카테고리 운영 가이드

## 목적
- 검색 최적화(SEO)와 탐색 일관성을 위해 포스트 메타데이터를 표준화한다.

## 기준 데이터
- 파일: `src/content/taxonomy.json`
- `categories`: 허용되는 카테고리 목록
- `allowedTags`: 허용되는 태그 목록

## 카테고리 규칙
- 카테고리는 포스트 파일 경로의 첫 디렉터리명으로 판정한다.
- 예: `src/content/posts/engineering/2026-05-28-example.md` → `engineering`
- 현재 허용 카테고리: `engineering`, `legacy`

## 태그 규칙
- `tags`는 반드시 1개 이상이어야 한다.
- 모든 태그는 `allowedTags`에 존재해야 한다.
- 오탈자/비허용 태그는 검증 단계에서 실패한다.

## 로컬 검증 명령
```bash
npm run validate:frontmatter
```

## 새 카테고리/태그 추가 절차
1. `src/content/taxonomy.json` 업데이트
2. 신규 포스트 frontmatter 반영
3. `npm run validate:frontmatter` 재실행
