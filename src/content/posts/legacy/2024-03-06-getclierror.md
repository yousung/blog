---
title: 'Flutter GetX CLI 에러 해결: waitFor 문제 진단 및 해결'
slug: 'getclierror'
author: "감성개발자"
date: '2024-03-06'
summary: 'Flutter GetX CLI에서 발생하는 "Synchronous waiting using dart:cli waitFor" 에러를 완전히 해결합니다. 최신 Dart 버전에서 동기 대기 기능이 deprecated되면서 발생하는 문제의 원인을 파악하고, 공식 해결 방법과 임시 해결 방법을 모두 제시합니다.'
oneLineSummary: 'GetX CLI waitFor 에러 완벽 해결 가이드'
tags: ['legacy', 'legacy-migration', 'Flutter', 'Dart', 'GetX', 'troubleshooting']
status: "draft"
updatedDate: '2026-04-23'
---

## 문제 상황

Flutter에서 GetX 프레임워크를 사용 중이라면 `get_cli`(GetX CLI)를 통해 자동으로 프로젝트 구조와 보일러플레이트를 생성하곤 합니다. 최근 다음과 같은 에러가 발생했습니다:

```
Synchronous waiting using dart:cli waitFor and C API Dart_WaitForEvent is deprecated 
and disabled by default. This feature will be fully removed in Dart 3.4 release. 
You can currently still enable it by passing --enable_deprecated_wait_for to the 
Dart VM. See https://dartbug.com/52121.
```

이 에러는 `get create`나 `get generate` 등의 명령을 실행할 때 발생합니다.

## 근본 원인 분석

### Dart의 변경 사항

Dart 3.0 이후 버전에서 동기 대기 기능(synchronous waiting)이 deprecated 되었습니다. 이는 보안과 성능상의 이유로 점진적으로 제거되고 있는 기능입니다.

### get_cli의 문제

공식 `get_cli` 패키지는 이 deprecated된 기능에 의존하고 있습니다. 최신 Dart 버전(3.3+)에서는 기본적으로 이 기능이 비활성화되어 있어서 에러가 발생합니다.

## 해결 방법 비교

| 방법 | 장점 | 단점 |
|------|------|------|
| **Flutter 다운그레이드** | 간단함 | 보안 취약, 미래 문제 |
| **플래그 사용** (임시) | 빠른 해결 | 임시방편, 곧 삭제됨 |
| **get_cli 업데이트** (권장) | 근본적 해결 | 별도 설치 필요 |

## 권장 해결책: 커뮤니티 포크 설치

공식 get_cli의 업데이트가 늦어서, 커뮤니티에서 최신 Dart와 호환되도록 패치한 버전이 나왔습니다.

### 단계별 설치 방법

**1단계: 기존 get_cli 제거**

```bash
flutter pub global deactivate get_cli
```

**2단계: 최신 커뮤니티 버전 설치**

```bash
flutter pub global activate --source=git https://github.com/inyong1/get_cli.git
```

**3단계: 설치 확인**

```bash
get --version
```

### 설치 후 사용

이제 일반적으로 사용하던 모든 get_cli 명령어가 정상 작동합니다:

```bash
get create page:home
get create controller:user
get generate
```

## 임시 해결책 (비권장)

만약 위의 방법이 작동하지 않는다면, Dart VM 플래그를 사용할 수 있습니다:

```bash
dart --enable_deprecated_wait_for /path/to/get_cli
```

하지만 이것은 **임시방편**이며, Dart 3.4에서 완전히 제거될 예정입니다.

## Flutter/Dart 버전 호환성

- **Dart 3.0-3.3**: 플래그로 deprecated 기능 활성화 가능
- **Dart 3.4+**: 완전 제거 (플래그도 작동 안 함)
- **최신 get_cli (포크 버전)**: 모든 Dart 3.x 버전에서 작동

## 마치며

GetX CLI는 여전히 Flutter 개발의 생산성을 크게 높이는 훌륭한 도구입니다. 다만 공식 버전의 업데이트가 늦을 수 있으므로, 이 가이드의 커뮤니티 포크 버전 설치를 권장합니다.

최신 기술을 사용하면서도 필요한 개발 도구를 계속 사용할 수 있는 좋은 해결책입니다. 만약 다른 에러가 발생한다면, 공식 get_cli GitHub 저장소의 이슈 섹션을 확인해보세요.
