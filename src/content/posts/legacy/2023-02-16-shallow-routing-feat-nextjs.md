---
title: 'Next.js Shallow Routing으로 효율적인 UX 구현하기'
slug: 'shallow-routing-feat-nextjs'
author: 'Lovizu'
date: '2023-02-16'
summary: 'Next.js의 Shallow Routing은 URL을 변경하면서도 페이지 상태와 데이터를 유지하는 고급 기능입니다. 불필요한 서버 요청을 제거하여 성능을 개선하고, 페이지네이션, 필터링, 모달 상태 관리 등에서 사용자 경험을 획기적으로 향상시킵니다. useRouter 훅의 shallow 옵션 사용법, 동적 라우팅 패턴 (Dynamic Routes, Catch-all Routes, Optional Catch-all), 초기 데이터 처리, 브라우저 히스토리 관리를 통해 실무 프로젝트에서 즉시 활용 가능한 예제와 주의사항을 상세히 설명합니다.'
oneLineSummary: 'Next.js Shallow Routing으로 서버 요청 없이 URL 변경하기'
tags: ['legacy', 'legacy-migration', 'Next.js', 'React', '라우팅', 'Web-Performance', 'UX']
status: 'published'
updatedDate: '2026-04-23'
---

## 도입: 페이지 이동의 비효율성

Next.js에서 페이지를 이동할 때마다 **새로운 서버 요청이 발생**합니다. 예를 들어, 게시글 목록에서 2번 페이지로 이동하려고 `router.push()`를 하면:

1. 현재 컴포넌트 언마운트
2. 서버에 요청 전송
3. 새로운 데이터 페칭
4. 컴포넌트 마운트
5. 페이지 렌더링

**이는 불필요한 성능 낭비입니다.** 사실 필요한 것은 URL만 변경하고, 현재 페이지의 상태와 데이터는 유지하는 것입니다. 이때 등장하는 것이 **Shallow Routing**입니다.

## Shallow Routing: 개념과 원리

**Shallow Routing은 URL만 변경하고 페이지의 초기 상태는 유지하는 기능**입니다. `getServerSideProps`나 `getStaticProps`를 다시 실행하지 않으므로:

✅ **장점**:
- 불필요한 서버 요청 제거
- 페이지 상태 보존 (스크롤 위치, 입력값 등)
- 빠른 페이지 이동
- 네트워크 대역폭 절약

⚠️ **주의사항**:
- URL은 변경되지만 데이터는 갱신되지 않음
- 새로운 초기 데이터가 필요하면 수동으로 처리해야 함
- 뒤로가기 시 서버에서 데이터를 다시 로드

## 기본 사용법

```jsx
import { useRouter } from 'next/router';

export default function PostList({ posts }) {
  const router = useRouter();

  const goToNextPage = () => {
    // shallow: true 옵션으로 URL만 변경
    router.push('/posts?page=2', undefined, { shallow: true });
  };

  return (
    <div>
      <h1>게시글 목록</h1>
      {posts.map(post => (
        <div key={post.id}>{post.title}</div>
      ))}
      <button onClick={goToNextPage}>다음 페이지</button>
    </div>
  );
}

export async function getStaticProps() {
  const posts = await fetchPosts(1);
  return { props: { posts } };
}
```

이 코드에서 "다음 페이지" 버튼을 클릭하면:
- URL이 `/posts?page=2`로 변경됨
- 현재 `posts` 데이터는 유지됨
- 서버에 새로운 요청이 발생하지 않음

## Shallow Routing의 실제 활용 사례

### 1. 페이지네이션

게시글 목록에서 페이지 이동 시 shallow routing 활용:

```jsx
export default function PostList({ posts, currentPage }) {
  const router = useRouter();

  const handlePageChange = (page) => {
    // URL 변경, 데이터는 유지, 서버 요청 없음
    router.push(`/posts?page=${page}`, undefined, { shallow: true });
  };

  return (
    <div>
      {posts.map(post => <PostItem key={post.id} post={post} />)}
      <Pagination 
        current={currentPage} 
        onPageChange={handlePageChange} 
      />
    </div>
  );
}
```

### 2. 필터링 (동적 데이터 업데이트 필요)

필터를 변경할 때 필요에 따라 클라이언트 필터링 또는 수동 데이터 로드:

