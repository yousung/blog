---
title: "Node.js 응답 지연은 이벤트 루프부터 본다: 블로킹 원인과 대응 체크리스트"
slug: "nodejs-event-loop-blocking-checklist"
author: "BLOA Team"
date: "2026-08-18"
summary: "Node.js 서비스가 갑자기 늦어질 때 DB·네트워크 이전에 이벤트 루프 블로킹부터 확인해야 하는 이유와, p95 지연을 줄이기 위한 진단·분리·완화 기준을 체크리스트로 정리했습니다."
oneLineSummary: "느려짐의 첫 의심 대상은 이벤트 루프 블로킹입니다. 측정 지표, 코드 패턴, 워커 분리 기준을 합쳐야 원인 파악이 빨라집니다."
tags: [Node.js, 백엔드, 성능, 성능최적화, 디버깅]
status: "published"
---

# Node.js 응답 지연은 이벤트 루프부터 본다: 블로킹 원인과 대응 체크리스트

배포 직후 사용자 화면이 갑자기 1초 이상 걸린다고 나왔을 때, 대개 먼저 DB나 캐시를 의심합니다. 실제로는 요청 하나가 오래 걸려 CPU를 잡아먹는 동기 함수 한 줄이 큐의 맨 앞을 차지해 전체 흐름이 밀리는 경우가 더 자주 있습니다. 이런 상황에서 로그의 `query` 부분만 붙잡고 분석하면 시간은 더 오래 걸립니다.  

내가 가장 먼저 보는 질문은 한 가지입니다.  

**“이 요청이 완료되지 못하게 만든 것은 대기 시간이 아니라, 이벤트 루프를 점유한 동기 작업인가?”**

최종 업데이트: 2026-08-18  
작성 관점: Node.js API 운영 현장에서 배포 전후 요청 지연/간헐적 타임아웃이 반복된 사례를 기준으로 정리했습니다.

## 이벤트 루프 병목의 핵심은 “누군가 죽어 있는가”가 아니다

Node.js는 기본적으로 한 개의 메인 이벤트 루프에서 콜백을 순차 처리합니다. 긴 동기 처리나 과도한 파싱/암호화 계산이 이 루프를 오래 점유하면, 다른 요청은 처리 순서를 기다리며 체감 지연이 생깁니다.

여기서 중요한 건 **이벤트 루프가 죽은 게 아니라, 바쁘게 빽빽하게 돌아가고 있는 것**이 문제라는 점입니다. DB 접속도 정상이고 애플리케이션 메모리도 안정적인데 타임아웃이 생기면, 이 패턴이 맞습니다.

이해를 돕기 위해 증상을 나누면 다음과 같습니다.

| 증상 | 주된 진단 가설 | 1차 확인 포인트 |
| --- | --- | --- |
| CPU 80% 이상이며 응답이 일정 구간만 느림 | 동기 코드 블로킹(루프, 대용량 JSON 처리, 동기 I/O) | 장시간 실행되는 JS 함수 로그/프로파일 |
| 메모리는 안정인데 응답이 산발적으로 느림 | 특정 요청에서만 루프 점유 집중 | 요청별 trace에서 outlier 확인 |
| 타임아웃은 간헐적, CPU가 급등하지 않음 | libuv 스레드풀 소모·이벤트 대기 | `UV_THREADPOOL` 사용량/대기 지연 확인 |
| 새 배포 후 특정 엔드포인트만 악화 | 미들웨어/파서 변경, 무거운 동기 유효성 검사 추가 | 배포 전/후 diff와 라우트별 p95 비교 |

## 먼저 정해야 할 판단 기준: CPU보다 “응답 분산도”를 본다

Node.js 운영에서 성능 지표를 오용하기 좋은 순간이 있습니다. “CPU가 높아야 문제”라는 전제를 그대로 쓰면 놓치는 일이 생깁니다. 이벤트 루프 블로킹은 평균 CPU가 높지 않아도 p95/p99 지연을 급격히 키우기 때문입니다.

가장 유용한 최소 지표는 두 가지입니다.

- **이벤트 루프 지연**: `perf_hooks.monitorEventLoopDelay()` 같은 지표로 확인  
- **요청 지연 상위 구간(p95/p99)**: 전체 평균보다 긴 꼬리 지연(헤드 오브 더 웨이브)을 추적

