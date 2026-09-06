---
title: "도커 디스크 용량 100% 장애 해결기: overlay2 정리부터 로그 로테이션까지"
slug: "docker-disk-full-overlay2-cleanup"
author: "감성개발자"
date: "2026-09-06"
summary: "도커를 쓰는 서버에서 디스크가 100% 차는 장애를 겪고 정리한 실전 가이드. overlay2가 커지는 이유, docker system prune 옵션별 주의사항, json-file 로그 무제한 문제와 daemon.json 로그 로테이션 설정, 크론 자동 정리와 이미지 경량화까지 순서대로 담았습니다."
oneLineSummary: "도커는 기본값 그대로 두면 디스크를 다 쓸 때까지 자란다"
tags: ["Docker", "DevOps", "인프라", "troubleshooting", "시스템-관리"]
status: "draft"
tistoryUrl: "https://lovizu.tistory.com/entry/docker-disk-full-overlay2-cleanup"
---

금요일 밤 11시에 배포가 실패했다. CI는 초록불인데 서버에서 컨테이너가 안 뜬다. 에러 메시지는 딱 한 줄. **no space left on device**. 디스크가 꽉 찬 것이다. 코드 몇 줄 바꿨다고 디스크가 찰 리는 없는데, 확인해 보니 루트 파티션 사용률이 100%였다. 범인은 도커였다.

이런 일을 한 번이라도 겪어본 분이라면 알 것이다. 이게 한 번으로 안 끝난다는 걸. 임시로 지우고 넘어가면 몇 달 뒤에 똑같은 알람이 또 울린다. 그래서 이번에 원인부터 재발 방지까지 한 번에 정리했다. 같은 장애를 겪고 있는 분들에게 시간을 아껴드리고 싶어서 쓰는 글이다.

## 증상: 디스크 100%, 로그인도 버벅거린다

증상은 단순했다. 배포 실패, 컨테이너 재시작 실패, 심하면 SSH 접속까지 버벅거린다. 디스크가 100%가 되면 도커만 문제가 아니라 시스템 전체가 이상해진다. 리눅스는 임시 파일 하나 못 쓰는 상태가 되면 생각보다 많은 것이 멈춘다.

먼저 뭐가 먹고 있는지부터 확인한다.

```bash
$ df -h
Filesystem      Size  Used Avail Use% Mounted on
/dev/xvda1       30G   29G    0G 100% /
```

루트가 꽉 찼으면 다음은 어디가 큰지 추적한다. 도커를 쓰는 서버라면 십중팔구 여기다.

```bash
$ sudo du -sh /var/lib/docker/*
1.2G    /var/lib/docker/containers
19G     /var/lib/docker/overlay2
2.7G    /var/lib/docker/tmp
850M    /var/lib/docker/volumes
```

내 경우 overlay2가 19기가였다. 30기가짜리 서버에서 도커 혼자 20기가 넘게 쓰고 있었던 셈이다. 도커 입장에서 보면 어디에 얼마나 쓰는지는 이 명령이 더 보기 편하다.

```bash
$ docker system df
TYPE            TOTAL     ACTIVE    SIZE      RECLAIMABLE
Images          24        3         14.2GB    12.1GB (85%)
Containers      5         3         1.1GB     0.9GB
Local Volumes   8         2         850MB     620MB
Build Cache     113       0         3.4GB     3.4GB
```

RECLAIMABLE 컬럼이 핵심이다. 회수 가능한 용량, 즉 지금 아무도 안 쓰는데 자리만 차지하는 데이터다. 이미지 85%가 회수 가능이라는 건 배포할 때마다 새 이미지를 받아놓고 옛날 이미지를 한 번도 안 지웠다는 뜻이다. 뜨끔했다.

## overlay2는 왜 이렇게 커지는가

overlay2는 도커의 기본 스토리지 드라이버다. 이미지 레이어, 컨테이너의 쓰기 레이어가 전부 여기 쌓인다. 도커 이미지는 레이어 구조라서, 배포를 반복하면 예전 이미지의 레이어들이 계속 남는다. 태그가 latest 하나뿐이어도 실제 레이어는 배포 횟수만큼 누적된다.

여기에 세 가지가 더 얹힌다.

첫째, **빌드 캐시**. 서버에서 직접 docker build를 하는 구조라면 빌드 캐시가 조용히 몇 기가씩 쌓인다. 위의 예에서도 3.4기가였다.

둘째, **중지된 컨테이너**. docker run을 --rm 없이 돌리고 지우지 않으면 죽은 컨테이너의 쓰기 레이어가 그대로 남는다.

