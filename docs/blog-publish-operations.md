# blog-publish 운영 가이드

## 워크플로우 개요
- 파일: `.github/workflows/blog-publish.yml`
- 트리거:
  - PR: 포스트/스크립트 변경 검증
  - Push(main): 검증 + 빌드 + GitHub Pages 배포

## 실패 로그 확인 포인트
1. `validate` job
- 메시지 예시: `Frontmatter schema violation`
- 조치: 해당 파일의 누락 필드/형식 수정

2. `build` job
- 메시지 예시: `Built site with ...` 미출력/스크립트 에러
- 조치: `scripts/build-site.mjs` 로그 확인 및 수정

3. `deploy` job
- 메시지 예시: Pages 권한/환경 관련 실패
- 조치: 저장소 Settings > Pages 가 GitHub Actions 소스로 설정됐는지 확인

## 로컬 사전 검증
```bash
node scripts/validate-frontmatter.mjs content/posts
node scripts/build-site.mjs
```
