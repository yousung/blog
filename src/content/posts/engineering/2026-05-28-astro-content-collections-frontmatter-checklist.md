---
title: "Astro Content Collections로 프론트매터 오류를 배포 전에 막는 방법"
slug: "astro-content-collections-frontmatter-checklist"
ogImage: "/images/posts/astro-content-collections-frontmatter-checklist/hero.png"
author: "감성개발자"
date: "2026-05-08"
updatedDate: "2026-05-28"
summary: "slug 오타 하나로 배포가 막히는 일을 겪고 나서 굳힌 기준. Astro Content Collections와 Zod로 frontmatter 오류를 빌드 전에 걸러내는 실무 순서를 풀었다."
oneLineSummary: "Astro Content Collections와 Zod로 frontmatter 오류를 배포 전에 막는 실무 순서."
tags: [Astro, 프론트매터, 유효성검사]
status: "published"
---

![frontmatter 검증 과정을 표현한 기술 일러스트](/images/posts/astro-content-collections-frontmatter-checklist/hero.png)

좋은 글을 다 써놓고 배포 버튼을 눌렀는데, 빌드 로그에 빨간 줄이 뜹니다. 원인은 로직이 아니라 `slug` 오타 하나, 혹은 빠뜨린 `date` 필드입니다. 에이전시에서 여러 프로젝트를 동시에 굴리던 시절부터 지금까지, 나를 가장 자주 붙잡은 건 복잡한 코드가 아니라 이런 사소한 metadata 실수였습니다. 그래서 저는 작성자 자유도보다 발행 안정성을 먼저 두는 스키마를 기본값으로 잡습니다.

이 글이 결국 말하려는 건 세 가지입니다.

1. frontmatter 검증은 글쓰기 편의 기능이 아니라 배포 안정성 장치입니다.
2. Astro는 컬렉션 스키마를 정의하면 Zod 기반 검증과 자동 TypeScript 타이핑을 함께 제공합니다.
3. 실무에서는 "필수 필드 정의 → slug 규칙 → 상태 필드 분리 → 빌드 전 검사" 순으로 굳히는 편이 빠릅니다.

