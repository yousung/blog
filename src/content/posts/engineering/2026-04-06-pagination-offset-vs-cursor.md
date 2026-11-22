---
title: "커서 기반 페이지네이션과 OFFSET, 목록 API에서 무엇을 쓸까 (Node.js 구현 예시)"
slug: "pagination-offset-vs-cursor"
ogImage: "/images/posts/pagination-offset-vs-cursor/hero.png"
author: "감성개발자"
date: "2026-04-06"
summary: "커서 기반 페이지네이션(keyset)과 OFFSET 방식을 화면 성격으로 갈라 고르는 기준입니다. 깊은 페이지에서 OFFSET이 느려지는 이유, 중복·누락 문제, Node.js 커서 토큰 구현과 timestamp 정밀도 함정까지 다룹니다."
oneLineSummary: "페이지 번호 UI가 꼭 필요하면 OFFSET, 무한 스크롤과 대용량 목록이면 커서 방식이 기본값이다."
tags: [SQL, Database, API, 페이지네이션]
status: "published"
updatedDate: "2026-11-22"
---

![OFFSET 순차 스캔과 커서 점프 방식을 대비한 기술 일러스트](/images/posts/pagination-offset-vs-cursor/hero.png)

목록 API를 처음 만들 때는 대부분 `LIMIT 20 OFFSET 40` 형태로 시작합니다. 구현이 단순하고 페이지 번호와 1:1로 맞아떨어지기 때문입니다. 문제는 서비스가 커진 뒤에 나타납니다. 뒤쪽 페이지로 갈수록 응답이 느려지고, 목록을 넘기는 중에 새 데이터가 들어오면 같은 항목이 두 번 보이거나 건너뛰어집니다. 결론부터 말하면 **페이지 번호로 임의 위치에 점프하는 UI가 꼭 필요할 때만 OFFSET을 쓰고, 무한 스크롤이나 대용량 목록은 커서(keyset) 방식을 기본값으로 잡는 것이 안전합니다.**

아래 내용은 PostgreSQL 공식 문서의 LIMIT/OFFSET 설명과, 인덱스 관점에서 페이지네이션을 다루는 Use The Index, Luke의 자료를 근거로 삼았습니다.

## OFFSET이 느려지는 이유

![OFFSET 페이지네이션 도중 목록 위쪽에 새 행이 추가되어 전체가 아래로 밀리는 상황을 두 시점의 세로 스택으로 나란히 보여주는 도식. 고정된 위치의 선택 프레임은 그대로 있는데 스택이 내려가면서, 앞 시점에서 프레임 위에 있던 같은 항목이 뒤 시점 프레임 안으로 들어와 두 번 잡히고 중복으로 강조된다.](/images/posts/pagination-offset-vs-cursor/figure.png)

