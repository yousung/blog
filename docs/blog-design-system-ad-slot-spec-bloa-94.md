# BLOA-94 블로그 디자인 시스템 + 광고 슬롯 레이아웃 명세

## 목적
- BLOA 블로그를 구현 가능한 수준의 디자인 시스템/레이아웃 규격으로 고정한다.
- Google AdSense 연동 전에도 광고 슬롯 플레이스홀더를 안정적으로 배치해 CLS를 방지한다.

## 적용 범위
- 페이지: 홈(`src/pages/index.astro`), 글 상세(`src/pages/posts/[...slug].astro`), 공통 레이아웃(`src/layouts/BaseLayout.astro`).
- 컴포넌트: 헤더, 푸터, 포스트 카드, 본문 래퍼, 광고 슬롯 컨테이너.

## 디자인 렌즈 근거
- 인지/주의: `Cognitive Load`, `Selective Attention`, `Hick's Law`.
- 시스템/상호작용: `Doherty Threshold`, `Jakob's Law`, `Progressive Disclosure`.
- 접근성: `WCAG POUR`, color-independence, 44x44 타깃.
- 레이아웃 지각: `Proximity`, `Common Region`, `Pragnanz`.

## 토큰 규격 (시스템 변경 제안)
아래 값은 공통 토큰으로 선언하고, 컴포넌트에서 직접 hex/px 하드코딩 금지.

- Color
- `--bg-canvas: #f8fafc`
- `--bg-surface: #ffffff`
- `--bg-muted: #eef2f7`
- `--text-primary: #0f172a`
- `--text-secondary: #475569`
- `--text-inverse: #f8fafc`
- `--border-default: #dbe3ee`
- `--accent-primary: #0ea5e9`
- `--focus-ring: #0284c7`
- `--ad-slot-bg: #f1f5f9`
- `--ad-slot-border: #cbd5e1`

- Typography
- `--font-body: "Pretendard", "Noto Sans KR", sans-serif`
- `--text-xs: 0.75rem`
- `--text-sm: 0.875rem`
- `--text-base: 1rem`
- `--text-lg: 1.125rem`
- `--text-xl: 1.25rem`
- `--text-2xl: 1.5rem`
- `--lh-tight: 1.3`
- `--lh-base: 1.6`

- Spacing
- `--space-2: 0.5rem`
- `--space-3: 0.75rem`
- `--space-4: 1rem`
- `--space-6: 1.5rem`
- `--space-8: 2rem`
- `--space-10: 2.5rem`
- `--space-12: 3rem`
- `--space-16: 4rem`

- Radius / Shadow / Motion
- `--radius-sm: 0.5rem`
- `--radius-md: 0.75rem`
- `--shadow-sm: 0 1px 2px rgba(15, 23, 42, 0.08)`
- `--shadow-md: 0 6px 20px rgba(15, 23, 42, 0.08)`
- `--motion-fast: 150ms`
- `--motion-base: 200ms`

## 반응형 기준선
- Mobile: `390x844` 기준, 1열.
- Tablet: `768~1023px`, 본문 1열 + 사이드바 하단 스택.
- Desktop: `1024px+`, 본문 + 우측 사이드바 2열.
- 컨텐츠 최대 폭: `72rem`.

## 페이지 IA/레이아웃

### 1) 홈
- 순서: `Header` → `TopAdSlot` → `FeaturedPost` → `PostList` → `Footer`.
- 리스트는 single primary action(카드 클릭)만 제공.
- 6개 카드마다 `InlineFeedAdSlot` 1회 삽입(모바일은 4개마다).

### 2) 글 상세
- 순서: `Header` → `TopAdSlot` → `Article` → `BottomAdSlot` → `Related/Next` → `Footer`.
- Desktop에서만 우측 `SidebarAdSlot` 노출, Tablet/Mobile은 본문 하단으로 이동.
- 본문 중간 `InArticleAdSlot`은 첫 단락 이후 35~45% 지점 1회 고정(과삽입 금지).

## 광고 슬롯 컴포넌트 규격
공통 원칙: 로딩 전 reserve-space 확보, placeholder 텍스트는 스크린리더에 과노출되지 않도록 처리.

