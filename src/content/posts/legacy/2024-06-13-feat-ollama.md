---
title: 'Ollama로 로컬 AI 코파일럿 구축하기'
slug: 'feat-ollama'
author: 'Lovizu'
date: '2024-06-13'
summary: 'Ollama를 사용하여 로컬 머신에서 무료로 실행할 수 있는 AI 코파일럿 환경을 구축합니다. Continue IDE 플러그인과의 연동으로 VSCode, JetBrains IDE에서 직접 AI 어시스턴트를 사용할 수 있으며, 별도의 API 키나 온라인 서비스 비용이 없습니다.'
oneLineSummary: '무료로 로컬 LLM 기반 AI 코파일럿 만들기'
tags: ['legacy', 'legacy-migration', 'AI', 'LLM']
status: 'published'
updatedDate: '2026-04-23'
---

## 배경: 유료 AI 서비스의 대안 찾기

ChatGPT가 처음 나왔을 때는 부정확한 답변이 많았습니다. 하지만 최근 ChatGPT 4o는 정말 유용하고 Google 검색보다 믿을 만합니다.

자연스럽게 GitHub Copilot 같은 **AI 코파일럿**에 관심을 갖게 되었습니다. 무료 체험은 훌륭했지만 **월 $20의 유료 비용**이 마음에 걸렸습니다.

그 과정에서 발견한 것이 **Ollama**입니다.

## Ollama란

Ollama는 **Large Language Model (LLM)**을 로컬 머신에서 직접 실행하는 도구입니다.

**장점:**
- 무료
- 인터넷 연결 불필요
- API 키 불필요
- 개인 코드를 외부 서버로 전송하지 않음
- 다양한 오픈소스 모델 지원 (Llama, Mistral, Neural Chat 등)

## 3단계 설치 가이드

### 1단계: Ollama 설치 및 모델 다운로드

[Ollama GitHub](https://github.com/ollama/ollama)에서 설치 파일을 다운로드합니다.

설치 후 터미널에서 모델을 다운로드합니다:

```bash
ollama run llama3.1
```

처음 실행 시 모델 파일이 자동 다운로드됩니다 (용량: 5-13GB).

### 2단계: IDE에 Continue 플러그인 설치

**VSCode:**
- [Continue - VS Marketplace](https://marketplace.visualstudio.com/items?itemName=Continue.continue)

**JetBrains (IntelliJ, WebStorm, PyCharm 등):**
- [Continue - JetBrains Plugin](https://plugins.jetbrains.com/plugin/22707-continue)

### 3단계: Continue 로컬 설정

IDE의 Continue 설정에서:

1. API 제공자를 "Local"로 선택
2. 기본 모델 설정 완료
3. (선택) 코드 자동완성 활성화

더 이상 외부 API가 필요하지 않습니다.

## 성능 및 실제 경험

현재 사용 중인 모델들의 성능:

| 모델 | 용량 | 성능 | 추천 용도 |
| --- | --- | --- | --- |
| Llama 3.1 | 8GB | 매우 좋음 | 일반 개발 |
| Mistral | 5GB | 좋음 | 빠른 응답 필요 시 |
| Neural Chat | 3GB | 충분함 | 리소스 제약 시 |

며칠 사용해본 결과, **첫인상은 합격**입니다. 특히 코드 설명, 버그 수정 제안, 리팩토링 아이디어에 매우 유용합니다.

## 언제 로컬 LLM이 더 나은가

1. **보안**: 민감한 기업 코드를 외부 서버로 전송하지 않음
2. **비용**: 개발자당 월 $20 절감
3. **독립성**: 인터넷 장애의 영향 없음
4. **프라이버시**: 완전한 로컬 처리

## 시작하기

처음 시작할 때 모델 다운로드가 시간이 걸리지만, 한 번 설치하면 오래 사용할 수 있습니다. 개인 프로젝트나 기업 환경 모두에서 좋은 선택입니다.

## 마치며

GitHub Copilot이나 ChatGPT 요금이 부담스럽다면, **Ollama는 충분히 실용적인 무료 대안**입니다. 오픈소스 LLM의 발전 속도를 감안하면, 시간이 갈수록 더 강력해질 것으로 기대됩니다.
