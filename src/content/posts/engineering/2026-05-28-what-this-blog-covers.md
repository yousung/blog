---
title: "감성개발자의 Blog는 무엇을 다루는가"
slug: "what-this-blog-covers"
author: "감성개발자"
date: "2026-03-01"
updatedDate: "2026-05-28"
summary: "11년 차 개발자가 왜 이 블로그를 쓰는지, 어떤 글을 공개하고 어떤 글은 보류하는지 운영 기준을 밝힌다."
oneLineSummary: "이 블로그가 다루는 주제와 글 공개 기준, 그 배경에 있는 기록의 이유."
tags: [블로그, Astro, SEO]
status: "published"
---

11년 동안 여러 웹에이전시에서 마감에 쫓기며 프로젝트를 만들었고, 남이 짜둔 코드를 인수했고, 중견기업을 거쳐 지금은 중소기업에서 CTO로 일합니다. 그 시간 동안 반복해서 확인한 게 하나 있습니다. "멋진 생각의 저장소"로 남긴 글은 반년만 지나면 나조차 다시 열지 않는다는 것. 오래 살아남는 건 언제나 팀과 내가 나중에 다시 찾아보는 운영 문서 쪽이었습니다.

이 블로그는 그 경험에서 시작했습니다. 개발자가 실제로 겪는 운영 문제를, 검색으로 다시 찾을 수 있는 문서로 남기자는 것. 그래서 회고보다 "실무에서 바로 다시 쓰는 문서"를 우선하고, 글마다 결론과 조건, 예외, 검증 순서를 먼저 드러냅니다. 다루는 주제는 Astro 기반 블로그 운영, 배포, 품질 검증, 개발 문서화이며, 모든 글은 1차 출처 링크와 실제 적용 기준, 재현 가능한 체크리스트를 담는 방향으로 씁니다.

Google은 도움이 되는 콘텐츠의 조건으로 원래 독자를 전제로 한 경험 기반 정보와 신뢰할 수 있는 페이지 경험을 함께 요구합니다. 검색 트래픽을 노리더라도 검색엔진용 문장이 아니라 사람에게 바로 도움이 되는 문장을 먼저 쓰는 이유입니다. (출처: [Google Search Central - Creating helpful, reliable, people-first content](https://developers.google.com/search/docs/fundamentals/creating-helpful-content), [Google Search Central - Understanding page experience in Google Search results](https://developers.google.com/search/docs/appearance/page-experience))

## 1. 이 블로그가 다루는 핵심 범위

이 블로그는 아래 세 축을 중심으로 운영합니다.

### Astro 기반 콘텐츠 운영

Astro는 `src/pages/` 기반 라우팅과 콘텐츠 컬렉션을 통해 정적 사이트 운영 구조를 명확하게 가져갈 수 있습니다. 이 블로그는 그 장점을 활용해 글 발행, 검증, 배포 흐름을 단순하게 유지하려고 합니다. (출처: [Astro Docs - Pages](https://docs.astro.build/en/basics/astro-pages/), [Astro Docs - Content collections](https://v6.docs.astro.build/en/guides/content-collections))

### 배포와 QA

GitHub Pages는 저장소의 HTML, CSS, JavaScript를 게시하는 정적 호스팅 서비스이며, Astro는 GitHub Actions를 이용한 자동 배포 방식을 공식 문서에서 안내합니다. 따라서 운영 과정에서는 "빌드 성공"과 "배포 화면 정상"을 분리해서 다루는 것이 중요합니다. (출처: [GitHub Docs - What is GitHub Pages?](https://docs.github.com/en/pages/getting-started-with-github-pages/what-is-github-pages), [Astro Docs - Deploy your Astro Site to GitHub Pages](https://v4.docs.astro.build/en/guides/deploy/github/))

### 검색 친화적이지만 사람 우선인 기술 글쓰기

Google은 자동화 여부보다 콘텐츠가 사람을 위해 작성되었는지, 실제 전문성과 깊이를 보여주는지에 더 초점을 맞춘다고 설명합니다. 그래서 이 블로그는 키워드만 반복하는 문장보다, 적용 기준과 실패 사례를 함께 정리하는 방식을 지향합니다. (출처: [Google Search Central - Creating helpful, reliable, people-first content](https://developers.google.com/search/docs/fundamentals/creating-helpful-content))

## 2. 공개 기준: 어떤 글을 발행하고 어떤 글은 보류하는가

아래 기준을 충족하지 못하면 초안을 더 다듬습니다.

| 항목 | 공개 기준 | 이유 |
| --- | --- | --- |
| 출처 | 공식 문서 또는 1차 자료 링크 포함 | 독자가 원문을 다시 확인할 수 있어야 함 |
| 경험 | 직접 적용한 판단 기준 또는 운영 맥락 포함 | Google이 말하는 first-hand expertise를 보여주기 위함 |
| 구조 | 도입부에서 질문에 바로 답함 | 검색 의도와 제목-본문 정합성 유지 |
| 페이지 경험 | 모바일, 광고 간섭, 본문 구분 확인 | 전체적인 page experience 품질 확보 |

특히 "경험" 항목을 기준에 넣은 건 에이전시 시절의 습관 때문입니다. 넘겨받은 유지보수 프로젝트마다 "동작은 하는데 왜 이렇게 짰는지 아무도 모르는" 코드가 있었고, 그때마다 판단 맥락 없는 기록이 얼마나 무력한지 겪었습니다. 그래서 이 블로그도 정답을 나열하기보다 "나는 이 상황에서 이렇게 판단했다"를 남기는 쪽을 택합니다.

마지막 페이지 경험 항목도 중요합니다. Google은 좋은 페이지 경험을 평가할 때 모바일 표시, 과도한 광고 회피, 메인 콘텐츠 식별 가능성 등을 함께 보라고 안내합니다. (출처: [Google Search Central - Understanding page experience in Google Search results](https://developers.google.com/search/docs/appearance/page-experience))

## 3. 처음 읽으면 좋은 글

이 글을 시작점으로 읽는다면 아래 순서를 권합니다.

1. [Astro 블로그 디자인 QA 체크리스트](/posts/design-application-audit-checklist/)
2. [Astro Content Collections로 프론트매터 오류를 배포 전에 막는 방법](/posts/astro-content-collections-frontmatter-checklist/)

둘 다 "글을 올리는 것"보다 "문제를 남기지 않고 운영하는 것"에 초점을 둔 글입니다.

앞으로도 기준은 단순하게 두려 합니다. 내가 반년 뒤에 다시 열어볼 글, 우리 팀에 그대로 건네도 부끄럽지 않은 글만 공개합니다. 화려한 결론보다 다시 재현할 수 있는 과정을, 정답을 나열하기보다 "나는 이 상황에서 이렇게 판단했다"를 남기는 일. 그게 이 블로그가 계속 붙잡으려는 한 가지입니다.

읽다가 근거가 약하거나 틀린 판단이 보이면 언제든 알려주셔도 좋습니다. 문서는 그렇게 한 번씩 고쳐질 때 가장 오래 삽니다.
