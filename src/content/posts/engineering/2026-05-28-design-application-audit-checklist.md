---
title: "Astro 블로그 디자인 QA 체크리스트"
slug: "design-application-audit-checklist"
author: "BLOA Team"
date: "2026-04-30"
updatedDate: "2026-05-28"
summary: "배포 후 기본 스타일처럼 보이는 문제를 줄이기 위해 홈, 상세, 모바일에서 확인할 디자인 QA 기준을 정리했다."
oneLineSummary: "배포 후 기본 스타일처럼 보이는 문제를 줄이기 위해 홈, 상세, 모바일에서 확인할 디자인 QA 기준을 정리했다."
tags: [Astro, QA, 디자인]
status: "published"
---

Astro 블로그를 배포한 뒤 "코드는 맞는데 화면은 어딘가 기본값처럼 보인다"는 순간이 자주 생깁니다. 이 글은 그런 문제를 줄이기 위해, 실제 배포 화면에서 무엇을 먼저 확인해야 하는지 한 번에 정리한 공개형 QA 체크리스트입니다. Google은 좋은 페이지 경험을 만들 때 Core Web Vitals만 보지 말고 모바일 표시, 과도한 광고 여부, 본문과 기타 요소의 구분까지 함께 확인하라고 안내합니다. 따라서 디자인 QA도 단순 미관 점검이 아니라 검색 경험 점검으로 봐야 합니다. (출처: [Google Search Central - Understanding page experience in Google Search results](https://developers.google.com/search/docs/appearance/page-experience), [web.dev - Web Vitals](https://web.dev/articles/vitals?hl=en))

> 요약
>
> - 홈, 상세, 모바일을 따로 보지 않으면 배포 후 누락을 놓치기 쉽습니다.
> - 좋은 페이지 경험은 속도만이 아니라 본문 가독성, 광고 간섭 최소화, 모바일 표시 품질까지 포함합니다. (출처: [Google Search Central - Understanding page experience in Google Search results](https://developers.google.com/search/docs/appearance/page-experience))
> - 저는 실제 블로그 QA에서 "홈 1회, 상세 1회, 모바일 1회"를 최소 단위로 묶어 확인합니다.

최종 업데이트: 2026-05-28

## 작성 관점

저는 정적 사이트를 배포할 때 Lighthouse 점수보다 먼저 실제 렌더링 화면을 봅니다. CSS 번들 누락, 광고 슬롯 순서 꼬임, 모바일 카드 간격 붕괴처럼 사용자가 바로 체감하는 문제는 화면 확인에서 가장 빨리 잡히기 때문입니다.

## 1. 홈 화면에서 먼저 확인할 것

홈에서는 아래 4가지를 한 번에 확인합니다.

1. 레이아웃: `Featured` 카드와 글 목록이 분리되어 보이는지
2. 타이포: 제목/요약/날짜의 크기와 대비가 기본 브라우저 스타일이 아닌지
3. 네비게이션: 헤더 메뉴(`홈`, `개인정보처리방침`)가 일관된 간격으로 보이는지
4. 광고 슬롯: 상단 슬롯과 피드 슬롯 플레이스홀더가 구분되어 보이는지

핵심은 "HTML이 보이는가"가 아니라 **의도한 정보 위계가 유지되는가**입니다. Google은 사용자가 메인 콘텐츠와 다른 요소를 쉽게 구분할 수 있어야 하며, 광고가 본문을 방해하지 않아야 한다고 설명합니다. 홈 화면은 그 기준이 가장 먼저 드러나는 경로입니다. (출처: [Google Search Central - Understanding page experience in Google Search results](https://developers.google.com/search/docs/appearance/page-experience))

## 2. 상세 화면에서 확인할 것

상세 페이지에서는 본문 구조와 부가 요소 배치가 함께 깨지지 않았는지 확인합니다.

1. 본문 카드(배경/테두리/패딩)가 적용되는지
2. 본문 중간 광고 슬롯(`In-article`)이 표시되는지
3. 하단 슬롯(`Bottom`)과 댓글 영역이 순서대로 노출되는지
4. 데스크톱에서 사이드바 슬롯이 sticky로 동작하는지
5. 모바일에서 사이드바 슬롯이 하단으로 재배치되는지

Core Web Vitals는 로딩, 상호작용, 시각적 안정성을 함께 봅니다. 현재 핵심 지표는 LCP, INP, CLS 세 가지이며, 각각이 실제 사용 경험을 대표합니다. 상세 페이지에서 레이아웃이 흔들리거나 광고 영역 때문에 본문이 밀리면 CLS와 체감 품질이 동시에 나빠질 수 있습니다. (출처: [web.dev - Web Vitals](https://web.dev/articles/vitals?hl=en))

특히 반응형 전환은 스크린샷 1장으로는 놓치기 쉬워서, 최소 데스크톱과 모바일 2개 뷰포트를 각각 확인하는 편이 안전합니다.

## 3. 배포 품질 게이트 제안

디자인 반영 이슈를 줄이려면 배포 직후 아래 4개 게이트를 통과시키는 방식이 안전합니다.

1. URL 게이트: 홈 + 상세 URL의 상태코드 200 확인
2. 시각 게이트: 홈/상세 핵심 카드와 광고 슬롯이 모두 보이는지 확인
3. 콘텐츠 게이트: 샘플 slug, 테스트 제목, 임시 문구가 남아 있지 않은지 확인
4. 기록 게이트: 커밋 SHA, 배포 시각(UTC/KST), 검증 결과를 이슈 코멘트에 남김

Astro는 정적 사이트를 로컬에서 빌드한 뒤 배포하는 흐름을 기본으로 안내하고, GitHub Pages 배포 시에는 `site`와 `base` 설정을 명확히 맞추라고 설명합니다. 배포 설정이 맞더라도 마지막 화면 검증이 없으면 CSS 또는 라우팅 누락이 그대로 운영에 올라갈 수 있습니다. (출처: [Astro Docs - Deploy your Astro Site to GitHub Pages](https://v4.docs.astro.build/en/guides/deploy/github/), [GitHub Docs - What is GitHub Pages?](https://docs.github.com/en/pages/getting-started-with-github-pages/what-is-github-pages))

## 4. 체크리스트를 실제 운영에 붙이는 방법

배포 자동화가 있어도 마지막에 사람이 볼 최소 체크리스트는 남겨두는 편이 좋습니다. 저는 아래 순서로 끝냅니다.

| 단계 | 확인 질문 | 통과 기준 |
| --- | --- | --- |
| 홈 | 첫 화면에서 이 블로그가 어떤 사이트인지 즉시 보이는가 | 제목, Featured, 카드 간격이 정상 |
| 상세 | 본문이 메인이고 광고가 보조라는 구조가 유지되는가 | 본문 카드 우선, 광고 간섭 없음 |
| 모바일 | 글 읽기 흐름이 깨지지 않는가 | 카드 폭, 여백, 댓글, 광고 순서 정상 |
| 기록 | 다시 확인할 근거가 남는가 | 커밋, 배포 시각, 결과 기록 완료 |

## FAQ

### Lighthouse 점수가 좋으면 디자인 QA를 생략해도 되나요?

아닙니다. Google도 좋은 리포트 점수가 상위 노출을 보장하지 않으며, 전체적인 페이지 경험을 함께 봐야 한다고 설명합니다. 실제 시각 구조와 모바일 표시 품질은 별도 확인이 필요합니다. (출처: [Google Search Central - Understanding page experience in Google Search results](https://developers.google.com/search/docs/appearance/page-experience))

### 광고 슬롯이 비어 있어도 QA를 해야 하나요?

해야 합니다. 광고가 실제 광고 코드 대신 플레이스홀더로 렌더되더라도, 본문을 밀어내거나 순서를 깨지 않는지 먼저 확인해야 합니다. Google 역시 메인 콘텐츠와 기타 요소의 구분, 과도한 광고 방지 여부를 페이지 경험 요소로 봅니다. (출처: [Google Search Central - Understanding page experience in Google Search results](https://developers.google.com/search/docs/appearance/page-experience))

## 출처

- [Google Search Central - Understanding page experience in Google Search results](https://developers.google.com/search/docs/appearance/page-experience)
- [web.dev - Web Vitals](https://web.dev/articles/vitals?hl=en)
- [Astro Docs - Deploy your Astro Site to GitHub Pages](https://v4.docs.astro.build/en/guides/deploy/github/)
- [GitHub Docs - What is GitHub Pages?](https://docs.github.com/en/pages/getting-started-with-github-pages/what-is-github-pages)
