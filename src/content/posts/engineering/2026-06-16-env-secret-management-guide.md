---
title: ".env 파일 관리, 깃에 한 번 올리면 비밀번호는 이미 샌 겁니다"
slug: "env-secret-management-guide"
ogImage: "/images/posts/env-secret-management-guide/hero.png"
author: "감성개발자"
date: "2026-06-16"
summary: "API 키와 비밀번호를 담은 .env 파일을 어떻게 다뤄야 하는지 실무 기준으로 풀었다. .gitignore로 막는 법, 이미 깃에 올라간 비밀을 처리하는 순서, 그리고 히스토리에서 지우는 것보다 먼저 해야 할 일까지 담았다."
oneLineSummary: ".env를 깃에 올렸다면 히스토리 삭제보다 키 교체가 먼저다. 처음부터 막는 법과 사고가 난 뒤의 대처 순서를 함께 담았다."
tags: [보안, Git, 개발팁, 12-factor, tip]
status: "published"
---

![환경변수와 시크릿 관리 구조를 표현한 기술 일러스트](/images/posts/env-secret-management-guide/hero.png)

금요일 저녁, 배포 하나만 넘기고 퇴근하려던 참이었습니다. 마음이 급하니 `git add .`, 커밋, push. 주말이 지나고 월요일 아침, 깃허브 저장소를 열어 보니 `.env` 파일이 커밋 목록에 얌전히 올라와 있습니다. 안에는 데이터베이스 비밀번호와 결제 API 키가 그대로 적혀 있고요. 저만 겪은 일이 아닙니다. 에이전시 시절 유지보수로 넘겨받은 프로젝트를 열어 보면 이 흔적이 남아 있는 경우가 유독 많았습니다.

이때 가장 흔한 반응은 "커밋을 지우면 되겠지"인데, 사실 그 순서가 틀렸습니다. 한 번 push된 비밀은 누가 봤는지 알 수 없기 때문에, 지우는 것보다 **그 키를 못 쓰게 만드는 일**이 먼저입니다.

핵심 세 가지만 먼저 짚고 들어가겠습니다.

