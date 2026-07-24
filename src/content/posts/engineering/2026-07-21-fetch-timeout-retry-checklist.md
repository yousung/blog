---
title: "fetch 타임아웃과 재시도, API 호출이 멈출 때 먼저 볼 기준"
slug: "fetch-timeout-retry-checklist"
ogImage: "/images/posts/fetch-timeout-retry-checklist/hero.png"
author: "감성개발자"
date: "2026-07-21"
summary: "브라우저와 Node.js에서 fetch 호출이 오래 멈추거나 실패할 때 타임아웃, AbortController, HTTP 상태 코드, 재시도 기준을 어떻게 나눠 봐야 하는지 실무에서 쓰는 체크리스트로 풀었다."
oneLineSummary: "fetch 호출은 타임아웃, 취소, HTTP 오류, 재시도 가능 여부를 분리해서 다뤄야 장애와 중복 요청을 줄일 수 있다."
tags: [JavaScript, Node.js, HTTP, API, 개발팁]
status: "published"
---

# fetch 타임아웃과 재시도, API 호출이 멈출 때 먼저 볼 기준

![fetch 타임아웃, 취소, 재시도 흐름을 표현한 기술 일러스트](/images/posts/fetch-timeout-retry-checklist/hero.png)

API 호출이 느릴 때 가장 답답한 순간은 에러가 아니라 침묵입니다. 버튼은 로딩 중이고, 서버 로그에는 요청이 보이지 않거나 늦게 찍히고, 사용자는 다시 클릭합니다. 이때 `catch`에 로그 하나 더 넣는 것으로는 부족합니다. 먼저 정해야 할 것은 "언제 포기할지", "무엇을 실패로 볼지", "다시 보내도 되는 요청인지"입니다.

내 기준은 하나로 모입니다. **`fetch`는 타임아웃, 취소, HTTP 오류, 재시도를 서로 다른 문제로 나눠 처리해야 합니다.** 네트워크 실패는 `catch`로 들어오지만, `404`나 `500` 같은 HTTP 상태 코드는 응답 객체로 정상 도착할 수 있습니다. 재시도도 모든 요청에 붙이면 안 됩니다. 같은 `POST`를 두 번 보내면 주문, 결제, 가입 요청이 중복될 수 있기 때문입니다.

먼저 정할 것 네 가지는 이렇습니다.

- `fetch`에는 업무 기준의 타임아웃을 명시해야 합니다. 오래 기다리는 것과 실패 처리는 다릅니다.
- HTTP 오류 상태는 자동으로 예외가 되지 않습니다. `response.ok`나 `response.status`를 직접 확인해야 합니다.
- 재시도는 네트워크 오류, `408`, `429`, 일시적인 `5xx`처럼 회복 가능성이 있는 실패에 제한하는 편이 안전합니다.
- `POST` 재시도는 멱등키나 중복 방지 설계가 있을 때만 검토하세요.

이 글은 브라우저 Fetch API, Node.js의 `AbortSignal`, web.dev의 fetch 오류 처리 가이드, HTTP Semantics RFC 9110을 바탕으로 삼았습니다. 특정 라이브러리보다 실무에서 API 호출 실패를 분류하는 기준에 초점을 맞췄습니다. (최종 업데이트: 2026-07-21)

## fetch가 멈춘 것처럼 보일 때 먼저 나눌 것

`fetch` 문제를 볼 때는 "요청이 실패했다"는 말부터 쪼개야 합니다. 서버가 늦는 것, 브라우저가 요청을 취소한 것, HTTP 상태 코드가 실패인 것, 응답 본문 파싱이 깨진 것은 서로 다른 문제입니다. 한 줄짜리 `try/catch`로 묶어버리면 로그는 단순해지지만 원인도 같이 사라집니다.

실무에서는 아래 네 가지를 먼저 구분하는 편이 좋습니다.