```jsx
export default function ProductList({ initialProducts }) {
  const router = useRouter();
  const [products, setProducts] = useState(initialProducts);
  const category = router.query.category;

  // 필터 변경 감지 시 데이터 로드
  useEffect(() => {
    if (category) {
      fetchProductsByCategory(category).then(setProducts);
    }
  }, [category]);

  const handleCategoryChange = (newCategory) => {
    router.push(`/products?category=${newCategory}`, undefined, { shallow: true });
  };

  return (
    <div>
      <CategoryFilter onChange={handleCategoryChange} />
      <ProductGrid products={products} />
    </div>
  );
}
```

### 3. 모달 상태 관리

모달을 열고 닫을 때 URL은 변경하되, 배경 콘텐츠는 유지:

```jsx
export default function Gallery({ images }) {
  const router = useRouter();
  const selectedId = router.query.id;

  const openModal = (id) => {
    router.push(`/gallery?id=${id}`, undefined, { shallow: true });
  };

  const closeModal = () => {
    router.push('/gallery', undefined, { shallow: true });
  };

  return (
    <div>
      {/* 갤러리 아이템들 */}
      {images.map(img => (
        <img 
          key={img.id} 
          onClick={() => openModal(img.id)} 
        />
      ))}
      {selectedId && (
        <Modal 
          image={images.find(img => img.id === selectedId)} 
          onClose={closeModal}
        />
      )}
    </div>
  );
}
```

## Shallow Routing 주의사항

### 1. 초기 데이터가 필요한 경우: 수동 처리 필수

Shallow routing은 `getServerSideProps`를 다시 실행하지 않습니다. 새로운 데이터가 필요하면 **useEffect에서 수동으로 데이터를 로드**해야 합니다:

```jsx
useEffect(() => {
  if (router.isReady) {
    // router.query가 준비된 후에 데이터 로드
    fetchData(router.query).then(setData);
  }
}, [router.query, router.isReady]);
```

### 2. 동적 라우팅 시 asPath 사용

동적 라우트의 경우 명시적으로 경로를 지정해야 합니다:

```jsx
// ❌ 잘못된 방법
router.push('/posts/[id]', undefined, { shallow: true });

// ✅ 올바른 방법
router.push('/posts/[id]', `/posts/${id}`, { shallow: true });
```

### 3. 브라우저 히스토리의 한계

Shallow routing은 히스토리를 추가하지만, **뒤로가기 시 서버에서 데이터를 다시 로드**합니다. 이는 성능 최적화이지만, 사용자가 이전 상태로 정확히 복원되지 않을 수 있습니다.

### 4. 라우팅 패턴별 가능성

| 패턴 | 파일 이름 | Shallow 적합도 |
|------|---------|------------|
| 일반 페이지 | `pages/posts.js` | ⭐⭐⭐⭐⭐ |
| 동적 단일 | `pages/posts/[id].js` | ⭐⭐⭐⭐ |
| Catch-all | `pages/docs/[...slug].js` | ⭐⭐⭐ |
| Optional Catch-all | `pages/[[...slug]].js` | ⭐⭐⭐ |

## 성능 비교: Shallow vs. 일반 라우팅

```javascript
// 일반 라우팅: 전체 라이프사이클 실행
router.push('/posts?page=2')
// 1. getStaticProps/getServerSideProps 실행 (서버 요청)
// 2. 새로운 props 전달
// 3. 컴포넌트 리렌더링
// 4. 스크롤 위치 초기화

// Shallow 라우팅: URL만 변경
router.push('/posts?page=2', undefined, { shallow: true })
// 1. URL 변경
// 2. router.query 업데이트
// 3. 필요시만 useEffect에서 데이터 로드
// 4. 스크롤 위치 유지
```

**결과**: Shallow routing이 **10배 이상 빠른 페이지 이동** 성능 제공

## Shallow Routing 사용 체크리스트

구현 전에 다음을 확인하세요:

- ✅ URL 변경만 필요한가? → Shallow routing 사용
- ✅ 페이지 상태를 유지해야 하는가? → Shallow routing 필요
- ❌ 새로운 초기 데이터가 필수인가? → 일반 라우팅 또는 수동 처리
- ❌ 정확한 히스토리 복원이 필요한가? → 일반 라우팅 사용

## 마치며

**Shallow routing은 Next.js의 숨겨진 성능 최적화 도구**입니다. 적절히 사용하면:

1. **성능 향상**: 불필요한 서버 요청 제거
2. **UX 개선**: 빠른 페이지 이동
3. **상태 보존**: 사용자 입력값, 스크롤 위치 등 유지

다만 **데이터 갱신이 필요한 경우는 반드시 useEffect에서 수동으로 처리**해야 합니다. 페이지네이션, 필터링, 모달 관리 등에서 선택적으로 활용하여 **사용자 경험을 한 단계 업그레이드**하세요.
