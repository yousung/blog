---
title: 'Laravel API 문서화: OpenAPI로 우아하게 Swagger 구현하기'
slug: 'laravel-swagger'
author: "감성개발자"
date: '2022-09-02'
summary: 'Laravel에서 vyuldashev/laravel-openapi와 darkaonline/l5-swagger를 사용하여 API 문서를 체계적으로 작성합니다. PHP 8 Attributes로 선언형 API 정의, 복잡한 주석 제거, Schema 재사용 등 모던 개발 방식으로 유지보수 가능한 API 스펙을 관리합니다.'
oneLineSummary: 'Laravel API: OpenAPI 속성으로 우아하고 재사용 가능하게'
tags: ['legacy', 'legacy-migration', 'Laravel', 'PHP', 'API', 'OpenAPI', 'Backend']
status: "draft"
updatedDate: '2026-04-23'
---
## 전통적 Swagger 주석의 문제점

![laravel-swagger 이미지](/images/posts/laravel-swagger/img-01.png)

![laravel-swagger 이미지](/images/posts/laravel-swagger/img-02.jpg)

![laravel-swagger 이미지](/images/posts/laravel-swagger/img-03.jpg)

![laravel-swagger 이미지](/images/posts/laravel-swagger/img-04.jpg)

![laravel-swagger 이미지](/images/posts/laravel-swagger/img-05.jpg)

![laravel-swagger 이미지](/images/posts/laravel-swagger/img-06.jpg)

![laravel-swagger 이미지](/images/posts/laravel-swagger/img-07.jpg)

![laravel-swagger 이미지](/images/posts/laravel-swagger/img-08.jpg)

PHP에서 기존 Swagger 주석은 코드 자체보다 훨씬 깁니다. 작은 API 하나 작성하는데 수십 줄의 주석이 필요하고, 중복 코드가 많아 유지보수가 어렵습니다.

```php
/**
 * @OA\Post(
 *     path="/users",
 *     @OA\RequestBody(
 *         required=true,
 *         @OA\JsonContent(
 *             required={"name","email"},
 *             @OA\Property(property="name", type="string"),
 *             @OA\Property(property="email", type="string", format="email"),
 *         ),
 *     ),
 *     @OA\Response(response=200, description="User created"),
 * )
 */
```

## 해결책: PHP 8 Attributes + vyuldashev/laravel-openapi

PHP 8의 Attributes를 활용하면 선언형으로 API를 정의하고, 코드의 가독성과 유지보수성을 크게 향상시킬 수 있습니다.

## 패키지 설치

### 1단계: vyuldashev/laravel-openapi 설치

```bash
composer require vyuldashev/laravel-openapi

php artisan vendor:publish --provider="Spatie\LaravelData\LaravelDataServiceProvider" --tag="data-config"

php artisan openapi:generate
```

**역할**: OpenAPI JSON 스펙 생성

### 2단계: darkaonline/l5-swagger 설치

```bash
composer require "darkaonline/l5-swagger"

php artisan vendor:publish --provider "L5Swagger\L5SwaggerServiceProvider"
```

**역할**: 생성된 JSON을 Swagger UI로 시각화

## 기본 사용법

### Controller 정의

```php
<?php

namespace App\Http\Controllers;

use Vyuldashev\LaravelOpenApi\Attributes as OpenApi;

#[OpenApi\PathItem]
class TestController extends Controller
{
    #[OpenApi\Operation(tags: ['tests'])]
    public function index()
    {
        return ['type' => 'test', 'list' => []];
    }

    #[OpenApi\Operation(tags: ['tests'])]
    public function show($id)
    {
        // 구현
    }

    #[OpenApi\Operation(tags: ['tests'])]
    public function store(TestData $dto)
    {
        return $dto;
    }
}
```

### Routes 정의

```php
Route::get('test', [TestController::class, 'index']);
Route::get('test/{id}', [TestController::class, 'show']);
Route::post('test', [TestController::class, 'store']);
```

### JSON 생성 및 확인

```bash
php artisan openapi:generate > storage/api-docs/api-docs.json
```

브라우저에서 접속:
```
http://example.test/api/documentation
```

## 고급 기능

### 1. 태그 관리

`config/openapi.php`에서 정의:

```php
'tags' => [
    [
        'name' => 'tests',
        'description' => '테스트 관련 API',
    ],
    [
        'name' => 'users',
        'description' => '사용자 관련 API',
    ],
],
```

### 2. Parameter 정의

```bash
php artisan openapi:make-parameters TestList
```

```php
namespace App\OpenApi\Parameters;

use GoldSpecDigital\ObjectOrientedOAS\Objects\Parameter;
use GoldSpecDigital\ObjectOrientedOAS\Objects\Schema;
use Vyuldashev\LaravelOpenApi\Factories\ParametersFactory;

class TestListParameters extends ParametersFactory
{
    public function build(): array
    {
        return [
            Parameter::query()
                ->name('name')
                ->description('(required) 검색 이름')
                ->required()
                ->schema(Schema::string()),

            Parameter::query()
                ->name('page')
                ->description('(optional) 페이지 번호')
                ->required(false)
                ->schema(Schema::integer()),
        ];
    }
}
```

Controller에 적용:

```php
#[OpenApi\Operation(tags: ['tests'])]
#[OpenApi\Parameters(factory: TestListParameters::class)]
public function index()
{
    // 구현
}
```

### 3. Schema 재사용

```bash
php artisan openapi:make-schema Test
```

```php
namespace App\OpenApi\Schemas;

use GoldSpecDigital\ObjectOrientedOAS\Contracts\SchemaContract;
use GoldSpecDigital\ObjectOrientedOAS\Objects\Schema;
use Vyuldashev\LaravelOpenApi\Contracts\Reusable;
use Vyuldashev\LaravelOpenApi\Factories\SchemaFactory;

class TestSchema extends SchemaFactory implements Reusable
{
    public function build(): SchemaContract
    {
        return Schema::object()->properties(
            Schema::integer('id')->example(1)->title('ID'),
            Schema::string('name')->example('Test 1')->title('이름'),
        )->required('id', 'name');
    }
}
```

RequestBody에서 재사용:

```php
MediaType::json()->schema(TestSchema::ref())
```

## 핵심 장점

| 장점 | 설명 |
|------|------|
| **재사용성** | Schema를 여러 곳에서 활용 가능 |
| **유지보수성** | 클래스 기반 관리로 리팩토링 용이 |
| **타입 안정성** | IDE 자동완성 지원 |
| **가독성** | 복잡한 주석 완전 제거 |

## 배포 자동화

CI/CD 파이프라인에 포함:

```bash
# JSON 생성
php artisan openapi:generate --write-path storage/api-docs/api-docs.json
```

## 마치며

vyuldashev/laravel-openapi를 사용하면 복잡한 Swagger 주석 없이 우아하고 유지보수하기 쉬운 API 문서를 작성할 수 있습니다. 

특히 다음 상황에서 진가가 드러납니다:

- 같은 schema를 여러 곳에서 사용
- API 엔드포인트가 많고 복잡
- 팀 단위 협업에서 일관된 문서화 필요
- 정기적인 API 스펙 업데이트 필요

모던 PHP의 Attributes와 객체지향 설계로 API 문서화를 재정의할 수 있습니다.
