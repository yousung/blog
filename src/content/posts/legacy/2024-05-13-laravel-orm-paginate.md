---
title: 'Laravel Eloquent paginate() 사용 시 필수: ORDER BY 절'
slug: 'laravel-orm-paginate'
author: 'Lovizu'
date: '2024-05-13'
summary: 'Laravel ORM의 paginate() 메서드 사용 시 ORDER BY 절이 없으면 페이지마다 다른 결과를 받을 수 있습니다. 특히 복수의 읽기 전용 데이터베이스 환경에서 이 문제가 더욱 심각합니다. 원인 분석과 실제 해결 방법을 제시합니다.'
oneLineSummary: 'paginate() 사용 시 ORDER BY는 필수, 선택이 아닙니다'
tags: ['legacy', 'legacy-migration', 'Laravel', 'ORM', 'Database', 'Eloquent']
status: "draft"
updatedDate: '2026-04-23'
---

## 문제 상황: 신비한 데이터 불일치

통계 시스템에서 이상한 버그가 보고되었습니다.

**증상**: 페이지를 새로고침할 때마다 일정 확률로 다른 집계 결과가 나타남

처음에는 **프론트엔드의 데이터 취합 오류**로 의심했습니다. 하지만 조사 결과, 훨씬 더 근본적인 문제였습니다.

```
새로고침 1: 결과 = [10, 11, 12, 13, 14]
새로고침 2: 결과 = [12, 10, 11, 14, 13]  ← 순서가 다름!
새로고침 3: 결과 = [10, 11, 12, 13, 14]
```

## 근본 원인 파악: ORDER BY 절의 부재

백엔드 쿼리를 분석한 결과, **paginate() 호출에서 ORDER BY 절이 없었습니다.**

```php
// ❌ 문제있는 코드
$items = Model::paginate(15);

// ✅ 올바른 코드
$items = Model::orderBy('id', 'desc')->paginate(15);
```

이게 정말 중요한 차이입니다.

## ORDER BY가 없으면 왜 순서가 바뀔까?

### 페이지네이션의 동작 원리

페이지네이션은 내부적으로 **OFFSET**을 사용합니다.

```sql
-- 페이지 1 (LIMIT 10 OFFSET 0)
SELECT * FROM items LIMIT 10 OFFSET 0;

-- 페이지 2 (LIMIT 10 OFFSET 10)
SELECT * FROM items LIMIT 10 OFFSET 10;
```

OFFSET으로 데이터를 선택할 때, **데이터베이스는 반환할 행의 순서를 결정해야 합니다.**

### 순서 보장의 부재

ORDER BY 절이 없으면:

```sql
-- ❌ 순서가 정의되지 않음
SELECT * FROM items LIMIT 10 OFFSET 0;

-- ✅ 순서가 명시됨
SELECT * FROM items ORDER BY id DESC LIMIT 10 OFFSET 0;
```

SQL 표준에서 ORDER BY 없는 SELECT는 **행 순서를 보장하지 않습니다.**

### 복수 데이터베이스 환경에서의 악화

회사가 여러 대의 리드(Read) 데이터베이스를 운영한다면 상황은 더 심해집니다:

```
요청 1: 리드DB 1 선택 → 행 순서 = [A, B, C, D, E]
요청 2: 리드DB 2 선택 → 행 순서 = [C, A, E, B, D]
요청 3: 리드DB 3 선택 → 행 순서 = [B, D, A, C, E]
```

각 데이터베이스가 다른 순서로 데이터를 반환할 수 있기 때문입니다.

## 현상의 재현

### 1. 순서 없는 쿼리

```php
// 데이터: items with id = 1, 2, 3, 4, 5
$items = Model::paginate(3);
// 반환: [2, 4, 1] 또는 [4, 2, 1] 또는 [1, 2, 3] (비결정적)
```

### 2. 리드 데이터베이스 환경

```
Primary DB: id 순서 = [1, 2, 3, 4, 5]
Replica DB 1: id 순서 = [3, 1, 5, 2, 4] (다를 수 있음)
Replica DB 2: id 순서 = [5, 3, 1, 4, 2] (또 다를 수 있음)
```

로드 밸런싱으로 다른 레플리카에 요청하면 완전히 다른 결과를 얻습니다.

## 해결 방법: ORDER BY 추가

### 기본 해결책

paginate() 호출 전에 **반드시 orderBy()를 추가**합니다.

```php
// ✅ 올바른 방법
$items = Model::orderBy('id', 'desc')->paginate(15);

// 또는
$items = Model::orderBy('created_at', 'desc')->paginate(15);

// 또는
$items = Model::orderBy('updated_at', 'desc')->paginate(15);
```

### 복합 정렬

여러 기준으로 정렬이 필요한 경우:

```php
$items = Model::orderBy('created_at', 'desc')
    ->orderBy('id', 'desc')
    ->paginate(15);
```

