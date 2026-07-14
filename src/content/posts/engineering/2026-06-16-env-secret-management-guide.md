---
title: ".env 파일 관리, 깃에 한 번 올리면 비밀번호는 이미 샌 겁니다"
slug: "env-secret-management-guide"
ogImage: "/images/posts/env-secret-management-guide/hero.png"
author: "BLOA Team"
date: "2026-06-16"
summary: "API 키와 비밀번호를 담은 .env 파일을 어떻게 다뤄야 하는지 실무 기준으로 정리했다. .gitignore로 막는 법, 이미 깃에 올라간 비밀을 처리하는 순서, 그리고 히스토리에서 지우는 것보다 먼저 해야 할 일까지 다룬다."
oneLineSummary: ".env를 깃에 올렸다면 히스토리 삭제보다 키 교체가 먼저다. 처음부터 막는 법과 사고 후 대처 순서를 정리했다."
tags: [보안, Git, 개발팁, 12-factor, tip]
status: "published"
---

![환경변수와 시크릿 관리 구조를 표현한 기술 일러스트](/images/posts/env-secret-management-guide/hero.png)

`.env` 파일을 다루다 한 번쯤은 같은 실수를 합니다. 급하게 작업하다가 `git add .`을 치고, 며칠 뒤에야 API 키가 통째로 깃허브에 올라가 있는 걸 발견하는 겁니다. 이때 가장 흔한 반응은 "커밋을 지우면 되겠지"인데, 사실 그 순서가 틀렸습니다. 한 번 push된 비밀은 누가 봤는지 알 수 없기 때문에, 지우는 것보다 **그 키를 못 쓰게 만드는 일**이 먼저입니다.

