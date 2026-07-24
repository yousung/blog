---
title: "Git merge와 rebase, 언제 무엇을 써야 할까"
slug: "git-merge-vs-rebase-when-to-use"
ogImage: "/images/posts/git-merge-vs-rebase-when-to-use/hero.png"
author: "감성개발자"
date: "2026-06-02"
summary: "Git merge와 rebase 중 무엇을 쓸지 헷갈릴 때 기준은 하나다. 그 커밋을 남이 봤는가. 둘의 차이부터 협업에서 절대 하면 안 되는 rebase, 상황별 선택과 충돌 대처까지 실무 기준으로 짚는다."
oneLineSummary: "merge냐 rebase냐, '그 커밋을 남이 봤는가' 하나로 갈리는 판단 기준."
tags: [Git, GitHub, tip]
status: "published"
---

![merge의 얽힌 히스토리와 rebase의 일직선 히스토리를 대비한 기술 일러스트](/images/posts/git-merge-vs-rebase-when-to-use/hero.png)

merge와 rebase를 두고 고민하는 시점은 대개 비슷합니다. 내 브랜치가 작업하는 동안 `main`이 앞서 나갔고, 이걸 어떻게 합칠지 결정해야 할 때입니다. 이때 잘못 고르면 커밋 히스토리가 지저분해지거나, 더 나쁘게는 같이 일하는 사람의 작업을 꼬이게 만듭니다. 결론부터 말하면 선택 기준은 취향이 아니라 "그 커밋을 다른 사람이 봤는가"입니다.

**한 문장으로 줄이면, push해서 남이 봤을 수 있는 커밋은 rebase하지 않는다는 것입니다.** 나머지 선택은 전부 여기서 갈립니다.

최종 업데이트: 2026-06-02

## 작성 관점

소규모 팀을 리딩하면서 가장 자주 수습한 사고가 "히스토리를 예쁘게 만들려다 남의 작업을 날린 경우"였습니다. 그래서 이 글은 "어느 쪽이 더 멋진가"가 아니라 "어느 쪽이 사고를 안 내는가"를 기준으로 정리했습니다.

## merge와 rebase는 결과부터 다릅니다

두 명령 모두 한 브랜치의 변경을 다른 브랜치로 합치는 일을 합니다. 다만 합치는 방식이 다릅니다.

