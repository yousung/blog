# BLOA Blog

Astro 기반의 정적 블로그. 포스트 관리, 페이지네이션, SEO 최적화를 지원합니다.

## 개발 환경 설정

### 필수 요구사항
- Node.js 18+
- npm

### 설치

```bash
npm install
```

## 사용 가능한 명령어

```bash
# 개발 서버 실행 (http://localhost:3000)
npm run dev

# 프로덕션 빌드
npm run build

# 사이트맵 생성
npm run build:sitemap

# 빌드 결과 미리보기
npm run preview

# 타입 체크
npm run check

# Frontmatter 검증
npm run validate:frontmatter

# 런치 신호 검증
npm run validate:launch-signals

# 사이트맵 검증
npm run validate:sitemap
```

## 프로젝트 구조

```
src/
├── pages/              # 라우팅 페이지 (Astro file-based routing)
│   ├── index.astro    # 홈페이지
│   └── posts/         # 포스트 페이지
├── layouts/           # 레이아웃 컴포넌트
├── components/        # 재사용 가능한 컴포넌트
└── styles/           # 스타일시트

public/               # 정적 자산
dist/                 # 빌드 출력 (배포용)
docs/                 # 문서
scripts/              # 빌드 및 검증 스크립트
```

## 포스트 작성

`docs/` 디렉토리에 Markdown 파일로 포스트를 작성합니다.

포스트 Frontmatter 형식:
```yaml
---
title: "포스트 제목"
description: "포스트 설명"
pubDate: "2026-04-23"
---
```

## 배포

빌드된 파일은 `dist/` 디렉토리에 생성되며, 정적 호스팅 서비스(Vercel, Netlify, AWS S3 등)에 배포할 수 있습니다.

```bash
npm run build
# dist/ 폴더를 호스팅 서비스에 업로드
```

## 기술 스택

- **Astro** - 정적 사이트 생성기
- **TypeScript** - 타입 안전성
- **Sharp** - 이미지 처리

## License

MIT