- `.env`는 코드가 아니라 환경마다 달라지는 설정이라, 처음부터 저장소에 넣지 않는 게 원칙입니다. (출처: [The Twelve-Factor App - Config](https://12factor.net/config))
- 막는 방법은 단순합니다. `.gitignore`에 `.env`를 넣고, 빈 `.env.example`만 올립니다.
- 이미 push했다면 히스토리 삭제보다 **키 교체(rotate)가 먼저**입니다. 깃허브 공식 문서도 같은 순서를 권합니다. (출처: [GitHub Docs - Removing sensitive data](https://docs.github.com/en/authentication/keeping-your-account-and-data-secure/removing-sensitive-data-from-a-repository))

최종 업데이트: 2026-06-16

## 왜 .env는 깃에 올리면 안 될까?

이유를 "보안" 한 단어로 뭉뜽그리면 판단이 흐려집니다. 더 정확한 기준은 코드와 설정을 분리하는 원칙입니다.

12-factor 방법론은 이걸 한 문장으로 정리합니다. "지금 이 저장소를 그대로 오픈소스로 공개해도 어떤 자격 증명도 새지 않는가?"를 통과해야 한다는 겁니다. (출처: [The Twelve-Factor App - Config](https://12factor.net/config)) 데이터베이스 비밀번호, 결제 API 키, 토큰 같은 값은 환경마다 다르고, 코드와 함께 버전 관리될 이유가 없습니다. 같은 코드라도 개발 서버와 운영 서버는 다른 키를 써야 하니까요.

그래서 `.env`는 "코드"가 아니라 "그 서버에만 있는 설정"으로 취급합니다. 저장소에는 코드만 올라가고, 실제 값은 각 환경이 따로 들고 있는 구조가 맞습니다.

"비공개 저장소니까 괜찮지 않나요"라는 반문이 자주 나오는데, 저는 권하지 않습니다. 저장소가 실수로 공개로 전환되거나, 협업자 계정이 털리거나, 나중에 포크·미러링되는 경우를 막을 수 없습니다. 비공개라도 코드와 비밀은 분리하는 습관이 결국 안전합니다.

## 처음부터 막는 법: 30초면 끝납니다

사고를 막는 비용은 거의 들지 않습니다. 새 프로젝트를 시작할 때 이 세 가지만 하면 됩니다.

**1. `.gitignore`에 추가합니다.**

```gitignore
# 환경 변수
.env
.env.local
.env.*.local
```

`.gitignore`는 "깃이 추적하지 않을 파일 목록"입니다. 여기에 적힌 파일은 `git add .`을 해도 스테이징되지 않습니다. (출처: [Git 공식 문서 - gitignore](https://git-scm.com/docs/gitignore))

**2. 대신 올릴 견본을 만듭니다.**

값은 비우고 키 이름만 남긴 `.env.example`을 만들어 올립니다. 같이 일하는 사람이 "어떤 환경 변수가 필요한지"를 알 수 있게 하는 용도입니다. 팀에 새 사람이 합류하면 첫날 가장 먼저 찾게 되는 파일이라, 저는 이걸 최신으로 유지하는 걸 규칙으로 둡니다. 다만 견본이라고 진짜 값을 조금이라도 끼워 넣으면 안 됩니다. 목적은 변수 목록을 알리는 것뿐이니 값은 모두 비우거나 `your-key-here` 같은 자리표시자만 둡니다.

```bash
# .env.example (이건 깃에 올려도 안전합니다)
DATABASE_URL=
STRIPE_SECRET_KEY=
JWT_SECRET=
```

**3. 정말 무시되는지 확인합니다.**

```bash
git status --short
```

여기에 `.env`가 보이면 아직 무시되지 않은 겁니다. 만약 예전에 한 번 커밋된 적이 있으면 `.gitignore`에 적어도 계속 추적되니, 추적만 끊어줍니다.

```bash
git rm --cached .env
```

이 명령은 로컬 파일은 그대로 두고 깃의 추적 목록에서만 빼냅니다.

## 이미 올렸다면? 순서를 헷갈리지 마세요

여기가 사람들이 가장 많이 실수하는 지점입니다. 나도 처음 겪었을 때는 히스토리부터 뒤졌는데, 몇 번 사고를 수습하고 나서야 순서를 바꿨습니다. 비밀이 push된 걸 발견하면 본능적으로 "커밋을 지워서 없던 일로 만들자"고 생각합니다. 그런데 이미 원격 저장소에 올라간 순간, 그 값은 봇과 사람 누구든 봤을 수 있다고 가정해야 합니다. 공개 저장소의 비밀은 자동 스캐너가 몇 분 안에 긁어가는 일이 흔합니다.

그래서 순서는 이렇게 잡습니다.

### 1단계: 키를 폐기하고 새로 발급합니다 (가장 중요)

깃허브 공식 문서도 "민감 정보가 비밀번호·토큰·자격 증명이라면, 첫 단계로 그 비밀을 폐기하거나 교체해야 한다"고 명시합니다. (출처: [GitHub Docs - Removing sensitive data](https://docs.github.com/en/authentication/keeping-your-account-and-data-secure/removing-sensitive-data-from-a-repository))

| 새는 값 | 해야 할 일 |
| --- | --- |
| API 키 / 토큰 | 해당 서비스 콘솔에서 키 재발급, 기존 키 폐기 |
| DB 비밀번호 | 비밀번호 변경, 가능하면 접근 IP 제한 점검 |
| OAuth 클라이언트 시크릿 | 시크릿 재생성 |

키를 새로 발급하는 순간, 유출된 옛날 값은 그냥 못 쓰는 문자열이 됩니다. 사고의 위험 대부분이 여기서 사라집니다.

### 2단계: 추적을 끊고 다시 커밋합니다

```bash
git rm --cached .env
echo ".env" >> .gitignore
git commit -m "chore: stop tracking .env"
```

이렇게 하면 **앞으로의** 커밋에서는 `.env`가 빠집니다. 단, 과거 히스토리에는 여전히 남아 있다는 점은 기억해야 합니다.

### 3단계: 히스토리에서 지우는 건 그다음입니다

키를 이미 교체했다면, 과거 히스토리에서까지 굳이 지워야 하는지 한 번 더 따져보세요. 깃허브도 "키를 교체했다면 교체된 비밀은 더 이상 기능상 위협이 아니니, 히스토리를 다시 쓰는 추가 작업이 정말 필요한지 판단하라"고 권합니다. (출처: [GitHub Docs - Removing sensitive data](https://docs.github.com/en/authentication/keeping-your-account-and-data-secure/removing-sensitive-data-from-a-repository))

그래도 지워야 한다면 깃허브가 안내하는 도구는 `git-filter-repo`입니다.

```bash
# git-filter-repo 설치 후
git filter-repo --path .env --invert-paths
```

이 작업은 커밋 해시를 전부 바꿉니다. 다른 사람과 함께 쓰는 저장소라면 모두가 다시 클론하거나 강제로 맞춰야 하므로, 협업 중이라면 팀과 먼저 합의하고 진행하세요.

## 운영 환경에서는 .env를 어디에 둘까?

로컬에서는 `.env` 파일이 편하지만, 실제 서버에서는 파일 하나에 비밀을 모아두는 것보다 나은 선택지가 있습니다. 정답은 환경에 따라 다르니 기준만 정리합니다. 나는 팀 규모와 서버 수를 먼저 봅니다. 사람 손이 두세 명뿐인데 비밀 관리 서비스를 먼저 붙이면 관리 비용만 늘고, 반대로 서버가 여러 대로 늘었는데 `.env` 파일을 계속 복사해 다니면 교체 한 번에 사고가 납니다.

- **소규모 / 단일 서버**: 서버 환경 변수로 직접 주입하거나, 권한을 좁힌 `.env` 파일을 두는 방식도 현실적입니다.
- **클라우드 / 여러 서버**: AWS Secrets Manager, GCP Secret Manager 같은 비밀 관리 서비스를 쓰면 키 교체와 접근 권한 관리가 분리됩니다.
- **CI/CD**: 빌드 파이프라인에는 비밀을 코드가 아니라 파이프라인 설정의 "시크릿" 항목으로 넣습니다. 로그에 값이 찍히지 않게 마스킹되는지도 확인하세요.

공통 기준은 하나입니다. **누가 그 비밀에 접근할 수 있고, 새면 얼마나 빨리 교체할 수 있는가.** 파일 한 개에 다 몰아넣을수록 이 두 가지를 답하기 어려워집니다.

## 지금 내 상황이라면 어디부터 하면 될까

읽고 끝내지 말고, 지금 저장소가 어느 단계인지 짚어 바로 적용하는 편이 낫습니다. 상황에 따라 시작점만 다릅니다.

**아직 안 새어 나갔고, 새 프로젝트를 여는 중이라면**

1. `.gitignore`에 `.env`, `.env.local`, `.env.*.local`을 넣습니다.
2. 값을 비운 `.env.example`을 만들어 커밋합니다.
3. `git status --short`로 `.env`가 목록에 안 보이는지 확인합니다. 예전에 커밋된 적이 있으면 `git rm --cached .env`로 추적만 끊습니다.

**이미 push해서 비밀이 새어 나갔다면**

1. 먼저 그 키부터 폐기하고 새로 발급합니다. 이게 1순위입니다. 교체하는 순간 유출된 값은 못 쓰는 문자열이 됩니다.
2. `git rm --cached .env` + `.gitignore` 등록 + 커밋으로 앞으로의 추적을 끊습니다.
3. 히스토리 정리는 그다음입니다. 키를 이미 바꿨다면 실질 위험은 사라졌으니, 히스토리에 남은 값이 과거 데이터나 다른 시스템 추적에 쓰일 여지가 있는지를 따져 필요할 때만 `git-filter-repo`까지 갑니다.

**서버가 여러 대로 늘고 있다면**

`.env` 파일 복사를 계속 끌고 가지 말고, "누가 접근하고, 새면 얼마나 빨리 바꿀 수 있는가"를 기준으로 비밀 관리 서비스로 옮길 시점을 잡습니다.

비밀이 한 번 새면 되돌릴 수 없습니다. 그래서 사고가 났을 때 가장 빠른 복구는 "지우기"가 아니라 "그 값을 무의미하게 만들기"라는 점만 기억하면, 대부분의 상황에서 침착하게 대응할 수 있습니다.

## 출처

- [The Twelve-Factor App - III. Config](https://12factor.net/config)
- [GitHub Docs - Removing sensitive data from a repository](https://docs.github.com/en/authentication/keeping-your-account-and-data-secure/removing-sensitive-data-from-a-repository)
- [Git 공식 문서 - gitignore](https://git-scm.com/docs/gitignore)