`git merge`는 두 브랜치의 스냅샷과 공통 조상을 가지고 3-way 병합을 수행하고, 그 결과를 담은 새 병합 커밋(merge commit)을 만듭니다. 그래서 두 갈래로 나뉘어 작업했다는 사실이 히스토리에 그대로 남습니다. (출처: [Pro Git - Basic Branching and Merging](https://git-scm.com/book/en/v2/Git-Branching-Basic-Branching-and-Merging))

`git rebase`는 다릅니다. 내 브랜치의 커밋들을 떼어내, 대상 브랜치의 최신 커밋 위에 하나씩 다시 올립니다. 결과적으로 마치 처음부터 일직선으로 작업한 것처럼 보이는 히스토리가 만들어집니다. (출처: [Pro Git - Rebasing](https://git-scm.com/book/en/v2/Git-Branching-Rebasing))

여기서 중요한 차이가 하나 나옵니다. merge는 기존 커밋을 건드리지 않지만, rebase는 커밋을 새로 만들어 옮깁니다. 즉 rebase는 히스토리를 다시 쓰는 작업입니다. 이 한 문장이 뒤에 나올 모든 판단의 근거가 됩니다.

## 먼저 보는 기준: 이 커밋을 남이 봤는가

![이미 공유된 커밋을 rebase한 뒤 force-push했을 때 벌어지는 상황을 형상화한 도식. 원래 커밋들이 동일한 복제본으로 다시 만들어져 하나의 공유 히스토리로 밀려 들어오면서 중복된 노드가 뒤엉켜 쌓이고, 그 커밋 위에 작업하던 동료의 브랜치가 원본 노드에서 끊겨 떨어져 나온 모습을 대비로 보여준다.](/images/posts/git-merge-vs-rebase-when-to-use/figure.png)

merge냐 rebase냐를 정할 때 가장 먼저 던질 질문은 "내가 정리하려는 커밋이 내 저장소 밖으로 나갔는가"입니다.

- 아직 push하지 않은, 나만 가진 로컬 커밋이라면 → rebase로 정리해도 안전합니다.
- 이미 push했고 다른 사람이 그 커밋을 받아갔을 수 있다면 → rebase하지 않습니다.

Pro Git 문서는 이 원칙을 황금률로 못 박습니다.

> "Do not rebase commits that exist outside your repository and that people may have based work on."
> (저장소 밖에 존재하고, 다른 사람이 그 위에 작업했을 수 있는 커밋은 rebase하지 마세요.) (출처: [Pro Git - Rebasing](https://git-scm.com/book/en/v2/Git-Branching-Rebasing))

이유는 단순합니다. rebase는 커밋 해시를 바꾸기 때문에, 같은 변경이라도 동료에게는 "전혀 다른 새 커밋"으로 보입니다. 내가 force-push로 밀어버리면 동료는 원래 커밋과 새 커밋이 뒤섞인 히스토리를 받게 되고, 자기 작업을 다시 병합하느라 시간을 씁니다. 중복처럼 보이는 커밋이 쌓이고, 무엇이 진짜 작업인지 헷갈리기 시작합니다. (출처: [Pro Git - Rebasing](https://git-scm.com/book/en/v2/Git-Branching-Rebasing))

## 상황별 선택 기준

위 기준을 실제 작업 흐름에 대입하면 이렇게 갈립니다.

| 상황 | 권장 | 이유 |
| --- | --- | --- |
| push 전, 내 로컬 커밋 정리 | rebase | 커밋이 아직 밖으로 안 나갔으므로 다시 써도 안전 |
| 작업 중 main의 최신 변경을 내 브랜치로 가져오기 | 팀 규칙 따름 (rebase 또는 merge) | 둘 다 가능하나 팀이 정한 한 가지로 통일 |
| 공유 브랜치(main, develop)에 기능 합치기 | merge | 누가 언제 합쳤는지 기록 보존, force-push 불필요 |
| 이미 push한 커밋을 고치고 싶을 때 | merge 또는 별도 커밋 | rebase 후 force-push는 협업 사고의 주원인 |
| 오픈소스에 PR 보내기 전 커밋 다듬기 | rebase | 리뷰어가 보기 좋은 깔끔한 단위로 정리 |

가장 추천하는 조합은 Pro Git이 권하는 절충안입니다. push하기 전 로컬에서는 rebase로 커밋을 깔끔하게 다듬되, 한 번이라도 push한 것은 절대 rebase하지 않는 방식입니다. 로컬은 깔끔하게, 공유된 것은 건드리지 않는다는 두 가지를 동시에 얻을 수 있습니다. (출처: [Pro Git - Rebasing](https://git-scm.com/book/en/v2/Git-Branching-Rebasing))

## 이런 경우에는 rebase하지 마세요

- 다른 사람과 공유하는 브랜치(`main`, `develop`, 배포 브랜치)에서는 rebase로 히스토리를 다시 쓰지 않습니다.
- 이미 PR이 올라가 리뷰가 진행 중인데, 리뷰어가 커밋 단위로 코멘트를 달아둔 경우 함부로 rebase하면 코멘트 위치가 어긋날 수 있습니다. 정리가 꼭 필요하면 리뷰어와 합의한 뒤 합니다.
- rebase 중간에 충돌이 여러 커밋에 걸쳐 반복된다면, 같은 충돌을 커밋마다 다시 푸는 셈입니다. 이럴 땐 merge 한 번으로 끝내는 편이 시간이 덜 듭니다.

반대로 merge에도 단점은 있습니다. 작은 브랜치를 자주 merge하면 병합 커밋이 쌓여 히스토리가 갈래갈래 복잡해집니다. 그래서 "공유 전 로컬 정리는 rebase, 공유 브랜치 통합은 merge"라는 역할 분담이 현실적인 타협점이 됩니다.

## 충돌이 났을 때 빠져나오는 법

어느 쪽이든 충돌은 납니다. 당황해서 히스토리를 더 망치지 않으려면 되돌리는 명령을 알아두는 게 먼저입니다.

- rebase 중 꼬였을 때: `git rebase --abort` 로 rebase 시작 전 상태로 되돌립니다.
- merge 중 꼬였을 때: `git merge --abort` 로 merge 시작 전 상태로 되돌립니다.
- 충돌 파일을 고친 뒤 rebase를 이어갈 때: 파일을 `git add` 한 다음 `git rebase --continue` 로 진행합니다.

이 세 가지만 알아도 "잘못 골라서 망쳤다" 싶을 때 대부분 원상복구가 됩니다. 특히 push하지 않은 상태라면 거의 모든 실수는 되돌릴 수 있습니다.

## 현장에서 자주 받는 질문

**Q. 그냥 항상 merge만 쓰면 안 되나요?**
됩니다. merge만 써도 작업은 안전하게 합쳐집니다. 다만 작은 브랜치를 자주 합치는 팀이라면 병합 커밋이 많아져 히스토리가 복잡해질 수 있어, push 전 로컬 정리에 한해 rebase를 곁들이는 경우가 많습니다.

**Q. `git pull` 할 때 rebase를 쓰라는 말은 뭔가요?**
`git pull --rebase`는 원격 변경을 가져올 때 내 로컬 커밋을 그 위에 다시 올려 병합 커밋 없이 일직선으로 만드는 옵션입니다. 아직 push하지 않은 내 로컬 커밋에만 작용하므로 황금률에 어긋나지 않습니다.

**Q. 이미 push한 브랜치를 rebase해버렸어요. 어떻게 하나요?**
혼자 쓰는 브랜치라면 force-push로 정리하고 넘어갈 수 있습니다. 하지만 다른 사람이 받아간 브랜치라면 먼저 팀에 알리고, 각자 자기 작업을 어떻게 다시 맞출지 합의해야 합니다. 조용히 force-push하는 것이 가장 큰 사고로 이어집니다.

## 정리하면

- merge는 기록을 보존하고, rebase는 기록을 다시 써서 깔끔하게 만듭니다.
- 판단 기준은 "이 커밋이 내 저장소 밖으로 나갔는가" 하나입니다.
- push 전 로컬은 rebase로 다듬고, 공유 브랜치 통합은 merge로 가는 조합이 사고를 가장 적게 냅니다.
- 꼬이면 `--abort`로 되돌릴 수 있으니, 되돌리는 명령부터 외워두는 게 안전합니다.

## 출처

- [Pro Git - Rebasing (Git 공식 문서)](https://git-scm.com/book/en/v2/Git-Branching-Rebasing)
- [Pro Git - Basic Branching and Merging (Git 공식 문서)](https://git-scm.com/book/en/v2/Git-Branching-Basic-Branching-and-Merging)