| 증상 | 흔한 원인 | 먼저 볼 코드 |
| --- | --- | --- |
| 로딩이 오래 끝나지 않음 | 타임아웃 기준 없음, 서버 지연, 네트워크 불안정 | `AbortSignal.timeout()` 또는 `AbortController` |
| `catch`로 들어옴 | 네트워크 오류, 명시적 취소, 타임아웃 | `error.name`, `signal.reason` |
| `catch`로 안 들어오는데 실패 화면이 필요함 | `404`, `429`, `500` 같은 HTTP 상태 코드 | `response.ok`, `response.status` |
| 같은 작업이 두 번 처리됨 | 무분별한 재시도, 버튼 중복 클릭, 멱등성 없음 | 요청 메서드, idempotency key, 서버 중복 방지 |

이 구분을 먼저 해두면 "재시도를 몇 번 할까"보다 중요한 질문이 보입니다. 이 요청은 다시 보내도 되는가. 사용자가 기다릴 수 있는 시간은 몇 초인가. 실패했을 때 화면은 무엇을 말해야 하는가.

에이전시 시절 인수받은 프로젝트에서 결제 중복 문의가 반복된 적이 있었는데, 원인은 대부분 이 네 가지를 한 덩어리로 묶어 둔 코드였습니다. 그 뒤로 나는 fetch 래퍼를 새로 만들 때 이 표부터 그려 놓고 시작합니다.

## 타임아웃은 UX 기준으로 정해야 합니다

`fetch` 호출에 타임아웃이 없으면 사용자는 실패인지 지연인지 알 수 없습니다. 로딩 스피너가 계속 돌면 사람은 보통 두 가지 중 하나를 합니다. 뒤로 가거나, 같은 버튼을 다시 누릅니다. 둘 다 개발자가 원하는 흐름은 아닙니다.

