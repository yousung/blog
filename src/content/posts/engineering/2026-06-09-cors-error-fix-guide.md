---
title: "CORS 에러, 프론트가 아니라 서버에서 풀어야 하는 이유"
slug: "cors-error-fix-guide"
ogImage: "/images/posts/cors-error-fix-guide/hero.png"
author: "감성개발자"
date: "2026-06-09"
summary: "콘솔에 뜨는 CORS 에러를 프론트엔드 코드만 고쳐서 해결하려다 시간을 버리는 경우가 많다. CORS가 누구를 막는 규칙인지, 왜 서버가 응답 헤더로 허용해야 하는지, preflight와 쿠키 인증까지 상황별로 어떻게 풀어야 하는지 짚어봤다."
oneLineSummary: "CORS 에러는 브라우저가 만드는 제약이라 프론트가 아니라 서버 응답 헤더에서 풀어야 한다. 어디를 고칠지부터 잡는 게 순서다."
tags: [HTTP, API, js-css-javascript, tip]
status: "published"
---

![서로 다른 출처 사이 CORS 허용 관문을 표현한 기술 일러스트](/images/posts/cors-error-fix-guide/hero.png)

```text
Access to fetch at 'https://api.example.com/users' from origin
'https://app.example.com' has been blocked by CORS policy:
No 'Access-Control-Allow-Origin' header is present on the requested resource.
```

이 빨간 글씨를 처음 만나면 거의 항상 같은 착각으로 시작합니다. 콘솔에 떴으니 내 프론트엔드 코드가 잘못됐다고 생각하고, `fetch` 옵션을 이리저리 바꿔보는 겁니다.

그런데 저 메시지를 다시 읽어 보면 답이 이미 들어 있습니다. `No 'Access-Control-Allow-Origin' header is present on the requested resource`. 요청받은 자원, 즉 서버 응답에 허용 헤더가 없다는 말입니다. 프론트가 뭘 잘못한 게 아니라 서버가 허락 표시를 안 붙인 겁니다. 어디를 고쳐야 하는지를 잘못 잡으면 멀쩡한 코드를 몇 시간씩 헛으로 만지게 되는데, 팀에 주니어가 들어올 때마다 이 함정을 가장 먼저 짚어주는 것도 그래서입니다.

최종 업데이트: 2026-06-09

## CORS는 누구를 막는 규칙일까?

CORS는 Cross-Origin Resource Sharing의 줄임말입니다. 이름 그대로 "다른 출처(origin) 간 자원 공유"를 다루는 규칙인데, 핵심은 이게 **공격자를 막는 보안 장치가 아니라 브라우저가 사용자를 보호하려고 거는 제약**이라는 점입니다.

여기서 출처(origin)는 프로토콜, 도메인, 포트 세 가지의 조합입니다. `https://app.example.com`과 `https://api.example.com`은 도메인이 다르니 다른 출처고, `http://localhost:3000`과 `http://localhost:8080`은 포트가 다르니 다른 출처입니다. 셋 중 하나만 달라도 브라우저는 "교차 출처 요청"으로 봅니다. 로컬 개발에서 프론트는 3000번, API 서버는 8080번에 띄워놓고 CORS 에러를 처음 만나는 이유가 바로 이것입니다.

