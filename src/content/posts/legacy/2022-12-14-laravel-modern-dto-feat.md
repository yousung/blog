---
title: 'Laravel에서 Spring처럼 Modern한 DTO 구현하기'
slug: 'laravel-modern-dto-feat'
author: 'Lovizu'
date: '2022-12-14'
summary: 'PHP 8의 Attribute 기능으로 Laravel도 Java Spring처럼 우아한 DTO 패턴을 구현할 수 있습니다. Spatie laravel-data와 laravel-route-attributes 패키지를 이용한 선언형 DTO와 라우팅. 유효성 검사를 DTO에서 관리하고 Route 속성으로 등록하는 모던한 개발 경험을 소개합니다.'
oneLineSummary: 'Laravel: PHP 8 Attribute로 Spring 스타일 DTO 구현'
tags: ['legacy', 'legacy-migration', 'Laravel', 'PHP', 'DTO', 'Backend', '아키텍처']
status: "draft"
updatedDate: '2026-04-23'
---
## PHP 8 Attribute와 Modern한 Laravel 개발

![laravel-modern-dto-feat 이미지](/images/posts/laravel-modern-dto-feat/img-01.png)

PHP 8에서 Attribute 기능이 추가되면서 Laravel도 Java Spring처럼 우아한 코드 작성이 가능해졌습니다. 특히 DTO(Data Transfer Object) 패턴 구현에서 큰 변화를 가져왔습니다.

## 기존 Laravel DTO의 문제점

### Request 클래스와 DTO의 이중 관리

전통적인 Laravel 개발에서는:

```php
// routes/api.php
Route::post('/dogs', [DogController::class, 'store']);

// DogRequest.php
public function rules()
{
    return [
        'name' => 'required|string',
        'type' => 'required|string|min:5',
    ];
}

// DogDTO.php
class DogDTO
{
    public function __construct(
        public string $name,
        public string $type,
    ) {}
}
```

**문제점:**

- Route 정의는 `routes/` 디렉토리
- Request 클래스는 `Requests/` 디렉토리
- DTO 클래스는 `DTO/` 디렉토리
- **유효성 검사 규칙이 분산되어 있음**

코드를 찾아다니며 읽어야 하고, 데이터 구조가 어디에 정의되어 있는지 파악하기 어렵습니다.

## 해결책: Spatie Laravel Data

### 설치 방법

```bash
# laravel-data 패키지
composer require spatie/laravel-data
php artisan vendor:publish --provider="Spatie\LaravelData\LaravelDataServiceProvider" --tag="data-config"

# laravel-route-attributes 패키지
composer require spatie/laravel-route-attributes
php artisan vendor:publish --provider="Spatie\RouteAttributes\RouteAttributesServiceProvider" --tag="config"
```

### 기본 사용법

#### 1단계: DTO 정의 (유효성 검사 포함)

```php
<?php

namespace App\DTO;

use Spatie\LaravelData\Attributes\Validation as Rule;
use Spatie\LaravelData\Data;

class DogData extends Data
{
    public function __construct(
        #[Rule\Required]
        public string $name,

        #[Rule\Min(5)]
        #[Rule\Nullable]
        public ?string $type
    ) {}
}
```

**장점:**

- DTO 클래스 하나에 데이터 구조와 유효성 검사 규칙이 모두 포함
- IDE 자동완성 지원
- 타입 안정성

#### 2단계: Controller에서 Route Attribute 사용

```php
<?php

namespace App\Http\Controllers;

use App\DTO\DogData;
use Spatie\RouteAttributes\Attributes\{Get, Post, Patch, Delete, Prefix};

#[Prefix('dogs')]
class DogController extends Controller
{
    #[Get('/', name: 'dog.index')]
    public function index(): array
    {
        return [
            'type' => 'dog',
            'list' => [],
        ];
    }

    #[Get('/{id}', name: 'dog.show')]
    public function show($id): array
    {
        return [
            'type' => 'dog',
            'id' => $id,
        ];
    }

    #[Post('/', name: 'dog.store')]
    public function store(DogData $dto): array
    {
        return [
            'type' => $dto->type,
            'name' => $dto->name,
        ];
    }

    #[Patch('/{id}', name: 'dog.update')]
    public function update($id, DogData $dto): array
    {
        return [
            'id' => $id,
            'type' => $dto->type,
            'name' => $dto->name,
        ];
    }
}
```

## Modern한 DTO의 장점들

### 1. 선언형 Route 정의

#### Before (전통적인 방식)

```php
// routes/api.php (어딘가에 흩어져 있음)
Route::get('dogs', [DogController::class, 'index']);
Route::get('dogs/{id}', [DogController::class, 'show']);
Route::post('dogs', [DogController::class, 'store']);
Route::patch('dogs/{id}', [DogController::class, 'update']);
```

#### After (Modern 방식)

