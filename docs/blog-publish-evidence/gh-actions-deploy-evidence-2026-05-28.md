# GH Actions 배포 증빙 (2026-05-28)

## 증빙 대상
- Repository: `yousung/blog`
- Workflow A: `blog-publish` (`.github/workflows/blog-publish.yml`)
- Workflow B: `Deploy Astro to GitHub Pages` (`.github/workflows/deploy.yml`)
- Trigger: `push` to `master`

## Run 증빙 경로
- `blog-publish`
  - Run URL: https://github.com/yousung/blog/actions/runs/26551591552
  - Run ID: `26551591552`
  - Commit SHA: `ce7ed73084d56f6d4dfb833603ec8748d24a4669`
  - Event: `push`
  - Head branch: `master`
- `deploy.yml`
  - Run URL: https://github.com/yousung/blog/actions/runs/26551591547
  - Run ID: `26551591547`
  - Commit SHA: `ce7ed73084d56f6d4dfb833603ec8748d24a4669`
  - Event: `push`
  - Head branch: `master`

## 판정 결과
- `blog-publish`: `success`
  - `validate`: `success`
  - `build`: `success`
  - `deploy`: `success`
- `deploy.yml`: `success`
  - `build`: `success`
  - `deploy`: `success`

이로써 master push 트리거 기준 배포/미리보기 경로의 성공 증빙(URL + Run ID + SHA)을 확보했다.

## 재현/검증 명령
```bash
curl -sS 'https://api.github.com/repos/yousung/blog/actions/workflows' | jq '.workflows[] | {name,path,id}'

curl -sS 'https://api.github.com/repos/yousung/blog/actions/workflows/blog-publish.yml/runs?per_page=5' \
  | jq '.workflow_runs[] | {id,html_url,status,conclusion,head_branch,head_sha,event,created_at}'

curl -sS 'https://api.github.com/repos/yousung/blog/actions/workflows/deploy.yml/runs?per_page=5' \
  | jq '.workflow_runs[] | {id,html_url,status,conclusion,head_branch,head_sha,event,created_at}'

curl -sS 'https://api.github.com/repos/yousung/blog/actions/runs/26551591552/jobs' \
  | jq '.jobs[] | {name,status,conclusion}'

curl -sS 'https://api.github.com/repos/yousung/blog/actions/runs/26551591547/jobs' \
  | jq '.jobs[] | {name,status,conclusion,html_url}'
```

## 보완 메모
- 원격 workflow 목록에 `deploy.yml`이 `active` 상태로 노출되는 것을 확인했다.
- 이전 `main` 기반 실패 run(`26551180317`)은 과거 이력이며, 본 문서의 수용 근거는 `master` 기준 최신 run이다.