셋째, 그리고 제일 악질인 **컨테이너 로그**. 이건 뒤에서 따로 다룬다. 진짜 범인은 이 녀석이었다.

## 응급 처치: prune 한 방, 단 옵션은 알고 쓰자

일단 서비스를 살려야 하니 미사용 리소스부터 정리한다.

```bash
$ docker system prune -a -f
```

이 한 줄로 19기가짜리 overlay2가 4기가대로 내려왔다. 다만 옵션 의미는 정확히 알고 써야 한다. 나도 처음엔 아무 생각 없이 복붙했다가 식겁한 적이 있다.

- **docker system prune**: 중지된 컨테이너, 미사용 네트워크, dangling 이미지, 빌드 캐시 삭제
- **-a**: 여기에 더해 실행 중인 컨테이너가 참조하지 않는 모든 이미지 삭제
- **--volumes**: 미사용 익명 볼륨까지 삭제

주의할 점 두 가지. 하나, **컨테이너가 내려가 있는 상태에서 -a를 돌리면 그 컨테이너의 이미지도 미사용으로 간주되어 지워진다.** 다음 기동 때 이미지를 처음부터 다시 받아야 한다. 프라이빗 레지스트리가 느리거나 이미지가 크면 복구 시간이 확 늘어난다. 둘, **--volumes는 신중하게.** DB 데이터를 익명 볼륨에 넣어둔 낡은 구성이라면 데이터가 날아갈 수 있다. 볼륨 정리는 docker volume ls로 목록을 눈으로 확인한 뒤에 하는 게 맞다.

운영 중인 서버라면 필터를 걸어서 최근 것은 남기는 쪽이 안전하다.

```bash
# 48시간 넘게 미사용인 것만 정리
$ docker system prune -f --filter "until=48h"
```

## 진짜 범인: 무한히 자라는 컨테이너 로그

prune으로 급한 불은 껐는데, containers 디렉터리에 1기가 넘는 게 남아 있었다. 파고 들어가 보니 로그 파일 하나가 900메가였다.

```bash
$ sudo du -sh /var/lib/docker/containers/*/*-json.log | sort -rh | head -3
912M    /var/lib/docker/containers/8ac2.../8ac2...-json.log
210M    /var/lib/docker/containers/f31b.../f31b...-json.log
```

도커의 기본 로그 드라이버인 json-file은 **기본값이 무제한이다.** max-size 기본값이 -1, 로테이션 없음. 컨테이너가 stdout으로 찍는 모든 로그가 파일 하나에 영원히 쌓인다. 애플리케이션이 디버그 로그라도 켜져 있으면 성장 속도가 무섭다. 도커를 몇 년 썼는데 이 기본값을 이번에 처음 제대로 봤다. 공식 문서에 떡하니 적혀 있는데도 장애를 겪고 나서야 읽게 되는 게 사람이다.

당장 급하면 로그 파일을 비워서 공간을 회수할 수 있다. 파일을 rm으로 지우면 안 되고(프로세스가 잡고 있어서 공간이 반환되지 않는다) truncate로 비운다.

```bash
$ sudo sh -c 'truncate -s 0 /var/lib/docker/containers/*/*-json.log'
```

물론 이건 응급 처치다. 로그는 다시 자란다.

## 재발 방지 1: daemon.json에 로그 로테이션 걸기

근본 대책은 데몬 레벨에서 로그 상한을 거는 것이다. /etc/docker/daemon.json에 다음을 넣는다. 파일이 없으면 만들면 된다.

```json
{
  "log-driver": "json-file",
  "log-opts": {
    "max-size": "10m",
    "max-file": "3"
  }
}
```

컨테이너당 로그를 10메가짜리 파일 3개, 최대 30메가로 제한하는 설정이다. 주의할 점은 값들을 **전부 문자열로** 써야 한다는 것. max-file을 숫자 3으로 쓰면 데몬이 안 뜬다. 그리고 max-file은 max-size가 같이 설정되어 있어야만 동작한다.

설정 후 도커를 재시작하는데, 여기 함정이 하나 더 있다. **이미 떠 있는 컨테이너에는 적용되지 않는다.** 새로 만들어지는 컨테이너부터 적용된다. 그래서 컴포즈를 쓴다면 재생성까지 해줘야 한다.

```bash
$ sudo systemctl restart docker
$ docker compose up -d --force-recreate

# 적용 확인
$ docker inspect --format '{{.HostConfig.LogConfig}}' my-app
{json-file map[max-file:3 max-size:10m]}
```

서비스별로 다르게 주고 싶으면 컴포즈 파일에서 logging 블록으로 오버라이드하면 된다. 로그가 많은 서비스만 따로 조이는 식이다.

