---
title: "JWT를 localStorage에 넣어도 될까? 쿠키와 세션을 고르는 기준"
slug: "jwt-localstorage-vs-cookie"
ogImage: "/images/posts/jwt-localstorage-vs-cookie/hero.png"
author: "BLOA Team"
date: "2026-06-23"
summary: "브라우저 로그인 상태를 설계할 때 JWT를 localStorage에 저장할지, HttpOnly 쿠키나 세션으로 처리할지 헷갈리는 경우가 많다. JWT 자체의 역할, localStorage의 한계, 쿠키 옵션과 CSRF 대응까지 실무 기준으로 정리했다."
oneLineSummary: "JWT는 저장 전략이 아니라 토큰 형식이다. 브라우저 웹앱 기본값은 대개 HttpOnly 쿠키가 더 낫고, localStorage는 예외가 분명할 때만 쓰는 편이 안전하다."
tags: [보안, HTTP, API, 개발팁]
status: "published"
---

![브라우저 토큰 보안 경계를 표현한 기술 일러스트](/images/posts/jwt-localstorage-vs-cookie/hero.png)

로그인 붙인 웹앱을 만들다 보면 거의 비슷한 순간이 옵니다. 백엔드는 JWT를 발급했고, 프론트는 그 값을 어디엔가 넣어야 합니다. 이때 가장 많이 나오는 말이 "요즘은 JWT니까 localStorage에 넣으면 되지 않나?"인데, 여기서 한 번 방향을 잘못 잡으면 나중에 XSS, 로그아웃 처리, CSRF, 서브도메인 쿠키 범위 같은 문제가 한꺼번에 따라옵니다.

결론부터 말하면, **브라우저에서 돌아가는 일반적인 웹앱이라면 인증 상태의 기본값은 `HttpOnly` 쿠키나 서버 세션 쪽이 더 안전한 경우가 많습니다.** JWT는 저장소가 아니라 토큰 형식이고, localStorage는 편하지만 자바스크립트가 읽을 수 있다는 약점이 분명하기 때문입니다. 반대로 브라우저가 아닌 모바일 앱, 별도 API 클라이언트, 정말로 헤더 제어가 필요한 구조라면 다른 선택이 맞을 수 있습니다.

