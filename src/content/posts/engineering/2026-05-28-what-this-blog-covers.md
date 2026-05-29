---
title: "감성개발자의 Blog는 무엇을 다루는가"
slug: "what-this-blog-covers"
author: "BLOA Team"
date: "2026-03-01"
updatedDate: "2026-05-28"
summary: "이 블로그가 어떤 기술 문제를 다루고, 어떤 기준으로 글을 공개하는지 운영 원칙과 독자 약속을 정리했다."
oneLineSummary: "이 블로그가 어떤 기술 문제를 다루고, 어떤 기준으로 글을 공개하는지 운영 원칙과 독자 약속을 정리했다."
tags: [블로그, Astro, SEO]
status: "published"
---

이 블로그는 "개발자가 실제로 겪는 운영 문제를, 검색으로 다시 찾을 수 있는 문서로 남기자"는 목적에서 시작했습니다. Google은 도움이 되는 콘텐츠를 만들 때 원래 독자를 전제로 한 경험 기반 정보와 신뢰할 수 있는 페이지 경험을 함께 요구합니다. 그래서 이 블로그도 단순 회고보다 "실무에서 바로 다시 쓰는 문서"를 우선합니다. (출처: [Google Search Central - Creating helpful, reliable, people-first content](https://developers.google.com/search/docs/fundamentals/creating-helpful-content), [Google Search Central - Understanding page experience in Google Search results](https://developers.google.com/search/docs/appearance/page-experience))

> 빠른 요약
>
> - 이 블로그의 주제는 Astro 기반 블로그 운영, 배포, 품질 검증, 개발 문서화입니다.
> - 글은 1차 출처 링크, 실제 적용 기준, 재현 가능한 체크리스트를 포함하는 방향으로 씁니다.
> - 검색 트래픽을 노리더라도 검색엔진용 문장이 아니라 사람에게 바로 도움이 되는 문장을 우선합니다. (출처: [Google Search Central - Creating helpful, reliable, people-first content](https://developers.google.com/search/docs/fundamentals/creating-helpful-content))

최종 업데이트: 2026-05-28

## 작성 관점

저는 개발 블로그가 "멋진 생각의 저장소"보다 "나중에 팀과 내가 다시 참고할 운영 문서"에 가까워야 오래 살아남는다고 봅니다. 그래서 이 블로그 글은 가급적 결론, 조건, 예외, 검증 순서를 먼저 드러내려고 합니다.

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

위 기준 중 마지막 항목은 특히 중요합니다. Google은 좋은 페이지 경험을 평가할 때 모바일 표시, 과도한 광고 회피, 메인 콘텐츠 식별 가능성 등을 함께 보라고 안내합니다. (출처: [Google Search Central - Understanding page experience in Google Search results](https://developers.google.com/search/docs/appearance/page-experience))

## 3. 처음 읽으면 좋은 글

이 글을 시작점으로 읽는다면 아래 순서를 권합니다.

1. [Astro 블로그 디자인 QA 체크리스트](/posts/design-application-audit-checklist/)
2. [Astro Content Collections로 프론트매터 오류를 배포 전에 막는 방법](/posts/astro-content-collections-frontmatter-checklist/)

둘 다 "글을 올리는 것"보다 "문제를 남기지 않고 운영하는 것"에 초점을 둔 글입니다.

## FAQ

### 이 블로그는 특정 프레임워크만 다루나요?

현재 공개 글은 Astro 운영 경험을 중심으로 쌓고 있지만, 기준 자체는 정적 사이트 운영, 배포, QA, 검색 품질처럼 다른 스택에도 옮겨갈 수 있는 주제를 우선합니다.

### SEO를 의식하면 글이 기계적으로 되지 않나요?

그 위험이 있습니다. 그래서 Google이 권하는 people-first 원칙처럼, 검색엔진을 속이기 위한 글이 아니라 기존 또는 의도된 독자가 직접 와도 유용한 글인지 먼저 확인합니다. (출처: [Google Search Central - Creating helpful, reliable, people-first content](https://developers.google.com/search/docs/fundamentals/creating-helpful-content))

## 출처

- [Google Search Central - Creating helpful, reliable, people-first content](https://developers.google.com/search/docs/fundamentals/creating-helpful-content)
- [Google Search Central - Understanding page experience in Google Search results](https://developers.google.com/search/docs/appearance/page-experience)
- [Astro Docs - Pages](https://docs.astro.build/en/basics/astro-pages/)
- [Astro Docs - Content collections](https://v6.docs.astro.build/en/guides/content-collections)
- [Astro Docs - Deploy your Astro Site to GitHub Pages](https://v4.docs.astro.build/en/guides/deploy/github/)
- [GitHub Docs - What is GitHub Pages?](https://docs.github.com/en/pages/getting-started-with-github-pages/what-is-github-pages)
