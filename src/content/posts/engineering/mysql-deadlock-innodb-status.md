---
title: "MySQL 데드락 원인 찾기, Deadlock found 에러가 날 때 INNODB STATUS부터 읽는 법"
slug: "mysql-deadlock-innodb-status"
author: "감성개발자"
date: "2026-11-27"
summary: "MySQL에서 Deadlock found when trying to get lock 에러가 반복될 때 SHOW ENGINE INNODB STATUS의 LATEST DETECTED DEADLOCK 섹션을 읽는 순서, 갭 락·넥스트키 락이 끼는 조건, 락 획득 순서와 인덱스로 원인을 좁히는 방법, 재시도를 트랜잭션 단위로 몇 번까지 둘지에 대한 기준을 MySQL 8.4 매뉴얼 기준으로 짚습니다."
oneLineSummary: "데드락 재시도 한 줄을 붙이기 전에, 누가 무엇을 쥐고 무엇을 기다렸는지부터 읽어야 같은 에러가 줄어듭니다."
tags: ["MySQL", "데이터베이스", "동시성", "troubleshooting", "sql"]
status: "published"
---

에러 알림 채널에 이런 메시지가 하루에도 몇 번씩 올라오는 장면은 웹 서비스를 운영하다 보면 한 번쯤 만나게 됩니다.

`SQLSTATE[40001]: Serialization failure: 1213 Deadlock found when trying to get lock; try restarting transaction`

메시지 끝에 친절하게 "트랜잭션을 다시 시작하라"고 적혀 있으니, 대부분 재시도 코드를 한 줄 붙이고 넘어갑니다. 그런데 재시도는 증상을 가리는 일이지 원인을 찾는 일이 아닙니다. 원인이 그대로 남아 있으면 요청이 몰리는 시간대에 같은 에러가 함께 늘어나고, 재시도한 요청끼리 다시 부딪히면서 응답까지 느려질 수 있습니다. 그래서 재시도를 붙이기 전에 MySQL 데드락 원인 찾기부터 하는 편이 결국 덜 수고롭습니다.

이 글은 그 순서를 정리한 것입니다. `SHOW ENGINE INNODB STATUS`의 `LATEST DETECTED DEADLOCK` 섹션을 어떻게 읽는지, 갭 락과 넥스트키 락이 언제 끼어드는지, 락 획득 순서와 인덱스로 원인을 어떻게 좁히는지, 그리고 마지막으로 재시도는 어디서 몇 번 하는 게 맞는지까지 다룹니다. 락 동작과 출력 예시는 MySQL 8.4 공식 레퍼런스 매뉴얼을 이번에 다시 열어 확인한 내용만 옮겼습니다.

![좁은 서버실 통로에서 두 사람이 서로 상대가 가진 열쇠를 기다리며 카트를 맞댄 채 멈춰 선 일러스트](/images/tistory/mysql-deadlock-innodb-status/1.jpg)

<p style="font-size:13px;color:#888;text-align:center;">일러스트: 좁은 통로에서 서로 상대가 가진 열쇠를 기다리며 멈춰 선 두 사람</p>

## 데드락은 오래 기다리는 게 아니라 서로 기다리는 상태입니다

MySQL 매뉴얼은 데드락을 "여러 트랜잭션이 각자 상대가 필요로 하는 락을 쥐고 있어서 아무도 진행하지 못하는 상태"로 정의합니다. 모두 같은 자원을 기다리고 있으니 누구도 자기 락을 놓지 않습니다. 그래서 그냥 두면 영원히 풀리지 않습니다.

InnoDB는 기본값으로 데드락 감지가 켜져 있어서, 이런 상태를 발견하면 트랜잭션 하나를 골라 롤백합니다. 이 트랜잭션을 희생자(victim)라고 부릅니다. 매뉴얼에 따르면 InnoDB는 되도록 작은 트랜잭션을 고르려 하고, 크기는 삽입·수정·삭제한 행 수로 판단합니다. 애플리케이션이 받는 1213 에러가 바로 이 롤백의 결과입니다. 롤백당한 쪽은 에러를 받고, 살아남은 쪽은 기다리던 락을 받아 그대로 진행합니다.

