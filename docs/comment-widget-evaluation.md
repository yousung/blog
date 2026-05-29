# 댓글 위젯 후보 비교 및 선정

## 후보

| 후보 | 성능 영향 | 인증/권한 | 운영 복잡도 | 비용 | 비고 |
|---|---|---|---|---|---|
| Giscus | iframe 1개 + 지연 로드로 초기 렌더 영향 낮음 | GitHub OAuth 기반, 저장소 Discussions 권한 모델 재사용 | 중간 (repo/category/id 세팅 필요) | 무료 | 오픈소스 블로그 친화적 |
| Utterances | iframe 1개, 경량 | GitHub OAuth 기반, 이슈 기반 저장 | 낮음 | 무료 | 스레드 구조가 Discussions보다 단순 |
| Disqus | 외부 스크립트/트래커 부담 큼 | 자체 계정 체계 | 낮음(설치), 중간(광고/정책 관리) | 무료 플랜 제약/유료 전환 | 성능/프라이버시 리스크 |

## 선정안

- 선정: **Giscus**
- 근거:
1. **성능**: 정적 페이지 본문과 분리된 iframe 로드라 Core Web Vitals 훼손 위험이 낮다.
2. **운영성**: GitHub Discussions를 사용해 별도 DB/백엔드 없이 운영 가능하다.
3. **품질**: 스레드/카테고리 구조로 포스트별 대화 맥락 관리가 쉽다.
4. **확장성**: 다국어/테마/매핑 옵션을 환경변수 기반으로 관리 가능하다.

## 통합 명세

- 컴포넌트: `src/components/Comments.astro`
- 포스트 템플릿: `src/pages/posts/[...slug].astro` 내 article/comments 섹션
- 포스트 라우트: `src/pages/posts/[...slug].astro`
- 환경변수:
  - `PUBLIC_GISCUS_REPO`
  - `PUBLIC_GISCUS_REPO_ID`
  - `PUBLIC_GISCUS_CATEGORY`
  - `PUBLIC_GISCUS_CATEGORY_ID`

## UX/접근성 점검

- `aria-label="댓글 영역"`으로 랜드마크 제공.
- 미설정 상태에 안내 메시지(폴백) 제공.
- 모바일(<=640px)에서 패딩/폭 최적화.
- 댓글 작성 UI는 Giscus의 접근성 정책을 따르며, 본문과 시각적으로 분리된 카드 컨테이너 사용.

## 성능 가드레일

- 외부 위젯은 포스트 본문 이후 로드.
- 메인 콘텐츠는 정적 렌더 유지.
- 추후 Lighthouse 점검 시 댓글 스크립트 미적용/적용 두 케이스 모두 기록 권장.
