---
title: "OpenAPI 문서와 실제 API가 어긋날 때 먼저 잡을 기준"
slug: "openapi-contract-drift-checklist"
author: "BLOA Team"
date: "2026-08-06"
summary: "OpenAPI 문서가 Swagger UI용 장식으로만 남지 않게, 실제 API 응답과 스키마가 어긋나는 지점을 계약 테스트와 변경 기준으로 줄이는 방법을 정리했습니다."
oneLineSummary: "OpenAPI는 문서가 아니라 프론트엔드와 백엔드가 함께 지켜야 할 API 계약으로 다뤄야 오래 갑니다."
tags: [OpenAPI, API, 백엔드, QA, 체크리스트]
status: "published"
---

# OpenAPI 문서와 실제 API가 어긋날 때 먼저 잡을 기준

Swagger UI에는 분명 `email`이 필수라고 적혀 있는데, 실제 응답에는 가끔 빠집니다. 문서에는 `status`가 `active | blocked`라고 되어 있지만 운영 데이터에는 `pending`이 섞여 있습니다. 프론트엔드는 타입을 믿고 배포했는데, 어느 날 특정 고객 계정에서만 화면이 깨집니다.

이런 문제는 OpenAPI를 "문서 자동 생성" 정도로만 보면 계속 반복됩니다. **OpenAPI는 예쁜 API 문서가 아니라, 클라이언트와 서버가 함께 지키는 계약으로 다뤄야 합니다.** 계약이 깨지는 순간은 대개 큰 리팩토링 때가 아닙니다. 필드 하나를 optional로 바꾸거나, 에러 응답 형식을 급하게 추가하거나, 테스트 데이터에 없는 케이스가 운영에서 들어올 때 조용히 생깁니다.

내가 작은 팀에서 OpenAPI를 볼 때 먼저 확인하는 기준은 세 가지입니다.

- 문서가 실제 라우트, 요청값, 응답값을 얼마나 좁게 설명하는가
- 실제 API가 문서에 적힌 스키마를 자동 검증받는가
- 변경이 생겼을 때 프론트엔드와 외부 연동자가 언제 알아차릴 수 있는가

최종 업데이트: 2026-08-06  
작성 관점: PHP/Laravel, Node.js, AWS 환경에서 API를 만들고 운영하며 겪는 문서 드리프트 문제를 기준으로 정리했습니다.

## OpenAPI가 틀어지는 순간은 생각보다 사소합니다

