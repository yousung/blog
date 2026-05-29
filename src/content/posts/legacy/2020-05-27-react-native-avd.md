---
title: 'React Native Android 빌드 실패: Gradle 설치로 해결'
slug: 'react-native-avd'
author: 'Lovizu'
date: '2020-05-27'
summary: 'React Native에서 yarn android 실행 시 안드로이드 가상머신(AVD) 연결 실패 에러가 발생합니다. Gradle이 설치되지 않았을 때 나타나는 증상과 해결 방법, 그리고 빌드 캐시 초기화 등 추가 트러블슈팅 방법을 설명합니다.'
oneLineSummary: 'React Native Android 빌드 실패는 Gradle 설치로 해결하기'
tags: ['legacy', 'legacy-migration', 'React Native', 'Android', 'Gradle', '개발환경']
status: 'published'
updatedDate: '2026-04-23'
---

## 문제 상황

React Native 프로젝트 초기 설정 후 `yarn android` 또는 `npm run android` 명령어를 실행하면 안드로이드 가상머신(AVD) 연결 실패 에러가 발생합니다.

## 에러 메시지 분석

다음과 같은 에러 메시지가 출력됩니다:

```
error Failed to install the app. Make sure you have the Android development environment set up: https://reactnative.dev/docs/environment-setup. Run CLI with --verbose flag for more details.

Error: Command failed: ./gradlew app:installDebug -PreactNativeDevServerPort=8081

FAILURE: Build failed with an exception.

* What went wrong:
Could not initialize class org.codehaus.groovy.runtime.InvokerHelper

* Try:
Run with --stacktrace option to get the stack trace. Run with --info or --debug option to get more log output.

* Get more help at https://help.gradle.org

BUILD FAILED in 512ms
```

## 핵심 원인

에러의 가장 중요한 부분은 다음 부분입니다:

```
Error: Command failed: ./gradlew app:installDebug
Could not initialize class org.codehaus.groovy.runtime.InvokerHelper
```

이것은 **Gradle이 설치되지 않았음**을 의미합니다. `./gradlew` 명령이 실행되지 않아 빌드가 실패하는 것입니다.

## 해결 방법

### 1단계: Gradle 설치

macOS에서 Homebrew를 사용하여 Gradle을 설치합니다:

```bash
brew install gradle
```

Linux 사용자:

```bash
sudo apt-get install gradle
```

Windows 사용자는 [Gradle 공식 웹사이트](https://gradle.org/)에서 다운로드합니다.

### 2단계: 설치 확인

설치가 완료되었는지 확인합니다:

```bash
gradle -v
```

Gradle 버전 정보가 출력되면 설치가 성공한 것입니다.

### 3단계: React Native 앱 빌드

이제 다시 Android 빌드를 시도합니다:

```bash
yarn android
```

또는 npm을 사용하는 경우:

```bash
npm run android
```

정상적으로 빌드가 진행되고 에뮬레이터에 앱이 설치됩니다.

## 추가 트러블슈팅

빌드 실패가 계속되면 다음 방법들을 시도하세요.

### 캐시 초기화 및 재빌드

Gradle 캐시를 삭제하고 처음부터 빌드합니다:

```bash
cd android
./gradlew clean
cd ..
yarn android
```

### 상세 에러 확인

더 자세한 에러 정보를 보려면 `--verbose` 플래그를 사용합니다:

```bash
yarn android --verbose
```

### 디바이스/에뮬레이터 확인

안드로이드 가상머신이 실행 중인지 확인합니다:

```bash
adb devices
```

에뮬레이터가 없으면 Android Studio에서 가상 디바이스를 생성합니다.

## Android Studio에서 환경 설정 확인

1. Android Studio를 엽니다
2. `Preferences` (또는 `Settings`) → `Appearance & Behavior` → `System Settings` → `Android SDK`
3. SDK Tools 탭에서 다음 항목들이 설치되었는지 확인:
   - Android SDK Build-Tools
   - Android Emulator
   - Android SDK Platform-Tools

## Android 환경 변수 설정

`ANDROID_SDK_ROOT` 환경 변수가 제대로 설정되어 있는지 확인합니다:

```bash
echo $ANDROID_SDK_ROOT
```

설정되지 않았으면 `~/.bash_profile` 또는 `~/.zshrc`에 다음을 추가합니다:

```bash
export ANDROID_SDK_ROOT=$HOME/Library/Android/sdk
export PATH=$PATH:$ANDROID_SDK_ROOT/emulator
export PATH=$PATH:$ANDROID_SDK_ROOT/tools
export PATH=$PATH:$ANDROID_SDK_ROOT/tools/bin
export PATH=$PATH:$ANDROID_SDK_ROOT/platform-tools
```

## 마치며

React Native의 Android 개발 환경 구성에서 Gradle은 필수 요소입니다. 초기 설정 시 모든 종속성이 제대로 설치되었는지 확인하는 것이 중요합니다. 에러 메시지를 신중히 읽고 그에 맞는 해결책을 찾으면 대부분의 빌드 문제를 해결할 수 있습니다. Gradle이 설치되지 않은 경우가 가장 흔한 원인이므로 먼저 이를 확인하시기 바랍니다.
