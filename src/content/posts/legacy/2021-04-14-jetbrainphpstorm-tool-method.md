---
title: 'JetBrains IDE에서 테스트 코드 한글 메서드명 설정하기'
slug: 'jetbrainphpstorm-tool-method'
author: 'Lovizu'
date: '2021-04-14'
summary: 'PhpStorm, IntelliJ 등 JetBrains IDE에서 테스트 코드의 한글 메서드명이나 snake_case 사용 시 발생하는 경고를 제거하는 방법. IDE 검사 설정과 PHP CodeSniffer 설정을 통해 테스트 코드의 명확한 의도를 보존하면서 프로덕션 코드의 규칙을 유지하는 실용적 가이드입니다.'
oneLineSummary: '테스트 코드에서 한글 메서드명 경고 없애기'
tags: ['legacy', 'legacy-migration', 'JetBrains', 'PhpStorm', '테스트코드', '개발환경', '설정']
status: 'published'
updatedDate: '2026-04-23'
---
## 테스트 코드의 특별한 관례

![jetbrainphpstorm-tool-method 이미지](/images/posts/jetbrainphpstorm-tool-method/img-01.png)

![jetbrainphpstorm-tool-method 이미지](/images/posts/jetbrainphpstorm-tool-method/img-02.png)

![jetbrainphpstorm-tool-method 이미지](/images/posts/jetbrainphpstorm-tool-method/img-03.jpg)

테스트 코드 작성 시 프로덕션 코드와 다른 명명 규칙을 사용합니다.

### 일반적인 패턴

**프로덕션 코드** (camelCase):
```php
public function getUser() { }
```

**테스트 코드** (한글 메서드명, snake_case):
```php
public function 유효한_사용자_정보로_로그인하면_성공한다() { }
public function test_user_login_with_valid_credentials() { }
```

이는 기술적 요구사항이 아닌 **명확한 의도 전달**을 위한 관례입니다.

## IDE의 "좋은 의도"의 문제

JetBrains IDE들은 코드 품질 유지를 위해 엄격한 규칙을 강제합니다:

- **PhpStorm**: "Non-ASCII characters in method name"
- **IntelliJ IDEA**: "Different languages in identifiers"

테스트 코드에서 이 규칙은 오히려 **가독성을 해칩니다**.

## 해결 방법

### 1단계: PHP CodeSniffer 설정 (PHP 프로젝트)

프로젝트 루트의 `phpcs.xml` 파일에서 tests 디렉토리를 제외합니다:

```xml
<rule ref="PSR1.Methods.CamelCapsMethodName">
  <exclude-pattern>./tests/*</exclude-pattern>
</rule>
```

이 설정으로 `./tests/` 디렉토리의 camelCase 강제 규칙이 무시됩니다.

### 2단계: IDE 검사 설정 변경

JetBrains IDE 공통 설정입니다.

**경로**: `Settings` → `Editor` → `Inspections`

#### 2-1. 찾아야 할 항목

"Inspections" 패널에서 다음 두 항목을 검색합니다:

```
1. Non-ASCII characters in Identifiers
2. Different languages in identifiers
```

#### 2-2. 설정 절차

각 항목별로:

1. **항목 찾기**: 검색 박스에 입력
2. **Scope 제한**: 항목 바로 위의 "Scope" 드롭다운을 **"tests"로 설정**
3. **체크박스 조정**: 필요시 경고 수준 조정

**중요**: 전체 프로젝트가 아닌 **tests 디렉토리에만 적용**해야 합니다. 이렇게 하면:
- 프로덕션 코드: 엄격한 naming convention 유지
- 테스트 코드: 유연한 명명 규칙 허용

## 테스트 메서드명의 가치

### 명확성의 중요성

```php
// ❌ 무엇을 테스트하는지 불명확
public function testUser() { }
public function testLogin() { }

// ✅ 테스트 의도가 명확
public function 유효한_사용자_정보로_로그인하면_성공한다() { }
public function test_invalid_email_throws_exception() { }
```