**중요**: 복합 정렬의 순서도 일관되어야 합니다. 첫 번째 정렬 기준이 같을 때만 두 번째 기준이 적용됩니다.

### 조건문과 함께 사용

```php
$items = Model::where('status', 'active')
    ->where('deleted_at', null)
    ->orderBy('created_at', 'desc')
    ->paginate(15);
```

**주의**: `where()` 뒤에 `orderBy()`를 하는 것이 일반적인 패턴입니다.

## 베스트 프랙티스

### 1. Eloquent Scope 활용

Order 규칙을 모델 레벨에서 관리합니다:

```php
class Item extends Model {
    public function scopeOrdered($query) {
        return $query->orderBy('created_at', 'desc')
                     ->orderBy('id', 'desc');
    }
}

// 사용
$items = Item::ordered()->paginate(15);
```

이 방식의 장점:
- 모든 쿼리에서 일관된 정렬 적용
- 정렬 로직 변경 시 한 곳만 수정
- 가독성 향상

### 2. 기본 정렬 설정

모델에서 기본 정렬을 정의할 수도 있습니다:

```php
class Item extends Model {
    protected $table = 'items';

    public function newQuery() {
        return parent::newQuery()
            ->orderBy('created_at', 'desc');
    }
}

// 모든 쿼리에 자동으로 정렬 적용
$items = Item::paginate(15);
```

### 3. Repository 패턴

대규모 프로젝트에서는 Repository 클래스에서 관리:

```php
class ItemRepository {
    public function getPaginated($perPage = 15) {
        return Item::where('status', 'active')
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);
    }
}
```

## 성능 최적화 고려사항

### 인덱스 설정

정렬에 사용되는 컬럼에는 **반드시 인덱스**가 필요합니다.

```php
// Migration
Schema::create('items', function (Blueprint $table) {
    $table->id();
    $table->timestamp('created_at')->index(); // ← 인덱스 추가
    $table->string('name');
});
```

### 복합 인덱스

여러 정렬 기준을 사용한다면 복합 인덱스가 효율적입니다:

```php
// Migration
$table->index(['created_at', 'id']); // ← 정렬 순서대로 인덱스 생성
```

## 테스트 작성

paginate() 동작을 테스트할 때는 **순서를 검증**해야 합니다.

```php
public function test_paginate_returns_ordered_results() {
    $item1 = Item::create(['name' => 'Item 1', 'created_at' => now()]);
    sleep(1);
    $item2 = Item::create(['name' => 'Item 2', 'created_at' => now()]);
    
    $items = Item::orderBy('created_at', 'desc')->paginate(10);
    
    // ✅ 검증: Item 2가 먼저 나와야 함
    $this->assertEquals($item2->id, $items->first()->id);
}
```

## 실제 버그 해결 사례

### Before: 버그 있는 코드

```php
public function getStatistics() {
    $items = Item::paginate(20); // ❌ ORDER BY 없음
    
    return [
        'total' => count($items),
        'avg_value' => $items->avg('value'),
    ];
}
```

**증상**: 새로고침할 때마다 평균값이 다르게 나옴

### After: 수정된 코드

```php
public function getStatistics() {
    $items = Item::orderBy('id', 'desc')
        ->paginate(20); // ✅ ORDER BY 추가
    
    return [
        'total' => count($items),
        'avg_value' => $items->avg('value'),
    ];
}
```

**결과**: 일관된 결과 반환

## 팀 규칙으로 정립

코드 리뷰 체크리스트에 추가:

```markdown
## Code Review Checklist

### Eloquent Query
- [ ] paginate() 사용 시 orderBy() 확인
- [ ] Order By 컬럼에 인덱스 확인
- [ ] 정렬 순서가 비즈니스 로직과 일치하는지 확인
```

## 자동화 검사

정적 분석 도구로 이런 문제를 사전에 감지할 수 있습니다:

```php
// PHPStan / Psalm 규칙
// paginate() 호출 전에 orderBy() 있는지 확인
```

## 마치며

페이지네이션은 단순한 기능처럼 보이지만, **데이터 일관성의 핵심**입니다.

### 핵심 규칙

```
paginate() 사용 = ORDER BY 필수
이것은 선택이 아닙니다.
```

### 체크리스트

모든 paginate() 호출을 다음과 같이 검토하세요:

```
☑ paginate() 직전에 orderBy() 있는가?
☑ 정렬 컬럼에 인덱스가 있는가?
☑ 복수 DB 환경에서도 순서가 보장되는가?
☑ 새로고침해도 결과가 일치하는가?
```

이 간단한 규칙을 지키면, 데이터 일관성 문제를 사전에 방지할 수 있습니다. 특히 대규모 시스템에서 여러 데이터베이스를 운영할 때는 더욱 중요합니다.
