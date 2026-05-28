# blog-publish Skill

AI 에이전트가 작성한 블로그 초안을 배포 가능한 포스트로 검증/정규화하는 운영 스킬.

## 목적
- 포스트 메타데이터(프런트매터) 일관성 확보
- 스키마 위반 시 배포 차단
- main 브랜치 push 기준 자동 발행

## 입력 형식
마크다운 파일(`content/posts/*.md`)의 YAML 프런트매터에 아래 필드를 포함해야 한다.

- `title` (string)
- `slug` (kebab-case string)
- `author` (string)
- `date` (`YYYY-MM-DD`)
- `summary` (string)
- `tags` (inline array)
- `status` (`draft` or `published`)

## 실행 규칙
1. 포스트 작성/수정 후 `node scripts/validate-frontmatter.mjs content/posts` 실행
2. 실패 시 에러 메시지의 파일/필드 기준으로 수정
3. 검증 통과 후 main 브랜치에 머지/푸시
4. GitHub Actions `blog-publish` 워크플로우가 Pages 배포 수행

## 실패 처리
- 스키마 실패: `validate` job 실패로 배포 중단
- 빌드 실패: `build` job 로그에서 정적 사이트 생성 오류 확인
- 배포 실패: `deploy` job에서 권한(`pages:write`, `id-token:write`) 및 Pages 설정 확인