여기서 자주 섞이는 개념이 락 대기 타임아웃입니다. 한쪽이 락을 오래 쥐고 있어서 다른 쪽이 `innodb_lock_wait_timeout`만큼 기다리다 포기하는 것은 "느린 트랜잭션" 문제이고, 데드락은 "순환 대기" 문제입니다. 해결 방향이 다릅니다. 앞의 것은 트랜잭션을 짧게 만드는 쪽, 뒤의 것은 락 순서와 범위를 손보는 쪽에 가깝습니다. 다만 데드락 감지를 끄면(`innodb_deadlock_detect`) InnoDB는 데드락도 이 타임아웃으로 풀기 때문에, 그런 서버에서는 둘이 같은 증상으로 보일 수 있습니다.

한 가지 더. 매뉴얼은 격리 수준이 데드락 가능성 자체를 바꾸지는 않는다고 적고 있습니다. 격리 수준은 읽기 동작을 바꾸는데, 데드락은 쓰기 때문에 생기기 때문입니다. 다만 같은 매뉴얼의 다른 절에서는 잠금 읽기를 쓴다면 READ COMMITTED를 시도해 보라고도 합니다. 이 차이는 아래 갭 락 이야기에서 다시 정리하겠습니다.

## 원인을 보기 전에 기록부터 남깁니다

데드락 분석에서 가장 흔한 실수는 정보가 사라진 뒤에 보러 가는 것입니다. `SHOW ENGINE INNODB STATUS`는 가장 최근 데드락 한 건만 보여 줍니다. 하루에 여러 번 나는 데드락이라면, 내가 명령을 치는 순간 보이는 건 원래 찾던 것이 아니라 그 뒤에 난 다른 데드락일 수 있습니다.

그래서 저는 데드락 알림이 반복되기 시작하면 먼저 `innodb_print_all_deadlocks`를 켭니다. 이 변수를 켜면 최근 한 건이 아니라 모든 데드락 정보가 MySQL 에러 로그에 쌓입니다. 매뉴얼도 디버깅이 끝나면 다시 끄라고 안내합니다. 발생 횟수는 `INFORMATION_SCHEMA.INNODB_METRICS`의 `lock_deadlocks` 값으로 볼 수 있어서, 수정 전후 비교 지표로 쓰기 좋습니다.

```sql
-- 1) 가장 최근 데드락 한 건
SHOW ENGINE INNODB STATUS\G

-- 2) 지금부터 발생하는 모든 데드락을 에러 로그에 남기기 (디버깅 끝나면 다시 OFF)
SET GLOBAL innodb_print_all_deadlocks = ON;
SELECT @@log_error;

-- 3) 서버가 켜진 뒤 누적된 데드락 횟수
SELECT `count` FROM INFORMATION_SCHEMA.INNODB_METRICS
WHERE NAME = 'lock_deadlocks';
```

관리형 데이터베이스를 쓴다면 서버 변수를 바꾸는 방법과 에러 로그를 보는 위치가 직접 설치한 MySQL과 다를 수 있으니, 해당 서비스 문서에서 설정 경로를 먼저 확인해 두는 것이 좋습니다. 커넥션 문제처럼 RDS 쪽 설정을 같이 봐야 하는 장애는 [RDS 커넥션 고갈 장애 해결기](/posts/rds-too-many-connections-troubleshooting/)에서도 비슷한 흐름으로 다뤘습니다.

## LATEST DETECTED DEADLOCK 섹션은 이 순서로 읽습니다

출력이 길고 16진수 덤프가 섞여 있어서 처음 보면 막막합니다. 실제로 봐야 할 곳은 많지 않습니다. 아래는 MySQL 매뉴얼의 데드락 예시 출력에서 레코드 덤프를 빼고 줄인 것입니다. 클라이언트 A와 B가 각각 Animals, Birds 테이블의 행을 공유 모드로 읽은 뒤 서로 상대 테이블의 행을 수정하려다 부딪힌 상황입니다.

