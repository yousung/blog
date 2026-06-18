---
title: 'NVM으로 프로젝트별 Node 버전 자동 관리'
slug: 'node-feat-nvm'
author: 'Lovizu'
date: '2023-03-22'
summary: 'NVM(Node Version Manager)을 활용하여 프로젝트별 Node 버전을 자동으로 관리하는 실무 가이드입니다. .nvmrc 파일과 zsh 훅을 조합하여 프로젝트 디렉토리 진입 시 자동으로 적절한 Node 버전이 로드됩니다. 레거시 유지보수와 신규 프로젝트를 동시에 진행할 때 버전 호환성 문제를 근본적으로 해결하는 방법입니다.'
oneLineSummary: 'NVM으로 다중 프로젝트 Node 버전 자동 관리'
tags: ['legacy', 'legacy-migration', 'Node.js', 'NVM', '개발환경', 'zsh']
status: "draft"
updatedDate: '2026-04-23'
---

## 도입

하나의 프로젝트만 진행할 때는 Node 버전이 크게 문제가 되지 않지만, 레거시 유지보수와 신규 프로젝트를 동시에 진행해야 할 때 프로젝트마다 필요한 Node 버전이 다를 수 있습니다. 이 경우 매번 수동으로 버전을 변경하는 것은 번거롭고 실수하기 쉽습니다. **NVM(Node Version Manager)**을 활용하면 이를 완전히 자동화할 수 있으며, 프로젝트 진입 시 필요한 Node 버전이 자동으로 로드됩니다.

## 준비물

- **NVM** (Node Version Manager)
- **버전이 다른 프로젝트 2개 이상**
- **Zsh** (macOS 기본 쉘)

본 가이드는 M1/M2 맥북을 기준으로 작성되었으나, Intel 맥도 대부분 동일합니다.

## NVM 설치 및 설정

### Step 1: NVM 설치

Homebrew를 통해 설치합니다:

```bash
brew install nvm
```

### Step 2: NVM 환경 설정

NVM 디렉토리를 생성하고 자동 로드 스크립트를 준비합니다:

```bash
# NVM 디렉토리 생성
mkdir -p ~/.nvm

# 자동 로드 스크립트 생성
cd ~/.nvm
touch auto-nvm.sh
chmod +x auto-nvm.sh

# .zshrc에 자동 로드 스크립트 추가
echo "source ~/.nvm/auto-nvm.sh" >> ~/.zshrc
```

### Step 3: auto-nvm.sh 파일 작성

`~/.nvm/auto-nvm.sh` 파일을 생성하고 다음 내용을 추가합니다. 이 스크립트는 디렉토리 변경 시 `.nvmrc` 파일을 감지하여 자동으로 Node 버전을 전환합니다:

```bash
# https://github.com/nvm-sh/nvm#zsh
autoload -U add-zsh-hook
load-nvmrc() {
  local nvmrc_path="$(nvm_find_nvmrc)"

  if [ -n "$nvmrc_path" ]; then
    local nvmrc_node_version=$(nvm version "$(cat "${nvmrc_path}")")

    if [ "$nvmrc_node_version" = "N/A" ]; then
      nvm install
    elif [ "$nvmrc_node_version" != "$(nvm version)" ]; then
      nvm use
    fi
  elif [ -n "$(PWD=$OLDPWD nvm_find_nvmrc)" ] && [ "$(nvm version)" != "$(nvm version default)" ]; then
    echo "Reverting to nvm default version"
    nvm use default
  fi
}
add-zsh-hook chpwd load-nvmrc
load-nvmrc
```

### Step 4: 설정 적용

```bash
source ~/.zshrc
```

## 프로젝트에 .nvmrc 파일 추가

각 프로젝트의 루트 디렉토리에 `.nvmrc` 파일을 생성하고 원하는 Node 버전을 지정합니다:

```bash
# 프로젝트 1 (Node v18)
cd ~/projects/my-new-project
echo "v18.15.0" > .nvmrc

# 프로젝트 2 (Node v16)
cd ~/projects/legacy-project
echo "v16.20.0" > .nvmrc
```

