---
title: "Docker 이미지 크기 줄이기, 순서대로 점검하는 체크리스트"
slug: "docker-image-size-checklist"
author: "감성개발자"
date: "2026-05-11"
summary: "Docker 이미지가 수 GB로 불어났을 때, 효과가 큰 순서대로 손대는 법. 멀티 스테이지 빌드, 베이스 이미지 선택, .dockerignore, 레이어 캐시를 살리는 명령 순서까지 실무 체크리스트로 짚는다."
oneLineSummary: "이미지 크기는 멀티 스테이지 빌드와 베이스 이미지 선택에서 대부분 결정된다. 나머지는 레이어 정리와 .dockerignore다."
tags: [Docker, DevOps, CI/CD, 체크리스트]
status: "published"
---

처음에는 아무도 이미지 크기를 신경 쓰지 않습니다. 그러다 배포가 느려지고, 레지스트리 용량 경고가 오고, 오토스케일링으로 새 인스턴스가 뜰 때마다 수 GB를 당겨오는 걸 보고 나서야 Dockerfile을 다시 열게 됩니다. 몇 번 이 순서를 반복하고 내린 기준은 단순합니다. **이미지 크기의 대부분은 "빌드 도구가 최종 이미지에 남아 있는가"와 "베이스 이미지가 무엇인가" 두 가지에서 결정됩니다.** 멀티 스테이지 빌드와 베이스 이미지 교체가 효과의 8할이고, 나머지는 레이어 정리입니다.

그래서 아래 항목은 효과가 큰 순서로 놓았습니다. 위에서부터 손대면 대개 1, 2순위에서 문제가 끝납니다. 근거는 Docker 공식 문서의 멀티 스테이지 빌드 문서와 Dockerfile 모범 사례 문서에 있습니다.

## 1순위: 멀티 스테이지 빌드

빌드에 필요한 도구와 실행에 필요한 파일은 다릅니다. Node.js 앱이라면 TypeScript 컴파일러와 devDependencies는 빌드에만 필요하고, 실행에는 산출물과 프로덕션 의존성만 있으면 됩니다. 멀티 스테이지 빌드는 이 둘을 분리합니다.

```dockerfile
# 빌드 스테이지
FROM node:22 AS build
WORKDIR /app
COPY package*.json ./
RUN npm ci
COPY . .
RUN npm run build

# 실행 스테이지
FROM node:22-slim
WORKDIR /app
ENV NODE_ENV=production
COPY package*.json ./
RUN npm ci --omit=dev
COPY --from=build /app/dist ./dist
CMD ["node", "dist/main.js"]
```

