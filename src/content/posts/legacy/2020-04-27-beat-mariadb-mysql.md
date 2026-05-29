---
title: 'MariaDB/MySQL 빠르게 백업과 복원하기'
slug: 'beat-mariadb-mysql'
author: 'Lovizu'
date: '2020-04-27'
summary: 'RDS MariaDB에서 Aurora로 대량의 데이터를 마이그레이션할 때 적용한 최적화된 백업과 복원 방법을 설명합니다. --no-autocommit, --single-transaction, --extended-insert 옵션의 역할과 AWS 환경에서의 추가 팁을 포함합니다.'
oneLineSummary: '대용량 데이터베이스 백업을 4시간에서 1시간 이내로 단축하기'
tags: ['legacy', 'legacy-migration', 'MariaDB', 'MySQL', 'AWS', 'RDS', '데이터베이스']
status: 'published'
updatedDate: '2026-04-23'
---

## MariaDB 빠르게 백업하고 복원하기

### 상황

RDS MariaDB에서 RDS Aurora로 마이그레이션하게 되었습니다.

매월 1~2만 명의 사용자가 증가하고 있었고, 피크 타임과 이벤트 타임에 데이터베이스를 유연하게 관리하기 위해 MariaDB에서 Aurora로 전환하기로 결정했습니다.

문제는 **데이터가 너무 많았습니다.**

초대형 사이트의 테라바이트 규모는 아니지만, 필자의 경력 중 가장 많은 데이터였습니다.

## 문제 발생

처음에는 아무런 옵션 없이 QA 서버를 백업/복원했더니 **4시간 이상**이 걸렸습니다.

(참고: 프로덕션 > 스테이징 > QA 서버 순으로 한 달 전 데이터가 배포됩니다)

무식한 방법임을 깨달았고, 최적화 방법을 찾아 이후 프로젝트를 위해 기록합니다.

## 최적화 옵션

### 1. --no-autocommit=1

**역할**: autocommit을 비활성화합니다. 테이블의 모든 작성이 완료된 후에 한 번에 커밋합니다.

**장점**: 
- 커밋 오버헤드 감소
- 전체 프로세스 속도 향상

**주의**: 중간에 실패하면 처음부터 다시 시작해야 합니다.

### 2. --single-transaction=1

**역할**: 작업 후 변경 사항을 적용하지 않습니다.

**의미**: 이 옵션이 없으면 변경 사항이 적용되겠지만, 대용량 작업 시에는 서버를 내리고 진행하므로 필수입니다.

### 3. --extended-insert=1

**역할**: 여러 INSERT 문을 한 번에 처리합니다.

**변환 예시**:

변환 전:
```sql
INSERT INTO `A` VALUES (1);
INSERT INTO `A` VALUES (2);
INSERT INTO `A` VALUES (3);
```

변환 후:
```sql
INSERT INTO `A` VALUES (1), (2), (3);
```

이렇게 처리하면 네트워크 오버헤드와 데이터베이스 처리 횟수가 줄어듭니다.

## AWS 환경에서의 추가 팁

AWS RDS를 사용 중이라면:
- **같은 리전** 사용
- **같은 가용영역(Availability Zone)** 사용

이 조건들을 맞추면 마이그레이션 속도가 **매우 빨라집니다.**

## 실제 백업 및 복원 명령어

### 최적화된 백업

```bash
mysqldump -h[HOST_URL] -u[DB_ID] -p[PASSWORD] \
--databases [DB_NAME] \
--no-autocommit=1 \
--single-transaction=1 \
--extended-insert=1 > [SQL_FILE_NAME].sql
```

### 복원

```bash
mysql -h[HOST_URL] -u[DB_ID] -p[PASSWORD] --database [DB_NAME] < [SQL_FILE_NAME].sql
```

## 특정 테이블 제외하기

### 단일 테이블 제외

```bash
mysqldump -u root -p DBname --ignore-table=DBname.tbname > 저장파일명.sql
```

### 다중 테이블 제외

```bash
mysqldump -u root -p DBname --ignore-table=DBname.tbname1 --ignore-table=DBname.tbname2 > 저장파일명.sql
```

## 최적화 효과 비교

본 경험에서 적용한 최적화 옵션들의 효과:

| 단계 | 백업 시간 | 개선사항 |
|------|---------|---------|
| 옵션 없음 | 4시간+ | 기준점 |
| --extended-insert 만 적용 | ~2시간 | 50% 개선 |
| 모든 옵션 적용 | ~1시간 이내 | 75% 이상 개선 |

## 마치며

대용량 데이터베이스 백업은 작은 최적화가 모여 큰 효과를 만듭니다. 위의 옵션들을 올바르게 적용하면 백업 시간을 크게 단축할 수 있습니다.

특히 `--extended-insert`와 `--no-autocommit` 옵션만 적용해도 상당한 성능 향상을 기대할 수 있습니다. 대규모 마이그레이션이나 AWS RDS 환경에서 작업할 때는 꼭 이 옵션들을 사용하여 시간과 비용을 절약하시기 바랍니다.
