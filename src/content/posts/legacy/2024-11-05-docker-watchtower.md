---
title: 'Docker 컨테이너 항상 최신 버전으로 유지하기'
slug: 'docker-watchtower'
author: 'Lovizu'
date: '2024-11-05'
summary: 'Watchtower를 사용하여 Docker 컨테이너를 자동으로 최신 버전으로 업데이트합니다. 수동 재배포 없이 항상 최신 이미지를 유지할 수 있습니다.'
oneLineSummary: 'Docker 자동 업데이트 설정'
tags: ['legacy', 'legacy-migration', 'Docker', '자동화', 'DevOps']
status: "draft"
updatedDate: '2026-04-23'
---

## Docker 컨테이너 자동 업데이트의 필요성

프로덕션 환경에서 실행되는 Docker 컨테이너들은 정기적으로 보안 업데이트와 버그 픽스를 받아야 합니다. 수동으로 각 컨테이너를 확인하고 업데이트하는 것은 시간이 많이 걸리고 실수하기 쉽습니다.

특히 장기 운영 중인 프로젝트에서는 컨테이너 이미지가 오래될수록:
- 보안 취약점에 노출될 가능성 증가
- 성능 개선사항을 놓치게 됨
- 의존성 버전 충돌 위험 증가

이러한 문제를 해결하기 위해 **Watchtower**라는 도구를 사용하면 자동으로 최신 이미지로 업데이트할 수 있습니다.

## Watchtower란?

Watchtower는 Docker 컨테이너를 자동으로 모니터링하고 업데이트하는 오픈소스 도구입니다. 컨테이너 레지스트리(Docker Hub, ECR 등)에서 새로운 이미지를 주기적으로 확인하고, 업데이트가 있으면 자동으로 이전 컨테이너를 종료하고 새 이미지로 재배포합니다.

### 주요 특징

| 기능 | 설명 |
|------|------|
| 자동 모니터링 | 레지스트리에서 새 이미지 자동 감지 |
| 무중단 업데이트 | 기존 컨테이너 종료 후 새 이미지 시작 |
| 정리 기능 | 사용 중인 이미지 자동 삭제 |
| 선택적 업데이트 | 라벨을 통해 특정 컨테이너만 관리 가능 |
| 다중 레지스트리 지원 | Docker Hub, ECR, 프라이빗 레지스트리 모두 지원 |

## Watchtower 설치 및 설정

### 기본 설정

```bash
docker run -d \
  --name=watchtower \
  -e TZ=Asia/Seoul \
  -v /var/run/docker.sock:/var/run/docker.sock \
  --restart=always \
  containrrr/watchtower \
  --cleanup
```

### 주요 매개변수 설명

```bash
-d                              # 백그라운드 모드로 실행
--name=watchtower             # 컨테이너 이름 지정
-e TZ=Asia/Seoul              # 타임존 설정 (한국 기준)

-v /var/run/docker.sock:...   # Docker 소켓 마운트
                              # 다른 컨테이너 관리 권한 부여

--restart=always              # 서버 재부팅 시 자동 재시작
--cleanup                     # 업데이트 후 이전 이미지 자동 삭제
```

### 고급 옵션

**업데이트 간격 설정:**
```bash
# 매일 오전 2시에 확인
docker run ... \
  containrrr/watchtower \
  --schedule="0 2 * * *"
```

**특정 컨테이너만 업데이트:**
```bash
# Watchtower 실행 시
docker run ... \
  containrrr/watchtower \
  --label-enable

# 업데이트 대상 컨테이너에 라벨 추가
docker run \
  --label=com.centurylinklabs.watchtower.enable=true \
  ...
```

**통지 설정:**
```bash
# Slack 알림
docker run ... \
  -e WATCHTOWER_NOTIFICATIONS=slack \
  -e WATCHTOWER_NOTIFICATION_SLACK_HOOK_URL=https://hooks.slack.com/... \
  containrrr/watchtower
```

## 동작 원리

Watchtower는 다음과 같은 과정으로 작동합니다:

1. **주기적 모니터링**: 설정된 간격으로 레지스트리 확인
2. **이미지 비교**: 현재 실행 중인 이미지와 최신 이미지 비교
3. **업데이트 감지**: 새 버전 발견 시 다운로드
4. **컨테이너 재배포**: 기존 컨테이너 중지 → 새 이미지로 시작
5. **정리**: `--cleanup` 옵션 활성화 시 이전 이미지 삭제

## 프로덕션 환경에서의 고려사항

### 언제 사용하면 좋은가?

- **개발/스테이징**: 안정적이고 예측 가능한 환경
- **지속적 배포가 필요한 서비스**: 마이크로서비스, 수백 개의 컨테이너 관리
- **보안 업데이트가 중요한 경우**: 기본 OS 및 런타임 패치

### 주의사항

| 상황 | 대응 방법 |
|------|---------|
| 예상치 못한 변경 | 라벨을 통해 선택적 업데이트 |
| 상태 저장 컨테이너 | 업데이트 전 데이터 백업 확인 |
| 롤백 필요 | 이전 이미지 태그 유지 |
| 서비스 다운타임 최소화 | 스케줄 시간을 비정규 시간으로 설정 |

### 롤백 전략

Watchtower는 이전 이미지를 자동으로 삭제할 수 있으므로, 롤백이 필요한 경우를 대비해야 합니다:

```bash
# 이미지 히스토리 확인
docker history <image_name>

# 이전 버전으로 롤백
docker run \
  -d \
  --name=<container> \
  <image>:<old_tag>
```

## 모니터링 및 로깅

Watchtower의 동작을 모니터링하려면:

```bash
# 로그 확인
docker logs watchtower

# 실시간 로그 추적
docker logs -f watchtower

# 업데이트 히스토리 확인
docker inspect watchtower
```

## 결론

Watchtower는 Docker 컨테이너 관리를 대폭 자동화하는 강력한 도구입니다. 적절한 설정과 모니터링을 통해 보안과 안정성을 모두 확보하면서, 운영 효율을 크게 높일 수 있습니다.

특히 마이크로서비스 아키텍처나 대량의 컨테이너를 관리하는 환경에서는 필수적인 도구라고 할 수 있습니다.