- `TopAdSlot`
- 위치: 헤더 직하단
- 사이즈: `min-height: 90px` (mobile), `120px` (tablet+)
- 폭: 컨텐츠 폭 100%

- `InlineFeedAdSlot`
- 위치: 목록 카드 사이
- 사이즈: `min-height: 280px`
- 폭: 카드 영역 100%

- `InArticleAdSlot`
- 위치: 본문 중간
- 사이즈: `min-height: 280px`
- 폭: 본문 폭 100%

- `SidebarAdSlot`
- 위치: 데스크톱 우측 컬럼 sticky
- 사이즈: `width: 300px`, `min-height: 600px`
- sticky offset: `top: var(--space-8)`

- `BottomAdSlot`
- 위치: 본문 종료 직후
- 사이즈: `min-height: 250px`
- 폭: 본문 폭 100%

## CLS 방지 원칙 (필수)
- 슬롯은 서버 렌더 시점부터 고정 `min-height`를 가진 컨테이너를 출력한다.
- 광고 스크립트 로드 성공/실패와 무관하게 컨테이너 높이를 줄이지 않는다.
- skeleton/placeholder는 `position: absolute` 오버레이 대신 기본 흐름 배치(레이아웃 점프 방지).
- lazy-load 시에도 placeholder를 선렌더링한다.

## 접근성 기준
- 모든 링크/버튼/탭 가능한 요소는 최소 `44x44px` 타깃.
- 색상 의존 금지: 광고 슬롯은 `"광고"` 텍스트 라벨을 시각적으로 제공.
- 포커스링: `outline` 또는 `box-shadow`로 3:1 이상 대비.
- 모션 축소: `prefers-reduced-motion`에서 transition 제거.
- 읽기 순서: DOM 순서 = 시각 순서 유지.

## 다크모드 + giscus 정합성
- 다크모드 기준: `data-theme="dark"` 또는 `prefers-color-scheme: dark` 중 단일 소스로 통일.
- giscus `data-theme` 매핑
- light 모드: `light`
- dark 모드: `dark_dimmed` (또는 프로젝트 표준 dark 테마 1개)
- 모드 전환 시 giscus iframe theme도 함께 갱신해야 한다.

## 구현 핸드오프 (Coder)
- 대상
- `src/layouts/BaseLayout.astro`: 토큰 선언, 반응형 컨테이너, 헤더/푸터/광고 슬롯 위치.
- `src/pages/index.astro`: featured/list 구조 분리 + 피드 광고 슬롯 규칙.
- `src/pages/posts/[...slug].astro`: 본문 중간/하단/사이드바 슬롯 배치.
- `src/components/ads/AdSlot.astro` (신규): 슬롯 타입별 variant(`top|feed|article|sidebar|bottom`) + reserve-space.

- 수용 기준
1. 1440x900, 768x1024, 390x844에서 슬롯 위치/크기가 명세와 일치한다.
2. 광고 스크립트가 늦게 로드돼도 콘텐츠 점프가 발생하지 않는다(CLS 관찰상 유의미한 점프 없음).
3. 다크/라이트 전환 시 giscus와 페이지 테마가 불일치하지 않는다.
4. 토큰 외 임의 hex/px 값 추가 없이 구현된다.
5. 키보드만으로 주요 인터랙션과 링크 진입이 가능하다.

## QA 체크리스트
- 데스크톱(1440x900): `Top/Sidebar/InArticle/Bottom` 슬롯 확인.
- 모바일(390x844): 사이드바 슬롯이 본문 하단으로 이동했는지 확인.
- 네트워크 느림(광고 스크립트 지연): placeholder 높이 유지 여부 확인.
- 다크모드 전환: 본문/슬롯/giscus 대비 및 테마 동기화 확인.

## 리스크와 트레이드오프
- 광고 밀도 증가 시 독서 흐름이 저하될 수 있어, 본문 중간 슬롯은 1회로 제한.
- 사이드바 고정 슬롯은 저해상도 노트북에서 시야 점유가 커질 수 있어 `1024px` 미만 숨김 처리.