Docker 공식 문서는 멀티 스테이지 빌드의 목적을 정확히 이렇게 설명합니다. 각 `FROM`이 새로운 스테이지를 시작하고, `COPY --from`으로 필요한 산출물만 다음 스테이지로 가져와, 최종 이미지에 빌드 도구를 남기지 않는 것입니다. (출처: [Docker Docs - Multi-stage builds](https://docs.docker.com/build/building/multi-stage/))

Go나 Rust처럼 단일 바이너리로 컴파일되는 언어는 효과가 극적입니다. 빌드 스테이지는 1GB가 넘어도, 최종 스테이지는 바이너리 하나만 담으면 수십 MB로 끝납니다.

## 2순위: 베이스 이미지 선택

같은 Node.js 22라도 태그에 따라 크기가 크게 다릅니다. 기본 이미지는 빌드 도구와 라이브러리를 폭넓게 포함하고, `slim`은 실행에 필요한 최소 구성, `alpine`은 musl 기반의 초경량 배포판입니다.

| 선택지 | 특징 | 주의점 |
| --- | --- | --- |
| 기본 (`node:22`) | 호환성 최고, 크기 최대 | 빌드 스테이지용으로는 적합 |
| `slim` | 실행에 필요한 최소 데비안 | 네이티브 모듈 빌드 도구 없음 |
| `alpine` | 가장 작음 | musl libc라 glibc 의존 바이너리와 비호환 가능 |

내 기준은 이렇습니다. 빌드 스테이지는 기본 이미지로 편하게 가고, 실행 스테이지만 `slim`이나 `alpine`으로 좁힙니다. `alpine`은 크기가 가장 작지만 glibc가 아닌 musl을 쓰기 때문에, `sharp` 같은 네이티브 의존성이나 사전 컴파일된 바이너리에서 호환 문제가 날 수 있습니다. 나도 몇 MB 더 줄여보겠다고 `alpine`으로 갔다가, 원인 모를 빌드 실패에 반나절을 태운 적이 있습니다. 아낀 용량보다 추적에 든 시간이 훨씬 비쌌습니다. 확신이 없으면 `slim`이 안전한 기본값입니다.

## 3순위: .dockerignore

`COPY . .`는 빌드 컨텍스트 전체를 복사합니다. `.dockerignore`가 없으면 `.git` 디렉터리, 로컬 `node_modules`, 로그 파일, 환경 파일까지 전부 이미지로 들어가거나 최소한 빌드 컨텍스트 전송량을 키웁니다. Docker 공식 모범 사례 문서도 빌드와 무관한 파일을 `.dockerignore`로 제외해 빌드 컨텍스트를 작게 유지하라고 안내합니다. (출처: [Docker Docs - Building best practices](https://docs.docker.com/build/building/best-practices/))

```
.git
node_modules
dist
*.log
.env*
Dockerfile
docker-compose*.yml
```

`.env`류를 제외하는 것은 크기보다 보안 문제입니다. 시크릿이 이미지 레이어에 박제되면 레지스트리에 접근 가능한 모두에게 노출됩니다.

## 4순위: 레이어를 이해하고 명령 순서 잡기

Docker 이미지는 명령마다 레이어가 쌓이는 구조입니다. 여기서 두 가지 실무 규칙이 나옵니다.

**지우려면 만든 레이어에서 지워야 합니다.** 아래처럼 나눠 쓰면 캐시를 지워도 이전 레이어에 파일이 그대로 남아 있어 이미지 크기는 줄지 않습니다.

```dockerfile
# 잘못된 예: 캐시가 이전 레이어에 남음
RUN apt-get update && apt-get install -y curl
RUN rm -rf /var/lib/apt/lists/*

# 올바른 예: 한 레이어에서 설치와 정리를 끝냄
RUN apt-get update \
 && apt-get install -y --no-install-recommends curl \
 && rm -rf /var/lib/apt/lists/*
```

**자주 바뀌는 것을 뒤로 보냅니다.** 레이어 캐시는 위에서부터 순서대로 유효합니다. 의존성 정의 파일을 먼저 복사해 설치하고, 소스 코드는 그 다음에 복사하면, 코드만 바뀐 빌드에서 의존성 설치 레이어가 캐시로 재사용됩니다. 이미지 크기가 아니라 빌드 시간을 줄이는 규칙이지만, CI 비용에는 크기만큼 직접적인 영향을 줍니다.

## 점검 도구

- `docker image ls`로 전체 크기를, `docker history <image>`로 레이어별 크기를 확인합니다. 어느 명령이 크기를 만드는지 바로 보입니다.
- [dive](https://github.com/wagoodman/dive) 같은 도구를 쓰면 레이어별로 어떤 파일이 추가됐는지 탐색할 수 있어, 예상 밖의 대형 파일을 찾기 좋습니다.

## 자주 묻는 질문

**Q. distroless 이미지는 어떤가요?**
셸조차 없는 최소 실행 이미지로, 크기와 공격 표면을 더 줄일 수 있습니다. 다만 컨테이너에 들어가 디버깅하는 방식이 통하지 않으므로, 로그와 관측 체계가 갖춰진 뒤에 도입하는 것을 권합니다.

**Q. npm ci와 npm install 중 무엇을 써야 하나요?**
CI와 이미지 빌드에서는 `npm ci`입니다. 락파일 그대로 설치해 재현성이 보장되고, 기존 `node_modules`를 지우고 시작하므로 캐시 오염이 없습니다. 실행 스테이지에서는 `--omit=dev`로 프로덕션 의존성만 설치합니다.

**Q. 이미지 하나 줄이는 것보다 태그 정리가 급한 것 같은데요?**
레지스트리 용량 문제라면 맞습니다. 오래된 태그 정리 정책(retention)은 이미지 크기 최적화와 별개로 필요합니다. 둘은 대체 관계가 아니라 병행 항목입니다.

## 점검 순서

이미지가 부담스러워졌을 때 나는 이 순서로 훑습니다. 위쪽에서 대부분 끝나기 때문에, 아래로 갈수록 급하지 않습니다.

1. 최종 이미지에 빌드 도구가 남아 있는지 본다. 남아 있으면 멀티 스테이지로 분리한다. 여기서 대개 승부가 난다.
2. 실행 스테이지 베이스를 `slim`으로 잡는다. `alpine`은 호환성 확인이 끝난 뒤에만 건드린다.
3. `.dockerignore`가 있는지 확인한다. 없으면 크기가 아니라 보안 문제이기도 하니 지금 만든다.
4. `RUN`에서 설치와 정리가 같은 레이어인지, 자주 바뀌는 파일 복사가 Dockerfile 뒤쪽에 있는지 확인한다.

## 출처

- [Docker Docs - Multi-stage builds](https://docs.docker.com/build/building/multi-stage/)
- [Docker Docs - Building best practices](https://docs.docker.com/build/building/best-practices/)
