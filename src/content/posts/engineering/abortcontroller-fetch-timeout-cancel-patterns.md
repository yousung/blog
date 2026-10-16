---
title: "fetch 요청 취소와 타임아웃, AbortController 하나로 끝내기 (15년차 개발자의 실전 패턴과 함정 3가지)"
slug: "abortcontroller-fetch-timeout-cancel-patterns"
author: "감성개발자"
date: "2026-10-16"
summary: "검색 자동완성 레이스 컨디션부터 fetch 타임아웃, 이벤트 리스너 일괄 정리까지 AbortController 하나로 해결하는 실전 패턴 정리. AbortSignal.timeout()과 AbortSignal.any() 사용법, TimeoutError와 AbortError 구분, 실무에서 직접 밟은 함정 3가지와 체크리스트를 담았습니다."
oneLineSummary: "취소를 제대로 다루는 코드는 티가 안 나지만, 없으면 재현 안 되는 버그로 돌아온다"
tags: ["JavaScript", "웹개발", "프론트엔드", "개발팁", "동시성"]
status: "draft"
tistoryUrl: "https://lovizu.tistory.com/entry/abortcontroller-fetch-timeout-cancel-patterns"
---

지난달에 사내 어드민 화면에서 묘한 버그 제보를 받았다. 검색창에 "김"까지 치고 "김철수"를 마저 입력했는데, 결과 목록에는 "김"으로 검색한 결과가 떠 있다는 것이다. 재현도 잘 안 되고, 새로고침하면 멀쩡하다. 네트워크 탭을 한참 들여다보고서야 원인을 찾았다. 먼저 보낸 "김" 요청이 늦게 도착해서, 나중에 보낸 "김철수" 응답을 덮어쓰고 있었다. 전형적인 레이스 컨디션이다.

이 문제의 정석 해법이 AbortController다. 이름은 다들 안다. 그런데 15년 일하면서 느낀 건, 이걸 "fetch 취소하는 거"라고만 알고 실제로는 안 쓰는 개발자가 생각보다 많다는 점이다. 요즘 자바스크립트에서 AbortController는 요청 취소를 넘어 타임아웃, 이벤트 리스너 정리까지 커버하는 범용 취소 도구가 됐다. 오늘은 내가 실무에서 실제로 쓰는 패턴 위주로 정리한다.

## 기본기: 컨트롤러 하나, 시그널 하나, 그리고 일회용이라는 사실

구조는 단순하다. AbortController를 만들면 signal이 딸려 나오고, 이 signal을 비동기 작업에 넘겨둔 뒤 필요할 때 abort()를 호출하면 작업이 중단된다.

```javascript
const controller = new AbortController();

fetch('/api/users', { signal: controller.signal })
  .then((res) => res.json())
  .catch((err) => {
    if (err.name === 'AbortError') {
      // 우리가 의도적으로 취소한 것. 에러가 아니다.
      return;
    }
    throw err;
  });

// 어딘가에서, 더 이상 이 응답이 필요 없어졌을 때
controller.abort();
```

여기서 두 가지만 기억하면 된다. 첫째, **컨트롤러는 일회용이다.** 한 번 abort()한 컨트롤러는 되돌릴 수 없고, 그 signal로 새 fetch를 보내면 시작하자마자 즉시 거부된다. 다시 요청하려면 컨트롤러를 새로 만들어야 한다. 둘째, **취소는 에러가 아니라 정상 흐름이다.** catch에서 AbortError를 구분하지 않으면 멀쩡한 취소가 에러 로그에 쌓인다. 이건 뒤에서 함정 편으로 다시 다룬다.

## fetch 타임아웃, 아직도 setTimeout으로 직접 짜고 있다면

fetch에는 타임아웃 옵션이 없다. 그래서 다들 한 번쯤 이런 코드를 짜봤을 것이다. 나도 짰다.

```javascript
// 예전 방식: 직접 조립
const controller = new AbortController();
const timerId = setTimeout(() => controller.abort(), 5000);

try {
  const res = await fetch(url, { signal: controller.signal });
  return await res.json();
} finally {
  clearTimeout(timerId); // 이거 빼먹으면 타이머가 샌다
}
```

동작은 한다. 그런데 clearTimeout을 빼먹기 쉽고, 파일마다 복붙되면서 조금씩 다른 버전이 늘어난다. 지금은 표준 한 줄로 끝난다.

```javascript
try {
  const res = await fetch(url, { signal: AbortSignal.timeout(5000) });
  return await res.json();
} catch (err) {
  if (err.name === 'TimeoutError') {
    // 5초 안에 응답이 안 온 것
  } else if (err.name === 'AbortError') {
    // 사용자 취소(탭 닫기, 중지 버튼 등)
  }
  throw err;
}
```

