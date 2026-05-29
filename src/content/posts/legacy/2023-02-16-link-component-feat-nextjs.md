---
title: 'Next.js Link 컴포넌트로 페이지 전환 최적화'
slug: 'link-component-feat-nextjs'
author: 'Lovizu'
date: '2023-02-16'
summary: 'Next.js의 Link 컴포넌트를 활용한 클라이언트 사이드 라우팅 완벽 가이드. 자동 prefetching, 동적 라우팅, SEO 최적화, 그리고 <a> 태그와의 차이점을 이해하고 실무에 바로 적용할 수 있는 실제 예제들을 담았습니다.'
oneLineSummary: 'Link 컴포넌트로 빠르고 효율적인 라우팅 구현'
tags: ['legacy', 'legacy-migration', 'Next.js', 'React', '라우팅']
status: 'published'
updatedDate: '2026-04-23'
---

## Link 컴포넌트란?

Next.js의 Link 컴포넌트는 내장 컴포넌트로, 애플리케이션의 페이지 간 이동을 최적화합니다. 일반적인 HTML `<a>` 태그와 달리, 클라이언트 사이드 라우팅을 사용하여 페이지 전체를 다시 로드하지 않고 필요한 부분만 업데이트합니다.

## 기본 사용법

Link 컴포넌트의 가장 간단한 사용 방법입니다.

```jsx
import Link from 'next/link';

function Home() {
  return (
    <div>
      <Link href="/about">
        <a>About</a>
      </Link>
    </div>
  );
}
```

- **href**: 필수 속성으로, 이동할 페이지의 경로를 지정합니다
- **자식 요소**: Link의 자식 요소는 보통 `<a>` 태그입니다

## Prefetching 기능

Link 컴포넌트는 기본적으로 뷰포트에 들어온 링크의 페이지를 자동으로 미리 로드(prefetch)합니다. 이를 통해 다음 페이지 로딩 속도를 크게 향상시킬 수 있습니다.

```jsx
<Link href="/about" prefetch={true}>
  <a>About</a>
</Link>
```

Prefetch를 비활성화하려면:

```jsx
<Link href="/about" prefetch={false}>
  <a>About</a>
</Link>
```

## 주요 속성

Link 컴포넌트의 주요 속성들:

| 속성 | 설명 |
|------|------|
| href | 이동할 페이지의 경로 (필수) |
| as | 브라우저 주소창에 표시할 경로 (마스킹 용도) |
| replace | true면 브라우저 히스토리 스택을 대체 |
| scroll | 페이지 이동 후 스크롤 위치 지정 (기본값: true) |
| prefetch | 링크 prefetching 활성화 여부 (기본값: true) |

## Link 컴포넌트 vs <a> 태그

### Link 컴포넌트의 장점

1. **클라이언트 사이드 라우팅**: 페이지 새로고침 없이 라우팅하므로 빠른 전환
2. **Prefetching**: 자동으로 다음 페이지를 미리 로드하여 성능 향상
3. **SEO 최적화**: 서버 사이드 렌더링과 잘 작동하여 SEO 친화적
4. **코드 분할**: 페이지별 필요한 코드만 로드하므로 번들 크기 감소

### <a> 태그 사용 시기

- 외부 링크로 이동할 때
- 다른 도메인으로 이동할 때
- 파일 다운로드 링크

## 실제 사용 예제

```jsx
import Link from 'next/link';

export default function Navigation() {
  return (
    <nav>
      <Link href="/">
        <a>Home</a>
      </Link>
      <Link href="/about">
        <a>About</a>
      </Link>
      <Link href="/posts">
        <a>Posts</a>
      </Link>
      {/* 외부 링크는 <a> 태그 사용 */}
      <a href="https://example.com">External Site</a>
    </nav>
  );
}
```

## 동적 라우팅과 함께 사용

```jsx
<Link href="/posts/[id]" as={`/posts/${postId}`}>
  <a>{postTitle}</a>
</Link>
```

## 핵심 요약

Next.js의 Link 컴포넌트는 웹 애플리케이션의 라우팅을 최적화하는 핵심 도구입니다. 클라이언트 사이드 라우팅, 자동 prefetching, 코드 분할 등의 기능으로 사용자 경험을 크게 향상시킬 수 있습니다. Next.js 애플리케이션에서는 내부 링크에 Link 컴포넌트를 사용하는 것이 권장됩니다.