## 자동 버전 전환 동작

### 동작 원리

1. **프로젝트 디렉토리 진입**: `cd ~/projects/my-new-project`
2. **.nvmrc 파일 감지**: 이전에 설정한 zsh 훅이 `.nvmrc` 파일 자동 감지
3. **버전 확인**: 파일에 명시된 Node 버전이 설치되어 있는지 확인
4. **자동 전환**: 필요시 자동으로 해당 버전으로 전환

### 실제 동작 예시

```bash
$ cd ~/projects/my-new-project
Found '.nvmrc' with version <v18.15.0>
Now using node v18.15.0 (npm 9.5.0)

$ node --version
v18.15.0

$ cd ~/projects/legacy-project
Found '.nvmrc' with version <v16.20.0>
Now using node v16.20.0 (npm 8.19.4)

$ node --version
v16.20.0

$ cd ~
Reverting to nvm default version
Now using node v18.15.0
```

## 실무 팁과 추가 설정

### .nvmrc 파일 정보 확인

프로젝트에서 사용 중인 Node 버전 확인:

```bash
cat .nvmrc
```

### 특정 Node 버전 설치

`.nvmrc`에서 지정한 버전이 설치되지 않았다면, NVM이 자동으로 설치를 제안합니다:

```bash
# 특정 버전 설치
nvm install v18.15.0

# 설치된 버전 목록 확인
nvm list

# 특정 버전 사용 (수동으로)
nvm use v18.15.0
```

### 기본 Node 버전 설정

터미널을 시작할 때 사용할 기본 버전을 설정합니다:

```bash
nvm alias default v18.15.0
```

### Global .nvmrc 설정 (권장)

홈 디렉토리에도 `.nvmrc` 파일을 두면, 프로젝트 디렉토리 밖에서는 이 버전을 기본값으로 사용합니다:

```bash
echo "v18.15.0" > ~/.nvmrc
```

### .gitignore에서 .nvmrc 제외

`.nvmrc`는 팀이 같은 Node 버전을 사용하도록 하는 중요한 파일이므로 버전 제어에 포함되어야 합니다. `.gitignore`에 추가되어 있다면 제거하세요:

```bash
# .gitignore에서 다음 줄 삭제
# .nvmrc
```

## 문제 해결

### NVM 명령이 작동하지 않는 경우

`.zshrc` 설정을 다시 로드하세요:

```bash
source ~/.zshrc
```

또는 터미널을 종료했다가 다시 열기

### 자동 버전 전환이 작동하지 않는 경우

1. **auto-nvm.sh 파일 권한 확인**:
   ```bash
   ls -la ~/.nvm/auto-nvm.sh
   ```
   실행 권한이 있어야 합니다 (`-rwx...`)

2. **.zshrc에 소스 경로 확인**:
   ```bash
   cat ~/.zshrc | grep "auto-nvm"
   ```

3. **터미널 재시작** 또는 `source ~/.zshrc` 실행

## 실제 사용 예

```bash
# 프로젝트 1로 이동
$ cd ~/projects/react-app
Found '.nvmrc' with version <v18.15.0>
Now using node v18.15.0
$ npm start  # React 18 프로젝트 실행

# 프로젝트 2로 이동
$ cd ~/projects/express-legacy
Found '.nvmrc' with version <v14.21.0>
Now using node v14.21.0
$ npm start  # Express 레거시 프로젝트 실행

# 홈으로 돌아가기
$ cd ~
Reverting to nvm default version
Now using node v18.15.0
```

## 핵심 요약

NVM을 활용한 자동 Node 버전 관리는 다중 프로젝트 환경에서 개발자의 생산성을 크게 향상시킵니다. `.nvmrc` 파일과 zsh 훅의 조합으로 프로젝트 간 전환 시 자동으로 올바른 버전이 로드되므로, 수동 설정 실수를 완전히 제거할 수 있습니다. 특히 레거시 유지보수와 신규 프로젝트를 동시에 진행할 때 버전 호환성 문제를 근본적으로 해결하는 강력한 도구입니다.
