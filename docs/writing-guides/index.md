# 글쓰기 가이드 인덱스

AI가 이 블로그의 글을 작성·수정하기 전에 반드시 읽어야 하는 가이드 목록.
**순서: common.md 먼저 → 해당 카테고리 가이드.**

| 가이드 | 대상 | 핵심 |
| --- | --- | --- |
| [common.md](./common.md) | 모든 글 | 페르소나, frontmatter 스키마, AI 티 제거(구조 다양화), 출처 규칙, AdSense 금지어, 검증 절차 |
| [engineering.md](./engineering.md) | `src/content/posts/engineering/` | 실무 판단 기준 중심, 6,000자+, 코드 예시·출처 필수, 발행 페이스 |
| [weekly-tech-news.md](./weekly-tech-news.md) | 주간 테크 뉴스 시리즈 | 시리즈 고정 포맷 유지, "이번 주 요약" 라벨, CTO 코멘트 규칙 |
| [life.md](./life.md) | `src/content/posts/life/` | 승인 전 발행 금지, 제품 리뷰는 실사용 증거 필수, 격리 시스템 우회 금지 |
| [money.md](./money.md) | `src/content/posts/money/` | YMYL 엄격 규칙, 투자 권유 금지, 공식 출처·기준 시점 명시 |
| [career.md](./career.md) | `src/content/posts/career/` | 경험담 비중 30%+, 익명화, 노동법 내용은 공식 출처 |

## 카테고리 외 참고 문서

- 태그·카테고리 추가 절차: [../taxonomy-authoring-guide.md](../taxonomy-authoring-guide.md)
- 주간 뉴스 템플릿: [../templates/weekly-tech-news-post.md](../templates/weekly-tech-news-post.md)
- AdSense 격리 로직: `src/lib/posts.ts` (격리 카테고리·태그·패턴)

## 절대 규칙 요약 (빠른 참조)

1. AI가 만든 새 글은 항상 `status: "draft"` — 발행 전환은 사람이 결정.
2. legacy 카테고리에는 새 글을 쓰지 않는다 (아카이브 전용, 광고 격리 대상).
3. frontmatter에 금지어(쿠팡/파트너스/제휴/상품/추천) 새로 넣지 않는다.
4. 출처 URL은 실존하는 것만. 사실 확인 안 된 내용은 쓰지 않는다.
5. 작성·수정 후 `npm run validate:frontmatter` + `npm run build` 통과 확인.
