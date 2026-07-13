---
title: 'AWS로 프론트엔드 배포하기 (S3 + CloudFront + Certificate + Route53)'
slug: 'frontend-aws-feat-s3-cloudefront-certificate-route53'
author: 'Lovizu'
date: '2022-03-04'
status: "draft"
summary: 'AWS를 이용해 정적 프론트엔드를 프로덕션 환경에 배포하는 완전한 가이드. S3 버킷 생성부터 CloudFront CDN 설정, SSL 인증서 발급, Route53 도메인 연결까지 모든 단계를 설명. CodePipeline을 통한 자동 배포 및 캐시 무효화 전략도 포함.'
oneLineSummary: 'AWS 프론트엔드 배포: S3부터 커스텀 도메인까지 완전 자동화'
tags: ['legacy', 'legacy-migration', 'AWS', 'S3', 'CloudFront', 'Route53', '프론트엔드', '배포']
updatedDate: '2026-04-23'
---

## AWS로 프론트엔드 배포하기

AWS를 이용해 정적 웹사이트를 안전하고 빠르게 배포하는 방법을 다룬다.

### 전체 아키텍처

```
도메인(Route53) → CloudFront(CDN) → S3(정적 콘텐츠)
```

### 1단계: S3 버킷 생성

#### 퍼블릭 액세스 설정

- **일반 퍼블릭**: CloudFront만 허용하도록 제한
- **직접 접근 차단**: S3 URL을 통한 직접 접근 불가능

#### 버킷 정책

CloudFront의 Origin Access Identity(OAI)를 통해서만 접근 허용한다.

### 2단계: CodePipeline 설정

자동 배포를 위한 파이프라인을 구성한다.

#### buildspec.yaml 예시

```yaml
version: 0.2

env:
  variables:
    NODE_ENV: "production"

phases:
  install:
    commands:
      - yarn add --dev typescript @types/react
      - yarn install
  build:
    commands:
      - echo Build started on `date`
      - echo Compiling the Node.js code
      - yarn build

artifacts:
  base-directory: "dist"
  files:
    - "**/*"
```

### 3단계: CloudFront 연결

#### 원본 설정

- **원본 도메인**: 생성한 S3 버킷 선택
- **자동완성 또는 수동입력**: 유니크한 이름 필요
- **S3 버킷 액세스**: OAI 사용 (기존 또는 신규 생성)

#### 설정

- **가격 분류**: 서비스 지역만 사용 (비용 최적화)
- **SSL 인증서**: 새로 생성 필수
  - **주의**: 반드시 **버지니아 북부**(N. Virginia) 리전에서 생성
  - (2022.03.04 기준)

### 4단계: SSL 인증서 생성

AWS Certificate Manager에서:

1. "인증서 요청" 선택
2. 도메인명 입력 (예: example.com, *.example.com)
3. DNS 검증 선택
4. CNAME 레코드 자동 생성
5. 검증 완료 대기

### 5단계: Route53 설정

#### 레코드 생성

- **레코드 유형**: A (IPv4)
- **값**: 별칭 선택
  - CloudFront 도메인 이름 검색 및 선택
- **별칭 대상**: 위에서 생성한 CloudFront 배포

### 6단계: S3에서 CloudFront 허용

#### CloudFront 설정 확인

생성한 CloudFront 배포 → 원본 항목 → Origin Access Identity 값 확인

```
origin-access-identity/cloudfront/[ID값]
```

#### S3 버킷 정책 설정

```json
{
    "Version": "2012-10-17",
    "Statement": [
        {
            "Sid": "CloudFrontAccess",
            "Effect": "Allow",
            "Principal": {
                "AWS": "arn:aws:iam::cloudfront:user/CloudFront Origin Access Identity [ID값]"
            },
            "Action": "s3:GetObject",
            "Resource": "[S3-버킷-ARN]/*"
        }
    ]
}
```

### 7단계: 오류 페이지 구성

SPA(Single Page Application)를 배포할 때:

- **HTTP 403 오류**: `/` (상태 코드 200)
- **HTTP 404 오류**: `/index.html` (상태 코드 200)

이렇게 설정하면 모든 라우트가 index.html로 리다이렉트된다.

### 문제 해결

#### 403 Forbidden 오류

- IAM 권한 확인
- S3 OAI ID 값 다시 확인
- CloudFront 배포 업데이트 필요

#### 404 오류

- 빌드 결과물 확인 (dist 폴더)
- S3에 파일이 제대로 업로드되었는지 확인
- CloudFront 캐시 무효화

### 캐시 무효화

배포 후 변경사항이 즉시 반영되지 않으면:

1. CloudFront 배포 선택
2. "무효화" 탭
3. 경로 입력: `/*`
4. 무효화 요청

### 성능 최적화

- **압축 활성화**: 텍스트 파일 자동 압축
- **캐시 정책**: CSS, JS는 길게, HTML은 짧게
- **버전 관리**: 파일명에 해시값 포함 (자동 캐시 무효화)

### 비용 최적화

- S3: 저장소 비용 매우 저렴
- CloudFront: 데이터 전송량 기반 청구
- Route53: 호스팅 요금 고정

### 배포 완료 체크리스트

- [ ] S3 버킷 생성 및 퍼블릭 액세스 제한
- [ ] CloudFront 배포 생성
- [ ] SSL 인증서 생성 (버지니아 북부)
- [ ] Route53 레코드 생성
- [ ] S3 버킷 정책에 CloudFront OAI 추가
- [ ] 오류 페이지 설정 (SPA인 경우)
- [ ] 도메인에서 정상 접근 확인

이 아키텍처로 안전하고 빠른 프론트엔드 서빙이 가능하다.
