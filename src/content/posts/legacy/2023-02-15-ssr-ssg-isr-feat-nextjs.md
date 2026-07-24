---
title: 'Next.js 렌더링 방식 완벽 비교: SSR, SSG, CSR, ISR'
slug: 'ssr-ssg-isr-feat-nextjs'
author: "감성개발자"
date: '2023-02-15'
summary: 'Next.js는 SSR(서버 사이드 렌더링), SSG(정적 사이트 생성), CSR(클라이언트 사이드 렌더링), ISR(증분 정적 재생성) 등 다양한 렌더링 방식을 지원합니다. 각 방식의 특징, 장단점, 구현 방법을 비교하여 프로젝트 요구사항에 맞는 최적의 렌더링 전략을 선택할 수 있습니다.'
oneLineSummary: 'Next.js의 4가지 렌더링 방식 완벽 이해하기'
tags: ['legacy', 'legacy-migration', 'Next.js', 'React', 'SEO', '렌더링']
status: "draft"
updatedDate: '2026-04-23'
---

## Next.js 렌더링 방식 개요

Next.js는 React 기반의 프레임워크로, 다양한 페이지 렌더링 방식을 지원합니다. 각 방식은 고유한 장단점을 가지고 있으며, 프로젝트의 요구사항에 따라 적절한 방식을 선택해야 합니다.

## SSR (Server Side Rendering)

### 정의
SSR은 클라이언트의 요청이 들어올 때마다 서버에서 HTML을 생성하여 전달하는 렌더링 방식입니다.

### 특징
- 서버에서 렌더링된 완전한 HTML을 전달
- `getInitialProps` 또는 `getServerSideProps` 메서드로 데이터 준비
- SEO 최적화에 유리함
- 초기 로딩 속도가 빠름

### 장점
- 검색 엔진 최적화(SEO)가 우수함
- 초기 페이지 로드 성능이 좋음
- 동적 데이터를 실시간으로 반영 가능

### 단점
- 매 요청마다 서버에서 렌더링하므로 서버 부하 증가
- 응답 시간이 상대적으로 느릴 수 있음

## SSG (Static Site Generation)

### 정의
SSG는 빌드 시점에 미리 정적인 HTML 파일을 생성하는 렌더링 방식입니다.

### 특징
- 빌드 시간에 모든 페이지 사전 생성
- `getStaticProps` 메서드로 빌드 시간 데이터 준비
- CDN에 캐싱하여 빠른 전달 가능
- 정적인 콘텐츠에 적합

### 장점
- 가장 빠른 페이지 로드 성능
- 서버 부하가 거의 없음
- SEO 최적화가 우수함
- CDN 캐싱으로 글로벌 배포에 효과적

### 단점
- 데이터 변경 시 재빌드 필요
- 동적 페이지에는 부적합
- 빌드 시간이 길어질 수 있음

## CSR (Client Side Rendering)

### 정의
CSR은 서버에서 빈 HTML 페이지를 전달하고, 클라이언트에서 JavaScript로 DOM을 생성하는 방식입니다.

### 특징
- 서버에서 최소한의 HTML 전달
- 클라이언트 브라우저에서 모든 렌더링 수행
- 동적 콘텐츠 업데이트에 유리함

### 장점
- 서버 부하가 적음
- 클라이언트 측에서 유연한 UI 조작 가능
- 빠른 페이지 전환

### 단점
- 초기 로딩 속도가 느림
- SEO 최적화가 어려움
- JavaScript 필수 (JavaScript 비활성화 사용자 대응 어려움)

## ISR (Incremental Static Regeneration)

### 정의
ISR은 SSG와 SSR의 장점을 결합한 방식으로, 정적 페이지를 주기적으로 재생성합니다.

### 특징
- 빌드 시간에 일부 페이지 사전 생성
- `revalidate` 속성으로 주기적 업데이트 설정
- SSG의 빠른 성능 + SSR의 최신 데이터 제공
- 하이브리드 렌더링 방식

### 장점
- SSG의 빠른 성능과 SSR의 동적 데이터 제공 결합
- 지정된 시간 간격으로 자동 업데이트
- 서버 부하 최소화
- SEO 최적화 가능

### 단점
- 설정이 복잡할 수 있음
- `revalidate` 시간 동안 오래된 데이터 제공

## 렌더링 방식 선택 가이드

| 특성 | SSR | SSG | CSR | ISR |
|------|-----|-----|-----|-----|
| SEO | 우수 | 우수 | 약함 | 우수 |
| 초기 로딩 속도 | 빠름 | 매우 빠름 | 느림 | 매우 빠름 |
| 서버 부하 | 높음 | 낮음 | 낮음 | 낮음 |
| 동적 콘텐츠 | 좋음 | 나쁨 | 좋음 | 보통 |
| 빌드 시간 | - | 길음 | - | 보통 |

## 핵심 요약

각 렌더링 방식은 고유한 특성을 가지고 있습니다. SEO가 중요한 블로그나 마케팅 사이트는 SSG나 ISR을, 실시간 동적 콘텐츠가 필요한 경우 SSR을, 사용자 인터랙션이 많은 SPA는 CSR을 선택하세요. Next.js의 강점은 이러한 다양한 방식을 페이지별로 선택할 수 있다는 것입니다.

## 참고 자료

- [Next.js Data Fetching 문서](https://nextjs.org/docs/basic-features/data-fetching)
- [Incremental Static Regeneration](https://nextjs.org/docs/basic-features/data-fetching/incremental-static-regeneration)