테스트 메서드명은 **테스트 결과를 직관적으로 전달**해야 합니다. Given-When-Then 패턴의 한글 표현이 이를 잘 달성합니다.

## 설정 전후 비교

### 설정 전

```php
public function 테스트_데이터를_조회하면_성공한다() { }
```

IDE 경고:
- Non-ASCII characters in method name (밑줄)
- Different language in identifier (밑줄)

### 설정 후

```php
public function 테스트_데이터를_조회하면_성공한다() { }
```

- ✅ 경고 없음 (tests 디렉토리 내에서)
- ✅ 프로덕션 코드는 여전히 보호됨

## 다른 JetBrains IDE 적용

동일한 방식이 모든 JetBrains IDE에 적용됩니다:

| IDE | 사용 언어 | 적용 가능 |
|-----|---------|---------|
| PhpStorm | PHP | ◎ 바로 적용 |
| IntelliJ IDEA | Java | ◎ 바로 적용 |
| WebStorm | JavaScript/TypeScript | ◎ 바로 적용 |
| PyCharm | Python | ◎ 바로 적용 |

모두 동일하게 `Settings` → `Inspections`에서 Scope를 조정하면 됩니다.

## 추가 테스트 메서드명 패턴

### Given-When-Then 패턴 (권장)

```php
// given_when_then 형식
public function 정상_사용자_정보가_주어졌을_때_로그인하면_성공한다() { }

// 약자 형식
public function 정상_사용자_로그인_성공() { }
```

### BDD 스타일

```php
// Behavior-Driven Development
public function should_login_successfully_with_valid_credentials() { }
```

## CI/CD 파이프라인 동기화

IDE 규칙과 자동화 도구의 규칙을 일치시켜야 합니다:

### TeamCity, SonarQube 설정

1. IDE의 규칙 먼저 확인
2. CI/CD의 Linter/Checker 설정 동기화
3. 팀 전체가 동일한 규칙 적용

**예시**: `phpcs.xml` 규칙이 TeamCity 서버에도 동일하게 적용되어야 빌드 실패를 방지할 수 있습니다.

## 플러그인 활용

일부 IDE 플러그인이 테스트 작성을 더 편하게 만듭니다:

### PHP/Laravel 플러그인

- **Laravel IDE Helper**: Laravel 프로젝트의 자동 완성 강화
- **PHP Annotations**: 주석 기반 타입 정보 제공

### BDD 지원

- **Gherkin**: Cucumber 스타일의 테스트 시나리오 작성
- **TestNG**: 테스트 메서드 자동 생성 및 실행

## 팀 규칙 문서화

팀 전체가 따를 규칙을 `CONTRIBUTING.md`에 기록합니다:

```markdown
## 테스트 코드 작성 규칙

### 메서드명
- 한글 메서드명 권장: `테스트_목적을_한글로_표현()`
- snake_case 허용: `test_purpose_in_english()`
- camelCase 불가: testPurpose() ❌

### IDE 설정
- PhpStorm: Inspections에서 Scope를 "tests"로 제한
- 제외 패턴: ./tests/* (CodeSniffer)

### 예시
- ❌ testUser()
- ✅ 유효한_사용자_정보로_로그인하면_성공한다()
- ✅ test_user_login_with_valid_credentials()
```

## 마치며

테스트 코드는 **실행 가능한 문서**입니다. 명확한 의도를 드러내기 위해 한글 메서드명이나 상세한 snake_case 사용이 합리적입니다.

JetBrains IDE의 강력한 도구를 최대한 활용하면서도 테스트 코드의 가독성을 높이려면:

1. **IDE Scope 설정**: tests 디렉토리에 맞춤 규칙 적용
2. **CodeSniffer 제외**: 빌드 시스템에서도 동일하게 제외
3. **팀 합의**: 명확한 문서로 규칙 공유

이를 통해 테스트 코드는 **명확하고 유지보수하기 쉬운** 형태를 유지할 수 있습니다. IDE의 도움을 받으면서도 테스트 코드만의 특별한 규칙을 효과적으로 적용하세요.