> 요약 박스
>
> - JWT는 "로그인 저장 방식"이 아니라, 클레임을 담아 전송하는 토큰 형식입니다. RFC 7519도 JWT를 HTTP Authorization 헤더나 URI 파라미터 같은 공간 제약 환경에서 쓰는 claims format으로 설명합니다. (출처: [RFC 7519](https://datatracker.ietf.org/doc/html/rfc7519))
> - OWASP는 session identifier를 localStorage에 저장하지 말라고 권합니다. 자바스크립트로 항상 접근 가능하고, XSS 한 번이면 그대로 털릴 수 있기 때문입니다. (출처: [OWASP HTML5 Security Cheat Sheet](https://cheatsheetseries.owasp.org/cheatsheets/HTML5_Security_Cheat_Sheet.html))
> - 쿠키를 쓴다면 `HttpOnly`, `Secure`, `SameSite` 같은 속성을 같이 설계해야 합니다. 특히 `HttpOnly`는 자바스크립트에서 쿠키를 읽지 못하게 해 XSS 피해를 줄이는 데 도움이 됩니다. (출처: [MDN Set-Cookie](https://developer.mozilla.org/en-US/docs/Web/HTTP/Reference/Headers/Set-Cookie), [MDN Secure cookie configuration](https://developer.mozilla.org/en-US/docs/Web/Security/Practical_implementation_guides/Cookies))

최종 업데이트: 2026-06-23

이 글의 권장 기준은 RFC, MDN, OWASP 문서를 바탕으로 정리한 실무 판단입니다. 프레임워크 기본값과 배포 환경에 따라 구현 방식은 달라질 수 있지만, 브라우저 인증을 설계할 때 어디서 위험이 생기는지는 크게 다르지 않습니다.

## 먼저 정리할 것: JWT는 저장 전략이 아니라 토큰 형식입니다

혼동이 가장 많이 생기는 지점이 여기입니다. JWT를 쓰면 자동으로 "무상태 인증 + localStorage 저장"까지 따라온다고 생각하는 경우가 많습니다. 그런데 RFC 7519는 JWT를 **클레임을 전달하는 compact format**으로 정의할 뿐, 브라우저 어디에 저장하라고 말하지는 않습니다. 즉, JWT는 저장소가 아니라 포맷입니다. (출처: [RFC 7519](https://datatracker.ietf.org/doc/html/rfc7519))

그래서 아래 조합은 모두 가능합니다.

| 조합 | 가능 여부 | 실무 메모 |
| --- | --- | --- |
| JWT + HttpOnly 쿠키 | 가능 | 브라우저 웹앱에서 가장 많이 권하는 쪽 |
| JWT + Authorization 헤더 + 메모리 저장 | 가능 | SPA, 네이티브 앱, BFF 없는 구조에서 종종 사용 |
| 서버 세션 ID + HttpOnly 쿠키 | 가능 | 가장 전통적이고 운영이 단순한 편 |
| JWT + localStorage | 가능 | 가능은 하지만 위험과 운영비용을 같이 감수해야 함 |

즉 질문은 "JWT를 쓸까 말까"보다 **브라우저가 인증 값을 어떻게 보관하고 언제 전송할지**를 먼저 정하는 쪽이 맞습니다.

## localStorage가 편해 보여도 기본값으로 잡기 어려운 이유

localStorage는 다루기 쉽습니다. 새로고침 뒤에도 값이 남고, `Authorization: Bearer ...` 헤더에 붙이기도 간단합니다. 그래서 초기에 구현 속도만 보면 매력적입니다.

문제는 브라우저 보안 모델입니다. MDN에 따르면 `localStorage`는 같은 origin의 모든 문서가 공유하고, 브라우저를 닫아도 유지됩니다. (출처: [MDN Web Storage API](https://developer.mozilla.org/en-US/docs/Web/API/Web_Storage_API)) 그리고 OWASP는 이 저장소에 session identifier를 넣지 말라고 분명히 권합니다. 이유는 단순합니다. **자바스크립트가 읽을 수 있기 때문입니다.** XSS가 한 번 나면 토큰 탈취가 바로 이어질 수 있습니다. (출처: [OWASP HTML5 Security Cheat Sheet](https://cheatsheetseries.owasp.org/cheatsheets/HTML5_Security_Cheat_Sheet.html))

여기서 중요한 건 "XSS가 나면 어차피 끝 아닌가?"라는 반응을 너무 쉽게 받아들이지 않는 겁니다. 피해 범위는 저장 방식에 따라 달라집니다. `HttpOnly` 쿠키는 자바스크립트에서 값을 직접 읽지 못하지만, localStorage에 들어 있는 토큰은 `localStorage.getItem()` 한 줄로 외부로 빠져나갈 수 있습니다. 공격자가 토큰 값을 복사해 다른 환경에서 재사용하기 쉬워진다는 뜻입니다.

또 하나는 운영 문제입니다. localStorage에 장기 토큰을 넣기 시작하면 로그아웃, 만료 처리, 탭 간 동기화, 토큰 갱신 실패, 기기 분실 대응 같은 일들이 생각보다 지저분해집니다. "브라우저가 알아서 쿠키를 보낸다"는 단순함을 버리는 대신, 애플리케이션이 상태 전송을 직접 책임져야 하기 때문입니다.

## 웹앱 기본값으로 HttpOnly 쿠키를 먼저 보는 이유

쿠키가 무조건 안전하다는 뜻은 아닙니다. 다만 **브라우저에서 로그인 세션을 다룰 때는 브라우저가 원래 잘하던 방식에 기대는 편이 실수가 적습니다.**

MDN은 `HttpOnly` 쿠키가 자바스크립트 접근을 막고, XSS로부터 세션 식별자가 탈취되는 위험을 줄이는 데 도움이 된다고 설명합니다. (출처: [MDN Set-Cookie](https://developer.mozilla.org/en-US/docs/Web/HTTP/Reference/Headers/Set-Cookie), [MDN Secure cookie configuration](https://developer.mozilla.org/en-US/docs/Web/Security/Practical_implementation_guides/Cookies)) OWASP도 localStorage 대신 쿠키의 `HttpOnly` 속성이 이 리스크를 줄일 수 있다고 안내합니다. (출처: [OWASP HTML5 Security Cheat Sheet](https://cheatsheetseries.owasp.org/cheatsheets/HTML5_Security_Cheat_Sheet.html))

실무에서는 이 차이가 꽤 큽니다.

- 프론트 코드가 토큰 원문을 직접 들고 다니지 않아도 됩니다.
- 브라우저가 같은 사이트 요청에 자동으로 쿠키를 붙입니다.
- 로그아웃 시 서버에서 쿠키 만료와 세션 무효화를 함께 처리하기 쉽습니다.
- SSR이나 BFF 구조에서는 헤더 조작보다 자연스럽게 붙습니다.

특히 React, Next.js, NestJS, Spring처럼 서버와 브라우저를 함께 다루는 구조라면, `Authorization` 헤더를 매번 수동으로 붙이는 것보다 쿠키 기반 흐름이 더 단순한 경우가 많습니다.

## 쿠키를 쓴다고 끝은 아닙니다: 같이 챙겨야 할 옵션

쿠키 기반 인증의 약점은 보통 "쿠키냐 아니냐"보다 **옵션을 허술하게 둔 상태**에서 생깁니다.

MDN 기준으로 세션 식별자 쿠키라면 적어도 아래는 먼저 확인하는 편이 좋습니다. (출처: [MDN Set-Cookie](https://developer.mozilla.org/en-US/docs/Web/HTTP/Reference/Headers/Set-Cookie), [MDN Secure cookie configuration](https://developer.mozilla.org/en-US/docs/Web/Security/Practical_implementation_guides/Cookies))

| 항목 | 왜 필요한가 | 기본 판단 |
| --- | --- | --- |
| `HttpOnly` | 자바스크립트에서 쿠키 값을 읽지 못하게 함 | 세션/리프레시 토큰이면 기본값으로 사용 |
| `Secure` | HTTPS 요청에서만 전송 | 운영 환경에서는 사실상 필수 |
| `SameSite=Lax` 또는 `Strict` | 다른 사이트에서 온 요청에 쿠키가 자동 전송되는 범위를 줄임 | 일반 웹앱 기본값은 `Lax`, 더 엄격하면 `Strict` 검토 |
| `Path=/` | 불필요한 경로 노출을 줄임 | 보통 `/` 또는 더 좁은 경로 |
| `Domain` 미지정 또는 최소화 | 서브도메인 전체로 퍼지는 걸 막음 | 꼭 필요할 때만 지정 |

추가로 MDN은 `__Host-` 또는 `__Host-Http-` 같은 prefix를 쓰면 host 범위와 속성 제한을 더 엄격하게 걸 수 있다고 설명합니다. 서브도메인이 많은 조직이라면 이 부분까지 챙겨두면 실수 여지가 줄어듭니다. (출처: [MDN Set-Cookie](https://developer.mozilla.org/en-US/docs/Web/HTTP/Reference/Headers/Set-Cookie))

## 쿠키를 쓰면 따라오는 질문: 그럼 CSRF는 어떻게 볼까?

쿠키 기반 인증을 꺼리는 이유 중 하나가 CSRF입니다. 이 걱정 자체는 맞습니다. 쿠키는 브라우저가 자동으로 보내기 때문에, 교차 사이트 요청에 대한 방어를 같이 설계해야 합니다.

다만 이걸 이유로 바로 localStorage로 달려가면 균형이 깨집니다. OWASP는 `SameSite`를 CSRF 완화 수단으로 설명하지만, 동시에 어디까지나 방어층 중 하나라고 봅니다. (출처: [OWASP CSRF Prevention Cheat Sheet](https://cheatsheetseries.owasp.org/cheatsheets/Cross-Site_Request_Forgery_Prevention_Cheat_Sheet.html)) MDN도 `SameSite=Strict`나 `Lax`가 CSRF에 대한 부분 방어라고 설명합니다. (출처: [MDN Secure cookie configuration](https://developer.mozilla.org/en-US/docs/Web/Security/Practical_implementation_guides/Cookies))

실무에서는 보통 이렇게 정리하면 됩니다.

- 같은 사이트 중심의 일반 웹앱: `HttpOnly` + `Secure` + `SameSite=Lax`를 기본으로 시작
- 민감한 작업이 많고 외부 링크 유입보다 보안 우선: `SameSite=Strict` 검토
- 크로스 사이트 임베드나 다른 도메인 프론트와의 통신이 꼭 필요: `SameSite=None; Secure` + 별도 CSRF 방어 설계

즉 쿠키의 문제는 "쿠키라서 위험"이 아니라, **CSRF 대응 없이 자동 전송만 믿는 설계**에 가깝습니다.

## 그럼 localStorage는 언제 고려할 수 있을까?

localStorage가 항상 틀렸다는 뜻은 아닙니다. 다만 "편해서"가 아니라, 아래 조건이 분명할 때만 고르는 편이 낫습니다.

### 1. 브라우저가 아닌 클라이언트가 중심일 때

모바일 앱, 데스크톱 앱, CLI처럼 요청 헤더를 클라이언트가 직접 제어하는 구조라면 쿠키보다 bearer token이 자연스러운 경우가 많습니다. 이 경우에도 저장 위치는 운영체제가 제공하는 secure storage를 먼저 보지, 브라우저 localStorage처럼 아무 데나 두지는 않습니다.

### 2. SPA가 별도 API에 헤더 기반으로 붙어야 할 때

완전히 분리된 프론트와 API 구조에서 `Authorization` 헤더를 명시적으로 붙여야 하고, BFF를 둘 계획도 없다면 토큰 기반 구조가 더 단순할 수 있습니다. 그래도 장기 보관이 꼭 필요한지, access token은 메모리에 두고 refresh token만 더 안전한 경로로 관리할 수 없는지 먼저 따져보는 편이 좋습니다.

### 3. 저장하는 값이 세션 식별자가 아닐 때

테마 설정, 최근 본 탭, 비민감 캐시처럼 "털려도 인증이 깨지지 않는 값"은 localStorage가 잘 맞습니다. OWASP도 localStorage 자체를 금지하는 게 아니라, **민감 정보와 세션 식별자 저장을 피하라**는 쪽에 가깝습니다. (출처: [OWASP HTML5 Security Cheat Sheet](https://cheatsheetseries.owasp.org/cheatsheets/HTML5_Security_Cheat_Sheet.html))

## 어떤 팀에 어떤 선택이 맞을까

정리하면 아래처럼 보면 됩니다.

### 이런 경우라면 쿠키/세션 쪽이 더 낫습니다

- 일반적인 웹 서비스 로그인
- SSR, BFF, 같은 도메인 API 구조
- 프론트가 토큰 원문을 굳이 읽을 필요가 없는 구조
- 보안 사고 때 토큰 탈취 범위를 줄이고 싶은 팀

### 이런 경우라면 토큰 헤더 방식이 더 자연스러울 수 있습니다

- 모바일 앱, CLI, 외부 파트너 API 클라이언트
- 여러 백엔드에 명시적으로 bearer token을 전달해야 하는 구조
- 브라우저 쿠키 정책보다 클라이언트 제어가 더 중요한 구조

### 추천하지 않는 경우

- 이유가 "튜토리얼에서 다들 그렇게 하던데요"뿐일 때
- 장기 access token을 localStorage에 그대로 넣고 XSS 대응은 나중으로 미룰 때
- 쿠키는 싫지만 CSRF, 토큰 만료, 재발급, 로그아웃 무효화 전략은 정하지 않은 상태

## 구현 전에 마지막으로 확인할 체크리스트

- [ ] 이 값은 정말 브라우저 자바스크립트가 직접 읽어야 하는가?
- [ ] 로그인 상태를 표현하는 값이라면 `HttpOnly` 쿠키로 숨길 수 없는가?
- [ ] 쿠키를 쓴다면 `Secure`, `SameSite`, `Path`, `Domain`을 명시했는가?
- [ ] 다른 서브도메인까지 쿠키가 퍼질 이유가 정말 있는가?
- [ ] XSS가 한 번 났을 때 토큰 원문이 바로 빠져나가도 감당 가능한가?
- [ ] 로그아웃과 강제 만료를 서버에서 확실히 무효화할 수 있는가?
- [ ] 크로스 사이트 요청이 필요한 구조라면 CSRF 방어를 별도로 설계했는가?

## FAQ

### JWT를 쿠키에 넣으면 JWT를 쓰는 의미가 없어지지 않나요?

그렇지 않습니다. JWT는 토큰 형식이고, 쿠키는 전달·보관 방식입니다. JWT를 쿠키에 담아도 여전히 JWT를 쓰는 것입니다. 다만 브라우저 웹앱에서는 JWT의 "무상태"보다 저장 방식의 안전성과 운영 편의가 더 큰 결정 요소가 되는 경우가 많습니다.

### sessionStorage는 localStorage보다 안전한가요?

범위는 더 좁습니다. MDN에 따르면 `sessionStorage`는 탭 단위로 분리되고 탭을 닫으면 사라집니다. (출처: [MDN Web Storage API](https://developer.mozilla.org/en-US/docs/Web/API/Web_Storage_API)) 하지만 자바스크립트가 읽을 수 있다는 점은 같아서, 세션 식별자를 넣는 기본값으로 삼기에는 여전히 조심해야 합니다.

### access token은 메모리, refresh token은 HttpOnly 쿠키로 나누는 방식은 어떤가요?

충분히 많이 쓰는 절충안입니다. 다만 이 글의 핵심은 "정답 하나"보다 **브라우저가 직접 읽어야 하는 값과 숨겨둘 수 있는 값을 분리하라**는 쪽에 있습니다. access token 재발급 흐름, 만료 시 UX, 다중 탭 동기화까지 같이 설계할 수 있다면 좋은 선택이 될 수 있습니다.

### 쿠키는 CSRF 때문에 위험하고, localStorage는 XSS 때문에 위험한 것 아닌가요?

맞습니다. 그래서 둘 중 하나가 마법처럼 안전한 건 아닙니다. 다만 브라우저 웹앱 기본값으로 보면, `HttpOnly` 쿠키는 토큰 원문 탈취를 줄여 주고 `SameSite` 같은 옵션도 붙일 수 있어서 방어 수단이 더 구조적으로 마련돼 있습니다. 반면 localStorage는 편하지만 토큰 원문이 자바스크립트에 그대로 노출됩니다.

## 정리

JWT를 쓴다고 해서 localStorage가 자동으로 정답이 되지는 않습니다. JWT는 어디까지나 토큰 형식이고, 브라우저 인증 설계의 핵심은 **그 값을 누가 읽을 수 있고, 어떤 요청에 자동으로 실리며, 사고가 났을 때 얼마나 빨리 무효화할 수 있는가**입니다.

그래서 일반 웹앱이라면 `HttpOnly` 쿠키나 서버 세션을 먼저 검토하는 편이 낫습니다. localStorage는 정말로 프론트가 토큰을 직접 들고 있어야 하는 이유가 분명할 때만 선택하세요. 구현을 시작하기 전에 "내 앱은 왜 브라우저 자바스크립트가 이 값을 읽어야 하는가"만 제대로 답해도, 저장 전략의 절반은 이미 정리됩니다.

## 출처

- [RFC 7519 - JSON Web Token (JWT)](https://datatracker.ietf.org/doc/html/rfc7519)
- [OWASP HTML5 Security Cheat Sheet](https://cheatsheetseries.owasp.org/cheatsheets/HTML5_Security_Cheat_Sheet.html)
- [OWASP Cross-Site Request Forgery Prevention Cheat Sheet](https://cheatsheetseries.owasp.org/cheatsheets/Cross-Site_Request_Forgery_Prevention_Cheat_Sheet.html)
- [MDN Web Storage API](https://developer.mozilla.org/en-US/docs/Web/API/Web_Storage_API)
- [MDN Set-Cookie](https://developer.mozilla.org/en-US/docs/Web/HTTP/Reference/Headers/Set-Cookie)
- [MDN Secure cookie configuration](https://developer.mozilla.org/en-US/docs/Web/Security/Practical_implementation_guides/Cookies)
