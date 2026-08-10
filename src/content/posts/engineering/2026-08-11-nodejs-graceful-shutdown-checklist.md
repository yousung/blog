---
title: "Node.js 컨테이너 종료가 느릴 때 먼저 볼 graceful shutdown 기준"
slug: "nodejs-graceful-shutdown-checklist"
author: "BLOA Team"
date: "2026-08-11"
summary: "Node.js 서비스를 컨테이너로 배포할 때 SIGTERM, HTTP 서버 종료, readiness, Docker/ECS/Kubernetes 헬스체크를 어떻게 나눠 봐야 하는지 실무 기준으로 정리했습니다."
oneLineSummary: "무중단 배포의 핵심은 새 컨테이너를 빨리 띄우는 것보다 기존 컨테이너가 요청을 끊지 않고 내려가는 기준을 정하는 데 있습니다."
tags: [Node.js, Docker, ECS, Backend, 체크리스트]
status: "published"
tistoryUrl: "https://blog.lovizu.com/entry/nodejs-graceful-shutdown-checklist"
---

# Node.js 컨테이너 종료가 느릴 때 먼저 볼 graceful shutdown 기준

배포 버튼을 눌렀을 뿐인데 몇 초 동안 502가 튑니다. 로그에는 에러가 많지 않고, 새 컨테이너도 정상으로 떠 있습니다. 그런데 사용자 입장에서는 방금 누른 저장 버튼이 실패했고, 운영자는 "배포 중 잠깐 그런 것 같다"는 애매한 말만 남깁니다.

이런 문제는 보통 배포 도구만 바꿔서는 사라지지 않습니다. **컨테이너가 내려갈 때 애플리케이션이 어떤 순서로 요청을 멈추고, 남은 일을 끝내고, 프로세스를 종료할지 정해져 있어야 합니다.** Node.js 서버라면 `SIGTERM`을 받고도 계속 새 요청을 받는지, `server.close()` 이후 오래 붙은 연결을 어떻게 다룰지, readiness가 먼저 내려가는지부터 확인해야 합니다.

나는 작은 팀의 Node.js 서비스를 볼 때 graceful shutdown을 아래 네 가지 질문으로 쪼갭니다.

- 종료 신호가 실제 Node.js 프로세스까지 도착하는가
- 새 요청을 먼저 막고, 처리 중인 요청은 끝낼 시간을 주는가
- readiness와 liveness가 서로 다른 실패를 보고 있는가
- 플랫폼의 강제 종료 시간 안에 정리 작업이 끝나는가

최종 업데이트: 2026-08-11  
작성 관점: Node.js API 서버를 Docker, ECS/Fargate, 로드밸런서 뒤에서 운영할 때 겪는 배포 중 502, 중단 요청, 느린 종료 문제를 기준으로 정리했습니다.

## graceful shutdown은 "프로세스 종료"보다 앞의 문제입니다

`docker stop`이나 오케스트레이터의 스케일 인이 시작되면 컨테이너는 언젠가 내려갑니다. 문제는 내려간다는 사실이 아니라, 내려가는 동안 트래픽이 어떻게 흐르느냐입니다. 로드밸런서는 아직 컨테이너가 살아 있다고 보고 요청을 보낼 수 있고, Node.js 프로세스는 새 연결을 받다가 갑자기 끊길 수 있습니다.

graceful shutdown을 제대로 보려면 종료를 네 단계로 나눠야 합니다.

| 단계 | 애플리케이션이 해야 할 일 | 실패하면 보이는 증상 |
| --- | --- | --- |
| 종료 신호 수신 | `SIGTERM` 같은 중지 신호를 핸들링한다 | 컨테이너가 강제 종료될 때까지 기다림 |
| 트래픽 차단 | readiness를 실패로 바꾸거나 새 연결을 받지 않는다 | 배포 중 일부 요청이 새로 들어와 끊김 |
| 진행 중 작업 마무리 | HTTP 요청, DB 쿼리, 큐 작업을 정리한다 | 저장, 결제, 파일 처리 같은 작업이 중간에 멈춤 |
| 제한 시간 안에 종료 | 타이머, DB 풀, 워커를 닫고 `exitCode`를 정한다 | 플랫폼이 `SIGKILL`로 강제 종료 |