Node.js 공식 문서는 이벤트 루프가 비동기 코드 스케줄링의 핵심이며, 블로킹이 늘어나면 다음 이벤트 처리가 지연된다고 설명합니다. (출처: [Node.js 가이드 — Event Loop](https://nodejs.org/en/learn/asynchronous-work/event-loop-timers-and-nexttick))  
`monitorEventLoopDelay()`는 이벤트 루프가 정상 주기에서 얼마나 벗어났는지 추적할 수 있게 해주고, 지연 분포를 수치로 확인할 수 있게 합니다. (출처: [Node.js perf_hooks API](https://nodejs.org/api/perf_hooks.html#perf_hooks_monitorEventLoopDelay))

실무적으로는 아래 임계값을 시작점으로 두고, 서비스 특성에 맞춰 조정합니다.

1. `eventLoopDelay`의 p95가 30ms를 넘으면 블로킹 후보로 분류  
2. p99가 100ms를 넘으면 사용자 체감 지연 우선 대응  
3. 동일 구간에서 특정 라우트가 과도하게 많으면 라우트 단위 코드 리팩터링

`p99`가 오르는데 DB 커넥션 수나 인프라 스케일링 지표는 안정적이면, 그 구간은 거의 이벤트 루프 병목 쪽으로 봐도 안전합니다.

```js
import { monitorEventLoopDelay } from "node:perf_hooks";

const delay = monitorEventLoopDelay({ resolution: 20 });
delay.enable();

setInterval(() => {
  const p95 = delay.percentile(95) / 1e6; // ms
  const p99 = delay.percentile(99) / 1e6; // ms

  logger.info({ p95Ms: p95.toFixed(1), p99Ms: p99.toFixed(1) }, "event-loop-delay");
  delay.reset();
}, 10_000);
```

### 주의할 점

`monitorEventLoopDelay`는 진단용 수치입니다. 절대치보다 “배포 직전/직후, 기능 토글 전/후”의 변화량을 보는 게 더 중요합니다. 특히 테스트가 적은 시간대에는 p99가 낮게 나와도, 피크 타임에 다른 작업들과 합쳐져 급격히 올라갈 수 있습니다.

## 블로킹 원인 분류: 동기 코드, 암묵적 동기 I/O, 스레드풀 정체

동기 코드 블로킹의 경우엔 패턴이 꽤 단순합니다.

- request 핸들러에서 `JSON.parse` 대상이 과도하게 크고 반복됨  
- 파일/응답 데이터를 `readFileSync`, `writeFileSync`, `Crypto`의 동기 함수로 처리  
- 정규식이 최악 탐색을 반복하는 패턴 사용  

둘째로 자주 놓치는 유형이 **암묵적 블로킹**입니다. 코드상 동기 호출이 보이지 않는데 블로킹이 생기면 다음 지점부터 의심합니다.

- 라우트 바디 파싱이 매우 큰 payload를 한 번에 파싱  
- 템플릿 렌더가 요청마다 반복적으로 거대한 문자열 재귀 조합  
- 로그/추적 함수가 과도한 동기 직렬화(`JSON.stringify`) 수행

셋째는 Node.js의 **libuv 스레드풀 자원 정체**입니다. 일부 작업은 이벤트 루프 대신 스레드풀에서 돌아가더라도, `UV_THREADPOOL_SIZE`와 큐가 포화되면 응답 준비 타이밍이 늦어질 수 있습니다. Node.js 문서는 스레드풀 크기 조절 환경변수의 의미를 설명하고 있으며, 과도 조정은 역효과를 일으킬 수 있다고 경고합니다. (출처: [Node.js CLI — `--uv-thread-pool-size`](https://nodejs.org/api/cli.html#--uv-thread-pool-size))

## 코드로 바로 고치는 체크리스트: 네 단계

### 1단계: 확신 없이 건드리지 말고 “차단 구간”만 떼어 본다

요청당 공통 미들웨어에서 오래 걸리는 동기 작업을 제거할 수 없다면, 문제 구간만 분리합니다.

```js
app.post("/upload/validate", async (req, res) => {
  const start = performance.now();
  const parsed = await parseLargePayload(req.body);
  metrics.histogram("upload.parse_ms").record(performance.now() - start);

  // ... 이후 처리는 비동기 큐로 이관
  await enqueueValidation(parsed);
  res.status(202).send({ status: "queued" });
});
```

핵심은 응답 경로와 무거운 로직을 분리해, 사용자가 같은 요청에 대해 즉시 피드백을 받게 하는 것입니다.

### 2단계: 동기/CPU 무거운 함수를 비동기 작업으로 이관

CPU 집약 작업은 이벤트 루프 외부에서 돌려야 합니다. Node.js Worker Threads는 CPU 바운드 작업을 메인 스레드와 분리해 처리할 수 있는 정석입니다.

```js
import { Worker } from "node:worker_threads";

function runCompressionTask(input) {
  return new Promise((resolve, reject) => {
    const worker = new Worker(new URL("./jobs/compress.mjs", import.meta.url), {
      workerData: input,
    });

    worker.once("message", resolve);
    worker.once("error", reject);
  });
}
```

Worker를 즉시 확대하지 마세요. 작은 배치에서는 스레드 생성 오버헤드가 더 클 수 있습니다. 큐 길이와 지연이 실제로 높은 라우트만 이관 대상으로 잡는 편이 비용 대비 효율이 좋습니다.

### 3단계: 스레드풀 경합은 큐에서 먼저 본다

암호화, zlib 압축, DNS 조회 같은 작업은 libuv 스레드풀을 타는 경우가 있어, 풀 크기가 충분하지 않으면 병목이 응답으로 번집니다. 모든 문제를 `UV_THREADPOOL_SIZE` 숫자 올리기로 풀려는 건 위험합니다. 운영 환경에서 스레드풀을 올리면 CPU 코어당 경쟁이 생기고, 컨텍스트 스위칭이 오히려 증가할 수 있습니다.  

대신 우선 아래 순서를 따릅니다.

1. 문제 라우트 한정에서만 해당 기능 호출 빈도 감소  
2. 호출 단위를 배치 처리로 묶거나 비동기 큐로 분산  
3. 배치 처리도 부족하면 `UV_THREADPOOL_SIZE` 조정 실험(비교 측정)  
4. 여전히 불안정하면 `worker_threads`로 아예 분리

### 4단계: 재시도·타임아웃 체계를 맞춘다

블로킹 이슈를 찾은 뒤에도 연결 타임아웃과 재시도 정책이 빠지면 재발합니다. 타임아웃 이전에 블록이 쌓인 요청을 그대로 재시도하면 큐가 더 길어집니다.

- 이벤트 루프 지연이 높은 구간에서 무차별 재시도 금지  
- `POST`는 멱등 조건이 없으면 자동 재시도 제외  
- 장애 구간 전용 `503` 정책으로 부하를 흩뜨림

백오프는 실패 로그보다 안정적인 정책이 됩니다.

```js
async function safeUpstreamFetch(url, attempt = 0) {
  try {
    return await fetch(url, { signal: AbortSignal.timeout(3000) });
  } catch (error) {
    if (attempt >= 2) throw error;
    const waitMs = 150 * 2 ** attempt + Math.random() * 80;
    await new Promise((resolve) => setTimeout(resolve, waitMs));
    return safeUpstreamFetch(url, attempt + 1);
  }
}
```

## 배포 전 1회 점검표

- [ ] 엔드포인트별 p95/p99와 이벤트 루프 p95/p99 비교
- [ ] 동일 코드 경로에서 동기 파일 I/O/동기 파서 사용 여부 점검
- [ ] CPU 스파이크 없는 시간대에서도 지연 spike 존재 여부 확인
- [ ] 큐잉/Worker 이관 대상 라우트 선정 및 폴백 전략 정리
- [ ] `UV_THREADPOOL_SIZE` 조정은 모니터링 기반 A/B로만 검증

## FAQ

### 이벤트 루프 지연은 CPU 사용량이 낮아도 문제가 될 수 있나요?
네. 짧은 구간에 작은 동기 작업이 반복되면 평균 CPU는 낮아도 긴 구간의 응답 지연만 커질 수 있습니다. p95/p99이 이 부분을 잡아냅니다.

### `perf_hooks`를 운영 로그에 넣으면 오버헤드가 크지 않나요?
수치 수집 비용이 있는 건 맞지만, 샘플링 주기와 집계 단위를 제한하면 충분히 운영 가능합니다. 100ms 간격으로 매 요청마다 찍는 방식은 피하고, 집계기에서 5~10초 단위로 묶어야 합니다.

### 동기 API를 worker로 모두 옮기면 끝인가요?
아닙니다. 가장 큰 리스크는 오히려 데이터 전달과 동기화 비용입니다. 가장 비싼 동기 구간부터 점검하고, 순차로 대체해야 합니다.

### worker_threads와 cluster를 같이 써야 하나요?
목적이 다릅니다. worker는 CPU 바운드 분리를 위해, cluster는 프로세스 수를 늘리기 위해 씁니다. 첫 접근은 CPU 분리부터, 두 번째는 배포 패턴과 운영 경험을 본 뒤 결정하는 편이 보수적입니다.

## 출처

- Node.js — Event Loop: https://nodejs.org/en/learn/asynchronous-work/event-loop-timers-and-nexttick
- Node.js Docs — `perf_hooks`: https://nodejs.org/api/perf_hooks.html
- Node.js Docs — `worker_threads`: https://nodejs.org/api/worker_threads.html
- Node.js Docs — Command-line options (`--uv-thread-pool-size`): https://nodejs.org/api/cli.html#--uv-thread-pool-size