```text
------------------------
LATEST DETECTED DEADLOCK
------------------------
*** (1) TRANSACTION:
TRANSACTION 43260, ACTIVE 186 sec starting index read
UPDATE Animals SET value=30 WHERE name='Aardvark'

*** (1) HOLDS THE LOCK(S):
RECORD LOCKS ... index PRIMARY of table `test`.`Birds` trx id 43260 lock mode S locks rec but not gap

*** (1) WAITING FOR THIS LOCK TO BE GRANTED:
RECORD LOCKS ... index PRIMARY of table `test`.`Animals` trx id 43260 lock_mode X locks rec but not gap waiting

*** (2) TRANSACTION:
TRANSACTION 43261, ACTIVE 209 sec starting index read
UPDATE Birds SET value=40 WHERE name='Buzzard'

*** (2) HOLDS THE LOCK(S):
RECORD LOCKS ... index PRIMARY of table `test`.`Animals` trx id 43261 lock mode S locks rec but not gap

*** (2) WAITING FOR THIS LOCK TO BE GRANTED:
RECORD LOCKS ... index PRIMARY of table `test`.`Birds` trx id 43261 lock_mode X locks rec but not gap waiting

*** WE ROLL BACK TRANSACTION (2)
```

제가 읽는 순서는 다섯 단계입니다.

1. **두 트랜잭션의 쿼리를 확인합니다.** `*** (1) TRANSACTION`과 `*** (2) TRANSACTION` 아래에 각자 실행 중이던 SQL이 나옵니다. 여기서 어떤 기능(주문, 재고, 정산 배치 등)의 코드인지 먼저 짐작합니다.
2. **HOLDS THE LOCK(S)로 무엇을 쥐고 있었는지 봅니다.** 테이블 이름, 인덱스 이름, 락 모드가 나옵니다.
3. **WAITING FOR THIS LOCK TO BE GRANTED로 무엇을 기다렸는지 봅니다.** 1번이 기다리는 것을 2번이 쥐고 있고, 2번이 기다리는 것을 1번이 쥐고 있으면 순환이 완성됩니다.
4. **인덱스 이름과 락 모드 문구를 해석합니다.** `PRIMARY`인지 보조 인덱스인지, 갭이 끼어 있는지가 원인을 가릅니다. 문구 해석은 아래 표에 정리했습니다.
5. **롤백된 쪽을 확인합니다.** `WE ROLL BACK TRANSACTION (N)` 줄이 어느 쪽이 희생자였는지 알려 줍니다. 애플리케이션 로그의 에러와 짝을 맞출 때 씁니다.

여기서 꼭 기억할 함정이 하나 있습니다. 위 예시에서 트랜잭션 (1)이 쥐고 있는 락은 Birds 테이블의 행인데, 화면에 보이는 쿼리는 Animals를 수정하는 UPDATE입니다. 그 Birds 락은 앞서 실행한 `SELECT ... FOR SHARE`가 잡은 것입니다. 즉 이 섹션은 각 트랜잭션이 **마지막으로 실행하던 문장**만 보여 주고, 락을 처음 잡은 문장은 보여 주지 않습니다. 그래서 출력만 보고 "UPDATE 두 개가 부딪혔다"고 결론 내리면 엉뚱한 곳을 고치게 됩니다. 실제 원인을 찾으려면 그 쿼리가 속한 트랜잭션 코드 전체를 열어서, 같은 트랜잭션 안에서 먼저 실행된 잠금 읽기·수정 문을 따라가야 합니다.

![긴 두루마리 기록에서 서로 맞물린 두 구간을 돋보기로 확인하는 개발자 일러스트](/images/tistory/mysql-deadlock-innodb-status/2.jpg)

<p style="font-size:13px;color:#888;text-align:center;">일러스트: 긴 기록지에서 서로 맞물린 두 구간을 돋보기로 짚어 보는 개발자</p>

| 출력 문구 | 뜻 | 먼저 의심할 것 |
|---|---|---|
| lock_mode X locks rec but not gap | 레코드 락(갭 없음) | 같은 행을 서로 다른 순서로 수정 |
| lock_mode X (뒤에 아무 문구 없음) | 넥스트키 락(레코드 + 앞쪽 갭) | 범위 조건, 비고유 인덱스 검색 |
| locks gap before rec insert intention waiting | 삽입 의도 락 대기 | 다른 트랜잭션이 잡은 갭에 INSERT 시도 |
| lock mode S | 공유 락 | FOR SHARE, 외래 키 검사, 중복 키 에러 |

데드락이 났을 때가 아니라 지금 막혀 있는 상황을 보고 싶다면 Performance Schema의 `data_locks`와 `data_lock_waits` 테이블이 더 편합니다. 누가 누구를 막고 있는지 행 단위로 보여 주고, 매뉴얼에 따르면 서버가 이미 가진 정보를 보여 주는 것이라 수집 부담도 따로 없습니다.