`title`, `slug`, `date`, `summary`, `tags`, `status` 같은 필드를 어떻게 통제하는지, 왜 Content Collections가 초반부터 필요한지를 아래에서 순서대로 풉니다. Astro는 콘텐츠 컬렉션 스키마가 frontmatter를 일관되게 검증하고, 스키마를 어긴 파일이 있으면 오류를 보여준다고 설명합니다. (출처: [Astro Docs - Content collections](https://v6.docs.astro.build/en/guides/content-collections), [Astro Docs - Content entry data does not match schema](https://docs.astro.build/en/reference/errors/content-entry-data-error/))

## 1. Content Collections를 먼저 붙여야 하는 이유

Astro 문서에 따르면, 스키마는 컬렉션 안의 frontmatter 또는 데이터가 일관된 형태를 가지도록 보장하며, 스키마를 정의하면 TypeScript 인터페이스도 자동 생성됩니다. 즉, 작성 시점과 렌더링 시점 모두에서 오류를 줄일 수 있습니다. (출처: [Astro Docs - Content collections](https://v6.docs.astro.build/en/guides/content-collections))

운영 관점에서는 장점이 더 단순합니다.

1. 필수값 누락을 초기에 막을 수 있습니다.
2. slug 형식을 강제해 URL 품질을 통일할 수 있습니다.
3. `draft`와 `published`를 구분해 홈, 피드, sitemap 노출을 제어하기 쉬워집니다.

## 2. 블로그에서 최소로 강제할 필드

제가 초반부터 고정하는 항목은 아래 정도입니다.

| 필드 | 왜 필요한가 | 체크 기준 |
| --- | --- | --- |
| `title` | 목록, 상세, OG 제목 공통 기준 | 빈 문자열 금지 |
| `slug` | URL 일관성 | 영문 소문자와 하이픈만 허용 |
| `date` | 정렬, 노출 기준 | 날짜 형식 강제 |
| `summary` | 목록 설명, 메타 설명 재사용 | 한 줄 요약 필수 |
| `tags` | 주제 분류 | 1개 이상 |
| `status` | 초안/공개 제어 | `draft` 또는 `published` |

Astro는 Zod로 이러한 스키마를 정의할 수 있고, 컬렉션 항목이 이를 어기면 오류를 알려줍니다. 공식 오류 문서도 컬렉션 스키마와 비교해 문제를 점검하라고 안내합니다. (출처: [Astro Docs - Content collections](https://v6.docs.astro.build/en/guides/content-collections), [Astro Docs - Content entry data does not match schema](https://docs.astro.build/en/reference/errors/content-entry-data-error/))

## 3. 실무 체크리스트: 빌드 전에 무엇을 보나

### 3-1. slug는 사람이 아니라 규칙이 결정하게 합니다

사람이 직접 slug를 정하면 대문자, 공백, 날짜 중복 같은 실수가 반복됩니다. 팀에 새 사람이 글을 올릴 때마다 가장 먼저 깨지던 게 이 부분이라, 저는 정규식으로 허용 범위를 좁혀 사람이 아니라 규칙이 결정하게 둡니다. Astro가 스키마 기반 검증을 지원하므로, 이 단계는 문서화보다 자동화가 확실히 유리합니다. (출처: [Astro Docs - Content collections](https://v6.docs.astro.build/en/guides/content-collections))

### 3-2. `status`가 없으면 초안 관리가 무너집니다

초기 블로그일수록 초안과 공개 글이 섞이기 쉽습니다. `status` 필드를 별도로 두면 홈과 피드에서 `published`만 필터링하기 쉬워집니다. 혼자 쓰는 블로그라면 폴더 분리로도 버티지만, 여러 명이 글을 올리기 시작하면 이 한 필드가 "실수로 초안이 공개된" 사고를 가장 확실하게 막아줍니다.

### 3-3. 오류는 빌드 전에 보이게 해야 합니다

Astro는 스키마 위반 시 빌드 또는 검증 흐름에서 오류를 보여줍니다. 따라서 로컬 또는 CI에서 frontmatter 검사를 먼저 돌리면, 게시 후 수정하는 비용보다 훨씬 적은 비용으로 문제를 막을 수 있습니다. (출처: [Astro Docs - Content entry data does not match schema](https://docs.astro.build/en/reference/errors/content-entry-data-error/))

## 다시 볼 때를 위한 메모

새 블로그에 스키마를 붙일 때, 혹은 반년 뒤 이 구조를 다시 손볼 때 내가 순서대로 확인하는 것들입니다.

1. `src/content.config.ts`에 최소 필드부터 고정합니다. 늘리는 건 나중에 해도 늦지 않습니다.
2. 새 글은 맨손으로 쓰지 말고 템플릿 frontmatter를 복사해 시작합니다.
3. 빌드 전에 frontmatter 검사를 먼저 돌려, 게시 후 고치는 상황을 만들지 않습니다.
4. 공개 직전에는 `published` 상태, 날짜, slug 중복 여부를 마지막으로 훑습니다.

결국 핵심은 하나입니다. 실수는 사람이 아니라 스키마가 먼저 잡게 두는 것.

## FAQ

### 필드가 적을수록 좋은가요?

초기에는 그렇습니다. 하지만 너무 적으면 요약, 상태, 태그 같은 운영 정보가 빠져 글이 쌓일수록 관리가 어려워집니다. 최소 운영 단위를 커버하는 정도까지는 고정하는 편이 좋습니다.

### MDX나 CMS를 써도 같은 원칙이 필요한가요?

필요합니다. 저장 형식이 바뀌어도 "필수 메타데이터를 강제하고, 위반 시 조기에 실패하게 한다"는 원칙은 그대로 유효합니다. Astro 공식 문서도 컬렉션 데이터의 예측 가능성을 스키마의 핵심 가치로 설명합니다. (출처: [Astro Docs - Content collections](https://v6.docs.astro.build/en/guides/content-collections))

## 출처

- [Astro Docs - Content collections](https://v6.docs.astro.build/en/guides/content-collections)
- [Astro Docs - Content entry data does not match schema](https://docs.astro.build/en/reference/errors/content-entry-data-error/)
