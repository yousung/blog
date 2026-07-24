---
title: 'Composer 기반 PHP 버전 자동 전환 설정하기'
slug: 'php-version-feat-php-monitor'
author: "감성개발자"
date: '2023-03-23'
summary: 'Laravel Valet과 PHP Monitor를 사용하는 개발자들을 위한 자동화 가이드. Composer.json의 PHP 버전을 읽어 프로젝트 진입 시 자동으로 PHP 버전을 전환하는 zsh 훅 스크립트를 구성하는 방법. Mac 환경에서 jq, Valet, PHP Monitor를 활용한 개발 환경 최적화.'
oneLineSummary: "프로젝트 진입 시 자동으로 PHP 버전을 전환하는 zsh 스크립트 설정"
tags: ['legacy', 'legacy-migration', 'PHP', 'Laravel', 'Valet', 'MacOS', '개발환경']
status: "draft"
updatedDate: '2026-04-23'
---

## 개요

Node.js 버전 관리와 유사하게, PHP 프로젝트도 각 프로젝트마다 다른 PHP 버전을 필요로 합니다. Laravel Valet과 PHP Monitor를 사용하는 개발자들을 위해 프로젝트 진입 시 자동으로 PHP 버전을 전환하는 방법을 소개합니다.

## 전제조건

다음 환경이 필요합니다:

- **macOS** 사용자
- **Homebrew**와 **zsh** 설치 완료
- **Laravel Valet** 사용 중 ([설치가이드](https://laravel.com/docs/10.x/valet))
- **PHP Monitor** 설치 ([PHP Monitor](https://phpmon.app/))
- **Composer** 사용 중
- Docker를 사용하지 않는 경우 (Docker 사용자는 컨테이너 내 버전 관리로 충분)

## 준비 단계

### 1단계: jq 설치

Composer.json을 파싱하기 위해 jq를 설치합니다:

```bash
brew install jq
```

### 2단계: PHP Monitor 환경변수 등록

PHP Monitor의 환경 변수를 .zshrc에 등록합니다:

```bash
# 홈 디렉토리의 .zshrc 파일에 다음 한 줄 추가
export PATH=$HOME/.config/phpmon/bin:$PATH
```

### 3단계: zsh 훅 스크립트 작성

다음 스크립트를 `~/Utils/php/auto-php.sh` 파일로 생성합니다:

```bash
autoload -U add-zsh-hook

change-php-version() {
  if [ -f composer.json ]; then
    PHPVERSION=$(cat composer.json | jq '.require.php' | grep -o '[0-9].[0-9]')
    NOWVERSION=$(php -v | grep -o '[0-9].[0-9]' | head -n 1)
    if [ "$PHPVERSION" != $NOWVERSION ]; then
      SWITCHVERSION=$(echo $PHPVERSION | sed -e "s/\.//g")
      . pm$SWITCHVERSION
    fi
  fi
}

add-zsh-hook chpwd change-php-version
```

**동작 원리:**
- 프로젝트 디렉토리 진입 시 `composer.json` 파일 존재 여부 확인
- jq로 `composer.json`에서 required PHP 버전 추출
- 현재 활성 PHP 버전과 비교
- 버전이 다르면 PHP Monitor 명령으로 자동 전환

### 4단계: .zshrc에 스크립트 등록

`~/.zshrc` 파일에 다음 라인을 추가합니다:

```bash
source ~/Utils/php/auto-php.sh
```

변경사항을 적용하려면 터미널을 재시작하거나 다음 명령을 실행합니다:

```bash
source ~/.zshrc
```

## 효과

이제 Laravel 프로젝트로 이동하면 자동으로 해당 프로젝트가 필요로 하는 PHP 버전으로 전환됩니다. 프로젝트별 버전 관리로 인한 호환성 문제를 해결할 수 있습니다.

## 문제 해결 팁

### 스크립트가 작동하지 않는 경우

**확인 사항:**
- `jq` 설치 여부: `which jq` 명령 실행
- PHP Monitor 경로 확인: `which pm74` (또는 다른 버전)
- `.zshrc` 파일 문법 확인: `zsh -n ~/.zshrc`

### PHP 버전 전환이 느린 경우

PHP Monitor가 버전을 전환하는 데 시간이 걸릴 수 있습니다. 이는 정상적인 동작입니다.

### Composer.json에 PHP 버전이 없는 경우

```bash
{
    "require": {
        "php": "^8.1"
    }
}
```

`composer.json`에 반드시 `require.php` 필드가 있어야 스크립트가 정상 작동합니다.

## 마치며

이 방법은 Valet 사용자들이 여러 프로젝트를 효율적으로 관리할 수 있게 해줍니다. 초기 설정이 약간 필요하지만, 일단 완료하면 프로젝트 디렉토리 진입 시 자동으로 PHP 버전이 전환되어 개발 생산성을 크게 높일 수 있습니다.

특히 PHP 5.6부터 PHP 8.2까지 다양한 레거시 프로젝트를 동시에 관리해야 하는 경우, 이 자동화 스크립트는 필수적인 도구가 됩니다.