```sql
-- 지금 기다리는 쪽과 막고 있는 쪽을 짝지어 보기 (Performance Schema, MySQL 8.4 매뉴얼 기준)
SELECT w.REQUESTING_ENGINE_TRANSACTION_ID AS waiting_trx,
       w.BLOCKING_ENGINE_TRANSACTION_ID   AS blocking_trx,
       l.OBJECT_NAME AS table_name,
       l.INDEX_NAME  AS index_name,
       l.LOCK_MODE   AS lock_mode,
       l.LOCK_DATA   AS lock_data
FROM performance_schema.data_lock_waits w
JOIN performance_schema.data_locks l
  ON l.ENGINE_LOCK_ID = w.BLOCKING_ENGINE_LOCK_ID;
```

## 갭 락과 넥스트키 락이 끼는 경우

순서만 맞추면 될 것 같은데 계속 데드락이 나는 경우, 범인은 대개 갭 락입니다. InnoDB의 기본 격리 수준인 REPEATABLE READ에서는 검색이나 인덱스 스캔을 할 때 넥스트키 락을 씁니다. 넥스트키 락은 인덱스 레코드 락에 그 레코드 바로 앞의 갭 락을 더한 것입니다. 갭 락의 목적은 하나, 다른 트랜잭션이 그 틈에 새 행을 넣지 못하게 막는 것입니다.

어떤 문장이 갭까지 잠그는지는 매뉴얼에 기준이 꽤 분명하게 나와 있습니다.

- **고유 인덱스로 행 하나를 정확히 찾을 때**는 찾은 레코드만 잠그고 갭은 잠그지 않습니다. 단, 여러 컬럼으로 된 고유 인덱스에서 일부 컬럼만 조건에 쓰면 갭 락이 생깁니다.
- **범위 조건이거나 비고유 인덱스로 검색할 때**는 스캔한 범위를 갭 락이나 넥스트키 락으로 잠급니다.
- **쓸 만한 인덱스가 없어서 테이블 전체를 스캔하면** 테이블의 모든 행이 잠기고, 다른 사용자의 INSERT가 전부 막힙니다.
- **UPDATE·DELETE·잠금 읽기는 WHERE 조건에서 걸러질 행이라도 스캔한 인덱스 레코드에는 락을 겁니다.** InnoDB는 WHERE 조건을 기억하지 않고 어떤 인덱스 범위를 훑었는지만 알기 때문입니다.

세 번째와 네 번째가 실무에서 가장 놓치기 쉽습니다. 흔한 예가 상태 컬럼으로 처리 대상을 고르는 배치입니다. `UPDATE orders SET ... WHERE status = 'pending'` 같은 문장인데 `status`에 인덱스가 없으면, 배치는 조건에 맞는 몇 행이 아니라 스캔한 행 전체에 락을 겁니다. 그동안 같은 테이블로 들어오는 주문 INSERT는 막히고, 주문 처리 쪽이 다른 락을 쥔 상태라면 배치와 서로 기다리는 구도가 만들어집니다. 쿼리 자체는 몇 줄짜리라 코드 리뷰에서는 잘 보이지 않습니다. 이럴 때는 `EXPLAIN`으로 실행계획을 열어 풀 스캔인지부터 확인합니다. 실행계획 읽는 법은 [인덱스를 걸었는데도 쿼리가 느린 이유](/posts/mysql-index-not-used-execution-plan/)에 따로 정리해 두었습니다.

INSERT도 안전지대가 아닙니다. INSERT는 넣는 행에 배타 락을 걸기 전에 삽입 의도 락이라는 갭 락을 먼저 겁니다. 같은 갭이라도 서로 다른 위치에 넣는 INSERT끼리는 기다리지 않지만, 다른 트랜잭션이 그 갭을 범위 잠금으로 쥐고 있으면 기다려야 합니다. 그리고 중복 키 에러가 나면 그 레코드에 공유 락이 걸리는데, 매뉴얼은 이 공유 락 때문에 데드락이 생기는 예를 직접 보여 줍니다. 세션 1이 키 1인 행을 지우는 동안 세션 2와 3이 같은 키로 INSERT를 시도하면, 둘 다 중복 키 에러와 함께 공유 락을 요청합니다. 세션 1이 커밋하는 순간 두 세션이 공유 락을 하나씩 받고, 그다음 서로 상대의 공유 락 때문에 배타 락을 얻지 못해 데드락이 됩니다. "같은 키로 동시에 들어오는 가입·쿠폰 발급 요청"에서 이 패턴이 자주 보입니다.

