---
title: 'Docker Compose로 PHP 개발 환경 완벽하게 구축하기'
slug: 'docker-php'
author: "감성개발자"
date: '2018-09-20'
summary: 'Docker와 Docker Compose를 사용하여 PHP 개발 환경을 빠르게 구축합니다. NGINX, PHP-FPM, MySQL, Redis, Memcached를 yml 파일 하나로 관리하는 방법. 팀 협업 시 동일한 개발 환경을 공유하고 프로덕션과의 차이를 제거하는 실무 가이드.'
oneLineSummary: 'Docker Compose로 PHP 스택 5분 안에 구성하기'
tags: ['legacy', 'legacy-migration', 'Docker', 'PHP', 'DevOps', 'NGINX']
status: "draft"
updatedDate: '2026-04-23'
---

## Docker로 PHP 개발 환경을 빠르게 준비하기

전통적인 방식으로는 로컬에 WAMP나 XAMPP를 설치하고, 프로젝트마다 PHP, MySQL, Redis 등의 버전을 맞춰야 하는 번거로움이 있습니다. Docker를 사용하면 이 모든 과정을 yml 파일 하나로 자동화할 수 있습니다. 팀의 모든 개발자가 정확히 동일한 환경에서 작업할 수 있고, 프로덕션 환경과의 차이도 제거할 수 있습니다.

## 왜 Docker를 써야 할까요

기존 방식의 문제점을 먼저 살펴보겠습니다.

**로컬 설치 방식의 문제들:**
- PHP, MySQL, Redis를 각각 설치하고 설정하는 과정이 복잡합니다
- 팀원마다 다른 설정으로 인한 '내 컴퓨터에서는 되는데' 현상 발생
- 프로젝트마다 다른 버전 요구사항이 충돌할 수 있습니다
- 개발 환경과 프로덕션 환경이 달라서 배포 후 예기치 않은 문제 발생

**Docker 기반 솔루션:**
- 모든 의존성을 컨테이너에 격리하여 버전 충돌 제거
- yml 파일만 공유하면 팀원 모두 동일한 환경 구성 가능
- 개발 환경 = 프로덕션 환경, 배포 후 놀라운 일이 없습니다
- 프로젝트 시작을 위한 세팅 시간을 몇 시간에서 5분으로 단축

## 구축할 환경 구성 요소

이 가이드에서 설정할 스택은 다음과 같습니다:

- **PHP 7.2 이상**: PHP-FPM 실행 엔진과 필수 확장 모듈
- **NGINX**: 웹 서버이자 PHP-FPM의 리버스 프록시
- **MySQL 5.7**: 데이터베이스 (기본 계정: homestead/secret)
- **Redis**: 캐싱 및 세션 스토리지
- **Memcached**: 추가 캐싱 레이어

이들을 하나의 docker-compose.yml로 통합 관리합니다.

## 프로젝트 디렉토리 구조

여러 프로젝트를 한 Docker Compose로 관리하는 구조를 추천합니다:

```
root-folder/
├── docker-compose.yml
├── nginx.conf
├── project1/
│   └── (Laravel 프로젝트)
├── project2/
│   └── (다른 PHP 프로젝트)
└── project3/
    └── (또 다른 프로젝트)
```

NGINX의 `/var/www` 디렉토리를 호스트의 root-folder에 바인딩하면, 모든 프로젝트에 접근할 수 있습니다.

## Docker Compose 설정 파일 작성

프로젝트 루트에 `docker-compose.yml` 파일을 생성합니다:

```yaml
version: '3.8'

services:
  php:
    image: php:7.2-fpm
    container_name: php-fpm
    volumes:
      - ./:/var/www
    networks:
      - php-network
    environment:
      - MYSQL_HOST=mysql
      - MYSQL_DATABASE=homestead
      - MYSQL_USER=homestead
      - MYSQL_PASSWORD=secret

  nginx:
    image: nginx:latest
    container_name: nginx
    ports:
      - "80:80"
      - "443:443"
    volumes:
      - ./:/var/www
      - ./nginx.conf:/etc/nginx/nginx.conf:ro
    depends_on:
      - php
    networks:
      - php-network

  mysql:
    image: mysql:5.7
    container_name: mysql
    environment:
      MYSQL_ROOT_PASSWORD: secret
      MYSQL_DATABASE: homestead
      MYSQL_USER: homestead
      MYSQL_PASSWORD: secret
    ports:
      - "3306:3306"
    volumes:
      - mysql-data:/var/lib/mysql
    networks:
      - php-network

  redis:
    image: redis:latest
    container_name: redis
    ports:
      - "6379:6379"
    networks:
      - php-network

  memcached:
    image: memcached:latest
    container_name: memcached
    ports:
      - "11211:11211"
    networks:
      - php-network

volumes:
  mysql-data:

networks:
  php-network:
    driver: bridge
```

## 실제 사용 명령어

컨테이너를 시작합니다:

```bash
docker-compose up -d
```

실행 상태 확인:

```bash
docker-compose ps
```

로그 확인 (실시간):

```bash
docker-compose logs -f
```

컨테이너 종료:

```bash
docker-compose down
```

## Docker Compose 사용의 장점 정리

**환경 일관성**: 팀의 모든 개발자가 정확히 같은 버전 사용
**빠른 구성**: 단 한 번의 명령으로 완전한 스택 구성
**격리된 환경**: 프로젝트 간 의존성 충돌이 없음
**쉬운 확장**: 새로운 서비스 추가가 간단함
**프로덕션 호환**: 개발과 배포 환경이 동일함

## 주의할 사항

Windows 사용자는 WSL2를 사용하면 성능이 크게 개선됩니다. MySQL 데이터는 반드시 volume으로 관리하여 컨테이너 삭제 후에도 데이터를 보존하세요. 프로덕션 배포 전에는 추가 보안 설정(방화벽, SSL 인증서, 강력한 비밀번호)을 반드시 적용하세요.

## 마치며

Docker Compose는 PHP 개발 환경 설정의 복잡도를 획기적으로 줄여줍니다. 이제 새로운 팀원이 프로젝트에 참여할 때 마주했던 '환경 차이'라는 골치 아픈 문제를 완전히 제거할 수 있습니다. 이 yml 파일을 버전 관리 시스템에 저장해두면, 누구나 같은 명령어 하나로 동일한 개발 환경을 구성할 수 있습니다.