이 순서가 없는 상태에서 health check만 추가하면 착시가 생깁니다. "컨테이너는 healthy"인데 배포 중 요청은 실패합니다. 헬스체크는 상태를 알려주는 장치이고, 종료 처리 코드는 실제로 요청을 멈추는 장치입니다. 둘을 같이 설계해야 합니다.

## 종료 신호가 Node.js까지 도착하는지 먼저 확인합니다

Node.js 공식 문서는 프로세스가 `SIGINT`, `SIGTERM` 같은 signal을 받으면 같은 이름의 이벤트가 발생한다고 설명합니다. 신호 핸들러는 첫 번째 인자로 신호 이름을 받을 수 있습니다. 이 글은 2026년 8월 11일 기준 Node.js 공식 문서를 확인해 작성했습니다. (출처: [Node.js Process - Signal events](https://nodejs.org/api/process.html#signal-events))

컨테이너 환경에서 자주 놓치는 지점은 신호가 애플리케이션까지 전달되지 않는 경우입니다. Dockerfile의 `CMD`나 `ENTRYPOINT`가 shell form으로 되어 있거나, 중간 쉘 스크립트가 마지막에 `exec`를 쓰지 않으면 실제 Node.js 프로세스가 PID 1이 아닐 수 있습니다. 이 경우 종료 신호는 쉘까지는 갔지만 앱은 모르는 상태가 됩니다.

먼저 Dockerfile을 이렇게 점검합니다.

```dockerfile
# 피하고 싶은 형태: shell이 중간에 끼기 쉽습니다.
CMD node server.js

# 더 명확한 형태: node 프로세스가 직접 실행됩니다.
CMD ["node", "server.js"]
```

초기화 스크립트가 꼭 필요하다면 마지막 실행에서 `exec`를 써서 프로세스를 대체해야 합니다.

```sh
#!/bin/sh
set -e

node scripts/migrate-once.js
exec node server.js
```

이 한 줄 차이가 배포 안정성에 영향을 줍니다. `exec` 없이 `node server.js`를 실행하면 shell이 부모 프로세스로 남고, 종료 신호 처리 방식이 의도와 달라질 수 있습니다. 작은 팀에서는 이런 문제가 로드밸런서 설정이나 ECS 배포 설정 탓처럼 보이다가, 결국 Dockerfile 한 줄에서 발견되는 일이 적지 않습니다.

Dockerfile 문서는 `STOPSIGNAL`이 컨테이너 중지 시 보낼 신호를 지정하며, 별도 정의가 없으면 기본값이 `SIGTERM`이라고 설명합니다. 또한 `docker stop`은 이 중지 신호를 사용합니다. 특별한 이유가 없다면 Node.js HTTP 서버에서는 기본 `SIGTERM` 흐름을 받아 애플리케이션 코드에서 종료 절차를 처리하는 편이 단순합니다. (출처: [Dockerfile reference - STOPSIGNAL](https://docs.docker.com/reference/dockerfile/#stopsignal))

## Node.js에서는 새 연결 차단과 기존 요청 마무리를 분리합니다

Node.js HTTP 서버의 `server.close()`는 새 연결을 받지 않게 하고, 요청을 보내고 있거나 응답을 기다리는 중이 아닌 연결을 닫습니다. Node.js v19.0.0부터는 반환 전 idle connection을 닫는 변경도 들어갔습니다. 오래 붙어 있는 연결까지 강제로 닫아야 할 때는 `server.closeAllConnections()` 같은 별도 메서드가 있지만, 이름 그대로 활성 연결도 닫을 수 있어 사용 위치를 조심해야 합니다. (출처: [Node.js HTTP - server.close()](https://nodejs.org/api/http.html#serverclosecallback), [Node.js HTTP - server.closeAllConnections()](https://nodejs.org/api/http.html#servercloseallconnections))

기본 구조는 아래처럼 시작할 수 있습니다.

```js
import http from "node:http";
import process from "node:process";

const app = async (req, res) => {
  if (req.url === "/healthz") {
    res.writeHead(200);
    res.end("ok");
    return;
  }

  if (req.url === "/readyz") {
    if (isShuttingDown) {
      res.writeHead(503);
      res.end("shutting down");
      return;
    }

    res.writeHead(200);
    res.end("ready");
    return;
  }

  res.writeHead(200, { "content-type": "application/json" });
  res.end(JSON.stringify({ ok: true }));
};

const server = http.createServer(app);
let isShuttingDown = false;

server.listen(3000, () => {
  console.log("server listening on :3000");
});

async function shutdown(signal) {
  if (isShuttingDown) return;

  isShuttingDown = true;
  console.log(`${signal} received. start graceful shutdown`);

  const forceExitTimer = setTimeout(() => {
    console.error("graceful shutdown timeout exceeded");
    process.exit(1);
  }, 25_000);

  forceExitTimer.unref();

  server.close(async (error) => {
    if (error) {
      console.error(error);
      process.exitCode = 1;
    }

    try {
      await closeDatabasePool();
      await closeQueueConsumers();
    } catch (cleanupError) {
      console.error(cleanupError);
      process.exitCode = 1;
    }
  });
}

process.on("SIGTERM", shutdown);
process.on("SIGINT", shutdown);

async function closeDatabasePool() {
  // 예: await pool.end();
}

async function closeQueueConsumers() {
  // 예: await worker.close();
}
```

이 예시에서 핵심은 `isShuttingDown`입니다. 종료 신호를 받는 순간 `/readyz`를 실패로 바꿉니다. 그러면 로드밸런서나 오케스트레이터가 이 인스턴스를 트래픽 대상에서 빼기 시작할 수 있습니다. 동시에 `server.close()`로 새 연결을 받지 않게 합니다. 처리 중인 요청은 가능한 한 끝내고, DB 풀과 큐 소비자 같은 외부 리소스는 마지막에 닫습니다.

주의할 점도 있습니다. `server.close()` 콜백 안에서 정리 작업을 시작했는데, 그 작업을 기다리는 Promise가 실제 종료 흐름에 묶이지 않으면 프로세스가 예상보다 빨리 내려갈 수 있습니다. 반대로 타이머나 워커가 남아 있으면 프로세스가 끝나지 않습니다. 그래서 운영 코드에서는 종료 흐름을 테스트로 한 번 재현해 보는 편이 좋습니다.

```sh
docker stop --time 30 my-node-api
```

이 명령으로 컨테이너가 30초 안에 내려가는지, 종료 로그가 남는지, 처리 중 요청이 끊기는지 확인합니다. 로컬에서 이 실험이 안 되면 운영 배포에서는 더 보기 어렵습니다.

## readiness와 liveness를 같은 엔드포인트로 두면 장애가 커질 수 있습니다

Kubernetes 문서는 probe를 startup, liveness, readiness로 나눕니다. startup probe는 앱 시작 완료를 확인하고, liveness probe는 컨테이너를 재시작해야 할 정도의 상태를 판단하며, readiness probe는 트래픽을 받을 준비가 됐는지 판단합니다. readiness가 실패하면 서비스의 EndpointSlice에서 해당 Pod가 트래픽 대상에서 빠질 수 있습니다. (출처: [Kubernetes - Liveness, Readiness, and Startup Probes](https://kubernetes.io/docs/concepts/workloads/pods/probes/))

문제는 이 세 가지를 모두 `/health` 하나로 묶을 때 생깁니다. DB가 잠깐 느려졌다는 이유로 liveness가 실패하면, 컨테이너가 재시작됩니다. 재시작이 몰리면 남은 인스턴스에 부하가 더 쌓이고, 부하는 다시 헬스체크 실패로 이어질 수 있습니다.

실무에서는 이렇게 나누는 편이 덜 위험합니다.

| 엔드포인트 | 용도 | 실패 기준 |
| --- | --- | --- |
| `/healthz` | 프로세스가 살아 있고 이벤트 루프가 응답 가능한지 확인 | 앱이 멈췄거나 기본 응답이 불가능할 때 |
| `/readyz` | 트래픽을 받아도 되는지 확인 | 종료 중, DB 연결 불가, 필수 캐시 준비 전 |
| `/startupz` | 초기화가 끝났는지 확인 | 마이그레이션, 캐시 로딩, 설정 로딩 미완료 |

liveness는 마지막 수단에 가깝게 잡아야 합니다. "잠깐 외부 API가 느리다"는 이유로 프로세스를 죽이면 회복이 아니라 흔들림이 됩니다. 반대로 readiness는 더 민감해도 됩니다. 이 인스턴스가 지금 요청을 처리하기 어렵다면 잠시 트래픽 대상에서 빠지는 것이 낫습니다.

Kubernetes 문서도 liveness probe를 잘못 구현하면 부하 상황에서 컨테이너 재시작이 이어져 장애가 커질 수 있다고 주의합니다. 그래서 liveness는 애플리케이션 자체가 복구 불가능하게 멈췄는지 보는 쪽에 가깝고, 의존성 상태는 readiness로 보내는 편이 운영상 안전합니다. (출처: [Kubernetes - Liveness probe caution](https://kubernetes.io/docs/concepts/workloads/pods/probes/#liveness-probe))

## Docker HEALTHCHECK와 ECS 헬스체크는 같은 말처럼 보이지만 위치가 다릅니다

Dockerfile의 `HEALTHCHECK`는 컨테이너 내부에서 명령을 실행해 컨테이너 상태를 판단합니다. Docker 문서는 health status가 `starting`, `healthy`, `unhealthy`로 변할 수 있고, 실패가 지정 횟수만큼 이어지면 unhealthy가 된다고 설명합니다. `--interval`, `--timeout`, `--start-period`, `--retries` 같은 옵션도 여기서 정합니다. (출처: [Dockerfile reference - HEALTHCHECK](https://docs.docker.com/reference/dockerfile/#healthcheck))

ECS를 쓰는 팀이라면 한 가지를 더 알아야 합니다. Amazon ECS 문서는 task definition에 지정한 컨테이너 헬스체크만 모니터링하고 보고한다고 설명합니다. 이미지에 들어 있는 Docker health check가 있더라도 ECS task definition에 지정되지 않았다면 ECS가 그것을 그대로 모니터링하는 구조가 아닙니다. 또한 task definition의 헬스체크 파라미터는 이미지에 있는 Docker health check를 override할 수 있습니다. (출처: [Amazon ECS - Determine task health using container health checks](https://docs.aws.amazon.com/AmazonECS/latest/developerguide/healthcheck.html))

이 차이를 모르고 Dockerfile에만 `HEALTHCHECK`를 넣으면 "로컬에서는 healthy인데 ECS에서는 배포 판단에 반영되지 않는" 상황이 생길 수 있습니다. 반대로 ECS task definition에도 같은 기준을 넣으면, 배포 플랫폼이 컨테이너 상태를 더 명확히 볼 수 있습니다.

작은 Node.js API라면 시작점은 아래 정도로 충분합니다.

```dockerfile
HEALTHCHECK --interval=30s --timeout=3s --start-period=20s --retries=3 \
  CMD node -e "fetch('http://127.0.0.1:3000/healthz').then(r => process.exit(r.ok ? 0 : 1)).catch(() => process.exit(1))"
```

다만 이 명령에도 트레이드오프가 있습니다. Node.js 런타임을 매번 띄우는 방식이라 아주 촘촘한 간격에는 부담이 될 수 있습니다. 이미지에 `curl`이나 `wget`이 없다면 이런 식으로 시작할 수 있지만, 운영에서는 컨테이너 크기, 실행 비용, 헬스체크 주기를 같이 봐야 합니다.

## 종료 제한 시간은 플랫폼과 코드가 같은 숫자를 보고 있어야 합니다

Kubernetes의 Pod 종료 흐름은 일반적으로 컨테이너 런타임이 프로세스 1에 `TERM` 신호를 보내고, grace period가 지난 뒤 남은 프로세스에 `KILL` 신호를 보내는 방식입니다. 기본 graceful delete는 30초 안에서 동작하며, `terminationGracePeriodSeconds`로 조정할 수 있습니다. (출처: [Kubernetes - Pod termination](https://kubernetes.io/docs/concepts/workloads/pods/pod-lifecycle/#pod-termination))

ECS도 비슷한 관점으로 봐야 합니다. AWS ECS 문서는 stop signal 이후 애플리케이션이 종료하지 않으면 `SIGKILL`이 보내지며, EC2 기반 ECS에서는 `ECS_CONTAINER_STOP_TIMEOUT` 기본값이 30초라고 설명합니다. 로드밸런서 connection draining을 다루는 ECS 문서도 애플리케이션이 `SIGTERM`을 받아 새 요청을 멈추고 진행 중 요청을 끝내는 방향을 안내합니다. (출처: [Amazon ECS - Optimize load balancer connection draining parameters](https://docs.aws.amazon.com/AmazonECS/latest/developerguide/load-balancer-connection-draining.html))

여기서 자주 생기는 실수는 코드의 강제 종료 타이머가 플랫폼보다 길다는 것입니다. 예를 들어 Kubernetes `terminationGracePeriodSeconds`가 30초인데 애플리케이션 코드의 force timeout을 60초로 잡으면, 코드가 직접 정리하기 전에 플랫폼이 먼저 프로세스를 죽일 수 있습니다. 앱 로그에는 "정상 종료 완료"가 남지 않고, DB 커넥션이나 큐 작업도 중간 상태로 남을 수 있습니다.

나는 보통 이렇게 맞춥니다.

- 플랫폼 grace period: 30초라면 애플리케이션 force timeout은 25초 안팎으로 둔다.
- 로드밸런서 deregistration delay: 평소 요청 시간이 짧은 서비스와 업로드/스트리밍 서비스는 따로 잡는다.
- readiness 실패 전환: `SIGTERM`을 받는 즉시 내려간다.
- 배치/큐 워커: HTTP 서버보다 더 긴 정리 시간이 필요할 수 있으므로 별도 컨테이너나 별도 stop timeout을 검토한다.

숫자는 서비스마다 달라질 수 있습니다. 핵심은 "코드는 60초를 기다리는데 플랫폼은 30초에 죽인다" 같은 엇갈림을 없애는 것입니다.

## 배포 중 502를 줄이는 점검 순서

graceful shutdown 문제는 한 번에 완벽하게 고치려 하면 범위가 커집니다. 나는 보통 아래 순서로 확인합니다.

1. **종료 신호 로그를 남긴다.**  
   `SIGTERM received` 로그가 배포 때 찍히는지 확인합니다. 안 찍히면 Dockerfile, ENTRYPOINT, 프로세스 관리자부터 봅니다.

2. **`/readyz`를 별도로 만든다.**  
   종료 중에는 503을 반환하게 둡니다. `/healthz`와 같은 응답을 쓰지 않습니다.

3. **`server.close()`를 호출한다.**  
   새 연결을 막고 기존 요청이 끝날 기회를 줍니다. 오래 붙는 연결이 있다면 WebSocket, SSE, 업로드 경로를 따로 점검합니다.

4. **정리 작업에 제한 시간을 둔다.**  
   DB 풀, 큐 소비자, cron성 타이머를 닫습니다. 플랫폼 grace period보다 짧은 force timeout을 둡니다.

5. **헬스체크 위치를 맞춘다.**  
   Dockerfile, ECS task definition, Kubernetes probe 중 실제 배포 플랫폼이 보는 설정을 확인합니다.

6. **배포 중 트래픽으로 재현한다.**  
   로컬 `docker stop`만으로는 부족합니다. 스테이징에서 요청을 계속 보내며 rolling deploy를 실행해 5xx, 지연, 중복 처리 여부를 봅니다.

이 순서는 화려하지 않지만 문제를 빨리 좁힙니다. 운영 사고 때 "로드밸런서가 이상하다"와 "Node.js가 종료 신호를 무시한다"는 전혀 다른 문제입니다. 신호, readiness, 서버 종료, 강제 종료 시간을 분리하면 원인 후보가 줄어듭니다.

## 이런 경우에는 graceful shutdown만으로 부족합니다

모든 중단 문제가 graceful shutdown 코드로 해결되는 것은 아닙니다. 특히 아래 경우에는 더 넓게 봐야 합니다.

- 긴 파일 업로드나 스트리밍 요청이 많다.
- WebSocket처럼 연결이 오래 유지된다.
- 큐 작업이 한 번 시작되면 수십 초 이상 걸린다.
- 같은 요청이 두 번 실행되면 데이터가 꼬인다.
- DB migration이 배포와 동시에 실행된다.

이런 서비스는 종료 코드만 고쳐서는 부족합니다. 요청 단위 idempotency, 작업 재개 설계, drain 기간, 배포 전후 migration 순서가 같이 필요합니다. 예를 들어 주문 생성 API라면 graceful shutdown보다 먼저 중복 요청 방지 키를 봐야 할 수 있습니다. 컨테이너가 아무리 우아하게 내려가도, 클라이언트가 재시도한 요청을 서버가 두 번 처리하면 운영 문제는 그대로 남습니다.

Node.js API 서버와 큐 워커를 같은 프로세스에 묶어 둔 경우도 조심해야 합니다. HTTP 요청은 빨리 내려가야 하는데 큐 작업은 끝까지 처리해야 한다면 종료 기준이 충돌합니다. 이때는 프로세스나 컨테이너를 분리하는 편이 더 단순할 수 있습니다.

## 작은 팀에서 바로 적용할 체크리스트

- [ ] Dockerfile의 `CMD` 또는 `ENTRYPOINT`가 exec form이거나, 쉘 스크립트 마지막에 `exec`를 사용한다.
- [ ] Node.js에서 `SIGTERM`과 `SIGINT`를 모두 받아 같은 종료 흐름으로 보낸다.
- [ ] 종료 신호를 받으면 즉시 readiness 응답이 실패한다.
- [ ] `server.close()`로 새 연결을 막고 진행 중 요청을 마무리한다.
- [ ] DB 풀, Redis 연결, 큐 소비자, 타이머를 닫는 코드가 있다.
- [ ] 애플리케이션 force timeout이 플랫폼 grace period보다 짧다.
- [ ] liveness는 프로세스 생존, readiness는 트래픽 수신 가능 상태를 본다.
- [ ] ECS를 쓴다면 task definition의 health check가 실제 배포 판단에 반영되는지 확인한다.
- [ ] Kubernetes를 쓴다면 startup/readiness/liveness probe 역할이 섞이지 않았는지 본다.
- [ ] 스테이징에서 배포 중 요청을 보내며 5xx와 중단 요청을 확인했다.

마지막으로 하나만 고른다면 `/readyz`와 `SIGTERM` 로그부터 넣겠습니다. 배포 중 인스턴스가 언제 트래픽 대상에서 빠지는지, 종료 신호가 앱까지 도착하는지 알 수 있어야 다음 원인을 찾을 수 있습니다. graceful shutdown은 멋진 운영 패턴이 아니라, 컨테이너가 내려가는 30초 동안 사용자의 요청을 어디까지 지켜줄지 정하는 약속입니다.

## 출처

- [Node.js Process - Signal events](https://nodejs.org/api/process.html#signal-events)
- [Node.js HTTP - server.close()](https://nodejs.org/api/http.html#serverclosecallback)
- [Node.js HTTP - server.closeAllConnections()](https://nodejs.org/api/http.html#servercloseallconnections)
- [Dockerfile reference - STOPSIGNAL](https://docs.docker.com/reference/dockerfile/#stopsignal)
- [Dockerfile reference - HEALTHCHECK](https://docs.docker.com/reference/dockerfile/#healthcheck)
- [Kubernetes - Liveness, Readiness, and Startup Probes](https://kubernetes.io/docs/concepts/workloads/pods/probes/)
- [Kubernetes - Pod termination](https://kubernetes.io/docs/concepts/workloads/pods/pod-lifecycle/#pod-termination)
- [Amazon ECS - Determine task health using container health checks](https://docs.aws.amazon.com/AmazonECS/latest/developerguide/healthcheck.html)
- [Amazon ECS - Optimize load balancer connection draining parameters](https://docs.aws.amazon.com/AmazonECS/latest/developerguide/load-balancer-connection-draining.html)