![책장 빈칸 하나가 줄로 막혀 새 책을 든 사람이 기다리는 갭 락 비유 일러스트](/images/tistory/mysql-deadlock-innodb-status/3.jpg)

<p style="font-size:13px;color:#888;text-align:center;">일러스트: 순서대로 꽂힌 책 사이의 빈칸 하나가 줄로 막혀 있어 새 책을 넣지 못하고 기다리는 사람</p>

앞에서 미뤄 둔 격리 수준 이야기를 여기서 정리하면 이렇습니다. 격리 수준을 READ COMMITTED로 내리면 검색과 인덱스 스캔에서 갭 락이 꺼지고, 외래 키 검사와 중복 키 검사에만 쓰입니다. WHERE 조건에 맞지 않는 행의 레코드 락도 조건 평가 뒤에 풀립니다. 그래서 갭 락 때문에 생기던 데드락은 줄어들 수 있습니다. 하지만 같은 행을 서로 반대 순서로 수정하는 데드락은 격리 수준과 상관없이 그대로 남습니다. 매뉴얼의 두 문장이 서로 모순처럼 보였던 건 이 차이 때문입니다. 저는 격리 수준 변경을 첫 번째 처방으로 쓰지 않습니다. 애플리케이션 전체의 읽기 동작이 바뀌는 결정이라, 원인을 모른 채 바꾸면 다른 곳에서 문제가 생겼을 때 추적이 더 어려워집니다.

## 락 획득 순서를 통일하면 대부분 사라집니다

원인을 찾고 나면 고치는 방법은 생각보다 단순한 경우가 많습니다. 매뉴얼의 표현을 빌리면, 여러 테이블이나 같은 테이블의 여러 행을 수정할 때 매번 같은 순서로 하면 트랜잭션들이 잘 정의된 줄을 서고 데드락이 생기지 않습니다. 그리고 비슷한 INSERT·UPDATE·DELETE 묶음을 여기저기 흩어 쓰지 말고 함수 하나로 모으라고 권합니다.

자주 나오는 패턴은 장바구니 재고 차감입니다. 사용자 A의 장바구니에는 상품 1, 2 순서로, 사용자 B의 장바구니에는 2, 1 순서로 담겨 있습니다. 담긴 순서대로 재고를 잠그고 줄이면, A는 1을 쥔 채 2를 기다리고 B는 2를 쥔 채 1을 기다립니다. 코드에는 아무 문제가 없어 보이는데 동시에 들어올 때만 터집니다. 해결은 잠그기 전에 상품 ID를 정렬하는 것입니다.

```php
use Illuminate\Support\Facades\DB;

// 재고 차감: 어떤 순서로 상품이 들어와도 id 오름차순으로 잠근다
DB::transaction(function () use ($items) {
    $ids = collect($items)->pluck('product_id')->unique()->sort()->values();

    DB::table('stocks')
        ->whereIn('product_id', $ids)
        ->orderBy('product_id')
        ->lockForUpdate()
        ->get();

    foreach ($items as $item) {
        DB::table('stocks')
            ->where('product_id', $item['product_id'])
            ->decrement('quantity', $item['qty']);
    }
}, attempts: 3);
```

Laravel 공식 문서의 비관적 잠금 예시도 송금에서 보내는 사람, 받는 사람 순서로 `lockForUpdate()`를 겁니다. 예시로는 충분하지만, 실무에서 A가 B에게, B가 A에게 보내는 요청이 동시에 들어오면 두 트랜잭션이 서로 반대 순서로 잠그게 됩니다. 이런 코드를 옮길 때는 역할(보내는 쪽·받는 쪽)이 아니라 id 크기 순으로 잠그도록 바꿔 두는 편이 안전합니다.

잠금 읽기에 `orderBy`를 넣은 것도 같은 이유입니다. `whereIn`만으로는 어떤 순서로 행을 잠글지 실행계획에 맡기게 되니, 정렬 기준을 명시해 두는 편이 안전합니다. 여기에 두 가지를 더 챙깁니다.

