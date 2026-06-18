---
title: '.gitignore 규칙 적용 안 될 때 해결하기'
slug: 'gitignore'
author: 'Lovizu'
date: '2018-09-14'
summary: '.gitignore는 새로운 파일만 제외합니다. 이미 Git이 추적 중인 파일은 무시되므로, git rm --cached로 Git 인덱스에서 제거하고 다시 추가해야 합니다. 이 문제의 원인과 해결 방법을 단계별로 설명합니다.'
oneLineSummary: 'Git이 .gitignore를 무시할 때 캐시 초기화로 해결하기'
tags: ['legacy', 'legacy-migration', 'Git', 'VCS', '버전관리']
status: 'published'
updatedDate: '2026-04-23'
---

## 문제 상황

`.gitignore` 파일에 제외 규칙을 추가했는데도 파일이 계속 Git에 추적되는 현상이 발생합니다. 특히 이미 저장소에 커밋된 파일들이 `.gitignore`를 무시하고 버전 관리 대상으로 남아있습니다.

## 원인 분석

Git의 동작 방식을 이해하면 이 문제를 쉽게 해결할 수 있습니다:

1. `.gitignore`는 **새로운 파일만 무시**합니다
2. 이미 커밋된 파일은 `.gitignore`가 작동하지 않습니다
3. `git add .` 후 미커밋 상태의 파일도 동일하게 처리됩니다

즉, Git이 한 번 추적하기 시작한 파일은 `.gitignore` 규칙을 무시하고 계속 추적합니다.

## 해결 방법

### 1단계: Git 인덱스 초기화

먼저 Git이 추적 중인 모든 파일을 인덱스에서 제거합니다:

```bash
git rm -r --cached .
```

이 명령어는:
- 모든 파일을 Git 인덱스에서 제거 (실제 파일 삭제 안 함)
- `-r`: 재귀적으로 디렉토리 처리
- `--cached`: 파일 시스템은 건드리지 않고 Git 저장소만 수정

### 2단계: 파일 다시 추가

이제 `.gitignore` 규칙을 적용하여 파일을 다시 추가합니다:

```bash
git add .
```

이번에는 제외된 파일들이 스테이징되지 않습니다.

### 3단계: 변경사항 커밋

```bash
git commit -m "git cache 초기화: .gitignore 규칙 적용"
```

## 특정 파일만 제외하기

모든 파일을 다시 추가하지 않고 특정 파일만 제외하려면:

```bash
git rm --cached path/to/file
git commit -m "Remove file from version control"
```

## .gitignore 작성 예시

실무에서 자주 사용되는 `.gitignore` 패턴들입니다:

```
# 환경 설정 파일
.env
.env.local
.env.*.local

# IDE 설정
.idea/
.vscode/
*.swp
*.swo

# 빌드 결과물
dist/
build/
*.o
*.a

# 패키지 매니저
node_modules/
vendor/
__pycache__/

# 운영체제 파일
.DS_Store
Thumbs.db

# 로그 파일
*.log
logs/

# 임시 파일
*.tmp
temp/
```

## 실행 시 주의사항

- `git rm -r --cached .`는 모든 파일을 처리하므로 대규모 저장소에서는 시간이 소요될 수 있습니다
- 팀 작업 중이라면 다른 팀원에게 사전에 알려주세요
- 중요한 파일이 실수로 제외되지 않았는지 확인하세요

## 결과 확인

변경사항이 올바르게 적용되었는지 검증합니다:

```bash
git status
```

`.gitignore`에 명시된 파일들이 `git status` 출력에서 사라졌다면 성공입니다.

## 마치며

`.gitignore`가 작동하지 않는 대부분의 경우는 이미 Git이 파일을 추적하고 있기 때문입니다. `git rm --cached` 명령으로 Git 인덱스에서 제거한 후 다시 추가하면 `.gitignore` 규칙이 정상 적용됩니다. 초기 프로젝트 설정 시 `.gitignore`를 먼저 준비하면 이러한 상황을 예방할 수 있습니다.
