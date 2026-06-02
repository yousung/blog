# BLOA-276 운영/배포 검증

- 실행 시각: `2026-06-02T22:06:14+0900` (추가 운영 반영: `2026-06-02T22:08:00+0900`)
- 대상: 운영(`https://blog.lovizu.com`)
- 배포 워크플로우: https://github.com/yousung/blog/actions/runs/26821821486

## 1) 배포 상태

- 상태: `completed`
- 결론: `success`
- 브랜치: `master`
- 커밋: `10b2e98` (`fix: support /m/entry redirect path`)
- 빌드 job: https://github.com/yousung/blog/actions/runs/26821821486/job/79078212160
- 배포 job: https://github.com/yousung/blog/actions/runs/26821821486/job/79078301684

## 2) `404` 라우팅 스모크

1) `m-entry-korean-hyphen` (`/m/entry/맥북에서-사진의-날짜-및-장소-일괄-변경하기`)
- 최종 URL: `/search/%EB%A7%A5%EB%B6%81%EC%97%90%EC%84%9C%20%EC%82%AC%EC%A7%84%EC%9D%98%20%EB%82%A0%EC%A7%9C%20%EB%B0%8F%20%EC%9E%A5%EC%86%8C%20%EC%9D%BC%EA%B4%84%20%EB%B3%80%EA%B2%BD%ED%95%98%EA%B8%B0/`
- 기대값: 동일 (PASS)

2) `entry-root` (`/entry/`)
- 최종 URL: `/`
- 기대값: `/` (PASS)

3) `m-entry-root` (`/m/entry/`)
- 최종 URL: `/`
- 기대값: `/` (PASS)

4) `unknown-path` (`/unknown-path`)
- 최종 URL: `/`
- 기대값: `/` (PASS)

## 3) 산출물

- `docs/qa-evidence/bloa-276-2026-06-02T22-06-14+0900/route-smoke.json`
- `docs/qa-evidence/bloa-276-2026-06-02T22-06-14+0900/operations-verification.md`