- **트랜잭션을 짧게 유지합니다.** 외부 API 호출, 파일 업로드, 메일 발송을 트랜잭션 안에 넣으면 락을 쥔 시간이 그만큼 길어지고 부딪힐 확률이 올라갑니다. 매뉴얼도 관련 변경을 마치면 바로 커밋하라고 합니다.
- **잠금이 꼭 필요한지 다시 봅니다.** 화면 표시용 조회에 습관처럼 `FOR UPDATE`를 붙인 코드가 의외로 많습니다. 조금 오래된 스냅샷을 읽어도 되는 조회라면 잠금 읽기를 빼는 것만으로 충돌이 줄어듭니다.

![사람들이 파란 문과 주황 문을 같은 순서로 지나가고 끝에 재시도 화살표가 있는 일러스트](/images/tistory/mysql-deadlock-innodb-status/4.jpg)

<p style="font-size:13px;color:#888;text-align:center;">일러스트: 여러 사람이 파란 문과 주황 문을 같은 순서로 차례차례 지나가는 장면</p>

## 재시도는 트랜잭션 단위로, 세 번 안쪽에서

원인을 고쳐도 데드락이 0이 되지는 않습니다. 매뉴얼은 아예 "데드락은 위험하지 않다, 다시 시도하면 된다"고 말하고, 애플리케이션은 항상 데드락으로 롤백된 트랜잭션을 다시 실행할 준비가 되어 있어야 한다고 합니다. 행 하나를 넣거나 지우는 트랜잭션에서도 데드락이 날 수 있다고 적혀 있을 정도입니다. 그래서 재시도는 필요합니다. 문제는 어디서, 몇 번 하느냐입니다.

먼저 **어디서**. 데드락이 나면 InnoDB는 그 트랜잭션 전체를 롤백합니다. 실패한 문장 하나만 다시 실행하면 앞에서 했던 작업은 이미 사라진 상태라 데이터가 어긋납니다. 재시도 단위는 반드시 트랜잭션 전체여야 합니다. Laravel이라면 `DB::transaction()`의 두 번째 인자로 데드락 시 재시도 횟수를 줄 수 있고, 횟수를 다 쓰면 예외가 던져집니다. 위 재고 예시에 `attempts: 3`을 넣은 것이 그 설정입니다. 프레임워크 없이 PDO를 쓴다면 아래처럼 에러 코드 1213만 골라서 다시 실행합니다.

```php
function runWithDeadlockRetry(PDO $pdo, callable $work, int $maxAttempts = 3)
{
    for ($attempt = 1; ; $attempt++) {
        try {
            $pdo->beginTransaction();
            $result = $work($pdo);
            $pdo->commit();
            return $result;
        } catch (PDOException $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            $isDeadlock = ($e->errorInfo[1] ?? null) === 1213;
            if (!$isDeadlock || $attempt >= $maxAttempts) {
                throw $e;
            }
            // 같은 순간에 다시 부딪히지 않도록 조금씩 다른 간격으로 쉰다
            usleep(random_int(50, 200) * 1000 * $attempt);
        }
    }
}
```

다음은 **몇 번**. 저는 세 번을 기본값으로 둡니다. 원인을 고친 뒤의 데드락은 드문 타이밍 충돌이라 한두 번 안에 거의 성공합니다. 세 번 넘게 실패한다면 재시도로 해결될 문제가 아니라 구조 문제라는 신호로 봅니다. 그때는 재시도 횟수를 늘리지 말고 실패를 알림으로 올려 위의 분석을 다시 해야 합니다. 그리고 다시 시도하기 전에 짧고 불규칙한 대기를 넣습니다. 같이 부딪힌 요청들이 같은 간격으로 다시 시도하면 또 같이 부딪히기 때문입니다.

재시도를 넣을 때 가장 조심할 것은 트랜잭션 밖으로 새어 나가는 부작용입니다. 클로저 안에서 결제 API를 호출하거나 큐에 작업을 넣었다면, 재시도할 때마다 그 일이 한 번씩 더 일어납니다. 데이터베이스는 롤백됐어도 외부 세계는 롤백되지 않습니다. 이런 작업은 커밋이 끝난 뒤에 실행하도록 빼 두는 게 원칙입니다.

## 그래도 줄지 않을 때 남은 선택지

