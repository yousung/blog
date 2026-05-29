---
title: 'JetBrains IDE 한글 언어팩 완벽 설정 가이드'
slug: 'jetbrains'
author: 'Lovizu'
date: '2024-09-26'
summary: 'JetBrains IDE(IntelliJ, WebStorm, PyCharm 등)에서 한글 언어팩을 활성화했는데도 인터페이스가 영어로 표시되는 문제의 완벽한 해결책. 플러그인 설정과 시스템 언어 설정을 함께 변경해야 하는 이유를 설명하고, 캐시 문제, 업데이트, 재설치 등 모든 상황에 대한 구체적인 트러블슈팅 방법을 제시합니다.'
oneLineSummary: 'JetBrains 한글 언어팩 완벽 설정: 플러그인 + 시스템 설정'
tags: ['legacy', 'legacy-migration', 'JetBrains', 'IDE', '개발도구', 'IntelliJ']
status: 'published'
updatedDate: '2026-04-23'
---

## 문제 상황

JetBrains IDE의 한글 언어팩 플러그인을 설치하고 활성화했는데도 IDE 인터페이스가 여전히 영어로 표시되는 경우가 많습니다. 이 문제는 단순히 플러그인만 활성화해서는 안 되고 추가 설정이 필요하기 때문입니다.

### 최근 변화

| 기간 | 설정 방식 |
|------|---------|
| 2021년 이전 | 플러그인 설치 (marketplace에서 다운로드) |
| 2021-2023년 | 플러그인 enable/disable 토글 방식 |
| 2024년 이상 | 플러그인 + 시스템 언어 설정 병행 필요 |

JetBrains는 IDE 아키텍처를 변경하면서 언어팩 설정 방식도 개선되었지만, 초기에는 이 점이 충분히 안내되지 않았습니다.

## 해결 방법

### 1단계: 플러그인 활성화 확인

**메뉴 경로**: Settings (또는 Preferences) → Plugins

```
1. 검색창에 "korean" 또는 "언어" 검색
2. "Korean Language Pack" 찾기
3. 상태 확인:
   - ✓ Installed (설치됨)
   - 버튼: [Enable] 또는 [Disable]
4. [Enable] 버튼이 있으면 클릭
```

### 2단계: IDE 시스템 언어 설정

**메뉴 경로**: Appearance & Behavior → System Settings → Language and Region

```
1. Appearance & Behavior 확장
2. System Settings 클릭
3. Language and Region 선택
4. Language 드롭다운 확인:
   - English
   - 한국어 (Korean)
   - 기타 언어들
5. 한국어 선택
```

### 3단계: IDE 재시작

설정 변경을 적용하려면 IDE를 완전히 재시작해야 합니다.

```
메뉴: File → Exit (또는 Mac에서 ⌘Q)

완전히 종료 후 다시 실행
```

## 상세 단계별 가이드

### 플러그인 활성화 상세

**IntelliJ IDEA에서:**
```
File → Settings → Plugins → 검색: Korean
```

**WebStorm에서:**
```
WebStorm → Preferences (Mac)
또는
File → Settings (Windows/Linux)
→ Plugins → 검색: Korean
```

**PyCharm에서:**
```
PyCharm → Preferences (Mac)
또는
File → Settings (Windows/Linux)
→ Plugins → 검색: Korean
```

### 시스템 언어 설정 상세

이 단계가 가장 중요합니다. 많은 사용자가 이 부분을 놓치고 있습니다.

**경로 확인:**
```
File → Settings (Windows/Linux)
또는
IDE명 → Preferences (Mac)
    ↓
Appearance & Behavior
    ↓
System Settings (또는 Advanced Settings)
    ↓
Language and Region
```

**언어 선택:**
- 드롭다운에서 "한국어"를 찾아 선택
- "English"로 돌아가려면 같은 위치에서 "English" 선택

## 트러블슈팅

### 문제 1: 플러그인이 설치되어 있지 않음

**증상**: Plugins 탭에서 "Korean Language Pack"을 찾을 수 없음

**해결책**:

```
1. Marketplace 탭 클릭
2. 검색: "Korean Language Pack"
3. 최신 버전 찾기
4. [Install] 버튼 클릭
5. IDE 재시작
```

**버전 호환성 확인:**
```
IDE 버전과 플러그인 호환성 확인
예: IntelliJ 2024.1 → Korean Language Pack 8.2.0
```

### 문제 2: 설정 변경 후에도 영어로 표시됨

**증상**: 
- 플러그인 enabled ✓
- 언어 설정: 한국어 ✓
- 하지만 여전히 영어 인터페이스

**원인**: 캐시가 개입

**해결책**:

