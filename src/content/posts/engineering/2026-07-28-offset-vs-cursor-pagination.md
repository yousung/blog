---
title: "페이지 번호를 포기해야 할 때, OFFSET과 커서 페이지네이션의 갈림길"
slug: "offset-vs-cursor-pagination"
author: "감성개발자"
date: "2026-07-28"
summary: "목록 API가 뒤쪽 페이지에서만 느려질 때 OFFSET의 비용, 커서 방식의 전제 조건, 정렬 키 유일성, COUNT 쿼리, 응답 스펙 변경 비용을 어떤 순서로 따져야 하는지 짚어본다."
oneLineSummary: "OFFSET과 커서 중 무엇을 쓸지는 데이터 양이 아니라 정렬 키의 유일성과 화면이 요구하는 이동 방식에서 갈린다."
tags: [페이지네이션, MySQL, Laravel, 쿼리, 성능]
status: "draft"
---

# 페이지 번호를 포기해야 할 때, OFFSET과 커서 페이지네이션의 갈림길

관리자 목록 화면에서 1페이지는 40ms, 900페이지는 4초가 나오는 상황을 본 적이 있습니다. 쿼리는 똑같습니다. 인덱스도 그대로입니다. 바뀐 건 `OFFSET` 뒤에 붙은 숫자 하나뿐입니다. 이때 개발자가 가장 먼저 손대는 곳은 보통 인덱스인데, 정작 문제는 인덱스가 아니라 "건너뛰기"라는 동작 자체에 있습니다.

이 글은 목록 API를 만들 때 OFFSET 방식과 커서 방식 중 무엇을 고를지 판단하는 순서를 정리한 것입니다. 성능 비교표를 만드는 것이 목적이 아니라, "우리 화면이 정말 페이지 번호를 필요로 하는가"부터 되묻는 쪽에 가깝습니다. MySQL, PostgreSQL 공식 문서와 Laravel 페이지네이션 문서를 기준으로 삼았고, 예시 코드는 PHP와 SQL로 적었지만 판단 기준 자체는 언어와 무관합니다.

먼저 이 글에서 다룰 다섯 가지를 미리 적어 둡니다.

- `OFFSET`으로 건너뛴 행도 서버가 계산은 다 한다는 사실
- 페이지 번호 UI가 실제로 쓰이는 화면과 그렇지 않은 화면의 구분
- 커서 페이지네이션이 요구하는 정렬 키 조건
- 전체 건수를 세는 `COUNT` 쿼리가 따로 만드는 비용
- 이미 배포된 API의 응답 형태를 바꿀 때 감수해야 하는 것

## 건너뛴 행은 사라지지 않고 계산됩니다