AbortSignal.timeout()은 지정 시간이 지나면 알아서 abort되는 시그널을 만들어준다. MDN 기준으로 2024년 4월부터 Baseline, 즉 주요 브라우저 전부에서 쓸 수 있다. 사내 서비스처럼 브라우저를 통제할 수 있는 환경이면 고민할 이유가 없고, 구형 브라우저 지원이 필요하면 위의 setTimeout 조립 방식을 유틸 함수 하나로 묶어두면 된다.

실무 포인트가 하나 있다. 타임아웃으로 중단되면 TimeoutError, 사용자가 중단하면 AbortError로 **에러 이름이 다르다.** 타임아웃은 재시도하거나 "네트워크가 느립니다"를 띄워야 할 상황이고, 사용자 취소는 조용히 넘어가야 할 상황이다. 이 둘을 구분하지 않으면 UX가 묘하게 어긋난다. 참고로 초기 구현에서는 일부 브라우저가 타임아웃인데도 AbortError를 던지는 버그가 있었는데, 요즘 버전에서는 스펙대로 동작하니 에러 이름으로 분기해도 된다.

또 하나, 이 타임아웃은 경과 시간이 아니라 "활성 시간" 기준이다. 탭이 bfcache에 들어가거나 워커가 일시 중단되면 타이머도 같이 멈춘다. 사용자가 다른 탭에 갔다 왔다고 애꿎은 타임아웃이 터지지 않는다는 뜻이라, 대부분의 경우 오히려 장점이다.

## 검색 자동완성 레이스 컨디션, 이렇게 끊는다

서두의 버그로 돌아가자. 입력할 때마다 요청을 보내는 자동완성에서, 응답 도착 순서는 요청 순서를 보장하지 않는다. 해법은 간단하다. **새 요청을 보내기 전에 이전 요청을 취소한다.**

```javascript
let currentController = null;

async function search(keyword) {
  // 이전 요청이 날아가 있으면 끊는다
  currentController?.abort();
  currentController = new AbortController();

  try {
    const res = await fetch(
      '/api/search?q=' + encodeURIComponent(keyword),
      { signal: currentController.signal },
    );
    renderResults(await res.json());
  } catch (err) {
    if (err.name === 'AbortError') return; // 취소는 정상 흐름
    showErrorToast();
  }
}
```

디바운스만 걸면 되지 않냐는 질문을 가끔 받는데, 디바운스는 요청 횟수를 줄여줄 뿐 이미 날아간 요청의 도착 순서는 어쩌지 못한다. 디바운스로 횟수를 줄이고, abort로 이전 요청을 끊는 것까지 해야 자동완성이 완성된다. 서버 입장에서도 어차피 버릴 응답을 끝까지 만들어 보내지 않아도 되니 부하가 준다.

## AbortSignal.any(): 타임아웃과 수동 취소를 한 줄로 합치기

실전에서는 "5초 타임아웃도 걸고, 사용자가 취소 버튼을 눌러도 끊고 싶다"는 요구가 흔하다. 예전에는 이걸 직접 조립하느라 코드가 지저분해졌는데, 지금은 AbortSignal.any()로 시그널 여러 개를 하나로 합칠 수 있다. 합쳐진 시그널은 입력 중 **먼저 abort되는 쪽**을 따라간다.

```javascript
const userCancel = new AbortController();
cancelButton.addEventListener('click', () => userCancel.abort());

const res = await fetch(url, {
  signal: AbortSignal.any([
    userCancel.signal,          // 사용자가 취소하거나
    AbortSignal.timeout(5000),  // 5초가 지나거나
  ]),
});
```

에러의 reason도 먼저 abort된 시그널의 것을 따라오기 때문에, 위에서 설명한 TimeoutError/AbortError 분기가 그대로 동작한다. any()는 timeout()보다 조금 늦은 2024년 3월부터 Baseline이고, caniuse 기준 글로벌 지원율이 92%를 넘겼다. 새로 짜는 코드라면 기본으로 깔고 가도 된다고 본다.

## 의외의 활용처: 이벤트 리스너를 한 방에 정리하기

내가 AbortController를 가장 요긴하게 쓰는 곳은 사실 fetch가 아니라 이벤트 리스너 정리다. addEventListener 옵션에 signal을 넘기면, 그 시그널이 abort될 때 **리스너가 자동으로 제거된다.** removeEventListener를 하나하나 짝 맞춰 호출할 필요가 없다.

```javascript
const controller = new AbortController();
const { signal } = controller;

window.addEventListener('resize', onResize, { signal });
window.addEventListener('scroll', onScroll, { signal });
document.addEventListener('keydown', onKeydown, { signal });

// 컴포넌트가 사라질 때 이 한 줄이면 셋 다 해제된다
controller.abort();
```