> 요약 박스
>
> - `.env`는 코드가 아니라 환경마다 달라지는 설정이라, 처음부터 저장소에 넣지 않는 게 원칙입니다. (출처: [The Twelve-Factor App - Config](https://12factor.net/config))
> - 막는 방법은 단순합니다. `.gitignore`에 `.env`를 넣고, 빈 `.env.example`만 올립니다.
> - 이미 push했다면 히스토리 삭제보다 **키 교체(rotate)가 먼저**입니다. 깃허브 공식 문서도 같은 순서를 권합니다. (출처: [GitHub Docs - Removing sensitive data](https://docs.github.com/en/authentication/keeping-your-account-and-data-secure/removing-sensitive-data-from-a-repository))

최종 업데이트: 2026-06-16

## 왜 .env는 깃에 올리면 안 될까?

이유를 "보안" 한 단어로 뭉뜽그리면 판단이 흐려집니다. 더 정확한 기준은 코드와 설정을 분리하는 원칙입니다.

12-factor 방법론은 이걸 한 문장으로 정리합니다. "지금 이 저장소를 그대로 오픈소스로 공개해도 어떤 자격 증명도 새지 않는가?"를 통과해야 한다는 겁니다. (출처: [The Twelve-Factor App - Config](https://12factor.net/config)) 데이터베이스 비밀번호, 결제 API 키, 토큰 같은 값은 환경마다 다르고, 코드와 함께 버전 관리될 이유가 없습니다. 같은 코드라도 개발 서버와 운영 서버는 다른 키를 써야 하니까요.

그래서 `.env`는 "코드"가 아니라 "그 서버에만 있는 설정"으로 취급합니다. 저장소에는 코드만 올라가고, 실제 값은 각 환경이 따로 들고 있는 구조가 맞습니다.

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

값은 비우고 키 이름만 남긴 `.env.example`을 만들어 올립니다. 같이 일하는 사람이 "어떤 환경 변수가 필요한지"를 알 수 있게 하는 용도입니다.

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

여기가 사람들이 가장 많이 실수하는 지점입니다. 비밀이 push된 걸 발견하면 본능적으로 "커밋을 지워서 없던 일로 만들자"고 생각합니다. 그런데 이미 원격 저장소에 올라간 순간, 그 값은 봇과 사람 누구든 봤을 수 있다고 가정해야 합니다. 공개 저장소의 비밀은 자동 스캐너가 몇 분 안에 긁어가는 일이 흔합니다.

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

로컬에서는 `.env` 파일이 편하지만, 실제 서버에서는 파일 하나에 비밀을 모아두는 것보다 나은 선택지가 있습니다. 정답은 환경에 따라 다르니 기준만 정리합니다.

- **소규모 / 단일 서버**: 서버 환경 변수로 직접 주입하거나, 권한을 좁힌 `.env` 파일을 두는 방식도 현실적입니다.
- **클라우드 / 여러 서버**: AWS Secrets Manager, GCP Secret Manager 같은 비밀 관리 서비스를 쓰면 키 교체와 접근 권한 관리가 분리됩니다.
- **CI/CD**: 빌드 파이프라인에는 비밀을 코드가 아니라 파이프라인 설정의 "시크릿" 항목으로 넣습니다. 로그에 값이 찍히지 않게 마스킹되는지도 확인하세요.

공통 기준은 하나입니다. **누가 그 비밀에 접근할 수 있고, 새면 얼마나 빨리 교체할 수 있는가.** 파일 한 개에 다 몰아넣을수록 이 두 가지를 답하기 어려워집니다.

## 자주 묻는 질문

**Q. 비공개(private) 저장소면 `.env`를 올려도 괜찮지 않나요?**

권하지 않습니다. 저장소가 실수로 공개로 전환되거나, 협업자 계정이 털리거나, 나중에 포크·미러링되는 경우를 막을 수 없습니다. 비공개라도 코드와 비밀은 분리하는 습관이 안전합니다.

**Q. `.env`를 `.gitignore`에 넣었는데도 깃에 계속 보입니다.**

이미 한 번 추적된 파일이기 때문입니다. `.gitignore`는 "아직 추적되지 않은" 파일에만 적용됩니다. `git rm --cached .env`로 추적을 끊은 뒤 커밋하세요.

**Q. 키만 바꾸면 히스토리는 그냥 둬도 되나요?**

유출된 키를 교체해서 더 못 쓰게 만들었다면, 그 키 자체의 실질적 위험은 사라집니다. 다만 히스토리에 남은 값으로 과거 데이터나 다른 시스템을 추적당할 여지가 있는지는 별도로 판단하세요. 공개 저장소이고 민감도가 높으면 히스토리 정리까지 하는 편이 안전합니다.

**Q. `.env.example`에는 진짜 값을 조금이라도 넣어도 되나요?**

안 됩니다. 견본 파일의 목적은 "어떤 변수가 필요한지" 알리는 것뿐입니다. 값은 모두 비워두거나 `your-key-here` 같은 자리표시자만 넣으세요.

## 정리

- `.env`는 코드가 아니라 환경 설정입니다. 저장소에는 처음부터 넣지 않는 게 원칙입니다.
- 막는 법은 간단합니다. `.gitignore`에 `.env`를 넣고, 빈 `.env.example`만 올립니다.
- 이미 올렸다면 순서가 중요합니다. **키 교체가 1순위**, 추적 끊기가 2순위, 히스토리 정리는 그다음입니다.
- 운영 환경에서는 "누가 접근하고, 새면 얼마나 빨리 바꿀 수 있는가"를 기준으로 비밀 보관 방식을 고릅니다.

비밀이 한 번 새면 되돌릴 수 없습니다. 그래서 사고가 났을 때 가장 빠른 복구는 "지우기"가 아니라 "그 값을 무의미하게 만들기"라는 점만 기억하면, 대부분의 상황에서 침착하게 대응할 수 있습니다.

## 출처

- [The Twelve-Factor App - III. Config](https://12factor.net/config)
- [GitHub Docs - Removing sensitive data from a repository](https://docs.github.com/en/authentication/keeping-your-account-and-data-secure/removing-sensitive-data-from-a-repository)
- [Git 공식 문서 - gitignore](https://git-scm.com/docs/gitignore)
