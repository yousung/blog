---
title: 'Flutter Freezed 생성 파일을 IDE에서 깔끔하게'
slug: 'freezeddart-gdart'
author: 'Lovizu'
date: '2023-04-22'
summary: 'Flutter 개발에서 Freezed, Drift 등의 패키지 사용 시 생성되는 다중 확장자 파일(.freezed.dart, .g.dart, .gr.dart 등)을 IntelliJ IDEA와 Android Studio의 파일 그룹핑 기능으로 깔끔하게 관리하는 방법입니다.'
oneLineSummary: "Freezed 생성 파일 3개를 IDE에서 하나로 묶기"
tags: ['legacy', 'legacy-migration', 'Flutter', 'Dart', 'IDE', 'IntelliJ']
status: "draft"
updatedDate: '2026-04-23'
---

Flutter 개발에서 Freezed나 Drift 같은 코드 생성 패키지를 사용하면 같은 이름으로 여러 개의 파일이 생성됩니다. 예를 들어 `user.dart` 파일이 있다면:

- `user.dart` (원본)
- `user.freezed.dart` (생성)
- `user.g.dart` (생성)

이렇게 3개의 파일이 생기는데, 직접 수정할 일은 거의 없으면서도 파일 탐색기가 지저분해 보입니다. IntelliJ IDEA와 Android Studio의 **파일 그룹핑(File Nesting)** 기능으로 이를 깔끔하게 정리할 수 있습니다.

## 파일 그룹핑 설정하기

### 1단계: 설정 메뉴 열기

IntelliJ IDEA 또는 Android Studio에서 아래 중 하나를 선택합니다:

**방법 1: 검색으로 빠르게**
- `Shift` 연속 2번으로 "Go to All" 검색창을 엽니다
- `file nesting` 또는 `nest`를 검색합니다
- "File Nesting" 설정을 클릭합니다

**방법 2: 메뉴로 열기**
- Mac: `IntelliJ > Preferences`
- Windows/Linux: `File > Settings`
- 검색: `File Nesting`

### 2단계: File Nesting 규칙 설정

![IntelliJ file nesting 검색 화면](/images/posts/freezeddart-gdart/img-01.png)

File Nesting 설정 페이지에서 다음을 수행합니다:

1. **"Enable File Nesting"** 체크박스를 체크합니다
2. **"Register New Pattern"** 버튼을 클릭합니다

아래와 같은 패턴들을 추가합니다:

```
*.freezed.dart <= *.dart
*.g.dart <= *.dart
*.gr.dart <= *.dart
*.config.dart <= *.dart
```

이는 다음을 의미합니다:
- `.freezed.dart` 파일은 기본 `.dart` 파일 아래에 묶입니다
- `.g.dart` 파일도 기본 `.dart` 파일 아래에 묶입니다
- `.gr.dart` 파일도 동일하게 적용됩니다

![파일 그룹핑 설정 예시](/images/posts/freezeddart-gdart/img-02.png)

### 3단계: 설정 완료

설정을 저장하면 파일 탐색기가 즉시 변경됩니다.

원래는:
```
user.dart
user.freezed.dart
user.g.dart
```

이제는:
```
user.dart
  └─ user.freezed.dart
  └─ user.g.dart
```

![합쳐진 파일 결과물](/images/posts/freezeddart-gdart/img-03.png)

## 추가 팁

### Freezed와 함께 사용하는 다른 생성 파일들

```
*.freezed.dart <= *.dart  # Freezed 클래스
*.g.dart <= *.dart        # JsonSerializable, Drift
*.gr.dart <= *.dart       # GetterHooks
*.config.dart <= *.dart   # 설정 파일
```

### 클릭해서 확장/축소

이제 파일 탐색기에서:
- `user.dart` 옆의 화살표를 클릭하면 생성된 파일들을 볼 수 있습니다
- 다시 클릭하면 숨길 수 있습니다

## 마치며

파일 그룹핑은 작지만 매우 효과적인 IDE 최적화입니다. 프로젝트가 커질수록 파일이 많아지는데, 이런 작은 설정들이 모여서 개발 경험을 훨씬 쾌적하게 만듭니다. 특히 Freezed나 Drift를 많이 사용하는 Flutter 프로젝트에서는 필수적인 설정이라고 할 수 있습니다.