```
1. IDE 완전 종료
2. 캐시 디렉토리 삭제:

   macOS:
   ~/Library/Caches/JetBrains/IntelliJIdea2024.1/

   Windows:
   C:\Users\[username]\AppData\Local\JetBrains\IntelliJIdea2024.1\cache

   Linux:
   ~/.cache/JetBrains/IntelliJIdea2024.1/

3. IDE 다시 실행
```

**또는 IDE 메뉴 사용:**
```
File → Invalidate Caches / Restart
→ [Invalidate and Restart] 클릭
```

### 문제 3: 캐시 삭제 후에도 작동 안 함

**더 강력한 해결책:**

```bash
# IntelliJ의 경우
rm -rf ~/Library/Application\ Support/JetBrains/IntelliJIdea*
rm -rf ~/Library/Caches/JetBrains/*
rm -rf ~/Library/Logs/JetBrains/*

# WebStorm의 경우
rm -rf ~/Library/Application\ Support/JetBrains/WebStorm*
```

그 후 IDE를 다시 실행합니다.

### 문제 4: 특정 메뉴만 영어로 표시됨

**원인**: 플러그인이 완전히 로드되지 않음

**해결책**:
```
1. File → Settings → Plugins
2. "Korean Language Pack" 찾기
3. 오른쪽 클릭 → [Uninstall]
4. IDE 재시작
5. Marketplace에서 다시 설치
6. IDE 재시작
7. 언어 설정 다시 확인
```

### 문제 5: 플러그인 마켓플레이스 연결 안 됨

**증상**: Plugins 탭에서 Marketplace를 열 수 없음

**해결책**:
```
1. 네트워크 연결 확인
2. VPN/프록시 설정 확인
3. IDE 네트워크 설정 확인:
   Settings → Appearance & Behavior → System Settings 
   → HTTP Proxy
4. IDE 재부팅
```

## IDE별 추가 팁

### IntelliJ IDEA
```
최신 버전: 2024.1 이상 권장
한글 플러그인: Korean Language Pack (공식)
버전 확인: Help → About IntelliJ IDEA
```

### WebStorm
```
최신 버전: 2024.1 이상 권장
한글 플러그인: Korean Language Pack
Node.js와 무관하게 설정 가능
```

### PyCharm
```
최신 버전: 2024.1 이상 권장
한글 플러그인: Korean Language Pack
Python 버전과 무관하게 설정 가능
```

### PhpStorm
```
최신 버전: 2024.1 이상 권장
한글 플러그인: Korean Language Pack
PHP 버전과 무관하게 설정 가능
```

## 언어 전환이 자주 필요한 경우

### IDE별 별칭 설정

```bash
# ~/.zshrc 또는 ~/.bash_profile에 추가

# IntelliJ를 영어로 실행
alias idea-en='IDEA_PROPERTIES="-Duser.country=US -Duser.language=en" idea'

# WebStorm을 한국어로 실행
alias ws-ko='WEBSTORM_PROPERTIES="-Duser.country=KR -Duser.language=ko" webstorm'
```

### 실행 시 언어 지정

```bash
# 환경 변수로 언어 지정
IDEA_PROPERTIES="-Duser.country=KR -Duser.language=ko" idea

# 또는
_JAVA_OPTIONS="-Duser.country=KR -Duser.language=ko" idea
```

## 검증: 완전히 한글화되었는지 확인

| 요소 | 확인 사항 |
|------|---------|
| 메뉴바 | File, Edit, View 등이 한글로 표시 |
| 설정 창 | Settings 창의 모든 옵션이 한글 |
| 대화상자 | 파일 저장, 프로젝트 생성 등의 팝업이 한글 |
| 상태 표시줄 | IDE 하단의 상태 메시지가 한글 |
| 플러그인 창 | Plugins 탭의 플러그인 설명이 한글 |

만약 이 중 일부만 한글이면, 캐시를 다시 삭제하고 재시작하세요.

## 한글 → 영어로 되돌리기

다시 영어로 사용하고 싶다면:

```
1. Settings → Appearance & Behavior → System Settings → Language
2. "English" 선택
3. IDE 재시작
```

플러그인은 비활성화할 수도 있고, 설치된 상태로 둘 수도 있습니다.

## 마치며

JetBrains IDE의 한글 설정은 복잡해 보이지만, 핵심은 두 가지입니다:

1. **플러그인 활성화**: Korean Language Pack이 enabled 상태
2. **시스템 언어 설정**: Language and Region에서 한국어 선택

이 두 단계를 거친 후 IDE를 재시작하면 완벽한 한글 환경을 구성할 수 있습니다. 만약 작동하지 않으면 캐시를 삭제하고 다시 시도하세요.

효과적인 개발을 위해서는 편한 언어 환경이 중요합니다. 이 가이드가 여러분의 한글화 문제를 완벽히 해결하는 데 도움이 되길 바랍니다.
