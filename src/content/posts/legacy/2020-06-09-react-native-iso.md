---
title: 'React Native iOS 실기기 배포 도구 ios-deploy 완벽 가이드'
slug: 'react-native-iso'
author: 'Lovizu'
date: '2020-06-09'
summary: 'React Native iOS 앱을 실제 iPhone/iPad에 배포하고 테스트하는 필수 도구인 ios-deploy를 소개합니다. npm 설치부터 실기기 배포까지 전 과정을 단계별로 설명하고, 개발 인증서 준비, USB 연결 등 주의사항을 다룹니다.'
oneLineSummary: 'ios-deploy로 React Native 앱을 실기기에 배포하기'
tags: ['legacy', 'legacy-migration', 'React Native', 'iOS', '모바일', 'DevOps']
status: "draft"
updatedDate: '2026-04-23'
---

## React Native 개발자가 꼭 알아야 할 도구

React Native로 iOS 앱을 개발할 때 에뮬레이터만으로는 부족합니다. 실제 기기에서 테스트해야 성능, 터치 반응성, 메모리 사용량 등을 정확히 파악할 수 있습니다. **ios-deploy**는 바로 이 작업을 가능하게 해주는 필수 도구입니다.

## ios-deploy란 무엇인가

ios-deploy는 커맨드라인에서 iOS 앱을 실제 iPhone이나 iPad에 직접 배포할 수 있는 도구입니다. Xcode GUI 없이도 빌드와 배포가 가능하므로 자동화에 매우 유용합니다.

**공식 NPM 패키지**: https://www.npmjs.com/package/ios-deploy

## 설치 방법

### npm으로 전역 설치 (권장)

```bash
npm install -g ios-deploy
```

이 방법이 가장 편합니다. 설치 후 모든 프로젝트에서 사용할 수 있습니다.

### 프로젝트별 로컬 설치

특정 프로젝트에만 설치하려면:

```bash
npm install ios-deploy
```

그 후 `npx ios-deploy` 또는 `./node_modules/.bin/ios-deploy`로 실행합니다.

### Homebrew로 설치 (대안)

Mac 패키지 관리자를 선호한다면:

```bash
brew install ios-deploy
```

## 설치 확인

제대로 설치되었는지 확인하려면:

```bash
ios-deploy --version
```

버전 정보가 출력되면 설치가 완료된 것입니다.

## React Native 앱 실기기에 배포하기

iOS 기기를 USB로 Mac에 연결한 후:

```bash
react-native run-ios --device
```

또는 Xcode 프로젝트에서 직접 사용할 수도 있습니다. 명령 실행 후 앱이 자동으로 빌드되어 기기에 설치됩니다.

## 필수 사전 준비사항

ios-deploy 사용 전에 반드시 준비해야 할 것들이 있습니다.

**Apple 개발자 계정**
앱을 실기기에 설치하려면 유효한 Apple 개발자 계정이 필요합니다. 유료 계정($99/년)이어야 실기기 배포가 가능합니다.

**개발 인증서와 프로비저닝 프로필**
- Xcode에서 자동으로 생성할 수 있습니다 (Xcode 8.0 이상)
- 수동으로 생성하려면 Apple Developer Website에서 생성하고 설치해야 합니다.

**기기 등록**
- Xcode에서 연결한 기기를 '신뢰할 수 있는 개발 기기'로 등록해야 합니다.
- 보통 처음 연결할 때 자동으로 등록됩니다.

## 실제 사용 과정

1. **USB 연결**: iPhone/iPad를 MacBook에 USB로 연결
2. **신뢰 설정**: 기기에서 '신뢰'를 선택 (처음 연결 시)
3. **명령 실행**: `react-native run-ios --device`
4. **자동 빌드**: React Native CLI가 자동으로 빌드 및 배포
5. **앱 실행**: 기기에서 앱이 자동으로 실행됩니다

## 자주 마주치는 문제와 해결법

**'인증서를 찾을 수 없음' 에러**
- Xcode에서 Signing & Capabilities 탭을 열어 자동 서명을 활성화하세요.
- 개발 팀이 올바르게 선택되어 있는지 확인하세요.

**기기가 감지되지 않음**
- 기기를 분리했다가 다시 연결하세요.
- Xcode에서 Device를 확인하여 기기가 신뢰 목록에 있는지 확인하세요.

**앱 설치는 되었으나 실행되지 않음**
- 번들 식별자가 올바른지 확인하세요.
- 프로비저닝 프로필이 유효한지 확인하세요.

## 에뮬레이터 vs 실기기

**에뮬레이터 사용 시기**
- 빠른 개발 피드백이 필요할 때
- 다양한 화면 크기를 테스트할 때

**실기기 사용 시기**
- 성능과 메모리 사용량을 확인해야 할 때
- 네이티브 기능(카메라, 마이크 등)을 테스트할 때
- 배포 전 최종 테스트를 할 때

## 마치며

ios-deploy는 React Native iOS 개발의 필수 도구입니다. 실기기 테스트는 단순히 '되는지 안 되는지' 확인하는 수준을 넘어, 실제 사용자가 경험할 성능과 안정성을 검증하는 중요한 과정입니다. 이 도구를 활용하면 개발 품질을 크게 높일 수 있습니다.

더 자세한 정보는 [ios-deploy 공식 GitHub](https://github.com/ios-control/ios-deploy)를 참고하세요.
