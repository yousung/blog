# GH Actions 배포 증빙 경로

목적: push 트리거 배포의 성공/실패를 재현 가능한 방식으로 판정한다.

## 1) 자동 수집 (권장)

```bash
scripts/collect-actions-evidence.sh yousung/blog blog-publish
```

출력 항목:
- `run_id`
- `url`
- `head_sha`
- `status` / `conclusion`
- `failed_jobs` (실패 시)

## 2) 수동 수집 (GitHub API)

최근 run 목록:

```bash
curl -sS 'https://api.github.com/repos/yousung/blog/actions/runs?per_page=20' | jq '.workflow_runs[] | {id, name, event, status, conclusion, head_sha, html_url, created_at}'
```

특정 run의 job 판정:

```bash
curl -sS 'https://api.github.com/repos/yousung/blog/actions/runs/<RUN_ID>/jobs' | jq '.jobs[] | {name, status, conclusion, html_url}'
```

## 3) 인증 제약 시 대체 증빙

- GitHub UI에서 해당 run 페이지 URL과 전체 job 결과(초록/빨강)를 스크린샷으로 남긴다.
- 최소 포함 정보: run URL, run ID, commit SHA, deploy job conclusion.
- 저장 위치: 이슈 코멘트에 링크 첨부 (또는 사내 공유 문서 링크).
