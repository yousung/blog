---
title: "GitHub Actions 캐시, 언제 넣어야 빨라질까? CI 속도 개선 체크리스트"
slug: "github-actions-cache-checklist"
ogImage: "/images/posts/github-actions-cache-checklist/hero.png"
author: "BLOA Team"
date: "2026-06-30"
summary: "GitHub Actions 워크플로가 느릴 때 무작정 캐시를 추가하면 오히려 원인 파악이 어려워질 수 있다. 의존성 설치, 빌드 산출물, 캐시 키, restore-keys, 비용까지 실무 기준으로 정리했다."
oneLineSummary: "GitHub Actions 캐시는 느린 CI의 만능 처방이 아니라 반복 다운로드와 빌드 비용이 큰 구간에만 효과가 있다."
tags: [GitHub, "CI/CD", DevOps, 개발팁]
status: "published"
---

![GitHub Actions 캐시 설계를 표현한 기술 일러스트](/images/posts/github-actions-cache-checklist/hero.png)

CI가 느려지면 가장 먼저 떠오르는 처방이 캐시입니다. `node_modules`를 저장하면 빨라질 것 같고, 빌드 폴더를 통째로 올리면 다음 실행이 금방 끝날 것 같습니다. 그런데 캐시는 잘못 넣으면 속도보다 혼란을 먼저 만듭니다. 오래된 의존성이 살아나거나, 캐시 복구 시간 때문에 오히려 느려지거나, 키가 자주 바뀌어 매번 새 캐시만 쌓이는 식입니다.

결론부터 말하면, **GitHub Actions 캐시는 "매번 같은 비용을 반복해서 내는 구간"에만 넣는 편이 좋습니다.** 의존성 다운로드처럼 입력이 명확하고 재사용 가능성이 높은 작업에는 잘 맞습니다. 반대로 테스트 결과, 임시 빌드 산출물, 환경마다 달라지는 파일을 무리하게 저장하면 디버깅 비용이 더 커질 수 있습니다.

> 요약 박스
>
> - 캐시는 의존성 설치, 컴파일 중간 산출물처럼 입력이 같으면 결과도 거의 같은 구간에 먼저 적용합니다.
> - 캐시 키에는 OS, 런타임 버전, lock 파일 해시처럼 결과를 바꾸는 값을 넣어야 합니다.
> - `restore-keys`로 부분 복구를 허용할 수 있지만, 정확한 키가 맞지 않으면 `cache-hit`은 `true`가 아닐 수 있습니다.
> - GitHub-hosted runner에서는 Actions 실행 시간과 캐시·아티팩트 저장 사용량이 비용에 영향을 줄 수 있으므로, 빠른 것과 싼 것을 따로 확인해야 합니다.

최종 업데이트: 2026-06-30

이 글은 GitHub Actions 공식 문서, `actions/cache` 문서, npm/pnpm 공식 문서를 기준으로 정리한 실무 체크리스트입니다. 저장소 구조, 패키지 매니저, runner 종류에 따라 맞는 캐시 범위가 달라질 수 있으므로, 적용 뒤에는 실제 실행 시간을 비교해야 합니다.

## 캐시를 넣기 전에 먼저 봐야 할 숫자

워크플로가 느릴 때 바로 YAML을 고치면 원인을 놓치기 쉽습니다. 먼저 최근 실행 로그에서 어느 단계가 시간을 쓰는지 봐야 합니다. `checkout`이 느린지, 패키지 설치가 느린지, 테스트가 느린지, Docker build가 느린지에 따라 처방이 완전히 다릅니다.

캐시가 효과를 내는 구간은 보통 세 가지입니다.

| 느린 구간 | 캐시 후보 | 먼저 확인할 것 |
| --- | --- | --- |
| 패키지 다운로드 | npm, yarn, pnpm, Composer, Gradle 캐시 | lock 파일이 안정적인가 |
| 컴파일 중간 산출물 | 빌드 도구의 cache 디렉터리 | OS·런타임 버전이 바뀌면 깨지지 않는가 |
| 큰 도구 설치 | 언어 런타임, CLI 다운로드 캐시 | 공식 setup action이 이미 캐시를 지원하는가 |

반대로 테스트 자체가 오래 걸린다면 캐시보다 테스트 분리, 병렬화, flaky test 제거가 먼저입니다. 캐시는 "같은 것을 다시 내려받는 시간"을 줄이는 도구이지, 느린 테스트 로직을 빠르게 만드는 도구가 아닙니다.

## 가장 먼저 캐시할 곳은 의존성 다운로드입니다

