---
title: "HTTP 캐싱 헤더, Cache-Control부터 ETag까지 실무 기준"
slug: "http-cache-headers-guide"
ogImage: "/images/posts/http-cache-headers-guide/hero.png"
author: "감성개발자"
date: "2026-03-30"
summary: "브라우저와 CDN의 캐시 동작을 좌우하는 Cache-Control, ETag, Last-Modified 헤더를 현장 감각으로 풀어본다. 정적 자산과 API 응답에 각각 어떤 정책을 줘야 하는지, 재검증이 어떻게 304로 이어지는지 짚는다."
oneLineSummary: "캐싱은 파일 종류별로 정책을 나누는 것이 핵심이다. 해시 붙은 정적 자산은 길게, HTML과 API는 재검증 중심으로 간다."
tags: [HTTP, 캐싱, Web-Performance, 개발팁]
status: "published"
---

![브라우저와 서버 사이 캐시 계층과 재검증을 표현한 기술 일러스트](/images/posts/http-cache-headers-guide/hero.png)

배포를 했는데 사용자 화면에는 예전 버전이 그대로 보인다는 제보. 반대로 매 요청마다 같은 파일을 다시 받아 트래픽이 새는 상황. 겉보기엔 정반대지만 원인은 하나입니다. 캐싱 정책을 명시하지 않았거나, 파일 성격과 맞지 않는 정책을 줬기 때문입니다.

캐싱 헤더가 하는 일은 "이 응답이 얼마나 오래 유효한가"를 파일 종류별로 다르게 선언하는 것뿐입니다. 그리고 현장에서 실제로 쓰는 조합은 두 줄로 좁혀집니다.

**내용이 바뀌면 URL도 바뀌는 자산(해시 붙은 정적 파일)은 아주 길게 캐시한다. URL이 고정된 응답(HTML·API)은 캐시하되 매번 재검증시킨다.**

나머지는 이 두 줄을 헤더로 옮기는 방법일 뿐입니다. 아래 내용은 MDN의 HTTP 캐싱 문서와 캐싱 표준인 RFC 9111을 근거로 삼았습니다.

## Cache-Control: 캐시 정책의 선언부

`Cache-Control`은 응답이 캐시에 어떻게 저장되고 언제까지 신선한지(fresh) 선언하는 헤더입니다. 실무에서 조합하는 지시자는 몇 개로 좁혀집니다.

| 지시자 | 의미 |
| --- | --- |
| `max-age=N` | N초 동안 재검증 없이 캐시 사용 가능 |
| `no-cache` | 캐시에 저장은 하되, 쓰기 전에 매번 서버에 재검증 |
| `no-store` | 아예 저장 금지 (민감 정보) |
| `private` | 브라우저 캐시만 허용, CDN 등 공유 캐시 금지 |
| `public` | 공유 캐시 저장 허용 |
| `immutable` | 신선한 동안에는 새로고침에도 재검증 생략 힌트 |