리액트 useEffect와 궁합이 특히 좋다. 클린업에서 abort() 한 번이면 리스너 해제와 진행 중이던 fetch 취소가 같이 끝난다. 언마운트된 컴포넌트에 setState를 호출해서 나오는 경고의 상당수가 이 패턴으로 사라진다.

```javascript
useEffect(() => {
  const controller = new AbortController();
  const { signal } = controller;

  window.addEventListener('resize', onResize, { signal });

  fetch('/api/dashboard', { signal })
    .then((res) => res.json())
    .then(setData)
    .catch((err) => {
      if (err.name !== 'AbortError') setError(err);
    });

  return () => controller.abort(); // 리스너 + 요청 동시 정리
}, []);
```

removeEventListener가 안 먹어서 디버깅해본 적이 있는 사람은 안다. bind나 화살표 함수 때문에 참조가 달라서 해제가 안 되는, 그 지긋지긋한 버그가 이 패턴에서는 원천적으로 발생하지 않는다. 같은 함수 참조를 유지할 필요 자체가 없기 때문이다.

## 실무에서 밟았던 함정 3가지

**함정 1. 취소를 에러로 로깅해서 모니터링을 오염시킨다.** 자동완성에 abort 패턴을 넣고 나서 에러 트래커에 AbortError가 수천 건 쌓인 적이 있다. 사용자가 타이핑할 때마다 "에러"가 찍히니 진짜 장애가 묻힌다. catch에서 err.name === 'AbortError'는 반드시 걸러서 조용히 리턴하고, TimeoutError는 별도 지표로 집계하는 게 좋다. 타임아웃 비율이 올라가는 건 백엔드나 네트워크가 느려지고 있다는 조기 신호라서, 오히려 따로 보는 가치가 있다.

**함정 2. abort된 컨트롤러를 재사용한다.** 모달을 닫을 때 abort하고, 다시 열 때 같은 컨트롤러로 fetch를 보내면 요청이 시작도 못 하고 즉시 거부된다. 한 번 abort된 시그널은 영원히 aborted 상태다. "열 때마다 새 컨트롤러"를 원칙으로 잡으면 깔끔하다. 나는 아예 컨트롤러 생성과 abort를 묶은 헬퍼를 만들어두고 쓴다.

**함정 3. 시그널에 직접 붙인 abort 리스너를 안 치운다.** AbortSignal.timeout()으로 만든 시그널은 타이머가 끝날 때까지, 거기에 abort 리스너가 붙어 있으면 가비지 컬렉션이 안 된다. MDN도 작업이 끝나면 리스너를 직접 제거하라고 명시하고 있다. 긴 타임아웃(수 분 단위)을 자주 만드는 코드라면 finally에서 removeEventListener를 챙기거나, once: true 옵션을 붙여두는 습관이 필요하다. fetch가 내부적으로 쓰는 처리는 알아서 정리되니, 어디까지나 "내가 직접 addEventListener한 것"이 대상이다.

하나 더 보태면, 이 모든 패턴은 브라우저 전용이 아니다. Node.js 18 이후의 내장 fetch도 같은 시그널을 받고, setTimeout 타이머나 스트림 같은 Node API도 signal 옵션을 지원한다. 서버에서 외부 API를 호출할 때 AbortSignal.timeout()으로 상한을 거는 건 브라우저에서와 똑같이 동작한다.

## 정리: 내 체크리스트

- 외부로 나가는 fetch에는 기본으로 AbortSignal.timeout()을 건다 (사내 기준 5~10초)
- 입력에 반응하는 요청(검색, 필터)은 새 요청 전에 이전 컨트롤러를 abort한다
- 타임아웃 + 수동 취소가 둘 다 필요하면 AbortSignal.any()로 합친다
- catch에서 AbortError는 조용히 리턴, TimeoutError는 별도 집계
- abort한 컨트롤러는 버리고 새로 만든다 (일회용)
- 여러 이벤트 리스너는 { signal } 옵션으로 등록하고 abort() 한 번으로 정리한다
- 리액트 useEffect 클린업은 controller.abort() 하나로 리스너와 요청을 같이 끊는다

취소를 제대로 다루는 코드는 티가 잘 안 난다. 잘 돌아갈 때는 아무도 모르고, 없을 때만 "검색 결과가 이상해요" 같은 재현 안 되는 제보로 돌아온다. 그래서 더 기본기에 가깝다고 생각한다. 다음 JS 글에서는 이번에 다루지 못한 스트리밍 응답 처리(ReadableStream)와 fetch 재시도 전략을 묶어서 정리해볼 생각이다.

이미지 출처: Unsplash (Gabriel Heinzer, William Warby, Arnold Francisca)