OpenAPI Specification은 HTTP API를 언어와 구현에 묶이지 않는 인터페이스 설명으로 정의합니다. 소스 코드나 별도 문서를 보지 않아도 서비스의 기능을 이해하고 호출할 수 있게 하는 것이 핵심입니다. 최신 공개 버전은 2025년 9월 19일 기준 OpenAPI Specification v3.2.0이며, 이 글은 2026년 8월 6일에 해당 문서를 확인해 작성했습니다. (출처: [OpenAPI Specification v3.2.0](https://spec.openapis.org/oas/latest.html))

그런데 실무에서 문제는 "OpenAPI를 쓰느냐"가 아니라 "OpenAPI가 실제와 맞느냐"입니다. 문서가 한 번 만들어진 뒤 관리되지 않으면, 오히려 안심하게 만드는 잘못된 정보가 됩니다. 프론트엔드 개발자는 문서에 없는 nullable을 처리하지 않고, QA는 문서에 적힌 에러 형식만 확인하고, 외부 연동사는 더 오래된 예제를 보고 구현합니다.

내가 가장 자주 본 드리프트는 아래 네 가지입니다.

| 어긋나는 지점 | 겉으로 보이는 증상 | 먼저 확인할 것 |
| --- | --- | --- |
| 응답 필드 | 특정 화면에서만 `undefined` 오류 | `required`, `nullable`, 실제 샘플 응답 |
| enum 값 | 새 상태값이 들어오면 분기 실패 | 운영 DB 값, 상태 전이표, schema enum |
| 에러 형식 | 실패 화면 메시지가 비거나 잘못 표시됨 | `4xx`, `5xx` 응답 스키마 |
| 인증/권한 | 문서에서는 호출 가능하지만 실제로는 401/403 | operation별 `security`, 권한 정책 |

문서와 실제가 어긋나는 이유는 게으름만이 아닙니다. 바쁜 팀에서는 API 변경이 먼저이고 문서 반영이 나중입니다. 긴급 장애 대응 중에는 에러 응답을 임시로 바꾸기도 합니다. 문제는 그 임시 변경이 다음 배포에도 살아남는다는 점입니다.

## 문서 품질보다 계약 범위를 먼저 정해야 합니다

OpenAPI 문서를 처음 만들 때 모든 것을 완벽하게 적으려 하면 금방 지칩니다. 반대로 너무 느슨하게 적으면 계약으로서 힘이 없습니다. `type: object`만 있고 필드가 비어 있는 스키마는 사실상 "아무거나 올 수 있다"는 뜻입니다.

처음부터 넓게 잡아야 할 영역과 좁게 잡아야 할 영역을 나누는 편이 현실적입니다.

- 좁게 잡을 것: 공개 API 응답, 프론트엔드가 직접 렌더링하는 필드, 상태값 enum, 에러 응답 형식
- 느슨하게 시작할 수 있는 것: 내부 관리자 전용 임시 API, 로그성 메타데이터, 실험 기능의 비핵심 필드
- 반드시 명시할 것: 인증 방식, 페이지네이션 형식, 날짜/시간 포맷, nullable 여부

OpenAPI 구조 학습 문서는 `paths`가 엔드포인트와 파라미터, 가능한 서버 응답을 설명하는 영역이라고 안내합니다. 또 응답은 `responses` 아래에 HTTP 상태 코드별로 적습니다. 이 말은 단순합니다. 성공 응답만 적어둔 문서는 절반짜리 계약입니다. (출처: [OpenAPI Learn - Structure of an OpenAPI Description](https://learn.openapis.org/specification/structure.html), [OpenAPI Learn - API Endpoints](https://learn.openapis.org/specification/paths.html))

작은 팀이라면 처음부터 모든 API를 계약 테스트 대상으로 삼기보다, 깨졌을 때 비용이 큰 API부터 시작하는 편이 낫습니다. 회원가입, 결제, 주문, 권한, 알림, 외부 연동 콜백처럼 한 번 어긋나면 고객 지원이나 데이터 정정으로 이어지는 곳입니다.

## 실제 응답을 문서에 맞춰 보는 작은 테스트부터 시작합니다

계약 테스트라고 하면 Pact 같은 도구나 복잡한 마이크로서비스 구조부터 떠올리기 쉽습니다. 하지만 시작점은 더 작아도 됩니다. "이 API의 실제 JSON 응답이 OpenAPI에 적힌 필수 필드와 타입을 만족하는가"만 CI에서 확인해도, 문서 드리프트의 상당 부분은 빨리 드러납니다.

아래 예시는 의존성 없이 Node.js에서 돌릴 수 있는 아주 작은 검증 스크립트입니다. OpenAPI 전체를 완벽하게 해석하지 않습니다. 대신 `application/json` 응답 스키마의 `required`와 기본 타입만 확인합니다. 팀에서 기준을 합의하기 위한 출발점으로 보면 됩니다.

```js
const spec = {
  paths: {
    "/users/me": {
      get: {
        responses: {
          "200": {
            content: {
              "application/json": {
                schema: {
                  type: "object",
                  required: ["id", "email", "role"],
                  properties: {
                    id: { type: "integer" },
                    email: { type: "string" },
                    role: { type: "string", enum: ["admin", "member"] },
                  },
                },
              },
            },
          },
        },
      },
    },
  },
};

const responseBody = {
  id: 42,
  email: "dev@example.com",
  role: "member",
};

function assertBySchema(schema, value, path = "$") {
  if (schema.type === "object") {
    if (value === null || Array.isArray(value) || typeof value !== "object") {
      throw new Error(`${path} must be object`);
    }

    for (const key of schema.required ?? []) {
      if (!(key in value)) {
        throw new Error(`${path}.${key} is required`);
      }
    }

    for (const [key, childSchema] of Object.entries(schema.properties ?? {})) {
      if (key in value) {
        assertBySchema(childSchema, value[key], `${path}.${key}`);
      }
    }
  }

  if (schema.type === "integer" && !Number.isInteger(value)) {
    throw new Error(`${path} must be integer`);
  }

  if (schema.type === "string" && typeof value !== "string") {
    throw new Error(`${path} must be string`);
  }

  if (schema.enum && !schema.enum.includes(value)) {
    throw new Error(`${path} must be one of ${schema.enum.join(", ")}`);
  }
}

const schema =
  spec.paths["/users/me"].get.responses["200"].content["application/json"].schema;

assertBySchema(schema, responseBody);
console.log("contract ok");
```

이 코드를 그대로 운영 검증 도구로 쓰라는 뜻은 아닙니다. 실제 프로젝트에서는 `$ref`, 배열, `oneOf`, 날짜 포맷, nullable, 추가 속성 처리까지 봐야 합니다. 다만 이 정도 테스트만 있어도 "문서에는 필수인데 실제 응답에는 빠지는 필드"와 "문서에는 문자열인데 실제로는 숫자로 내려오는 필드"를 배포 전에 잡을 수 있습니다.

OpenAPI v3.2.0 문서는 Schema Object를 통해 데이터 구조를 표현하고, Responses Object를 통해 상태 코드별 응답을 기술합니다. 계약 테스트는 이 두 지점을 실제 실행 결과와 붙여 보는 일에 가깝습니다. 문서를 사람이 읽는 산출물로만 두지 않고, 배포 과정에서 실행되는 규칙으로 바꾸는 것입니다. (출처: [OpenAPI Specification v3.2.0 - Schema Object](https://spec.openapis.org/oas/latest.html#schema-object), [OpenAPI Specification v3.2.0 - Responses Object](https://spec.openapis.org/oas/latest.html#responses-object))

## required와 nullable은 팀에서 가장 먼저 합의해야 합니다

OpenAPI 문서가 있어도 `required`와 nullable 기준이 흐리면 프론트엔드는 결국 방어 코드를 남발합니다. 모든 필드에 `?.`를 붙이고, 화면마다 기본값을 다르게 넣고, 실제로는 필수인 값이 빠져도 조용히 넘어갑니다. 그러면 오류는 줄어드는 것처럼 보이지만 데이터 품질 문제는 늦게 발견됩니다.

나는 응답 필드를 세 가지로 나눠 적는 편입니다.

- **필수 값**: 화면이나 비즈니스 로직이 없으면 안 되는 값. OpenAPI의 `required`에 넣는다.
- **명시적 없음**: 값이 없을 수 있다는 의미가 도메인에 있는 값. `null` 허용 여부를 문서에 적는다.
- **확장 메타데이터**: 없어도 화면이 깨지지 않는 값. 필수로 두지 않되 의미를 설명한다.

예를 들어 사용자 프로필 응답에서 `id`와 `email`은 보통 필수입니다. 반면 `lastLoginAt`은 아직 로그인 기록이 없으면 없을 수 있습니다. 이때 빈 문자열, `null`, 필드 미포함을 섞어 쓰면 클라이언트 처리가 복잡해집니다. "없음"을 무엇으로 표현할지 하나로 정해야 합니다.

Laravel이나 Node.js 프로젝트에서 자주 생기는 실수는 DB 컬럼 기준을 그대로 API 계약으로 착각하는 것입니다. DB에서는 nullable이지만 API에서는 항상 채워서 내려줄 수도 있고, 반대로 DB에는 값이 있어도 권한 때문에 응답에서 제외할 수 있습니다. OpenAPI는 테이블 구조가 아니라 외부로 내보내는 인터페이스를 설명해야 합니다.

## 에러 응답은 성공 응답보다 더 빨리 어긋납니다

성공 응답은 화면 개발 중에 계속 보입니다. 반면 에러 응답은 테스트 데이터가 부족하면 잘 보이지 않습니다. 그래서 문서에는 `400`만 있고 실제로는 `422`, `401`, `403`, `409`, `429`가 제각각 내려오는 일이 많습니다.

에러 응답에서 최소로 맞춰야 할 것은 네 가지입니다.

- 사람이 읽을 메시지와 개발자가 추적할 코드가 분리되어 있는가
- 필드 검증 오류가 어느 필드의 어떤 문제인지 알 수 있는가
- 인증 실패와 권한 부족을 같은 오류처럼 처리하지 않는가
- 재시도 가능한 실패와 사용자가 수정해야 하는 실패가 구분되는가

예를 들어 입력 검증 실패는 화면에서 필드 옆에 표시해야 합니다. 서버 내부 오류는 사용자에게 상세 스택을 보여주면 안 됩니다. 권한 부족은 다시 로그인으로 해결되는지, 관리자 권한이 필요한지에 따라 안내가 달라집니다. 이 차이가 OpenAPI에 없으면 프론트엔드는 상태 코드와 문자열 메시지에 의존하게 됩니다.

나는 새 API를 만들 때 성공 응답보다 에러 응답 예시를 먼저 보기도 합니다. 성공 케이스는 개발자가 자연스럽게 확인하지만, 실패 케이스는 누군가 일부러 붙잡지 않으면 뒤로 밀립니다. 특히 외부 연동 API라면 에러 형식이 곧 지원 비용입니다.

## 문서 생성 위치를 하나로 정해야 오래 갑니다

OpenAPI 문서를 유지하는 방식은 팀마다 다릅니다. 코드에서 주석이나 데코레이터로 생성할 수도 있고, 별도 YAML을 먼저 쓰고 서버와 클라이언트를 맞출 수도 있습니다. 어느 쪽이든 중요한 것은 "정본이 어디인가"를 정하는 일입니다.

정본이 두 개가 되면 거의 반드시 어긋납니다. 백엔드는 컨트롤러 타입을 고치고, 프론트엔드는 별도 문서를 보고, 외부 연동사는 더 오래된 Swagger UI를 봅니다. 누가 맞는지 논쟁하는 순간 이미 계약 관리는 실패한 상태입니다.

실무에서는 아래 기준으로 고르면 됩니다.

| 팀 상황 | 정본 후보 | 주의할 점 |
| --- | --- | --- |
| 백엔드가 API를 주도하고 변경 속도가 빠름 | 코드에서 OpenAPI 생성 | 생성된 문서를 CI에서 diff로 확인 |
| 외부 연동사가 많고 사전 합의가 중요함 | OpenAPI YAML/JSON 우선 | 서버 구현이 문서를 따라가는 테스트 필요 |
| 프론트와 백엔드가 동시에 개발됨 | 문서 우선 + mock 서버 | 임시 mock과 실제 구현 차이를 주기적으로 검증 |
| 레거시 API를 정리하는 중 | 실제 응답 샘플에서 역으로 문서화 | 현재 동작과 목표 계약을 분리해서 표시 |

나는 작은 팀에서는 코드 생성 방식으로 시작하되, 공개 API나 외부 연동 API는 문서 diff를 반드시 리뷰에 올리는 방식을 선호합니다. 코드에서 생성되면 최신성은 좋아지지만, 의도치 않은 계약 변경도 조용히 문서에 반영될 수 있기 때문입니다. "문서가 자동으로 바뀌었다"는 말은 "계약 변경 리뷰가 생략됐다"는 뜻이 될 수 있습니다.

## 변경 관리는 버전보다 호환성 기준이 먼저입니다

API 버전을 `v1`, `v2`로 나누는 것만으로는 충분하지 않습니다. 같은 `v1` 안에서도 필수 필드가 추가되거나 enum 값이 바뀌면 클라이언트는 깨질 수 있습니다. 반대로 새 optional 필드를 추가하는 정도는 버전을 올리지 않아도 되는 경우가 많습니다.

변경을 볼 때는 아래처럼 나누면 판단이 쉬워집니다.

- **대체로 안전한 변경**: optional 응답 필드 추가, 새 endpoint 추가, 설명 보강
- **주의가 필요한 변경**: enum 값 추가, 에러 코드 추가, nullable 정책 변경
- **깨지는 변경**: 필수 필드 제거, 타입 변경, 필수 요청 파라미터 추가, 상태 코드 의미 변경

enum 값 추가를 "안전한 변경"으로 보는 팀도 있지만, 나는 보수적으로 봅니다. 프론트엔드가 `switch` 문에서 알려진 값만 처리하고 있다면 새 값은 바로 빈 화면이나 기본 분기로 떨어질 수 있습니다. 상태값은 필드 하나가 아니라 화면 흐름입니다.

OpenAPI 문서의 `info.version`은 API 설명의 버전을 나타내는 값으로 사용할 수 있습니다. 다만 이 숫자가 올라갔다고 해서 소비자가 자동으로 안전해지는 것은 아닙니다. 중요한 것은 변경 로그에 "무엇이 호환되고 무엇이 깨지는지"를 적는 것입니다. (출처: [OpenAPI Learn - Structure of an OpenAPI Description](https://learn.openapis.org/specification/structure.html))

## 보안과 개인정보는 문서에도 남기지 말아야 할 것이 있습니다

OpenAPI 문서는 개발자에게 친절해야 하지만, 운영 비밀을 담아서는 안 됩니다. 예제 토큰, 실제 고객 이메일, 내부 관리자 경로, 운영 서버의 민감한 URL이 문서에 들어가면 문서 자체가 노출면이 됩니다.

OpenAPI v3.2.0의 보안 고려사항은 외부 리소스 참조, 참조 순환, Markdown/HTML 위생 처리 같은 도구 사용 리스크도 다룹니다. 문서 렌더링 도구가 외부 `$ref`를 자동으로 따라가거나, 설명 필드에 들어간 HTML을 그대로 보여주면 예상하지 못한 문제가 생길 수 있습니다. (출처: [OpenAPI Specification v3.2.0 - Security Considerations](https://spec.openapis.org/oas/latest.html#security-considerations))

문서 공개 범위를 정할 때는 아래 질문을 먼저 던집니다.

- 이 OpenAPI 문서가 외부에 공개되어도 되는가
- 내부용 endpoint와 외부용 endpoint가 같은 문서에 섞여 있지 않은가
- 예제 값에 실제 개인정보나 운영 토큰이 들어가지 않았는가
- Swagger UI 접근 권한이 운영 관리자 권한과 분리되어 있는가
- 외부 `$ref`를 가져오는 도구라면 허용 도메인을 제한했는가

API 문서는 개발 속도를 높이기 위한 도구입니다. 하지만 운영 정보가 섞이면 문서가 아니라 보안 부채가 됩니다.

## 작은 팀에서 바로 적용할 OpenAPI 운영 체크리스트

OpenAPI를 제대로 운영하려고 처음부터 플랫폼을 크게 만들 필요는 없습니다. 작은 팀이라면 아래 순서로도 충분히 시작할 수 있습니다.

- [ ] 공개 API와 프론트엔드 핵심 API부터 OpenAPI 문서 범위를 정했다.
- [ ] 성공 응답뿐 아니라 주요 `4xx`, `5xx` 에러 응답 형식을 적었다.
- [ ] `required`, nullable, enum 값을 실제 화면 기준으로 정했다.
- [ ] 문서 정본이 코드인지 YAML/JSON인지 팀 안에서 합의했다.
- [ ] PR에서 OpenAPI diff를 볼 수 있다.
- [ ] 실제 API 응답 샘플이 문서 스키마를 통과하는지 CI에서 확인한다.
- [ ] enum 값 추가와 필수 필드 변경을 깨지는 변경 후보로 리뷰한다.
- [ ] 예제 값에 실제 개인정보, 토큰, 내부 URL이 들어가지 않게 검사한다.
- [ ] 외부 연동 API는 변경 로그와 적용 일자를 같이 남긴다.

처음부터 전부 자동화하지 않아도 됩니다. 가장 먼저 할 일은 "문서가 맞는지"를 사람의 기억에 맡기지 않는 것입니다. 핵심 endpoint 몇 개만이라도 실제 응답과 OpenAPI 스키마를 함께 검증하면, Swagger UI가 오래된 안내판으로 남는 일을 꽤 줄일 수 있습니다.

## 자주 묻는 질문

### OpenAPI 문서는 코드에서 자동 생성하는 게 항상 좋나요?

항상 그렇지는 않습니다. 코드 생성 방식은 최신성을 유지하기 쉽지만, 의도하지 않은 계약 변경도 함께 반영될 수 있습니다. 외부 연동사가 있는 API라면 생성된 문서 diff를 PR에서 검토하고, 깨지는 변경인지 따로 판단해야 합니다.

### Swagger UI가 있으면 계약 테스트는 없어도 되나요?

Swagger UI는 사람이 API를 읽고 시도해 보기 좋은 도구입니다. 하지만 실제 배포 과정에서 응답이 문서와 맞는지 자동으로 보장하지는 않습니다. 계약 테스트는 문서를 화면으로 보여주는 일이 아니라, 실제 응답과 문서 스키마를 비교하는 일입니다.

### 모든 응답 필드를 required로 잡아야 안전한가요?

아닙니다. 실제로 없을 수 있는 값을 필수로 잡으면 서버는 가짜 기본값을 만들거나 클라이언트는 문서를 믿지 않게 됩니다. 화면과 비즈니스 로직에 반드시 필요한 값만 필수로 두고, 의미 있는 "없음"은 nullable 또는 필드 미포함 정책으로 분명히 정해야 합니다.

### OpenAPI 버전을 올리면 깨지는 변경 문제가 해결되나요?

버전은 소비자에게 변경을 알리는 장치일 뿐입니다. 필수 필드 제거, 타입 변경, 상태 코드 의미 변경처럼 실제 호환성을 깨는 내용은 버전 숫자와 별개로 명확히 기록하고 전환 기간을 둬야 합니다.

## 출처

- [OpenAPI Specification v3.2.0](https://spec.openapis.org/oas/latest.html)
- [OpenAPI Learn - Structure of an OpenAPI Description](https://learn.openapis.org/specification/structure.html)
- [OpenAPI Learn - API Endpoints](https://learn.openapis.org/specification/paths.html)
