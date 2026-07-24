# Agent 작업 규칙 — lovizu 블로그

이 저장소에서 작업하는 모든 AI 에이전트가 따르는 규칙.

## 글 작성·수정

`src/content/posts/**`를 다루기 전에 반드시 **[docs/writing-guides/index.md](docs/writing-guides/index.md)** 를 읽을 것.
common.md(공통 규칙) → 해당 카테고리 가이드(engineering / weekly-tech-news / life / money / career) 순서로 적용한다.

절대 규칙:

1. 새 글은 항상 `status: "draft"` — 발행 전환은 사람이 한다.
2. legacy 카테고리에 새 글 금지 (아카이브 전용).
3. frontmatter에 쿠팡/파트너스/제휴/상품/추천 단어 추가 금지 (AdSense 격리 트리거, `src/lib/posts.ts`).
4. 출처 URL은 실존하는 것만. 사실 확인 안 된 내용 작성 금지.
5. 태그는 `src/content/taxonomy.json`의 `allowedTags`에 있는 값만.
6. 작성·수정 후 `npm run validate:frontmatter`와 `npm run build` 통과 확인.

## 빌드·배포

- 빌드: `npm run build`
- 배포: master 커밋 + 푸시 = CI/CD 자동 배포. 커밋·푸시는 사용자 지시가 있을 때만.