중요한 건 차단의 주체입니다. 서버는 요청을 정상적으로 받아 응답까지 보냅니다. 그 응답이 브라우저에 도착했을 때, 브라우저가 "이 응답에 내 출처를 허용한다는 표시가 없네"라고 판단하면 자바스크립트가 응답 내용을 읽지 못하게 막습니다. 그래서 서버 로그에는 200 OK가 찍혀 있는데 프론트에서는 에러가 나는, 처음엔 이해 안 되는 상황이 생깁니다. (출처: [MDN - Cross-Origin Resource Sharing (CORS)](https://developer.mozilla.org/en-US/docs/Web/HTTP/Guides/CORS))

## 왜 프론트 코드를 고쳐도 안 풀릴까?

이 지점을 받아들이면 디버깅 방향이 정리됩니다. 응답을 허용할지 말지는 응답을 보내는 쪽, 즉 서버가 헤더로 선언합니다. 브라우저는 그 헤더를 읽고 판단할 뿐입니다. 클라이언트 자바스크립트는 이 판단에 끼어들 수 없습니다.

그래서 `fetch`의 `mode`를 바꾸거나, 헤더를 추가하거나, 옵션을 조합해도 근본 해결이 안 됩니다. `mode: 'no-cors'` 같은 옵션이 에러를 사라지게 만드는 것처럼 보일 때가 있는데, 이건 해결이 아니라 응답 본문을 아예 읽을 수 없는 불투명(opaque) 응답으로 바꿔버리는 것입니다. 에러는 없어졌지만 데이터도 못 받는, 사실상 더 나쁜 상태가 됩니다.

정리하면 이렇습니다. **CORS 에러를 보면 가장 먼저 의심할 곳은 내 프론트 코드가 아니라 서버 응답 헤더입니다.** 내가 그 서버를 고칠 수 있는지부터 따지는 게 순서입니다.

## 가장 흔한 해결: 서버 응답 헤더 추가

내가 서버를 제어할 수 있다면 해결은 단순합니다. 응답에 허용 출처를 명시하는 헤더를 붙이면 됩니다.

```http
Access-Control-Allow-Origin: https://app.example.com
```

이 한 줄이 "이 출처에서 온 요청은 응답을 읽어도 된다"는 선언입니다. 프레임워크별로 적용 위치만 다를 뿐 원리는 같습니다. Express라면 `cors` 미들웨어, Spring이라면 `@CrossOrigin`이나 전역 CORS 설정, Nginx 앞단이라면 `add_header`로 붙입니다.

여기서 자주 하는 실수가 `Access-Control-Allow-Origin: *` 와일드카드를 습관적으로 쓰는 겁니다. 모든 출처를 다 허용한다는 뜻이라 당장은 편하지만, 두 가지 문제가 있습니다.

- 공개 API가 아니라면 굳이 전 세계에 응답을 열어줄 이유가 없습니다.
- 쿠키나 인증 토큰을 함께 보내는 요청에서는 `*`를 쓸 수 없습니다. 이건 선택이 아니라 사양상 금지입니다.

저는 운영 환경에서는 `*`를 기본적으로 쓰지 않습니다. 허용할 출처를 정확히 적거나, 허용 목록을 두고 요청 출처가 목록에 있을 때만 그 출처를 헤더에 돌려주는 방식으로 둡니다.

## preflight 요청이 따로 뜨는 경우

![실제 본 요청 전에 작은 사전 확인 요청이 먼저 건너가 서버의 허용 조건과 맞춰지는 preflight 과정을 나타낸 도식. 큰 본 요청은 승인 전까지 차단막 뒤에서 대기하고, 조건 하나가 맞지 않아 강조된 지점에서 사전 확인 단계가 막히는 실패 모드를 형태 대비로 보여준다.](/images/posts/cors-error-fix-guide/figure.png)

개발자 도구 네트워크 탭을 보면 실제 요청 전에 `OPTIONS` 메서드 요청이 한 번 더 가는 걸 볼 때가 있습니다. 이게 preflight(사전 확인) 요청입니다. 브라우저가 "이런 요청을 보내도 되는지" 서버에 먼저 물어보는 절차입니다.

모든 요청이 preflight를 거치진 않습니다. 단순 요청(GET, 일부 POST 등 특정 조건을 만족하는 요청)은 바로 갑니다. 하지만 다음 같은 경우엔 브라우저가 먼저 `OPTIONS`로 확인합니다.

- `PUT`, `DELETE`, `PATCH` 같은 메서드를 쓸 때
- `Content-Type: application/json`처럼 단순 요청 조건을 벗어나는 헤더를 보낼 때
- `Authorization` 같은 커스텀 헤더를 붙일 때

(출처: [MDN - Preflight request](https://developer.mozilla.org/en-US/docs/Glossary/Preflight_request))

이때는 본 요청뿐 아니라 `OPTIONS` 요청에 대한 응답도 CORS 헤더를 제대로 갖춰야 합니다. 자주 빠뜨리는 헤더가 이 둘입니다.

```http
Access-Control-Allow-Methods: GET, POST, PUT, DELETE
Access-Control-Allow-Headers: Content-Type, Authorization
```

프론트에서 `Authorization` 헤더를 붙였는데 서버의 `Access-Control-Allow-Headers`에 그 헤더가 없으면 preflight 단계에서 막힙니다. "GET은 되는데 POST만 안 된다", "헤더 하나 추가했더니 갑자기 막혔다" 같은 증상은 거의 이 preflight 설정 누락입니다. 본 요청 응답만 고치고 `OPTIONS` 응답을 빠뜨렸는지 확인해 보세요.

## 쿠키·인증 정보를 함께 보낼 때

세션 쿠키나 인증 정보를 교차 출처 요청에 실어 보내려면 양쪽을 모두 맞춰야 합니다. 한쪽만 설정하면 조용히 실패하거나 에러가 납니다.

프론트는 요청에 자격 증명을 포함하겠다고 명시해야 합니다.

```js
fetch("https://api.example.com/me", {
  credentials: "include",
});
```

서버는 자격 증명을 허용한다고 응답하고, 이때 허용 출처를 와일드카드가 아니라 정확한 값으로 돌려줘야 합니다.

```http
Access-Control-Allow-Origin: https://app.example.com
Access-Control-Allow-Credentials: true
```

다시 강조하면, `Access-Control-Allow-Credentials: true`와 `Access-Control-Allow-Origin: *`는 함께 쓸 수 없습니다. 쿠키를 보내야 한다면 출처를 반드시 구체적으로 적어야 합니다. (출처: [MDN - Access-Control-Allow-Credentials](https://developer.mozilla.org/en-US/docs/Web/HTTP/Reference/Headers/Access-Control-Allow-Credentials))

## 서버를 못 고칠 때: 임시방편과 그 한계

문제는 내가 그 서버의 주인이 아닐 때입니다. 외부 API를 호출하는데 그쪽이 내 출처를 허용해주지 않는 경우, 브라우저에서 직접 부르는 건 막힙니다. 이때 선택지는 나뉩니다.

**개발 중 임시방편(운영 금지):**

- 브라우저 확장프로그램으로 CORS 검사를 끄는 방법이 있습니다. 내 브라우저에서만 동작하니 다른 사용자에겐 그대로 막힙니다. 잠깐 확인용으로만 씁니다.
- 개발 서버의 프록시 기능(예: Vite, webpack dev server의 proxy 설정)으로 같은 출처처럼 보이게 우회할 수 있습니다. 로컬 개발에는 편하지만 빌드 결과물에는 적용되지 않습니다.

**운영에서 쓸 수 있는 정공법:**

- 내 백엔드에 중계 엔드포인트를 두는 방법입니다. 프론트는 내 서버를 부르고, 내 서버가 외부 API를 호출해 결과를 돌려줍니다. 서버 간 통신에는 CORS 제약이 없으니 깔끔하게 풀립니다.
- 이 방식은 외부 API 키를 프론트에 노출하지 않아도 된다는 장점도 같이 따라옵니다.

임시방편을 운영에 그대로 올리면 안 되는 이유는 분명합니다. 확장프로그램 우회는 내 환경에서만 통하고, "CORS를 끄는" 식의 설정을 운영 서버에 무분별하게 적용하면 보호 장치를 스스로 걷어내는 셈이 됩니다.

## 증상별 빠른 점검 체크리스트

CORS 에러를 만났을 때 순서대로 확인하면 헤매는 시간을 줄일 수 있습니다.

- [ ] 에러 메시지에 적힌 차단된 출처와 요청 대상 URL이 정말 다른 출처인지 확인했는가? (프로토콜·도메인·포트 중 하나라도 다르면 교차 출처)
- [ ] 이 서버를 내가 고칠 수 있는가, 아니면 외부 API인가?
- [ ] 서버 응답에 `Access-Control-Allow-Origin`이 있는가? (네트워크 탭 응답 헤더에서 확인)
- [ ] 네트워크 탭에 `OPTIONS` 요청이 따로 있는가? 있다면 그 응답에도 CORS 헤더가 붙어 있는가?
- [ ] 쿠키·인증을 보낸다면 프론트에 `credentials: 'include'`, 서버에 `Allow-Credentials: true` + 구체적 출처가 둘 다 있는가?
- [ ] 와일드카드 `*`와 자격 증명을 동시에 쓰고 있지 않은가?

## 자주 묻는 질문

**Q. 서버 로그엔 200이 찍히는데 왜 프론트는 에러가 나나요?**
서버는 요청을 정상 처리해 응답을 보냅니다. 그 응답을 받은 브라우저가 허용 헤더가 없다고 판단해 자바스크립트의 접근을 차단한 것입니다. 차단 주체가 서버가 아니라 브라우저라서 생기는 현상입니다.

**Q. `mode: 'no-cors'`를 쓰면 에러가 사라지던데 이걸로 해결하면 안 되나요?**
에러는 사라지지만 응답 본문을 읽을 수 없는 불투명 응답이 됩니다. 데이터를 받아야 하는 상황이라면 해결이 아니라 더 나쁜 상태입니다.

**Q. Postman에서는 잘 되는데 브라우저에서만 막혀요.**
CORS는 브라우저의 규칙입니다. Postman이나 curl, 서버 간 통신에는 적용되지 않습니다. 그래서 "도구로는 되는데 브라우저만 안 되는" 상황이 정상입니다.

**Q. 와일드카드 `*`를 쓰면 다 해결되지 않나요?**
공개 API가 아니라면 권장하지 않고, 쿠키·인증을 함께 보내는 요청에서는 사양상 쓸 수 없습니다. 그 경우 허용할 출처를 구체적으로 적어야 합니다.

## 내가 따르는 순서

앞의 체크리스트가 "무엇을 볼지"라면, 이건 CORS 에러를 만났을 때 내가 실제로 손을 움직이는 순서입니다. 위에서부터 차례로 내려가면 대부분 중간에 원인이 잡힙니다.

1. 에러 메시지에 적힌 두 출처를 나란히 읽습니다. 프로토콜·도메인·포트 중 뭐가 달라서 교차 출처가 됐는지부터 확인합니다.
2. 그 서버가 내 것인지 외부 것인지 가릅니다. 여기서 이후 경로가 완전히 갈립니다.
3. 내 서버라면 응답 헤더에 `Access-Control-Allow-Origin`이 붙어 있는지 네트워크 탭에서 봅니다. 없으면 붙이는 게 끝입니다.
4. 네트워크 탭에 `OPTIONS` 요청이 따로 떠 있으면, 본 요청이 아니라 그 preflight 응답의 `Allow-Methods`·`Allow-Headers`를 먼저 의심합니다.
5. 쿠키나 인증을 싣는다면 프론트의 `credentials: 'include'`와 서버의 `Allow-Credentials: true` + 구체적 출처가 둘 다 있는지 맞춰봅니다.
6. 서버가 외부 API라 손댈 수 없으면, `fetch` 옵션을 붙들고 있지 말고 내 백엔드에 중계 엔드포인트를 둡니다.

정리하면 하나입니다. `fetch` 옵션을 바꾸는 것으로 풀리는 문제가 아니라는 점을 처음에 받아들이는 게, 결국 가장 빠른 길입니다.

## 출처

- [MDN - Cross-Origin Resource Sharing (CORS)](https://developer.mozilla.org/en-US/docs/Web/HTTP/Guides/CORS)
- [MDN - Preflight request](https://developer.mozilla.org/en-US/docs/Glossary/Preflight_request)
- [MDN - Access-Control-Allow-Credentials](https://developer.mozilla.org/en-US/docs/Web/HTTP/Reference/Headers/Access-Control-Allow-Credentials)
- [MDN - Reason: CORS header 'Access-Control-Allow-Origin' missing](https://developer.mozilla.org/en-US/docs/Web/HTTP/Guides/CORS/Errors/CORSMissingAllowOrigin)