가장 흔한 오해가 `no-cache`입니다. 나도 한참을 "캐시 금지"로 잘못 읽고 있었고, 팀에 주니어가 들어올 때마다 이 지시자부터 짚어줍니다. 이름과 달리 캐시를 금지하지 않습니다. MDN 문서도 `no-cache`는 응답을 캐시에 저장할 수 있지만 재사용 전에 반드시 원 서버에 검증을 거치게 하는 지시자라고 설명합니다. 저장 자체를 막으려면 `no-store`를 써야 합니다. (출처: [MDN - Cache-Control](https://developer.mozilla.org/en-US/docs/Web/HTTP/Headers/Cache-Control))

## 조합 1: 해시 붙은 정적 자산은 최대한 길게

요즘 빌드 도구는 `app.3f2a8c.js`처럼 파일 내용 해시를 파일명에 넣습니다. 내용이 바뀌면 파일명이 바뀌므로, 같은 URL의 내용이 달라질 일이 없습니다. 이런 자산은 캐시를 무효화할 필요 자체가 없으므로 가장 긴 정책을 줍니다.

```
Cache-Control: public, max-age=31536000, immutable
```

`max-age=31536000`은 1년입니다. `immutable`은 사용자가 새로고침을 해도 이 자산은 재검증하지 않아도 된다는 힌트로, 새로고침 시 발생하는 불필요한 재검증 요청을 줄여줍니다. MDN은 이 패턴을 캐시 버스팅(cache busting)이라 부르며, 콘텐츠가 바뀔 때 URL을 바꾸는 방식과 결합할 때 긴 `max-age`가 안전해진다고 설명합니다. (출처: [MDN - HTTP caching](https://developer.mozilla.org/en-US/docs/Web/HTTP/Caching))

## 조합 2: HTML과 API 응답은 재검증 중심으로

HTML 문서는 URL이 고정입니다. `/index.html`을 1년 캐시하면 배포해도 사용자는 옛 화면을 봅니다. 그런데 HTML 안에서 참조하는 자산들은 해시 덕에 항상 최신을 가리킵니다. 그래서 HTML만 재검증시키면 전체가 맞물려 돌아갑니다.

```
Cache-Control: no-cache
```

이렇게 하면 브라우저는 캐시본을 갖고 있으면서도 매번 서버에 "바뀌었나요?"라고 묻습니다. 안 바뀌었으면 서버는 `304 Not Modified`로 본문 없이 답하고, 브라우저는 캐시본을 그대로 씁니다. 전송량은 헤더 몇 줄로 끝납니다.

로그인 사용자별로 다른 API 응답이라면 공유 캐시에 남으면 안 되므로 `private`을 더합니다. 개인정보나 결제 정보처럼 디스크에 남는 것 자체가 문제라면 `no-store`입니다.

CDN을 앞에 두더라도 이 설정은 그대로 필요합니다. CDN은 브라우저와 서버 사이에 공유 캐시 계층이 하나 더 생기는 것이고, `Cache-Control`은 두 계층 모두에 적용됩니다. 대부분의 CDN이 공유 캐시 전용 `s-maxage`를 지원하므로, 브라우저는 짧게 CDN은 길게 가져가는 분리도 이 헤더 하나로 가능합니다.

```
Cache-Control: private, no-cache        # 사용자별 응답, 재검증 허용
Cache-Control: no-store                 # 저장 자체 금지
```

## 재검증의 실체: ETag와 Last-Modified

재검증이 동작하려면 서버가 "버전 표식"을 응답에 실어줘야 합니다. 두 가지 방식이 있습니다.

**`Last-Modified` + `If-Modified-Since`.** 서버가 리소스의 최종 수정 시각을 보내면, 브라우저는 다음 요청에 `If-Modified-Since`로 그 시각을 되돌려 보냅니다. 초 단위 정밀도라 1초 안에 여러 번 바뀌는 리소스는 놓칠 수 있습니다.

**`ETag` + `If-None-Match`.** 서버가 리소스 내용 기반 식별자(보통 해시)를 보내면, 브라우저는 `If-None-Match`로 되돌려 보냅니다. 내용이 같으면 시각과 무관하게 304를 줄 수 있어 더 정확합니다. 둘을 함께 보내도 문제없고, 이때 조건부 요청에서는 `If-None-Match`(ETag)가 먼저 평가됩니다. RFC 9111은 조건부 요청으로 캐시된 응답을 검증하고, 유효하면 `304 (Not Modified)`로 저장된 응답을 갱신해 재사용하는 흐름을 표준 동작으로 정의합니다. (출처: [RFC 9111 - HTTP Caching](https://www.rfc-editor.org/rfc/rfc9111))

```
# 첫 응답
HTTP/1.1 200 OK
ETag: "33a64df5"
Cache-Control: no-cache

# 재검증 요청
GET /index.html
If-None-Match: "33a64df5"

# 안 바뀐 경우
HTTP/1.1 304 Not Modified
ETag: "33a64df5"
```

주의할 점 하나. 이건 로드밸런서를 처음 두고 나서 직접 겪고 나서야 기준을 잡은 부분입니다. 여러 대의 서버가 같은 파일을 서빙할 때, 서버마다 ETag 생성 방식이 다르면(예: 파일 시스템 메타데이터 기반) 같은 내용인데 ETag가 달라져 재검증이 항상 실패할 수 있습니다. 로드밸런서 뒤에 여러 대를 두는 구성이라면 ETag 생성 방식을 통일하거나 `Last-Modified` 기준으로 맞춰야 합니다.

## 캐싱 정책이 없으면 어떻게 되나

`Cache-Control`을 아예 안 보내면 안전하게 캐시가 안 될 것 같지만, 실제로는 반대에 가깝습니다. 브라우저는 휴리스틱 캐싱이라 해서 `Last-Modified` 같은 단서로 캐시 기간을 임의로 추정할 수 있습니다. RFC 9111도 명시적 만료 시간이 없을 때 캐시가 휴리스틱으로 신선도를 추정하는 동작을 정의하고 있습니다. 즉 정책을 안 정하면 "캐시 안 됨"이 아니라 "브라우저 마음대로 캐시됨"이 됩니다. 배포 반영이 예측 불가능해지는 가장 흔한 원인입니다.

## 정리하면

- 캐싱 정책의 핵심 질문은 "이 URL의 내용이 바뀔 수 있는가"입니다.
- 해시 붙은 정적 자산: `public, max-age=31536000, immutable`.
- HTML·API 응답: `no-cache`(+ 사용자별이면 `private`, 민감하면 `no-store`).
- 재검증은 `ETag`/`Last-Modified`가 있어야 304로 이어집니다. 정책을 명시하지 않으면 휴리스틱 캐싱으로 통제 불능이 됩니다.

## 출처

- [MDN - HTTP caching](https://developer.mozilla.org/en-US/docs/Web/HTTP/Caching)
- [MDN - Cache-Control](https://developer.mozilla.org/en-US/docs/Web/HTTP/Headers/Cache-Control)
- [RFC 9111 - HTTP Caching](https://www.rfc-editor.org/rfc/rfc9111)