`OFFSET`은 건너뛰는 것처럼 보이지만, 데이터베이스 입장에서는 건너뛸 행도 일단 만들어야 합니다. PostgreSQL 공식 문서는 `OFFSET`으로 건너뛰는 행 역시 서버 내부에서 계산되어야 하므로, 큰 OFFSET은 비효율적일 수 있다고 명시합니다. (출처: [PostgreSQL Documentation - LIMIT and OFFSET](https://www.postgresql.org/docs/current/queries-limit.html))

```sql
SELECT * FROM posts
ORDER BY created_at DESC
LIMIT 20 OFFSET 100000;
```

이 쿼리는 20행을 반환하지만, 그 전에 100,000행을 정렬 순서대로 읽어서 버립니다. 1페이지와 5000페이지의 응답 시간이 다른 이유입니다. 인덱스가 잘 걸려 있어도 "읽고 버리는" 작업 자체는 사라지지 않습니다.

성능만이 문제가 아닙니다. 나는 무한 스크롤에서 같은 카드가 두 번 뜬다는 제보를 몇 번 받고 나서야 이 문제를 성능과 분리해서 보게 됐습니다. 사용자가 2페이지를 보는 사이 새 글이 3개 등록되면, 3페이지 요청 시 OFFSET 기준점이 밀려서 2페이지에서 본 항목이 다시 나타납니다. 반대로 삭제가 일어나면 항목을 건너뜁니다. 무한 스크롤 UI에서 같은 카드가 두 번 보이는 버그의 전형적인 원인입니다.

## 커서 방식: 위치가 아니라 값으로 이어가기

커서 방식(keyset pagination)은 "몇 번째부터"가 아니라 "이 값 다음부터"로 다음 페이지를 정의합니다.

```sql
-- 첫 페이지
SELECT * FROM posts
ORDER BY created_at DESC, id DESC
LIMIT 20;

-- 다음 페이지: 마지막 행의 (created_at, id)를 커서로
SELECT * FROM posts
WHERE (created_at, id) < ('2026-04-01 10:30:00', 8123)
ORDER BY created_at DESC, id DESC
LIMIT 20;
```

`WHERE` 조건이 인덱스 범위 검색으로 처리되므로, 첫 페이지든 만 번째 페이지든 읽는 행 수는 20개 언저리로 같습니다. Use The Index, Luke도 OFFSET 방식이 페이지가 깊어질수록 느려지는 반면, keyset 방식은 마지막으로 받은 값 기준의 조건으로 다음 페이지를 가져오므로 인덱스를 그대로 활용한다고 설명합니다. (출처: [Use The Index, Luke - Paging Through Results](https://use-the-index-luke.com/sql/partial-results/fetch-next-page))

행 값 비교 `(created_at, id) < (?, ?)`는 PostgreSQL과 MySQL 8.0에서 지원합니다. 지원하지 않는 환경이라면 동치인 조건으로 풀어 씁니다.

```sql
WHERE created_at < ?
   OR (created_at = ? AND id < ?)
```

두 형태 모두 `(created_at DESC, id DESC)` 복합 인덱스가 있어야 제 성능이 납니다.

## 커서 설계에서 지켜야 할 것

**커서는 유일해야 합니다.** `created_at`만으로 커서를 만들면 같은 시각에 생성된 행들 사이에서 순서가 보장되지 않아 중복이나 누락이 생깁니다. 그래서 정렬 컬럼 뒤에 기본키를 붙여 `(created_at, id)`처럼 유일한 조합을 만듭니다.

**커서는 불투명하게 내보냅니다.** API 응답에는 `(created_at, id)` 값을 그대로 노출하기보다, base64 등으로 감싼 불투명 토큰으로 내보내는 편이 좋습니다. 클라이언트가 커서 내부 구조에 의존하지 않게 되어, 나중에 정렬 기준이 바뀌어도 API 계약이 깨지지 않습니다.

```json
{
  "items": [...],
  "nextCursor": "eyJjcmVhdGVkQXQiOiIyMDI2LTA0LTAxVDEwOjMwOjAwWiIsImlkIjo4MTIzfQ"
}
```

**정렬 기준이 바뀌면 커서도 바뀝니다.** 커서는 특정 정렬 순서 위에서만 의미가 있습니다. 사용자가 "인기순"으로 정렬을 바꾸면 이전 커서는 무효입니다. 커서 토큰 안에 정렬 기준을 함께 넣어 검증하면 잘못된 조합을 막을 수 있습니다.

## 커서 기반 페이지네이션 구현: 토큰 만들기부터 다음 페이지 판단까지

원리는 SQL 두 줄이지만, API로 내보내려면 손이 더 갑니다. 아래는 Node.js(16 이상)와 PostgreSQL 기준으로 커서 토큰을 만들고, 받은 토큰을 검증해서 다음 페이지를 조회하는 최소 구현입니다. `db.query`는 행 배열을 돌려준다고 가정했습니다(node-postgres라면 `result.rows`).

```js
const SORT = 'created_desc';

function encodeCursor(row) {
  // created_at은 DB에서 문자열로 받아 정밀도를 그대로 보존한다
  const payload = { s: SORT, t: row.created_at_text, id: row.id };
  return Buffer.from(JSON.stringify(payload)).toString('base64url');
}

function decodeCursor(token) {
  if (!token) return null;
  let payload;
  try {
    payload = JSON.parse(Buffer.from(token, 'base64url').toString('utf8'));
  } catch {
    throw new Error('INVALID_CURSOR');
  }
  if (payload.s !== SORT || typeof payload.t !== 'string' || !Number.isInteger(payload.id)) {
    throw new Error('INVALID_CURSOR');
  }
  return payload;
}

async function listPosts(db, { cursor, limit = 20 }) {
  const size = Math.min(Math.max(Number(limit) || 20, 1), 100);
  const c = decodeCursor(cursor);
  const cols = 'id, title, created_at, created_at::text AS created_at_text';
  const rows = c
    ? await db.query(
        `SELECT ${cols} FROM posts
         WHERE (created_at, id) < ($1, $2)
         ORDER BY created_at DESC, id DESC
         LIMIT $3`,
        [c.t, c.id, size + 1],
      )
    : await db.query(
        `SELECT ${cols} FROM posts
         ORDER BY created_at DESC, id DESC
         LIMIT $1`,
        [size + 1],
      );

  const hasMore = rows.length > size;
  const items = hasMore ? rows.slice(0, size) : rows;
  return {
    items,
    nextCursor: hasMore ? encodeCursor(items[items.length - 1]) : null,
  };
}
```

코드에서 눈여겨볼 지점은 세 군데입니다.

- **`LIMIT size + 1`로 다음 페이지 유무를 판단합니다.** 한 행을 더 읽어 보고 남으면 `nextCursor`를 내보내고, 모자라면 `null`로 끝을 알립니다. 다음 페이지가 있는지 확인하려고 `COUNT(*)`를 따로 낼 필요가 없습니다.
- **토큰 안에 정렬 기준(`s`)을 넣고, 디코딩할 때 검증합니다.** 앞에서 말한 "정렬이 바뀌면 커서도 무효"를 코드로 강제하는 부분입니다. 깨진 토큰이나 다른 정렬의 토큰이 들어오면 400 응답으로 돌려보내면 됩니다.
- **`created_at`을 JavaScript `Date`로 왕복시키지 않습니다.** 이 부분이 실무에서 가장 자주 밟는 함정입니다.

마지막 항목은 조금 더 설명이 필요합니다. PostgreSQL의 `timestamp`는 1마이크로초 해상도로 값을 저장합니다(출처: [PostgreSQL Documentation - Date/Time Types](https://www.postgresql.org/docs/current/datatype-datetime.html)). 반면 JavaScript `Date`는 밀리초까지만 담습니다. 커서를 `row.created_at.toISOString()`으로 만들면 마이크로초가 잘린 값이 비교 기준이 되고, 같은 밀리초 안에 생성된 행들 사이에서 경계가 어긋납니다. 그래서 위 코드는 `created_at::text`로 원래 값을 문자열 그대로 받아 토큰에 넣습니다. 컬럼 자체를 밀리초 정밀도(`timestamptz(3)`)로 정의해 두는 것도 방법입니다.

나는 커서 방식으로 바꾼 직후에 "가끔 한두 건이 빠진다"는 증상을 만나면 인덱스보다 이 정밀도 문제를 먼저 의심합니다. 재현이 드물고 로그로도 잘 안 보여서, 원인을 모르면 한참 헤매는 종류의 버그입니다.

MySQL을 쓴다면 `$1` 대신 `?` 자리표시자를 쓰고, `created_at::text` 대신 `DATE_FORMAT(created_at, '%Y-%m-%d %H:%i:%s.%f')`처럼 소수 초까지 포함한 문자열로 받으면 같은 구조가 그대로 통합니다. 행 값 비교가 인덱스 범위 검색으로 잘 풀리지 않는 버전이라면 앞에서 본 `OR` 형태 조건으로 바꿉니다.

## 그래도 OFFSET을 쓰는 경우

커서 방식이 항상 답은 아닙니다. OFFSET이 맞는 상황도 분명히 있습니다.

- **페이지 번호 점프가 필수인 UI.** 관리자 화면처럼 "37페이지로 바로 이동"이 요구되면 커서로는 표현할 수 없습니다.
- **전체 페이지 수 표시.** 커서 방식은 전체 개수를 모릅니다. `COUNT(*)`를 따로 내야 하는데, 이 비용이 OFFSET 절감분을 상쇄할 수 있습니다.
- **데이터가 작고 변동이 적은 목록.** 수천 행 수준이면 OFFSET의 비용도, 중복·누락 문제도 체감되지 않습니다.

그래서 나는 한 서비스 안에서도 화면 단위로 나눠 씁니다. 사용자향 무한 스크롤은 커서, 내부 관리자 화면은 OFFSET. 둘 중 하나로 통일하려다 오히려 양쪽 다 불편해지는 경우를 여러 번 봤습니다.

## 자주 묻는 질문

**Q. UUID를 기본키로 쓰는데 커서로 써도 되나요?**
정렬 가능한 값이어야 커서가 됩니다. 무작위 UUID(v4)는 생성 순서와 정렬 순서가 무관하므로 단독 커서로는 부적합합니다. `created_at`을 앞에 두고 UUID를 유일성 보조로만 쓰거나, 시간 정렬이 가능한 UUIDv7 같은 형식을 검토하세요.

**Q. 총 개수와 무한 스크롤을 둘 다 원하는데요?**
전체 개수는 별도 쿼리로 한 번만 조회하고(필요하면 근사치나 캐시), 목록 이동은 커서로 처리하는 분리가 일반적입니다. 매 페이지마다 `COUNT(*)`를 내는 것이 가장 비쌉니다.

**Q. 이전 페이지로 돌아가기는 어떻게 구현하나요?**
방향을 뒤집은 조건(`>`)과 역정렬로 "이전 커서"를 구현할 수 있습니다. 다만 UI가 무한 스크롤이라면 클라이언트가 이미 받은 목록을 유지하는 편이 단순합니다.

## 화면 성격으로 고르는 기준표

원리를 다 짚었어도 결국 손이 가는 건 한 장짜리 판단표입니다. 새 목록 화면을 잡을 때 나는 이 표부터 봅니다.

| 화면 성격 | 권장 방식 | 이유 |
| --- | --- | --- |
| 무한 스크롤·피드 | 커서 | 깊이와 무관한 성능, 목록 변동에도 중복·누락 없음 |
| 대용량 목록, 뒤쪽 페이지 접근이 잦음 | 커서 | OFFSET의 "읽고 버리기" 비용을 없앰 |
| "37페이지로 이동" 같은 번호 점프가 필수 | OFFSET | 임의 위치는 커서로 표현 불가 |
| 전체 페이지 수를 꼭 노출해야 함 | OFFSET | 커서는 총 개수를 모름 (별도 `COUNT` 필요) |
| 수천 행 규모의 작고 변동 적은 목록 | 둘 다 무방 | 비용도 중복 문제도 체감되지 않으니 구현 단순한 쪽 |

커서를 쓰기로 했다면 기준 컬럼은 유일한 조합(`created_at, id`)으로 만들고, API로는 불투명 토큰으로 내보낸다는 두 가지만 지키면 됩니다. 한 서비스 안에서 화면마다 방식이 갈려도 괜찮습니다. 오히려 하나로 통일하려다 양쪽 다 불편해지는 경우를 더 자주 봤습니다.

## 출처

- [PostgreSQL Documentation - LIMIT and OFFSET](https://www.postgresql.org/docs/current/queries-limit.html)
- [Use The Index, Luke - Paging Through Results](https://use-the-index-luke.com/sql/partial-results/fetch-next-page)
- [PostgreSQL Documentation - Date/Time Types](https://www.postgresql.org/docs/current/datatype-datetime.html)
