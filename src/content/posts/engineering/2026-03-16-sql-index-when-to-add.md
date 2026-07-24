---
title: "느린 쿼리에 인덱스부터 걸기 전에 봐야 할 기준"
slug: "sql-index-when-to-add"
author: "감성개발자"
date: "2026-03-16"
summary: "쿼리가 느릴 때 인덱스를 걸지 말지 판단하는 순서를 짚어본다. 실행 계획으로 원인을 확인하는 방법, 인덱스가 효과 없는 패턴, 복합 인덱스 컬럼 순서, 인덱스를 늘렸을 때 치르는 쓰기 비용까지 현장 기준으로 본다."
oneLineSummary: "인덱스는 실행 계획으로 풀스캔을 확인한 뒤에 걸어야 하고, 컬럼 순서와 쓰기 비용까지 같이 봐야 한다."
tags: [SQL, Database, MySQL, 성능, 개발팁]
status: "published"
---

에이전시 시절 유지보수로 넘겨받은 프로젝트에는 공통점이 하나 있었습니다. 느려졌다는 화면을 열어보면 WHERE에 쓰인 컬럼마다 인덱스가 덕지덕지 붙어 있는데, 그런데도 여전히 느렸습니다. 앞사람이 "일단 인덱스부터 걸어보자"로 대응한 흔적이었습니다. 운이 좋으면 빨라지지만, 대개는 아무 변화가 없거나 쓰기 성능만 축났습니다.

인덱스는 실행 계획을 먼저 확인한 뒤, 쿼리가 실제로 어떤 경로로 데이터를 읽는지 보고 거는 것입니다. 이 순서를 몸에 붙이고 나서야 인덱스로 헛발질하는 일이 줄었습니다.

아래 내용은 MySQL 8.0 공식 문서의 인덱스 동작 설명과 PostgreSQL 공식 문서의 인덱스 챕터를 바탕으로, 데이터베이스 종류와 무관하게 통하는 판단 순서로 정리했습니다.

## 인덱스를 걸기 전에: 실행 계획부터

쿼리가 느린 이유는 하나가 아닙니다. 풀스캔일 수도 있고, 인덱스는 타는데 반환 행이 너무 많을 수도 있고, 정렬이나 임시 테이블이 병목일 수도 있습니다. 이걸 구분해주는 도구가 실행 계획입니다.

MySQL에서는 쿼리 앞에 `EXPLAIN`을 붙이면 옵티마이저가 선택한 접근 방식을 보여줍니다.

```sql
EXPLAIN SELECT * FROM orders
WHERE user_id = 42 AND status = 'paid'
ORDER BY created_at DESC
LIMIT 20;
```

먼저 볼 컬럼은 세 가지입니다.

| 컬럼 | 의미 | 위험 신호 |
| --- | --- | --- |
| `type` | 테이블 접근 방식 | `ALL`(풀스캔), `index`(인덱스 풀스캔) |
| `rows` | 옵티마이저가 예상하는 스캔 행 수 | 반환 행보다 훨씬 큰 값 |
| `Extra` | 추가 작업 | `Using filesort`, `Using temporary` |

`type`이 `ALL`이고 `rows`가 수십만 단위라면 인덱스가 없거나 못 쓰고 있다는 뜻입니다. 반대로 `type`이 `ref`나 `range`인데도 느리다면, 인덱스 문제가 아니라 반환 데이터 양이나 정렬, 조인 순서 쪽을 봐야 합니다. 인덱스를 추가하는 결정은 이 확인 다음에 와야 합니다.

