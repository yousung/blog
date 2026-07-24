---
title: 'AWS Lambda에 C 라이브러리 의존성 포함시키기'
slug: 'lambda-c-library-dependencies'
author: "감성개발자"
date: '2023-06-06'
summary: 'AWS Lambda에서 외부 도구(jpegoptim 등)가 필요할 때 C 라이브러리 의존성을 처리하는 실무 가이드. Docker를 이용한 로컬 테스트, ldd 명령으로 동적 라이브러리 파악, exodus로 자동화된 의존성 수집·패키징까지 단계별로 설명합니다. 맥이나 윈도우에서 리눅스 기반 Lambda 환경 정확히 재현하는 방법.'
oneLineSummary: 'Lambda의 C 라이브러리 의존성을 exodus로 자동 수집하기'
tags: ['legacy', 'legacy-migration', 'AWS', 'Lambda', 'Docker', 'C라이브러리', '배포']
status: "draft"
updatedDate: '2026-04-23'
---

## 도입

AWS Lambda 함수를 개발할 때 흔히 마주치는 문제가 있습니다. 로컬 컴퓨터(맥이나 윈도우)에서는 정상 동작하지만, Linux 기반의 Lambda 환경에서는 실패하는 경우입니다. 특히 `jpegoptim`, `imagemagick` 같은 외부 도구를 사용할 때 더 심합니다.

원인은 간단합니다. 이런 도구들은 C 언어로 컴파일된 바이너리로, 각각 많은 동적 라이브러리(.so 파일) 의존성을 가집니다. 도구 자체만 Lambda에 포함시키면 안 되고, 그 도구가 필요로 하는 모든 라이브러리도 함께 포함시켜야 합니다.

## Lambda 함수 로컬 테스트하기

배포 전에 로컬 환경에서 정확하게 테스트해야 합니다. Lambda는 Linux 기반이므로 Docker를 이용해서 재현합니다.

### Lambda 환경 Docker로 시뮬레이션

```bash
# Node.js 18 Lambda 환경 실행
docker run -it -p 8080:8080 -v $PWD:/var/task public.ecr.aws/lambda/nodejs:18 app.handler

# 로컬에서 테스트
curl localhost:8080/2015-03-31/functions/function/invocations \
  -d '{"payload": true}'
```

Python이라면 `public.ecr.aws/lambda/python:3.11` 이런 식으로 바꾸면 됩니다.

## 동적 라이브러리 의존성 파악하기

예를 들어 이미지 최적화를 위해 `jpegoptim`이 필요하다고 하겠습니다. 먼저 필요한 모든 라이브러리를 파악해야 합니다.

### Ubuntu Docker에서 확인

Lambda 환경과 동일한 Linux 환경을 준비합니다.

```bash
# Ubuntu 최신 버전 실행
docker run -it ubuntu:latest /bin/bash

# 컨테이너 내부에서 실행
apt update
apt install jpegoptim

# jpegoptim이 의존하는 모든 라이브러리 확인
ldd $(which jpegoptim)
```

출력 예시:
```
linux-vdso.so.1 (0x00007fffbffdc000)
libjpeg.so.8 => /lib/x86_64-linux-gnu/libjpeg.so.8 (0x00007f1234567000)
libc.so.6 => /lib/x86_64-linux-gnu/libc.so.6 (0x00007f1234389000)
... (더 많은 라이브러리들)
```

### 의존성 처리 방식

여기서 두 가지 선택지가 있습니다:

| 방식 | 장점 | 단점 |
|------|------|------|
| **동적 라이브러리 함께 패킹** | 간단하고 안정적 | 파일이 많을 수 있음 |
| **정적 링크로 재컴파일** | 파일 수 최소화 | 복잡, 빌드 실패 가능 |

동적 라이브러리 방식이 더 안전하고 실무적입니다. 문제는 때로 수백 개의 라이브러리를 수동으로 처리해야 한다는 것입니다.

## Exodus로 자동화하기

`exodus`는 바이너리와 그 의존성을 자동으로 모아주는 Python 도구입니다.

### Exodus 설치 및 실행

Ubuntu Docker 내부에서:

```bash
# Python과 pip 설치
apt install -y python3 python3-pip

# exodus 설치
pip3 install --user exodus-bundler

# PATH에 추가
export PATH="${PATH}:${HOME}/.local/bin"

# jpegoptim의 모든 의존성을 자동 수집 및 압축
exodus -t -o jpegoptim.tar.gz $(which jpegoptim)
```

이 명령은 `jpegoptim.tar.gz` 파일을 생성하는데, 여기에 `jpegoptim` 바이너리와 모든 필요한 라이브러리가 포함되어 있습니다.

### 압축 파일을 로컬로 복사

Docker 컨테이너에서 생성된 파일을 호스트 머신으로 복사합니다:

```bash
# 다른 터미널에서 컨테이너 ID 확인
docker ps

# 파일 복사
docker cp <CONTAINER_ID>:/root/jpegoptim.tar.gz .

# 압축 해제
tar xzf jpegoptim.tar.gz
```

압축을 풀면 `exodus` 폴더가 생성되고, 그 안의 `bin` 폴더에 실행 가능한 바이너리들이 있습니다.

```
exodus/
├── bin/
│   └── jpegoptim    # 실행 가능한 바이너리
└── lib/
    ├── libjpeg.so.8
    ├── libc.so.6
    └── ... (모든 라이브러리들)
```

## Lambda 패키징

이제 Lambda 배포 패키지를 구성합니다:

```
lambda-package/
├── app.py           # Lambda 함수 코드
├── requirements.txt  # Python 의존성
└── bin/
    └── jpegoptim    # exodus가 수집한 바이너리와 라이브러리들
```

Python 코드에서 호출할 때:

```python
import subprocess
import os

def lambda_handler(event, context):
    # 현재 디렉토리의 bin 폴더에서 jpegoptim 찾기
    jpegoptim_path = os.path.join(os.getcwd(), 'bin', 'jpegoptim')
    
    # 라이브러리 경로 설정
    lib_path = os.path.join(os.getcwd(), 'lib')
    
    env = os.environ.copy()
    env['LD_LIBRARY_PATH'] = lib_path
    
    # jpegoptim 실행
    result = subprocess.run(
        [jpegoptim_path, '--version'],
        env=env,
        capture_output=True,
        text=True
    )
    
    return {
        'statusCode': 200,
        'body': result.stdout
    }
```

## 주의사항

- **아키텍처 일치**: exodus는 실행하는 OS와 아키텍처에 맞춰 의존성을 수집합니다. Lambda는 항상 Linux/x86_64이므로 반드시 Linux Docker에서 실행하세요.
- **버전 호환성**: 호스트 Ubuntu 버전과 Lambda 런타임의 glibc 버전이 맞지 않으면 실패할 수 있습니다. 가능하면 `ubuntu:20.04` 같은 안정적인 버전을 사용하세요.
- **용량 확인**: 패키징 후 전체 크기가 Lambda 제한(250MB)을 초과하지 않는지 확인하세요.

## 마치며

Lambda에서 외부 도구를 사용하려면 단순히 바이너리만 포함하면 안 됩니다. C 라이브러리 의존성을 정확히 파악하고 함께 배포해야 합니다. Docker로 로컬 테스트하고 exodus로 의존성을 자동 수집하면, "로컬에서는 되는데 Lambda에서는 안 되는" 좌절스러운 상황을 피할 수 있습니다.