Node.js 프로젝트라면 `actions/setup-node`의 내장 캐시부터 보는 편이 단순합니다. GitHub의 `setup-node` 문서는 npm, yarn, pnpm에 대해 캐시 옵션을 제공하고, 내부적으로 `actions/cache`를 사용한다고 설명합니다. 직접 `actions/cache`를 조합하기 전에 기본 기능으로 해결되는지 확인하는 게 좋습니다. (출처: [actions/setup-node](https://github.com/actions/setup-node), [GitHub Docs - Building and testing Node.js](https://docs.github.com/actions/guides/building-and-testing-nodejs))

예를 들어 npm 프로젝트라면 아래처럼 시작할 수 있습니다.

```yaml
steps:
  - uses: actions/checkout@v4

  - uses: actions/setup-node@v4
    with:
      node-version: 20
      cache: "npm"

  - run: npm ci
  - run: npm test
```

여기서 중요한 건 `npm install`보다 `npm ci`가 CI에 더 잘 맞는 경우가 많다는 점입니다. npm 공식 문서에 따르면 `npm ci`는 lock 파일이 필요하고, `package.json`과 lock 파일이 맞지 않으면 실패합니다. 기존 `node_modules`도 지운 뒤 깨끗하게 설치합니다. CI에서는 이 엄격함이 오히려 장점입니다. 로컬에서 우연히 맞던 의존성 상태를 배포 파이프라인으로 가져오지 않기 때문입니다. (출처: [npm Docs - npm ci](https://docs.npmjs.com/cli/v9/commands/npm-ci/))

pnpm을 쓴다면 조금 더 조심해야 합니다. pnpm 공식 CI 문서는 store 캐시 예시를 제공하면서도, store 캐시가 항상 설치를 빠르게 만든다고 보장되지는 않는다고 말합니다. 즉 pnpm은 "캐시를 켜면 무조건 개선"이 아니라, 실제 로그에서 다운로드와 설치 시간이 얼마나 줄었는지 봐야 합니다. (출처: [pnpm - Continuous Integration](https://pnpm.io/continuous-integration))

## 캐시 키는 "결과를 바꾸는 값"으로 만들어야 합니다

GitHub Actions 캐시에서 가장 많이 틀리는 부분은 키입니다. 키가 너무 넓으면 오래된 파일이 살아나고, 너무 좁으면 매번 새 캐시가 만들어져 재사용 효과가 사라집니다.

GitHub 공식 문서는 캐시 키에 Actions context, 함수, 문자열을 조합할 수 있고, `hashFiles()`로 lock 파일이 바뀔 때 새 캐시를 만들 수 있다고 설명합니다. 이 원리를 실무 기준으로 풀면 아래와 같습니다. (출처: [GitHub Docs - Dependency caching reference](https://docs.github.com/en/actions/reference/workflows-and-actions/dependency-caching))

```yaml
- name: Cache npm cache directory
  uses: actions/cache@v4
  with:
    path: ~/.npm
    key: ${{ runner.os }}-node-20-npm-${{ hashFiles('package-lock.json') }}
    restore-keys: |
      ${{ runner.os }}-node-20-npm-
```

이 예시에서 `runner.os`를 넣는 이유는 Linux, macOS, Windows에서 캐시를 섞지 않기 위해서입니다. `node-20`을 넣는 이유는 런타임 버전 차이로 설치 결과가 달라질 수 있기 때문입니다. `hashFiles('package-lock.json')`는 의존성 목록이 바뀌었을 때 새 캐시를 만들기 위한 장치입니다.

키를 만들 때는 스스로 이렇게 물어보면 됩니다.

- OS가 바뀌면 이 캐시를 재사용해도 되는가?
- Node, PHP, Java 같은 런타임 버전이 바뀌어도 되는가?
- lock 파일이 바뀌었는데 이전 캐시를 그대로 써도 되는가?
- monorepo라면 패키지별 lock 파일과 경로를 구분했는가?

이 질문 중 하나라도 "아니다"라면 키에 그 값을 넣는 편이 안전합니다.

## `restore-keys`는 편하지만, 오래된 캐시를 불러올 수도 있습니다

`restore-keys`는 정확한 키가 없을 때 더 넓은 prefix로 캐시를 찾아주는 기능입니다. 예를 들어 `package-lock.json`이 바뀌어 정확한 키가 없더라도, 같은 OS와 Node 버전의 이전 npm 캐시를 가져올 수 있습니다. 의존성 다운로드를 줄이는 데는 꽤 유용합니다.

다만 이 기능은 "정확히 같은 결과를 보장한다"는 뜻이 아닙니다. `actions/cache` 문서는 `cache-hit` 출력이 primary key로 정확히 복구됐을 때 `true`가 되고, `restore-keys`나 캐시 미스에서는 `false`가 된다고 설명합니다. 로그에 캐시가 복구됐다고 나오더라도, 정확한 키가 맞은 건 아닐 수 있습니다. (출처: [actions/cache README](https://github.com/actions/cache))

그래서 `restore-keys`를 쓸 때는 아래처럼 구분하는 편이 좋습니다.

- 패키지 매니저 다운로드 캐시: 부분 복구를 허용해도 비교적 안전한 편
- `node_modules`, `vendor`, build output: 부분 복구를 허용하면 깨질 가능성이 커짐
- 테스트 결과, coverage, 임시 파일: 캐시보다 artifacts나 별도 리포트로 관리하는 편이 나음

캐시가 복구됐는데 테스트가 이상하게 실패한다면, 가장 먼저 `restore-keys`가 너무 넓지 않은지 확인하세요. "어제까지 되던 캐시"가 아니라 "오늘의 입력과 맞는 캐시"인지가 핵심입니다.

## `node_modules`를 통째로 캐시하기 전에 한 번 멈춰야 합니다

CI 속도를 줄이고 싶을 때 `node_modules` 전체를 캐시하고 싶은 유혹이 큽니다. 운이 좋으면 빠릅니다. 문제는 실패했을 때 원인을 찾기 어려워진다는 점입니다.

Node 프로젝트에서는 OS, Node 버전, 패키지 매니저 버전, native module 빌드 결과가 얽힐 수 있습니다. 그래서 일반적인 첫 선택은 `node_modules` 전체보다 패키지 매니저의 다운로드 캐시입니다. npm이면 `~/.npm`, pnpm이면 store, yarn이면 해당 캐시 경로를 먼저 봅니다. `setup-node`의 `cache` 옵션도 이 방향에 가깝습니다. (출처: [actions/setup-node](https://github.com/actions/setup-node))

`node_modules` 전체 캐시가 맞는 경우도 있습니다. 설치 시간이 지나치게 길고, OS·런타임·lock 파일이 안정적이며, 실패 시 캐시를 쉽게 지울 수 있는 운영 절차가 있다면 검토할 수 있습니다. 하지만 작은 팀이나 개인 프로젝트라면 먼저 단순한 캐시부터 적용하고, 실행 시간 비교표를 남기는 쪽이 낫습니다.

## 캐시는 비용도 같이 봐야 합니다

캐시를 넣으면 실행 시간이 줄어 비용도 줄 것 같지만, 항상 그렇지는 않습니다. GitHub Actions 과금 문서는 GitHub-hosted runner의 분 단위 사용량과 artifacts·caches 저장 사용량이 청구에 영향을 줄 수 있다고 설명합니다. 즉 실행 시간이 조금 줄어도 큰 캐시를 자주 만들고 오래 보관하면 저장 비용과 관리 부담이 생길 수 있습니다. (출처: [GitHub Docs - About billing for GitHub Actions](https://docs.github.com/billing/managing-billing-for-github-actions/about-billing-for-github-actions), [GitHub Actions - Billing and usage](https://docs.github.com/en/actions/concepts/billing-and-usage))

또 하나는 한도입니다. GitHub Actions limits 문서에는 저장소별 캐시 다운로드 요청 한도가 명시되어 있습니다. 일반적인 소규모 저장소에서는 잘 체감하지 않지만, matrix job이 많고 monorepo가 큰 팀은 캐시 전략이 과해질 수 있습니다. (출처: [GitHub Docs - Actions limits](https://docs.github.com/en/actions/reference/limits))

실무에서는 아래 세 값을 같이 비교하면 됩니다.

- 캐시 적용 전후 전체 workflow 시간
- 실제로 줄어든 단계 시간
- 새로 만들어지는 캐시 크기와 개수

전체 시간이 8분에서 7분 40초로 줄었는데 캐시가 매번 새로 생긴다면 좋은 개선이라고 보기 어렵습니다. 반대로 의존성 설치가 3분에서 40초로 줄고 캐시 키가 안정적으로 재사용된다면 유지할 가치가 있습니다.

## 적용 순서는 이렇게 잡으면 실패가 적습니다

처음부터 모든 job에 캐시를 넣지 마세요. 한 워크플로, 한 병목 단계부터 시작하는 편이 낫습니다.

1. 최근 5~10개 실행 로그에서 가장 느린 단계를 확인합니다.
2. 그 단계가 반복 다운로드인지, 반복 빌드인지, 테스트 실행 자체인지 나눕니다.
3. 의존성 다운로드라면 공식 setup action의 내장 캐시를 먼저 씁니다.
4. 직접 `actions/cache`를 쓴다면 OS, 런타임 버전, lock 파일 해시를 키에 넣습니다.
5. `restore-keys`는 다운로드 캐시처럼 부분 복구가 안전한 곳에만 좁게 둡니다.
6. 적용 후 최소 몇 번은 cold cache와 warm cache 실행 시간을 따로 기록합니다.
7. 실패가 이상하게 재현되면 캐시를 끄고 같은 커밋에서 다시 실행해 비교합니다.

캐시 개선은 "YAML을 예쁘게 만드는 작업"이 아닙니다. 매번 낭비되는 시간을 줄이되, 실패했을 때 원인을 추적할 수 있어야 합니다.

## 작은 프로젝트라면 캐시보다 단순함이 이길 때도 있습니다

개인 블로그, 작은 API, 테스트가 몇 초 안에 끝나는 프로젝트라면 캐시가 체감되지 않을 수 있습니다. 오히려 캐시 복구와 저장 로그가 늘어나면서 workflow만 복잡해집니다. 이 경우에는 `npm ci`, lock 파일 관리, 불필요한 job 제거, `paths` 필터 같은 기본 정리가 먼저입니다.

반대로 monorepo, 프론트엔드 빌드, E2E 테스트, Docker image build가 섞인 저장소라면 캐시를 제대로 설계할 가치가 큽니다. 다만 이때도 "전체를 저장하자"가 아니라 "반복 비용이 큰 입력을 좁게 저장하자"가 출발점입니다.

## FAQ

### 캐시를 지우면 다음 실행이 깨질 수 있나요?

깨지면 안 됩니다. 캐시는 속도를 위한 보조 수단이어야 합니다. 캐시가 없어서 빌드가 실패한다면 workflow가 캐시에 너무 의존하고 있다는 뜻입니다.

### `cache-hit`이 `false`인데 로그에는 캐시가 복구됐다고 나옵니다. 이상한 건가요?

꼭 이상한 것은 아닙니다. `actions/cache` 기준으로 primary key가 정확히 맞아야 `cache-hit`이 `true`입니다. `restore-keys`로 부분 복구된 경우에는 캐시가 복구됐더라도 `cache-hit`은 `false`일 수 있습니다. (출처: [actions/cache README](https://github.com/actions/cache))

### lock 파일이 없으면 캐시 키를 어떻게 만들면 되나요?

먼저 lock 파일을 쓰는 쪽을 검토하는 게 좋습니다. CI에서 의존성 재현성을 확보하려면 lock 파일이 기준점이 됩니다. npm의 `npm ci`도 `package-lock.json` 또는 `npm-shrinkwrap.json`이 있어야 동작합니다. (출처: [npm Docs - npm ci](https://docs.npmjs.com/cli/v9/commands/npm-ci/))

### Docker build도 GitHub Actions cache로 해결하면 되나요?

일부 파일 캐시는 가능하지만, Docker build cache는 별도 전략이 필요한 경우가 많습니다. 이 글의 범위는 Actions dependency cache입니다. Docker image build가 병목이라면 BuildKit cache, registry cache, layer 구조를 따로 점검하는 편이 맞습니다.

## 마무리: 캐시는 마지막 튜닝이 아니라 작은 실험입니다

GitHub Actions 캐시는 잘 쓰면 매일 반복되는 CI 대기 시간을 줄여 줍니다. 하지만 캐시 키, 복구 범위, 저장 비용을 보지 않고 넣으면 "빠른 CI"보다 "이유를 알 수 없는 CI"가 먼저 옵니다.

처음 적용한다면 하나만 고르세요. 가장 느린 의존성 설치 단계에 공식 setup action의 내장 캐시를 켜고, 적용 전후 시간을 비교합니다. 그 결과가 분명할 때만 직접 `actions/cache`, `restore-keys`, 빌드 산출물 캐시로 넓히는 편이 실패가 적습니다.

## 출처

- [GitHub Docs - Dependency caching reference](https://docs.github.com/en/actions/reference/workflows-and-actions/dependency-caching)
- [actions/cache README](https://github.com/actions/cache)
- [actions/setup-node](https://github.com/actions/setup-node)
- [GitHub Docs - Building and testing Node.js](https://docs.github.com/actions/guides/building-and-testing-nodejs)
- [npm Docs - npm ci](https://docs.npmjs.com/cli/v9/commands/npm-ci/)
- [pnpm - Continuous Integration](https://pnpm.io/continuous-integration)
- [GitHub Docs - About billing for GitHub Actions](https://docs.github.com/billing/managing-billing-for-github-actions/about-billing-for-github-actions)
- [GitHub Actions - Billing and usage](https://docs.github.com/en/actions/concepts/billing-and-usage)
- [GitHub Docs - Actions limits](https://docs.github.com/en/actions/reference/limits)