```yaml
services:
  api:
    image: my-api
    logging:
      driver: json-file
      options:
        max-size: "20m"
        max-file: "5"
```

참고로 도커 공식 문서는 디스크 효율을 위해 local 드라이버를 권장한다. local은 압축과 로테이션이 기본으로 켜져 있다(파일당 20메가 5개). 다만 내부 바이너리 포맷이라 Filebeat처럼 로그 파일을 직접 읽는 수집기와는 궁합이 안 맞는다. docker logs 명령으로만 로그를 본다면 local로 갈아타는 것도 좋은 선택이다.

## 재발 방지 2: 정리를 크론에 맡긴다

사람이 기억해서 정리하는 방식은 반드시 실패한다. 나는 이번에 크론에 넣어버렸다.

```bash
# crontab -e
# 매일 새벽 4시, 48시간 이상 미사용 리소스 정리
0 4 * * * docker system prune -f --filter "until=48h"

# 일요일 새벽 5시, 일주일 이상 미사용 이미지까지 정리
0 5 * * 0 docker image prune -af --filter "until=168h"
```

빌드를 서버에서 직접 하는 구성이라면 빌드 캐시 상한도 같이 걸어두면 좋다.

```bash
# 빌드 캐시를 2GB 이내로 유지
$ docker buildx prune -f --max-used-space 2GB
```

그리고 디스크 사용률 알람. 거창한 모니터링 없이도 크론에 쉘 스크립트 하나면 된다. 사용률 85% 넘으면 슬랙 웹훅으로 쏘게 해뒀다. 디스크 장애는 예고 없이 오는 게 아니라, 알람을 안 걸어놔서 예고를 못 들은 것뿐이다.

## 재발 방지 3: 애초에 이미지를 작게 만든다

정리를 아무리 잘해도 이미지 하나가 1.5기가면 근본적으로 힘들다. 우리 서비스의 Node 기반 이미지가 딱 그랬다. 손댄 건 두 가지다.

하나는 베이스 이미지를 alpine 계열로 바꾼 것. node:20에서 node:20-alpine으로 바꾸는 것만으로 베이스가 1기가 가까이에서 100메가대로 내려간다. glibc 의존성이 있는 네이티브 모듈은 빌드가 깨질 수 있으니 CI에서 한 번 돌려보고 넘어가야 하지만, 웬만한 웹 서비스는 문제없이 넘어간다.

다른 하나는 멀티 스테이지 빌드. 빌드 도구와 devDependencies는 빌드 스테이지에만 두고, 최종 스테이지에는 산출물과 프로덕션 의존성만 복사한다.

```dockerfile
FROM node:20-alpine AS build
WORKDIR /app
COPY package*.json ./
RUN npm ci
COPY . .
RUN npm run build

FROM node:20-alpine
WORKDIR /app
COPY --from=build /app/dist ./dist
COPY --from=build /app/package*.json ./
RUN npm ci --omit=dev
CMD ["node", "dist/main.js"]
```

이 두 가지로 이미지가 1.5기가에서 400메가 밑으로 내려왔다. 이미지가 작아지면 overlay2에 쌓이는 레이어도, 배포 때 받는 시간도, 레지스트리 비용도 같이 줄어든다. 디스크 정리를 반복하는 것보다 이쪽이 훨씬 남는 장사다.

## 정리: 순서대로 하면 되는 체크리스트

같은 상황을 만난 분을 위해 순서대로 요약한다.

1. **현황 파악**: df -h → sudo du -sh /var/lib/docker/* → docker system df
2. **응급 처치**: docker system prune -a -f (내려간 컨테이너의 이미지가 지워질 수 있음에 주의, --volumes는 볼륨 목록 확인 후에)
3. **로그 확인**: containers 밑 *-json.log 크기 확인, 크면 truncate로 비우기
4. **재발 방지**: daemon.json에 max-size/max-file 설정 → 도커 재시작 → 컨테이너 재생성(--force-recreate)까지 해야 적용
5. **자동화**: prune 크론 등록, 빌드 캐시 상한, 디스크 사용률 알람

돌아보면 이 장애의 교훈은 도커 명령어가 아니었다. **기본값을 믿지 말자**는 것이었다. json-file 로그 무제한, 이미지 무한 누적, 빌드 캐시 무제한. 도커는 기본 상태로 두면 디스크를 다 쓸 때까지 자란다. 처음 서버 세팅할 때 daemon.json에 로그 설정 네 줄만 넣어뒀다면 금요일 밤을 반납할 일도 없었다. 새 서버를 세팅할 일이 있다면 도커 설치 직후에 로그 로테이션부터 걸어두시길. 미래의 내가 고마워한다.
