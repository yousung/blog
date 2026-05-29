# BLOA-68 블로그 레이아웃/컴포넌트 UX 결정

## 목적
- 현재 `src/pages/index.astro`와 `src/layouts/BaseLayout.astro`의 기본 리스트 UI를 제품 수준으로 승격하기 위한 UX 기준 확정.
- 구현자가 그대로 적용 가능한 수준으로 IA, 컴포넌트 구성, 토큰, 접근성/성능 기준 명시.

## 현재 상태 진단
- 인지/지각 (`Cognitive Load`, `Selective Attention`): 제목/본문/설명 우선순위가 동일해 정보 스캔 속도가 느림.
- 게슈탈트 (`Proximity`, `Common Region`): 카드 경계/리듬 부재로 글 단위 군집 인식이 약함.
- 결정/주의 (`Hick's Law`): 목록 내 클릭 타깃의 일관 규칙이 없어 선택 비용 증가.
- 사용성 휴리스틱 (`Recognition over Recall`): 메타 정보(날짜/태그)가 없어 글 성격을 기억에 의존.
- 접근성 (`WCAG POUR`): 포커스/링크 상태/터치 타깃 기준 미정.

## IA/사용자 흐름 결정
1. 홈에서 "최신 글 1개"를 시각적으로 강조(`Featured Post`)하고 나머지는 "최근 글 목록"으로 분리.
2. 각 글은 `제목 > 설명 > 메타(날짜/태그)` 순으로 고정.
3. 카드 전체는 단일 주요 액션(상세 페이지 이동)으로 설계하고, 보조 액션은 두지 않음.

## 레이아웃 결정
- 뷰포트
- 데스크톱: 1440x900 기준 컨텐츠 최대폭 `72rem`.
- 모바일: 390x844 기준 좌우 패딩 축소, 단일 컬럼 유지.
- 구조
- `SiteHeader` (로고/블로그명)
- `Hero/Featured` (최신 글 1건)
- `PostList` (2번째 글부터)
- `SiteFooter` (저작권/간단 링크)
- 밀도
- 블로그 성격상 읽기 중심 중밀도. 카드 간 간격은 넉넉하게 유지.

## 컴포넌트 사양
- `PostCard`
- 포함: `title`, `description`, `pubDate`, `tags[]`, `href`.
- 상호작용: 카드 hover 시 배경/보더만 변화, 레이아웃 이동 없음.
- 접근성: 키보드 포커스 링 명확, 링크 텍스트는 제목 그대로.
- `FeaturedPost`
- `PostCard` 대비 시각 우선순위 1단계 상향(타이포/패딩/배경).
- `EmptyState`
- 게시글 0건일 때 "아직 게시글이 없습니다" + 짧은 안내 문구.
- `TagPill`
- 색상만으로 상태 전달 금지. 텍스트 자체로 의미 전달.

## 디자인 토큰 제안 (시스템 변경)
현재 코드에는 토큰 레이어가 없어 아래 CSS 변수 도입을 시스템 변경으로 제안.

- 색상
- `--color-bg: #f8fafc`
- `--color-surface: #ffffff`
- `--color-text-primary: #0f172a`
- `--color-text-secondary: #475569`
- `--color-border: #e2e8f0`
- `--color-accent: #0ea5e9`
- 간격
- `--space-2: 0.5rem`
- `--space-3: 0.75rem`
- `--space-4: 1rem`
- `--space-6: 1.5rem`
- `--space-8: 2rem`
- `--space-12: 3rem`
- 타입
- `--text-sm: 0.875rem`
- `--text-base: 1rem`
- `--text-lg: 1.125rem`
- `--text-2xl: 1.5rem`
- 라운드/그림자
- `--radius-md: 0.75rem`
- `--shadow-sm: 0 1px 2px rgba(15, 23, 42, 0.08)`

## 접근성/성능 기준
- 색 대비: 본문 4.5:1 이상, 큰 제목 3:1 이상.
- 링크/버튼 터치 영역: 최소 44x44px.
- 키보드 탐색: `Tab` 순서가 시각 순서와 일치.
- 모션: hover/transition 150~200ms, `prefers-reduced-motion` 존중.
- 지각 성능 (`Doherty Threshold`): 리스트 렌더 체감 400ms 내, 이미지 지연 로딩.

## 윤리/신뢰 기준
- 다크패턴 금지: 뉴스레터 강제 모달, 과장된 FOMO 문구, 숨김 해지 동선 금지.
- 데이터 최소수집: 목록 화면에서 불필요한 개인정보 수집 UI 추가 금지.

## 구현 핸드오프 (엔지니어용)
- 대상 파일
- `src/layouts/BaseLayout.astro`: 토큰 변수 + 헤더/푸터 슬롯 구조 추가.
- `src/pages/index.astro`: featured + list 섹션 분리.
- `src/components/PostCard.astro` (신규)
- `src/components/FeaturedPost.astro` (신규)
- `src/components/EmptyState.astro` (신규)
- 수용 기준
1. 데스크톱(1440x900)/모바일(390x844)에서 제목-설명-메타 위계가 즉시 식별된다.
2. 게시글 0건일 때 빈 상태 컴포넌트가 표시된다.
3. 키보드만으로 모든 글 카드 탐색/진입이 가능하다.
4. 색상 대비와 포커스 상태가 WCAG POUR 원칙을 충족한다.
5. 토큰 외 임의 px/hex 값이 추가되지 않는다.

## 잔여 리스크
- 글 상세 템플릿(`src/pages/posts/[slug].astro` 예정)과 타이포 스케일 합의가 아직 없음.
- 태그 수가 많아질 때 카드 높이 균일성 전략(줄수 제한/접기) 추가 결정 필요.
