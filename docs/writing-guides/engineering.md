# engineering 카테고리 글쓰기 가이드

[common.md](./common.md)를 먼저 적용하고, 아래 규칙을 더한다.
주간 테크 뉴스는 이 가이드가 아니라 [weekly-tech-news.md](./weekly-tech-news.md)를 따른다.

## 성격

블로그의 중심 카테고리. AdSense 노출 대상. 실무 판단 기준을 다루는 기술 글.

## 주제 선정

- "개념 소개"가 아니라 "실무에서 무엇을 기준으로 결정하는가"를 다룬다.
  - 나쁨: "Docker란 무엇인가" / 좋음: "Docker 이미지 크기 줄이기, 순서대로 점검하는 체크리스트"
- 저자 배경(PHP/Laravel, Node.js, AWS, MySQL, 소규모 팀 운영)과 접점이 있는 주제 우선.
- 기존 발행 글과 주제 중복 금지. `src/content/posts/engineering/`의 `status: "published"` 글 목록 먼저 확인.

## 구성

- 분량: 본문 6,000~15,000자. 얇은 글 금지.
- 필수 요소:
  - 문제 상황(독자가 겪는 증상)에서 출발
  - 판단 기준·트레이드오프 (표 활용 가능)
  - 동작하는 코드 예시 1개 이상 (검증된 문법만)
  - 공식 문서·표준 출처 2개 이상
- 도입·요약·마무리 방식은 common.md의 구조 다양화 규칙대로 직전 발행 글들과 다르게 고른다.

## 태그

- 주 기술 태그(예: `Docker`, `SQL`, `Node.js`) + 성격 태그(`개발팁`, `체크리스트`, `성능` 등) 조합 3~5개.
- `taxonomy.json`의 `allowedTags`에 없는 태그가 필요하면 taxonomy부터 갱신(docs/taxonomy-authoring-guide.md 절차).

## 발행 페이스

- 주 1~2회 간격 유지. 하루에 여러 개 발행 금지 (대량생산 패턴으로 보임).