MySQL 공식 문서도 인덱스의 역할을 "특정 컬럼 값을 가진 행을 빠르게 찾는 것"으로 설명하면서, 인덱스가 없으면 첫 행부터 전체 테이블을 읽어야 하고 테이블이 클수록 이 비용이 커진다고 설명합니다. (출처: [MySQL 8.0 Reference Manual - How MySQL Uses Indexes](https://dev.mysql.com/doc/refman/8.0/en/mysql-indexes.html))

## 인덱스가 있어도 못 타는 패턴

인덱스를 걸었는데 실행 계획이 그대로라면, 쿼리가 인덱스를 쓸 수 없는 형태일 가능성이 높습니다. 에이전시 시절 유지보수로 넘겨받은 프로젝트에서 "인덱스는 걸려 있는데 왜 그대로 느리냐"는 질문을 받으면, 원인은 거의 아래 네 가지 중 하나였습니다.

**컬럼을 함수나 연산으로 감싼 경우.** 인덱스는 컬럼의 원본 값 기준으로 정렬되어 있습니다. 컬럼을 가공하면 그 정렬을 쓸 수 없습니다.

```sql
-- 인덱스를 못 탈 수 있는 형태
SELECT * FROM orders WHERE DATE(created_at) = '2026-03-10';

-- 범위 조건으로 바꾸면 인덱스를 탈 수 있음
SELECT * FROM orders
WHERE created_at >= '2026-03-10 00:00:00'
  AND created_at <  '2026-03-11 00:00:00';
```

**앞쪽 와일드카드 `LIKE`.** `LIKE 'kim%'`은 인덱스 범위 검색이 되지만, `LIKE '%kim'`은 문자열의 시작을 알 수 없어 인덱스 정렬을 쓸 수 없습니다. 뒤쪽 일치 검색이 자주 필요하다면 검색 엔진이나 전문 검색 인덱스를 검토하는 편이 맞습니다.

**타입이 다른 비교.** 문자열 컬럼을 숫자와 비교하면(`phone = 01012345678` 같은 형태) 데이터베이스가 암묵적 형변환을 하면서 인덱스를 포기할 수 있습니다. 비교 값의 타입을 컬럼 타입과 맞춰야 합니다.

**선택도가 낮은 컬럼 단독 인덱스.** `status` 값이 세 종류뿐인 컬럼에 단독 인덱스를 걸어도, 조건에 걸리는 행이 전체의 30~40%라면 옵티마이저는 인덱스를 버리고 풀스캔을 택할 수 있습니다. 인덱스는 조건으로 행이 확 줄어들 때 효과가 있습니다.

## 복합 인덱스는 컬럼 순서가 설계다

조건이 두 개 이상이라면 컬럼별 단독 인덱스보다 복합 인덱스가 효과적인 경우가 많습니다. 이때 핵심 규칙은 하나입니다. **복합 인덱스는 왼쪽 컬럼부터 순서대로만 쓰입니다.** MySQL 문서는 이를 leftmost prefix 규칙으로 설명합니다. `(user_id, status, created_at)` 인덱스가 있다면 `user_id` 단독, `user_id + status`, 세 컬럼 전부는 인덱스를 탈 수 있지만, `status` 단독이나 `created_at` 단독 조건은 이 인덱스를 쓰지 못합니다. (출처: [MySQL 8.0 Reference Manual - Multiple-Column Indexes](https://dev.mysql.com/doc/refman/8.0/en/multiple-column-indexes.html))

순서를 정하는 실무 기준은 이렇습니다.

- 동등 비교(`=`) 조건에 쓰이는 컬럼을 앞에 둡니다.
- 범위 비교(`>`, `<`, `BETWEEN`)나 정렬에 쓰이는 컬럼을 뒤에 둡니다.
- 범위 조건 컬럼 뒤의 컬럼은 인덱스 효과가 떨어진다는 점을 기억합니다.

위 주문 조회 예시라면 `(user_id, status, created_at)` 순서가 자연스럽습니다. `user_id`와 `status`로 행을 좁힌 뒤, 남은 행이 `created_at` 순으로 정렬된 상태이므로 `ORDER BY created_at DESC LIMIT 20`을 별도 정렬 없이 처리할 수 있습니다. 실행 계획에서 `Using filesort`가 사라졌는지로 확인할 수 있습니다.

## 인덱스의 비용: 조회가 빨라지는 만큼 쓰기가 느려진다

인덱스는 공짜가 아닙니다. 행을 넣거나 수정하거나 지울 때마다 관련 인덱스도 함께 갱신해야 합니다. PostgreSQL 공식 문서도 인덱스가 데이터베이스 시스템에 유지 부담을 더하므로, 자주 쓰이는 쿼리에 도움이 되는 인덱스만 유지하는 것이 좋다고 설명합니다. (출처: [PostgreSQL Documentation - Indexes](https://www.postgresql.org/docs/current/indexes.html))

CTO로 넘어온 뒤로는 인덱스를 추가할 때 조회 속도만큼 쓰기 부담을 먼저 봅니다. 쓰기가 잦은 테이블에 인덱스를 하나 더 얹는 결정은 팀 전체가 나눠 갚는 비용이기 때문입니다. 인덱스를 정리할 때 쓰는 기준은 이렇습니다.

- 같은 컬럼으로 시작하는 인덱스가 중복되어 있지 않은지 확인합니다. `(user_id)` 인덱스는 `(user_id, status)` 인덱스가 있으면 대부분 불필요합니다.
- 쓰기가 많은 테이블일수록 인덱스 개수에 보수적으로 접근합니다.
- 사용되지 않는 인덱스를 주기적으로 확인합니다. MySQL은 `sys.schema_unused_indexes`, PostgreSQL은 `pg_stat_user_indexes`로 볼 수 있습니다.

## 자주 묻는 질문

**Q. 인덱스를 걸면 무조건 빨라지는 것 아닌가요?**
아닙니다. 조건으로 행이 충분히 줄어들 때만 효과가 있습니다. 전체 행의 상당 비율을 읽어야 하는 쿼리라면 옵티마이저가 인덱스를 무시하고 풀스캔을 선택하는 것이 오히려 정상 동작입니다.

**Q. 운영 중인 큰 테이블에 인덱스를 추가해도 되나요?**
MySQL 8.0과 PostgreSQL 모두 온라인 인덱스 생성을 지원하지만, 생성 중 부하와 디스크 사용량 증가는 피할 수 없습니다. 트래픽이 적은 시간대에 진행하고, PostgreSQL이라면 `CREATE INDEX CONCURRENTLY`를 검토하세요.

**Q. 실행 계획의 rows 값이 실제와 다른데요?**
`rows`는 통계 기반 추정치입니다. 데이터 분포가 크게 바뀌었는데 통계가 오래됐다면 어긋날 수 있습니다. MySQL은 `ANALYZE TABLE`, PostgreSQL은 `ANALYZE`로 통계를 갱신한 뒤 다시 확인하세요.

## 출처

- [MySQL 8.0 Reference Manual - How MySQL Uses Indexes](https://dev.mysql.com/doc/refman/8.0/en/mysql-indexes.html)
- [MySQL 8.0 Reference Manual - Multiple-Column Indexes](https://dev.mysql.com/doc/refman/8.0/en/multiple-column-indexes.html)
- [PostgreSQL Documentation - Indexes](https://www.postgresql.org/docs/current/indexes.html)