브라우저에서는 `AbortSignal.timeout()`으로 일정 시간이 지나면 요청을 중단하는 신호를 만들 수 있습니다. MDN은 이 메서드가 지정한 시간 뒤 자동으로 abort되는 `AbortSignal`을 반환하며, 타임아웃 시 `TimeoutError` 성격의 예외로 구분할 수 있다고 설명합니다. 이 기능은 MDN 기준으로 2024년 4월부터 최신 브라우저 범위에서 Baseline으로 분류되어 있습니다. (출처: [MDN - AbortSignal.timeout()](https://developer.mozilla.org/en-US/docs/Web/API/AbortSignal/timeout_static))

```js
async function fetchJson(url) {
  const response = await fetch(url, {
    signal: AbortSignal.timeout(5000),
  });

  if (!response.ok) {
    throw new Error(`HTTP ${response.status}`);
  }

  return response.json();
}
```

Node.js에서도 같은 방향으로 볼 수 있습니다. Node.js 문서는 `AbortSignal.timeout(delay)`가 지연 시간 뒤 abort되는 새 `AbortSignal`을 반환한다고 설명합니다. 또한 여러 신호를 합치는 `AbortSignal.any()`도 제공하므로, 사용자 취소와 업무 타임아웃을 함께 다루는 구조를 만들 수 있습니다. (출처: [Node.js Docs - Global objects](https://nodejs.org/dist/latest/docs/api/globals.html))

다만 타임아웃 숫자는 정답이 없습니다. 목록 조회와 파일 업로드를 같은 5초로 묶으면 한쪽은 너무 느슨하고, 다른 한쪽은 너무 빡빡합니다. 기준은 기능별로 잡아야 합니다. 나는 전역 기본값을 하나 두되, 그 값을 모든 호출에 그대로 쓰지는 않는 쪽을 기본으로 잡습니다. 기본값은 안전망이고, 실제 기준은 화면마다 다르기 때문입니다.

- 자동완성, 검색 제안: 짧게 실패시키고 조용히 대체 UI를 보여준다.
- 일반 목록 조회: 몇 초 안에 실패 메시지와 다시 시도 버튼을 보여준다.
- 결제, 주문, 회원가입: 빨리 재시도하기보다 서버 처리 상태 확인 경로를 먼저 둔다.
- 업로드, 대용량 다운로드: 중단과 재개 가능 여부를 별도로 설계한다.

타임아웃은 서버를 재촉하는 옵션이 아닙니다. 사용자가 기다릴 수 있는 시간을 애플리케이션이 정하는 장치입니다.

## AbortController는 실패 처리보다 취소 의도에 가깝습니다

`AbortController`는 "에러를 잡기 위한 도구"라기보다 "이 요청은 더 이상 필요 없다"고 말하는 도구에 가깝습니다. 예를 들어 검색창에서 사용자가 `rea`를 입력한 뒤 바로 `react`까지 입력했다면, 앞선 요청은 결과가 와도 쓸모가 없습니다. 이런 요청을 계속 살려두면 오래된 응답이 나중에 도착해 최신 화면을 덮을 수 있습니다.

web.dev의 fetch 오류 처리 가이드도 사용자가 업로드 중 다른 파일을 고르면 기존 업로드가 뒤에서 계속 진행될 수 있고, 이때 자바스크립트가 기존 fetch 요청을 취소해 불필요한 대역폭 낭비를 줄일 수 있다고 설명합니다. (출처: [web.dev - Implement error handling when using the Fetch API](https://web.dev/articles/fetch-api-error-handling))

검색 요청이라면 이런 식으로 최신 요청만 살리는 구조가 필요합니다.

```js
let currentController;

async function search(keyword) {
  currentController?.abort();
  currentController = new AbortController();

  const response = await fetch(`/api/search?q=${encodeURIComponent(keyword)}`, {
    signal: currentController.signal,
  });

  if (!response.ok) {
    throw new Error(`HTTP ${response.status}`);
  }

  return response.json();
}
```

여기서 중요한 건 abort를 실패로만 기록하지 않는 것입니다. 사용자가 새 검색어를 입력해서 이전 요청을 취소한 것은 장애가 아닙니다. 반대로 서버가 늦어서 타임아웃된 것은 관찰해야 할 신호입니다. 로그와 화면 메시지를 똑같이 처리하면 운영에서 쓸 수 없는 데이터가 쌓입니다.

## HTTP 오류는 catch에 안 들어올 수 있습니다

![네트워크 실패는 부서져 catch 그물에 걸리지만, 404나 500 같은 HTTP 오류 응답은 봉인된 채 그물을 지나 도착 지점에 정상 도달하는 모습을, 금이 간 채 배달된 봉투와 그물에 걸린 파편의 대비로 보여주는 도식.](/images/posts/fetch-timeout-retry-checklist/figure.png)

`fetch`를 처음 다룰 때 자주 놓치는 부분이 있습니다. 네트워크 요청 자체가 성공하면 HTTP 상태 코드가 `404`나 `500`이어도 `fetch` Promise는 응답으로 resolve될 수 있습니다. web.dev 가이드도 `429 Too Many Requests` 같은 상태 코드는 `catch` 블록으로 가지 않으며, `Response.ok`나 `Response.status`로 직접 확인해야 한다고 설명합니다. (출처: [web.dev - Implement error handling when using the Fetch API](https://web.dev/articles/fetch-api-error-handling))

그래서 아래처럼 최소한의 래퍼를 두는 편이 낫습니다.

```js
async function requestJson(url, options = {}) {
  const response = await fetch(url, options);

  if (!response.ok) {
    const body = await response.text().catch(() => "");
    throw new HttpError(response.status, body);
  }

  return response.json();
}

class HttpError extends Error {
  constructor(status, body) {
    super(`HTTP ${status}`);
    this.name = "HttpError";
    this.status = status;
    this.body = body;
  }
}
```

이렇게 해두면 화면과 로그에서 분기하기 쉬워집니다. `401`은 로그인 만료, `403`은 권한 없음, `404`는 없는 리소스, `429`는 요청 과다, `500` 계열은 서버 문제로 다르게 다룰 수 있습니다.

주의할 점은 응답 본문 파싱도 실패할 수 있다는 것입니다. 서버가 HTML 에러 페이지를 돌려줬는데 클라이언트가 무조건 `response.json()`을 호출하면 `SyntaxError`가 납니다. 장애 화면에서는 "JSON 파싱 실패"보다 원래 HTTP 상태 코드가 더 중요할 때가 많습니다. 그래서 오류 응답은 먼저 `text()`로 읽고, 필요한 경우에만 JSON 파싱을 시도하는 방식이 운영 로그에 유리합니다.

## 재시도는 모든 실패에 붙이면 안 됩니다

재시도는 사용자를 살리는 장치가 될 수도 있고, 장애를 키우는 장치가 될 수도 있습니다. 서버가 이미 과부하인데 모든 클라이언트가 즉시 재시도하면 트래픽은 더 몰립니다. 결제나 주문 요청이 중복 처리되면 기술 문제가 아니라 고객 지원 문제가 됩니다.

HTTP 사양인 RFC 9110은 GET, HEAD 같은 safe method와 PUT, DELETE를 멱등 메서드로 설명합니다. 멱등성이란 같은 요청을 여러 번 보내도 서버에 의도한 효과가 한 번 보낸 것과 같아야 한다는 뜻입니다. 이 성질이 있는 요청은 통신 실패 후 자동 재시도를 검토하기 쉽습니다. (출처: [RFC 9110 - Idempotent Methods](https://datatracker.ietf.org/doc/html/rfc9110#section-9.2.2))

실무 기준은 이렇게 잡으면 덜 위험합니다.

- `GET`, `HEAD`: 네트워크 오류나 일시적 `5xx`에서 제한적으로 재시도한다.
- `PUT`, `DELETE`: 사양상 멱등으로 볼 수 있지만, 실제 서버 구현이 정말 같은 효과를 보장하는지 확인한다.
- `POST`: 기본적으로 자동 재시도하지 않는다. 필요하면 idempotency key나 서버 중복 방지 로직을 먼저 둔다.
- `429`: 응답에 `Retry-After`가 있으면 그 값을 존중한다. 없다면 짧은 즉시 재시도보다 백오프를 둔다.
- `400`, `401`, `403`, `404`: 입력, 인증, 권한, 리소스 문제일 가능성이 높아 반복 재시도로 해결될 확률이 낮다.

재시도 횟수도 작게 시작하는 편이 좋습니다. 보통은 1~2회만으로도 일시적 네트워크 흔들림을 흡수할 수 있습니다. 그 이상은 사용자의 대기 시간을 늘리고 서버 상태를 더 나쁘게 만들 수 있습니다. 나는 `POST`를 자동 재시도 대상에서 빼는 것을 기본값으로 잡습니다. 중복 결제 한 건을 되돌리는 비용이, 재시도로 얻는 편의보다 훨씬 크다는 걸 몇 번 겪고 나서 정한 기준입니다.

## 최소한의 재시도 래퍼는 이렇게 시작합니다

아래 코드는 완성형 네트워크 라이브러리가 아닙니다. 대신 팀 안에서 기준을 합의하기 위한 출발점으로 볼 수 있습니다. 핵심은 재시도 가능한 오류를 좁히고, 타임아웃과 HTTP 상태를 같이 다루는 것입니다.

```js
const RETRYABLE_STATUS = new Set([408, 429, 500, 502, 503, 504]);

async function fetchWithRetry(url, options = {}) {
  const {
    retries = 2,
    timeoutMs = 5000,
    method = "GET",
    ...fetchOptions
  } = options;

  const normalizedMethod = method.toUpperCase();
  const canRetryMethod = ["GET", "HEAD", "PUT", "DELETE"].includes(normalizedMethod);

  let lastError;

  for (let attempt = 0; attempt <= retries; attempt += 1) {
    try {
      const response = await fetch(url, {
        ...fetchOptions,
        method: normalizedMethod,
        signal: AbortSignal.timeout(timeoutMs),
      });

      if (response.ok) {
        return response;
      }

      if (!canRetryMethod || !RETRYABLE_STATUS.has(response.status) || attempt === retries) {
        throw new Error(`HTTP ${response.status}`);
      }
    } catch (error) {
      lastError = error;

      const isAbort = error.name === "AbortError" || error.name === "TimeoutError";
      if (!canRetryMethod || attempt === retries || isAbort) {
        throw error;
      }
    }

    await wait(300 * 2 ** attempt);
  }

  throw lastError;
}

function wait(ms) {
  return new Promise((resolve) => setTimeout(resolve, ms));
}
```

작은 팀이라면 이 정도 래퍼 하나를 공용으로 두고 시작해도 충분합니다. 트래픽이 커지고 백엔드가 여러 서비스로 갈라지기 시작하면, 그때는 재시도와 백오프를 게이트웨이나 서비스 메시 계층으로 올릴지 따로 판단해야 합니다. 처음부터 그 계층을 도입하는 건 대개 과합니다.

이 예시는 일부러 보수적으로 잡았습니다. `POST`는 제외했고, 타임아웃은 자동 재시도하지 않았습니다. 타임아웃 재시도를 넣을 수도 있지만, 서버가 실제로 요청을 처리 중인지 모르는 상태에서 같은 요청을 다시 보내는 위험이 있습니다. 특히 쓰기 요청이라면 먼저 서버의 중복 방지 정책부터 확인해야 합니다.

실서비스라면 여기에 세 가지를 더 붙이는 편이 좋습니다.

- 요청별 `requestId`를 로그에 남긴다.
- 재시도 횟수와 최종 실패 상태를 모니터링한다.
- `Retry-After` 헤더가 있을 때 대기 시간을 서버 지시에 맞춘다.

## 화면 메시지는 원인을 숨기지 말고 행동을 줘야 합니다

사용자에게 "오류가 발생했습니다"만 보여주면 다시 클릭할지, 기다릴지, 새로고침할지 알 수 없습니다. 개발자에게도 좋지 않습니다. 같은 문구로 네트워크 끊김, 인증 만료, 서버 장애, 요청 과다가 모두 묶이면 문의가 들어와도 원인을 좁히기 어렵습니다.

메시지는 기술 용어를 그대로 보여주지 않더라도 행동 기준은 줘야 합니다.

- 네트워크 오류: "인터넷 연결을 확인한 뒤 다시 시도해 주세요."
- 타임아웃: "응답이 오래 걸리고 있습니다. 잠시 뒤 다시 시도해 주세요."
- `401`: "로그인이 만료되었습니다. 다시 로그인해 주세요."
- `403`: "이 작업을 실행할 권한이 없습니다."
- `429`: "요청이 많아 잠시 제한되었습니다. 조금 뒤 다시 시도해 주세요."
- `5xx`: "서버에서 처리하지 못했습니다. 문제가 반복되면 잠시 뒤 다시 시도해 주세요."

업로드나 결제처럼 되돌리기 어려운 작업은 더 조심해야 합니다. "다시 시도" 버튼보다 "처리 상태 확인" 버튼이 먼저일 수 있습니다. 사용자가 이미 결제를 눌렀는데 화면만 늦는 상황이라면, 같은 결제를 다시 보내는 버튼은 위험합니다.

## 운영 전에 확인할 체크리스트

API 호출 래퍼를 만들었거나 기존 코드를 손볼 때는 아래 항목을 먼저 확인해 보세요.

- [ ] 기능별 타임아웃 기준이 정해져 있다.
- [ ] 사용자 취소와 타임아웃을 로그에서 구분한다.
- [ ] HTTP 상태 코드를 `response.ok` 또는 `response.status`로 확인한다.
- [ ] 오류 응답 본문 파싱 실패가 원래 상태 코드를 덮지 않는다.
- [ ] 재시도 대상 상태 코드가 제한되어 있다.
- [ ] `POST` 재시도에는 멱등키 또는 서버 중복 방지 로직이 있다.
- [ ] `429`나 `503`에서 `Retry-After` 헤더를 고려한다.
- [ ] 같은 버튼을 여러 번 눌렀을 때 중복 요청이 나가지 않거나 서버가 막는다.
- [ ] 요청별 식별자를 남겨 프론트 로그와 서버 로그를 연결할 수 있다.

이 체크리스트는 네트워크 코드를 복잡하게 만들자는 뜻이 아닙니다. 실패했을 때 "무엇을 다시 보낼 수 있고, 무엇은 확인해야 하는지"를 팀이 같은 기준으로 보자는 뜻에 가깝습니다.

## FAQ

### fetch에는 기본 타임아웃이 없나요?

브라우저와 런타임이 내부적으로 연결을 정리하는 경우는 있지만, 애플리케이션 입장에서 의미 있는 업무 타임아웃과는 다릅니다. 사용자가 몇 초까지 기다릴지, 그 뒤 어떤 메시지와 버튼을 보여줄지는 직접 정해야 합니다.

### AbortController로 취소하면 서버 처리도 무조건 멈추나요?

그렇게 단정하면 위험합니다. 클라이언트가 요청을 중단해도 서버가 이미 요청을 받아 처리 중일 수 있습니다. 그래서 쓰기 요청에서는 취소와 재시도를 가볍게 붙이면 안 됩니다. 서버가 중복 처리를 막는지, 처리 상태를 조회할 수 있는지 같이 봐야 합니다.

### 500 오류는 항상 재시도해도 되나요?

항상은 아닙니다. 일시적인 장애라면 재시도가 도움이 될 수 있지만, 서버가 과부하인 상태에서 많은 클라이언트가 동시에 재시도하면 더 악화될 수 있습니다. 횟수를 작게 제한하고, 가능하면 지수 백오프와 `Retry-After`를 고려하는 편이 안전합니다.

### axios를 쓰면 이 고민이 사라지나요?

사라지지 않습니다. 라이브러리가 타임아웃 옵션이나 인터셉터를 편하게 제공할 수는 있지만, 재시도해도 되는 요청인지와 사용자에게 어떤 행동을 줄지는 애플리케이션이 정해야 합니다. 도구를 바꿔도 멱등성 문제는 남습니다.

### POST도 idempotency key가 있으면 자동 재시도해도 되나요?

서버가 같은 키의 중복 요청을 같은 작업으로 인식하고, 이미 처리된 결과를 안전하게 돌려준다는 계약이 있을 때만 검토할 수 있습니다. 키만 보내고 서버가 저장·검증하지 않으면 아무 의미가 없습니다.

## 장애 때 꺼내 보는 목록

API 호출이 멈추거나 실패했을 때, 재시도 횟수부터 늘리기 전에 이 목록을 순서대로 짚습니다.

- **타임아웃**: 사용자가 기다릴 수 있는 시간을 애플리케이션이 정했는가. 지연과 실패를 구분하고 있는가.
- **취소**: 더 이상 필요 없는 요청을 정리하고 있는가. abort를 장애 로그와 섞지 않는가.
- **HTTP 오류**: 응답은 받았지만 원하는 결과가 아닌 상태를 `response.ok`로 확인하는가. `401`, `403`, `404`, `429`, `5xx`를 다르게 다루는가.
- **재시도**: 같은 요청을 다시 보내도 되는지 확인한 뒤에 붙였는가. `POST`는 자동 재시도에서 빼 뒀는가.

작게 시작하려면 세 가지만 먼저 해도 됩니다. `AbortSignal.timeout()`으로 업무 타임아웃을 정하고, `response.ok`를 확인하고, `POST` 자동 재시도를 막으세요. 이 세 가지가 잡히면 로딩이 끝나지 않는 화면, 원인을 알 수 없는 실패 로그, 중복 요청 사고를 꽤 많이 줄일 수 있습니다.

## 출처

- [MDN - AbortSignal: timeout() static method](https://developer.mozilla.org/en-US/docs/Web/API/AbortSignal/timeout_static)
- [Node.js Docs - Global objects](https://nodejs.org/dist/latest/docs/api/globals.html)
- [web.dev - Implement error handling when using the Fetch API](https://web.dev/articles/fetch-api-error-handling)
- [RFC 9110 - HTTP Semantics, Idempotent Methods](https://datatracker.ietf.org/doc/html/rfc9110#section-9.2.2)