순서와 인덱스를 손봤는데도 특정 구간에서 데드락이 계속된다면, 매뉴얼은 마지막 수단으로 직렬화를 제안합니다. 하나는 행이 하나뿐인 보조 "세마포어" 테이블을 만들어 모든 트랜잭션이 다른 테이블에 접근하기 전에 그 행을 먼저 수정하게 하는 방법이고, 다른 하나는 `LOCK TABLES`로 테이블 단위 잠금을 거는 방법입니다. 둘 다 동시성을 포기하고 안정성을 사는 선택이라, 정산처럼 처리량보다 정확성이 중요하고 동시에 도는 작업 수가 적은 곳에만 쓰는 편이 맞다고 봅니다.

`innodb_deadlock_detect`를 끄는 선택도 있습니다. 매뉴얼은 동시성이 아주 높은 시스템에서 많은 스레드가 같은 락을 기다릴 때 데드락 감지 자체가 느려질 수 있고, 그럴 때는 감지를 끄고 `innodb_lock_wait_timeout`에 맡기는 편이 효율적일 수 있다고 설명합니다. 다만 이건 데드락을 줄이는 방법이 아니라 처리 방식을 바꾸는 방법이고, 데드락이 즉시 풀리지 않고 타임아웃만큼 기다리게 됩니다. 소규모 팀의 일반적인 웹 서비스에서 이 설정까지 갈 일은 드물다고 봅니다.

## MySQL 데드락 원인 찾기, 알림이 왔을 때 확인 순서

1. 알림이 반복되면 `innodb_print_all_deadlocks`를 켜서 모든 건을 에러 로그에 남긴다.
2. `lock_deadlocks` 누적값을 기록해 두고 수정 전후를 비교한다.
3. `LATEST DETECTED DEADLOCK`에서 두 쿼리, 쥔 락, 기다린 락, 인덱스 이름, 롤백된 쪽을 순서대로 읽는다.
4. 보이는 쿼리만 보지 말고, 그 트랜잭션에서 먼저 실행된 잠금 읽기·수정 문까지 코드에서 따라간다.
5. 락 모드에 갭이 끼어 있으면 `EXPLAIN`으로 인덱스 사용 여부와 스캔 범위를 확인한다.
6. 여러 행·여러 테이블을 잠그는 코드는 정렬 기준을 정해 한 함수로 모은다.
7. 트랜잭션 안의 외부 호출을 커밋 뒤로 빼고, 재시도는 트랜잭션 단위로 세 번 안쪽에서 한다.
8. 디버깅이 끝나면 `innodb_print_all_deadlocks`를 다시 끈다.

재시도 한 줄은 5분이면 붙이지만, 그 한 줄이 가리는 원인은 트래픽이 늘수록 커집니다. 에러 메시지가 "다시 시도하라"고 말할 때, 한 번쯤은 INNODB STATUS를 먼저 열어 보는 습관을 들여 두면 나중에 훨씬 덜 고생합니다.

## 출처

- [MySQL 8.4 Reference Manual — 17.7.5 Deadlocks in InnoDB](https://dev.mysql.com/doc/refman/8.4/en/innodb-deadlocks.html)
- [MySQL 8.4 Reference Manual — 17.7.5.1 An InnoDB Deadlock Example](https://dev.mysql.com/doc/refman/8.4/en/innodb-deadlock-example.html)
- [MySQL 8.4 Reference Manual — 17.7.5.2 Deadlock Detection](https://dev.mysql.com/doc/refman/8.4/en/innodb-deadlock-detection.html)
- [MySQL 8.4 Reference Manual — 17.7.5.3 How to Minimize and Handle Deadlocks](https://dev.mysql.com/doc/refman/8.4/en/innodb-deadlocks-handling.html)
- [MySQL 8.4 Reference Manual — 17.7.1 InnoDB Locking](https://dev.mysql.com/doc/refman/8.4/en/innodb-locking.html)
- [MySQL 8.4 Reference Manual — 17.7.3 Locks Set by Different SQL Statements in InnoDB](https://dev.mysql.com/doc/refman/8.4/en/innodb-locks-set.html)
- [MySQL 8.4 Reference Manual — The data_locks Table](https://dev.mysql.com/doc/refman/8.4/en/performance-schema-data-locks-table.html)
- [Laravel 12.x Documentation — Query Builder: Pessimistic Locking](https://laravel.com/docs/12.x/queries)
- [Laravel 12.x Documentation — Database: Database Transactions, Handling Deadlocks](https://laravel.com/docs/12.x/database)