```php
// Controller 클래스에 모두 정의
#[Get('/', name: 'dog.index')]
public function index() { }

#[Get('/{id}', name: 'dog.show')]
public function show($id) { }

#[Post('/', name: 'dog.store')]
public function store(DogData $dto) { }

#[Patch('/{id}', name: 'dog.update')]
public function update($id, DogData $dto) { }
```

**이점:**

- Route와 메서드가 함께 있어 한눈에 이해
- 리팩토링이 쉬움
- 스프링의 `@GetMapping` 같은 경험

### 2. DTO 중심의 유효성 검사

유효성 검사가 Request 클래스에서 DTO로 옮겨집니다.

```php
class UserData extends Data
{
    public function __construct(
        #[Rule\Required]
        #[Rule\Email]
        public string $email,

        #[Rule\Required]
        #[Rule\Min(8)]
        public string $password,

        #[Rule\Required]
        public string $name,
    ) {}
}
```

Controller는 매우 간단해집니다:

```php
#[Post('/')]
public function register(UserData $user)
{
    // $user는 이미 유효성 검사를 통과한 상태
    User::create($user->toArray());
    return ['success' => true];
}
```

### 3. 자동 타입 캐스팅

```php
class UserData extends Data
{
    public function __construct(
        #[Rule\Required]
        #[Rule\Numeric]
        public int $age,  // 문자열 "25" → 정수 25로 자동 변환
        
        #[Rule\Required]
        public bool $isActive,  // "1" → true로 자동 변환
    ) {}
}
```

## 고급 기능

### 중첩 DTO

주소를 별도의 DTO로 분리:

```php
class AddressData extends Data
{
    public function __construct(
        #[Rule\Required]
        public string $street,

        #[Rule\Required]
        public string $city,

        #[Rule\Required]
        public string $zipCode,
    ) {}
}

class UserData extends Data
{
    public function __construct(
        #[Rule\Required]
        public string $name,

        #[Rule\Required]
        public AddressData $address,  // 중첩 DTO
    ) {}
}
```

요청 JSON:

```json
{
  "name": "John",
  "address": {
    "street": "123 Main St",
    "city": "Seoul",
    "zipCode": "12345"
  }
}
```

### 커스텀 메서드

```php
class UserData extends Data
{
    public function __construct(
        public string $firstName,
        public string $lastName,
    ) {}

    public function fullName(): string
    {
        return "{$this->firstName} {$this->lastName}";
    }
}
```

### Array 변환

```php
$data = UserData::from([
    'firstName' => 'John',
    'lastName' => 'Doe',
]);

// 또는 POST 요청에서 자동 변환
public function store(UserData $data)
{
    // $data는 자동으로 UserData 인스턴스
}

// 배열로 변환
$array = $data->toArray();
```

## Route 속성 완전 가이드

```php
use Spatie\RouteAttributes\Attributes\{
    Get, Post, Patch, Delete, Prefix
};

#[Prefix('users')]
class UserController
{
    // GET /users
    #[Get('/', name: 'user.index')]
    public function index() { }

    // POST /users
    #[Post('/', name: 'user.store')]
    public function store(UserData $data) { }

    // GET /users/{id}
    #[Get('/{id}', name: 'user.show')]
    public function show($id) { }

    // PATCH /users/{id}
    #[Patch('/{id}', name: 'user.update')]
    public function update($id, UserData $data) { }

    // DELETE /users/{id}
    #[Delete('/{id}', name: 'user.destroy')]
    public function destroy($id) { }
}
```

## 등록된 라우트 확인

```bash
php artisan route:list
```

모든 라우트가 명확히 표시됩니다.

## 개발 경험의 혁신

Java Spring을 경험한 PHP 개발자라면 이 방식이 얼마나 편한지 실감할 것입니다:

| 항목 | 이점 |
|------|------|
| **DTO 중심** | 요청 데이터의 구조가 명확함 |
| **선언형** | Route 정의가 메서드 바로 위 |
| **유효성 검사** | 한 곳에서만 관리 |
| **타입 안정성** | IDE 자동완성과 타입 체크 |

## 고민: Spring처럼 변하면서의 선택

이렇게 Laravel이 Spring처럼 현대화되면서 가끔 이런 생각이 듭니다:

- "이렇게 Spring 스타일로 할 거라면 그냥 Spring을 할까?"
- "하지만 PHP의 유연성과 빠른 개발 속도는 여전히 매력적이다"

결국 **프레임워크는 선택의 문제**입니다.

## 마치며

Spatie의 laravel-data와 laravel-route-attributes는 PHP 8의 강력한 Attribute 기능을 활용하여:

- Laravel을 Spring만큼 현대적이고 우아하게 만들고
- DTO 관리의 복잡도를 크게 줄이며
- 팀 프로젝트에서 코드 일관성을 높입니다

특히 대규모 프로젝트나 팀 협업 상황에서 이 패턴의 가치가 두드러집니다.

**지금 바로 시도해보세요.**