`LIMIT 20 OFFSET 18000`이라는 쿼리를 보면 20건만 읽을 것 같지만 그렇지 않습니다. PostgreSQL 공식 문서는 이 동작을 아주 짧게 못 박아 둡니다. `OFFSET` 절이 건너뛴 행도 서버 내부에서는 여전히 계산되어야 하므로 큰 `OFFSET`은 비효율적일 수 있다는 설명입니다. (출처: [PostgreSQL Documentation - LIMIT and OFFSET](https://www.postgresql.org/docs/current/queries-limit.html))

MySQL도 결이 비슷합니다. MySQL 8.4 레퍼런스 매뉴얼의 LIMIT 최적화 문서는 `ORDER BY`가 인덱스로 처리되면 매우 빠르지만, filesort가 필요한 경우에는 `LIMIT` 없이 조건에 맞는 행을 모두 뽑아 대부분을 정렬한 뒤에야 앞쪽 행을 찾는다고 설명합니다. (출처: [MySQL 8.4 Reference Manual - LIMIT Query Optimization](https://dev.mysql.com/doc/refman/8.4/en/limit-optimization.html))

정리하면 비용은 반환하는 행 수가 아니라 "도달하기 위해 지나쳐야 하는 행 수"에 비례합니다. 그래서 이런 현상이 생깁니다.

| 증상 | 실제 원인 | 인덱스 추가로 해결되나 |
| --- | --- | --- |
| 1페이지는 빠른데 뒷페이지만 느림 | OFFSET 누적 스캔 | 아니오. 스캔 범위 자체가 커짐 |
| 전체 페이지가 고르게 느림 | 정렬 키에 인덱스 없음, filesort | 예. 정렬 인덱스가 효과 있음 |
| 목록은 빠른데 응답이 느림 | 전체 건수 COUNT, N+1 조회 | 부분적. 쿼리 구조를 봐야 함 |
| 새로고침할 때마다 항목이 중복되거나 사라짐 | 페이지 이동 중 데이터 삽입·삭제 | 아니오. 페이징 방식 문제 |

마지막 줄은 성능 문제가 아니라 정확성 문제인데, 실무에서는 오히려 이쪽이 더 오래 방치됩니다. 사용자가 1페이지를 보는 동안 새 글 3건이 올라오면, 2페이지로 넘어갔을 때 이미 봤던 항목이 다시 나타납니다. 반대로 삭제가 일어나면 한 번도 보지 못한 항목이 조용히 건너뛰어집니다. Laravel 문서도 쓰기가 잦은 데이터셋에서는 OFFSET 방식이 레코드를 건너뛰거나 중복해서 보여줄 수 있다고 같은 문제를 지적합니다. (출처: [Laravel Docs - Cursor Pagination](https://laravel.com/docs/12.x/pagination#cursor-pagination))

## 페이지 번호가 정말 필요한 화면인지부터 봅니다

기술을 바꾸기 전에 화면을 먼저 봐야 합니다. 페이지 번호 UI는 공짜가 아닙니다. "3페이지로 바로 가기"를 지원하려면 전체 건수를 알아야 하고, 임의의 위치로 점프할 수 있어야 하고, 그 위치가 재현 가능해야 합니다. 세 가지 모두 비용입니다.

실제로 페이지 번호가 필요한 화면은 생각보다 좁습니다.

- 정말 필요한 경우: 운영자가 "몇 건 중 몇 번째"를 근거로 대화하는 백오피스, 감사·정산처럼 전체 건수가 업무 의미를 갖는 목록, 인쇄나 엑셀 내보내기 범위를 사람이 지정하는 화면
- 대체로 필요 없는 경우: 모바일 무한 스크롤, 알림·피드·타임라인, 채팅 로그, 최신순으로만 소비되는 목록, 검색 결과 상위 몇 십 건만 실제로 열람되는 화면

두 번째 그룹에서는 페이지 번호를 유지하는 비용이 거의 낭비입니다. 사용자는 837페이지로 점프하지 않고, 통계는 별도 대시보드에서 봅니다. 그런데도 "예전부터 그렇게 만들었으니까" 전체 건수를 세고 있는 API가 흔합니다.

판단이 애매하다면 로그를 보면 됩니다. 실제로 요청된 `page` 파라미터 값의 분포를 하루치만 뽑아 보세요. 대부분 1에서 5 사이에 몰려 있고 그 뒤로는 크롤러 요청뿐이라면, 페이지 번호는 이미 기능이 아니라 장식입니다.

## 커서는 위치를 숫자가 아니라 조건으로 표현합니다

커서 페이지네이션의 아이디어는 단순합니다. "몇 개를 건너뛸까"가 아니라 "어디서부터 이어서 읽을까"를 조건으로 쓰는 것입니다. Laravel 문서는 두 방식이 만드는 SQL을 나란히 보여줍니다. OFFSET 방식이 `select * from users order by id asc limit 15 offset 15`라면, 커서 방식은 `select * from users where id > 15 order by id asc limit 15`가 됩니다. 앞의 쿼리는 지나온 데이터를 전부 훑지만, 뒤의 쿼리는 인덱스 위의 시작점으로 곧장 이동합니다. (출처: [Laravel Docs - Cursor Pagination](https://laravel.com/docs/12.x/pagination#cursor-pagination))

Laravel을 쓴다면 메서드 하나만 바꾸면 됩니다.

```php
// OFFSET 기반: 전체 건수 조회 + 페이지 번호 링크
$posts = Post::where('status', 'published')->paginate(20);

// 커서 기반: order by가 반드시 있어야 하고, 정렬 컬럼은 해당 테이블 소유여야 함
$posts = Post::where('status', 'published')
    ->orderBy('published_at', 'desc')
    ->orderBy('id', 'desc')
    ->cursorPaginate(20);
```

여기서 `orderBy`를 두 번 건 이유가 이 글의 핵심입니다.

## 정렬 키가 유일하지 않으면 커서는 흔들립니다

커서 방식은 "정렬 순서상 이 값보다 뒤"라는 조건으로 위치를 재구성합니다. 그래서 정렬 키가 중복되면 경계에서 문제가 생깁니다. `published_at`이 초 단위이고 같은 시각에 5건이 등록됐다면, `where published_at < :cursor` 조건은 그 5건 중 일부를 통째로 건너뛰거나 다음 페이지에서 다시 보여줄 수 있습니다.

Laravel 문서도 커서 페이지네이션의 제약을 명시합니다. 정렬이 최소 하나의 유일한 컬럼 또는 유일한 조합에 기반해야 하고, `null` 값을 가진 컬럼은 지원하지 않는다는 조건입니다. (출처: [Laravel Docs - Cursor Pagination](https://laravel.com/docs/12.x/pagination#cursor-pagination))

MySQL 문서도 같은 함정을 다른 각도에서 설명합니다. `ORDER BY` 컬럼 값이 동일한 행들의 순서는 비결정적이며, 실행 계획에 따라 달라질 수 있어서 `LIMIT`이 있을 때와 없을 때 순서가 달라질 수 있다는 것입니다. 순서를 확정하려면 `ORDER BY`에 컬럼을 더 넣어 결정적으로 만들라는 권고가 따라옵니다. (출처: [MySQL 8.4 Reference Manual - LIMIT Query Optimization](https://dev.mysql.com/doc/refman/8.4/en/limit-optimization.html))

즉 커서 방식으로 넘어갈 때 반드시 해야 하는 일은 **정렬 키 뒤에 유일 키를 붙여 tie-breaker를 만드는 것**입니다. 정렬 키가 하나뿐인 단순한 목록이라면 기본 키 하나로 충분하지만, "최신순" 같은 정렬은 거의 항상 복합 조건이 됩니다.

프레임워크 없이 직접 구현한다면 이런 형태가 됩니다. PDO 기준으로 동작하는 최소 예시입니다.

```php
<?php

declare(strict_types=1);

/**
 * (published_at DESC, id DESC) 정렬 기준 커서 페이지네이션.
 * 커서는 마지막 행의 (published_at, id) 쌍을 그대로 담는다.
 */
function fetchPage(PDO $pdo, ?string $cursor, int $limit = 20): array
{
    $params = [':limit' => $limit + 1];
    $keyset = '';

    if ($cursor !== null) {
        [$publishedAt, $id] = decodeCursor($cursor);

        // 행 값 비교로 (published_at, id)를 한 덩어리로 다룬다.
        // MySQL 8.x, PostgreSQL, SQLite 모두 지원하는 표준 문법이다.
        $keyset = 'AND (published_at, id) < (:published_at, :id)';
        $params[':published_at'] = $publishedAt;
        $params[':id'] = $id;
    }

    $sql = <<<SQL
        SELECT id, title, published_at
        FROM posts
        WHERE status = 'published'
        {$keyset}
        ORDER BY published_at DESC, id DESC
        LIMIT :limit
    SQL;

    $stmt = $pdo->prepare($sql);
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value, $key === ':limit' || $key === ':id' ? PDO::PARAM_INT : PDO::PARAM_STR);
    }
    $stmt->execute();

    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // limit + 1건을 요청해 두면 COUNT 없이 다음 페이지 존재 여부를 알 수 있다.
    $hasMore = count($rows) > $limit;
    if ($hasMore) {
        array_pop($rows);
    }

    $last = $rows[count($rows) - 1] ?? null;

    return [
        'items' => $rows,
        'nextCursor' => $hasMore && $last !== null
            ? encodeCursor($last['published_at'], (int) $last['id'])
            : null,
    ];
}

function encodeCursor(string $publishedAt, int $id): string
{
    return rtrim(strtr(base64_encode(json_encode([$publishedAt, $id])), '+/', '-_'), '=');
}

/** @return array{0: string, 1: int} */
function decodeCursor(string $cursor): array
{
    $json = base64_decode(strtr($cursor, '-_', '+/'), true);
    $decoded = $json === false ? null : json_decode($json, true);

    if (!is_array($decoded) || count($decoded) !== 2) {
        throw new InvalidArgumentException('Invalid cursor');
    }

    return [(string) $decoded[0], (int) $decoded[1]];
}
```

이 코드에서 실무적으로 의미 있는 부분은 세 곳입니다.

첫째, `(published_at, id) < (:published_at, :id)`라는 행 값 비교입니다. 이걸 `published_at < :a OR (published_at = :a AND id < :b)`로 풀어써도 결과는 같지만, 튜플 비교 쪽이 의도가 분명하고 `(published_at, id)` 복합 인덱스와도 잘 맞습니다.

둘째, `LIMIT`에 `+1`을 더해 한 건 더 읽는 방식입니다. 다음 페이지가 있는지 알기 위해 전체 건수를 세지 않아도 됩니다.

셋째, 커서를 base64로 감싼 이유는 보안이 아니라 계약입니다. 커서를 불투명한 문자열로 정의해 두면, 나중에 정렬 키를 하나 더 추가해도 클라이언트 코드를 고치지 않아도 됩니다. 반대로 커서에 원본 ID를 그대로 노출하면 그 값이 사실상 공개 API 스펙이 되어 버립니다.

## COUNT 쿼리는 따로 계산해야 합니다

목록 API가 느릴 때 범인이 목록 쿼리가 아니라 전체 건수 쿼리인 경우가 꽤 있습니다. Laravel 문서는 `paginate` 메서드가 레코드를 가져오기 전에 조건에 맞는 전체 레코드 수를 먼저 센다고 설명하고, 전체 페이지 수를 UI에 보여줄 계획이 없다면 그 카운트 쿼리는 불필요하므로 `simplePaginate`로 단일 쿼리만 실행할 수 있다고 안내합니다. (출처: [Laravel Docs - Pagination](https://laravel.com/docs/12.x/pagination))

그래서 선택지는 두 개가 아니라 세 개입니다.

- `paginate`: 페이지 번호와 전체 건수가 모두 필요할 때. 카운트 쿼리 1회 추가.
- `simplePaginate`: 이전·다음만 필요하지만 OFFSET 구조는 유지하고 싶을 때. 카운트는 없앴지만 뒷페이지 스캔 비용은 남는다.
- `cursorPaginate`: 이전·다음만 필요하고 뒷페이지 성능과 중복 방지가 중요할 때.

중간 단계인 `simplePaginate`가 의외로 쓸모 있습니다. 커서로 넘어가려면 응답 스펙과 클라이언트를 함께 고쳐야 하지만, 카운트 제거는 서버에서만 끝나는 변경이라 위험이 훨씬 작습니다. 총 건수가 화면 어딘가에 정말 필요하다면 근사치로 타협하는 방법도 있습니다. 검색 결과에 "1,000건 이상"이라고 표시하는 서비스들이 정확한 숫자를 몰라서 그러는 게 아닙니다. 세는 비용이 그 정보의 가치보다 크기 때문입니다.

## 이미 나간 API를 바꾸는 비용

기술적으로 옳은 선택이라고 해서 지금 바꿀 수 있는 건 아닙니다. 커서 방식은 응답 형태를 바꿉니다. `total`, `last_page`, `current_page`가 사라지고 `next_cursor`가 생깁니다. 앱 스토어 심사를 거쳐 배포되는 모바일 클라이언트가 붙어 있다면, 구버전 앱은 몇 달 동안 살아 있습니다.

이럴 때 쓸 수 있는 순서가 있습니다.

1. 새 파라미터를 추가만 한다. `?cursor=` 가 오면 커서 모드, `?page=` 가 오면 기존 모드로 동작시킨다.
2. 응답에 두 형태를 함께 담는다. 기존 필드는 유지하고 `next_cursor`를 추가한다.
3. 클라이언트를 커서 모드로 전환하고, `page` 파라미터 사용률을 모니터링한다.
4. 사용률이 충분히 떨어지고 지원 종료 버전이 정리되면 OFFSET 경로를 제거한다.

이 과정에서 놓치기 쉬운 것이 정렬 조건 변경입니다. 커서 도입과 동시에 정렬 키를 `created_at`에서 `published_at`으로 바꾸는 식의 변경을 끼워 넣으면, 문제가 생겼을 때 원인이 커서인지 정렬인지 구분할 수 없습니다. 한 번에 하나씩 바꾸는 편이 결국 빠릅니다.

## 화면별로 어떤 방식이 맞는지

| 화면 | 권장 | 이유 |
| --- | --- | --- |
| 모바일 피드, 무한 스크롤 | 커서 | 뒤로 갈수록 느려지지 않고, 중복·누락이 없다 |
| 채팅·알림 로그 | 커서 | 쓰기가 잦고 최신순 단방향 이동이다 |
| 백오피스 검색 결과 | OFFSET 유지 | 운영자가 페이지 번호로 위치를 공유한다 |
| 정산·감사 목록 | OFFSET + 카운트 | 전체 건수 자체가 업무 데이터다 |
| 공개 목록 페이지 (SEO 필요) | OFFSET | 페이지 URL이 색인 대상이어야 한다 |
| 데이터 배치 처리 | 커서 | 100만 건을 OFFSET으로 순회하면 뒤로 갈수록 느려진다 |

마지막 줄은 특히 자주 사고가 납니다. 배치 스크립트에서 `LIMIT 1000 OFFSET $n`으로 전체 테이블을 도는 코드는 초반에는 멀쩡하다가 후반부에 급격히 느려집니다. 게다가 처리 중에 행이 삭제되면 건너뛰는 행이 생깁니다. 키 기반 순회로 바꾸면 두 문제가 동시에 사라집니다.

## 적용 전 확인할 것

- [ ] `page` 파라미터의 실제 사용 분포를 로그로 확인했다.
- [ ] 느린 쪽이 목록 쿼리인지 카운트 쿼리인지 분리해서 측정했다.
- [ ] 정렬 키에 유일 컬럼을 tie-breaker로 붙였다.
- [ ] 정렬에 쓰는 컬럼 조합에 인덱스가 있고 `EXPLAIN`으로 확인했다.
- [ ] 정렬 컬럼에 `null`이 들어갈 수 있는지 확인했다.
- [ ] 커서를 불투명 문자열로 정의해 내부 구조 변경 여지를 남겼다.
- [ ] 잘못된 커서가 들어왔을 때 500이 아니라 400으로 응답한다.
- [ ] 응답에서 제거할 필드가 있다면 폐기 일정과 대체 경로를 공지했다.
- [ ] 배치·내보내기 스크립트도 같은 기준으로 점검했다.

## FAQ

### 인덱스를 잘 걸면 OFFSET도 빨라지지 않나요?

정렬 자체는 빨라집니다. 인덱스 순서로 읽으면 filesort를 피할 수 있으니까요. 하지만 인덱스를 탄다고 해도 건너뛸 행을 순서대로 지나가는 일은 남습니다. 뒤쪽 페이지일수록 읽어야 하는 인덱스 엔트리가 늘어나는 구조는 인덱스로 없앨 수 없습니다.

### 커서 방식에서 "마지막 페이지로 가기"는 어떻게 하나요?

정렬을 뒤집으면 됩니다. 최신순 목록의 마지막 페이지는 오래된 순으로 첫 페이지를 조회한 뒤 결과를 역순으로 뒤집는 것과 같습니다. 다만 임의의 N번째 페이지로 점프하는 기능은 커서 방식으로 만들 수 없습니다. 그게 필요하다면 애초에 커서로 갈 화면이 아닙니다.

### 커서에 노출되는 ID가 보안 문제가 되지 않나요?

base64는 인코딩이지 암호화가 아니므로 누구나 디코딩할 수 있습니다. 순차 증가하는 ID가 노출되는 것이 문제라면 커서 형식이 아니라 식별자 설계를 봐야 합니다. UUID나 ULID를 쓰거나, 커서에 서명을 붙여 위조를 막는 방법도 있습니다. 다만 서명은 위조 방지일 뿐 내용 은닉은 아닙니다.

### 사용자가 정렬 기준을 바꿀 수 있는 화면은 어떻게 하나요?

정렬이 바뀌면 기존 커서는 의미를 잃습니다. 정렬 조건을 커서 안에 함께 담고, 요청의 정렬 조건과 다르면 커서를 무시하고 첫 페이지부터 시작하는 편이 안전합니다. 조용히 이상한 결과를 주는 것보다 첫 페이지로 되돌리는 쪽이 디버깅하기 쉽습니다.

### 이미 잘 돌아가는 서비스도 지금 바꿔야 하나요?

뒷페이지 요청이 거의 없다면 바꿀 이유가 없습니다. 이 선택은 성능 좋은 방식으로 통일하는 문제가 아니라, 화면이 요구하는 이동 방식에 구현을 맞추는 문제입니다. 페이지 번호를 쓰는 사람이 있다면 OFFSET은 그 화면에서 여전히 맞는 답입니다.

## 남는 질문 하나

이 글의 판단 기준을 한 줄로 줄이면 이렇게 됩니다. 사용자가 목록에서 하는 행동이 "다음을 계속 본다"인지 "특정 위치로 이동한다"인지 먼저 정하고, 그 다음에 쿼리를 고른다.

당장 할 수 있는 일은 두 가지입니다. 접속 로그에서 `page` 값 분포를 뽑아 보세요. 그리고 가장 느린 목록 API에서 카운트 쿼리와 데이터 쿼리의 시간을 따로 재 보세요. 이 두 숫자만 손에 쥐어도 커서로 갈지, 카운트만 걷어낼지, 아무것도 안 해도 되는지가 대부분 결정됩니다.

정작 어려운 건 기술이 아니라 합의입니다. "총 1,284건"이라는 문구를 화면에서 지워도 되는지 묻는 자리에서 논의가 멈추는 경우가 많습니다. 그 숫자가 누구의 어떤 판단에 쓰이는지 확인하는 것이, 인덱스를 하나 더 만드는 것보다 먼저입니다.

## 출처

- [PostgreSQL Documentation - 7.6. LIMIT and OFFSET](https://www.postgresql.org/docs/current/queries-limit.html)
- [MySQL 8.4 Reference Manual - LIMIT Query Optimization](https://dev.mysql.com/doc/refman/8.4/en/limit-optimization.html)
- [Laravel Docs - Database: Pagination](https://laravel.com/docs/12.x/pagination)
- [Laravel Docs - Cursor Pagination](https://laravel.com/docs/12.x/pagination#cursor-pagination)
