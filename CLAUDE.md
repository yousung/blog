# lovizu 블로그 (Astro)

개인 기술 블로그. `npm run build`로 빌드하며, master에 커밋 후 푸시하면 CI/CD로 자동 배포된다.

## 글 작성·수정 규칙 (필수)

블로그 글(`src/content/posts/**`)을 작성하거나 수정하기 전에 반드시
**[docs/writing-guides/index.md](docs/writing-guides/index.md)** 를 읽고,
common 가이드 + 해당 카테고리 가이드를 따를 것.

핵심만 요약하면:

- 저자는 "감성개발자" 단일 페르소나 (11년 차, 에이전시→중견기업→중소기업 CTO).
- AI가 만든 새 글은 항상 `status: "draft"`. 발행 전환은 사람이 결정.
- 글들끼리 구조(도입/요약/마무리)가 반복되면 안 됨 — 구조 다양화 규칙 준수.
- frontmatter 금지어: 쿠팡/파트너스/제휴/상품/추천 (AdSense 격리 트리거).
- 출처는 실존 공식 문서만. 태그는 `src/content/taxonomy.json`의 allowedTags만.

## 검증

```bash
npm run validate:frontmatter   # frontmatter 스키마·태그 검증
npm run build                  # 전체 빌드 (frontmatter 검증 포함)
```
