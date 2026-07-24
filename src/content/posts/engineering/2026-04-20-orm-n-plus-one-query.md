---
title: "ORM N+1 쿼리, 발견하는 법과 고치는 기준"
slug: "orm-n-plus-one-query"
ogImage: "/images/posts/orm-n-plus-one-query/hero.png"
author: "감성개발자"
date: "2026-04-20"
summary: "ORM으로 목록 하나를 그리는 데 쿼리가 수십 개 나가는 N+1 문제. 왜 생기는지, 개발 단계에서 어떻게 발견하는지, eager loading으로 고칠 때의 기준과 모든 관계를 미리 부르는 과적재의 부작용까지 실무에서 쓰는 판단으로 적었다."
oneLineSummary: "N+1은 지연 로딩의 기본 동작에서 나온다. 쿼리 로그로 발견하고, 화면이 실제로 쓰는 관계만 eager loading으로 미리 가져온다."
tags: [ORM, Database, Laravel, 성능최적화]
status: "published"
---

![N+1 쿼리 분산과 eager loading 묶음을 대비한 기술 일러스트](/images/posts/orm-n-plus-one-query/hero.png)

느려진 목록 화면을 붙잡고 쿼리 로그를 열었더니, `SELECT * FROM users WHERE id = ?`가 바인딩 값만 바꿔 스무 줄 넘게 이어져 있었습니다. 게시글 20개를 가져오는 쿼리 1개에, 각 게시글의 작성자를 가져오는 쿼리 20개가 그대로 따라붙은 그림입니다. 데이터가 적을 때는 티가 안 나다가 목록이 길어지고 관계가 중첩될수록 쿼리 수가 곱셈으로 늘어납니다. 개발 환경에서는 멀쩡하던 화면이 운영에서만 느려지는 흔한 원인이 바로 이 N+1입니다.

한 번 크게 데고 나서 정리한 기준은 이렇습니다. **N+1은 ORM의 버그가 아니라 지연 로딩(lazy loading)의 기본 동작이며, 쿼리 로그로 발견하고 화면이 실제로 쓰는 관계만 미리 로딩(eager loading)하는 것이 표준 대응입니다.**

아래 내용은 Laravel 공식 문서의 Eloquent 관계 로딩 설명을 중심으로 두되, 다른 ORM에도 그대로 통하는 기준으로 풀어 갑니다.

## N+1은 왜 생기나

ORM의 지연 로딩은 관계 데이터를 "처음 접근하는 순간" 조회합니다. 코드만 보면 자연스럽습니다.

```php
$posts = Post::limit(20)->get();          // 쿼리 1번

foreach ($posts as $post) {
    echo $post->author->name;             // 접근할 때마다 쿼리 1번씩, 총 20번
}
```

한 줄 한 줄은 합리적인데, 반복문과 만나면 쿼리 21번이 됩니다. 관계가 한 단계 더 들어가면(작성자의 프로필 이미지 등) 곱셈이 됩니다. Laravel 공식 문서도 관계를 프로퍼티로 접근하면 지연 로딩되어 실제 접근 시점에 쿼리가 나가므로, 상위 모델 조회 후 관계마다 추가 쿼리가 발생하는 N+1 문제가 생길 수 있다고 설명합니다. (출처: [Laravel Documentation - Eloquent: Relationships, Eager Loading](https://laravel.com/docs/12.x/eloquent-relationships#eager-loading))

ORM 종류는 중요하지 않습니다. Django의 쿼리셋, JPA의 지연 페치, Prisma의 관계 접근 모두 같은 구조에서 같은 문제가 납니다.

## 발견: 쿼리 로그가 가장 확실하다

N+1은 코드 리뷰만으로 잡기 어렵습니다. 관계 접근이 템플릿이나 직렬화 계층에 숨어 있는 경우가 많기 때문입니다. 개발 환경에서는 데이터가 적어 멀쩡하다가 운영에 올라가서야 터지는 흐름을, 여러 프로젝트를 인수하며 반복해서 봤습니다. 확실한 방법은 요청 하나당 실행된 쿼리를 눈으로 보는 것입니다.

- **쿼리 로그 확인.** 같은 모양의 쿼리가 바인딩 값만 바꿔 반복되면 N+1입니다. `SELECT * FROM users WHERE id = ?`가 20번 찍히는 식입니다.
- **개발 도구 활용.** Laravel은 Debugbar나 Telescope로 요청당 쿼리 수를 볼 수 있습니다. Django는 debug toolbar, Rails는 로그의 쿼리 수가 같은 역할을 합니다.
- **지연 로딩 자체를 금지.** Laravel은 `Model::preventLazyLoading()`을 켜면 지연 로딩이 발생하는 순간 예외를 던집니다. 개발·스테이징 환경에서 켜두면 N+1이 배포 전에 드러납니다. (출처: [Laravel Documentation - Configuring Eloquent Strictness](https://laravel.com/docs/12.x/eloquent#configuring-eloquent-strictness))

숫자 감각도 도움이 됩니다. 목록 화면 하나에 쿼리가 수십 개라면 거의 확실히 N+1이 있습니다. 잘 정리된 목록 화면의 쿼리 수는 관계 개수 + 1 수준으로 떨어집니다.

## 해결: 화면이 쓰는 관계만 미리 로딩

eager loading은 관계 데이터를 IN 쿼리 한 번으로 몰아서 가져옵니다.

```php
$posts = Post::with('author')->limit(20)->get();
// SELECT * FROM posts LIMIT 20
// SELECT * FROM users WHERE id IN (1, 5, 8, ...)
```

쿼리 21번이 2번이 됩니다. 중첩 관계는 점 표기로, 필요한 컬럼만 가져오는 제한도 가능합니다.

```php
Post::with(['author:id,name', 'comments.user:id,name'])->get();
```

이미 조회한 컬렉션에 나중에 관계가 필요해졌다면 `load()`로 뒤늦게 몰아 가져올 수 있습니다. 반복문 안에서 접근하기 전에만 실행되면 효과는 같습니다.

집계값만 필요한 경우는 관계 전체를 로딩할 이유가 없습니다. 댓글 개수만 보여주는 화면이라면 `withCount('comments')`가 댓글 행 전체를 가져오는 것보다 훨씬 쌉니다.

여기서 "차라리 JOIN으로 한 방에 가져오면 되지 않나"라는 말이 자주 나옵니다. 목적에 따라 다릅니다. 1:N 관계를 JOIN하면 상위 행이 하위 행 수만큼 복제되어 전송량이 오히려 늘 수 있고, ORM의 IN 방식 eager loading은 그 복제를 피하려고 나온 설계입니다. 집계나 필터링이 목적이면 JOIN·서브쿼리가 맞고, 관계 객체 자체가 필요하면 eager loading이 맞습니다.

## 과적재: 반대 방향의 실수

N+1을 한 번 크게 겪고 나면 모델의 기본 로딩(`$with`)에 관계를 계속 추가하는 방향으로 흐르기 쉽습니다. 팀에서도 이 전환이 자주 나오는데, 이건 반대편 함정입니다.

- 목록 화면은 작성자 이름만 쓰는데 프로필, 권한, 소속까지 항상 로딩되면 쿼리 수는 적어도 전송량과 메모리가 커집니다.
- 기본 로딩이 쌓이면 "이 화면에서 어떤 데이터가 필요한지"가 코드에서 사라지고, 어느 관계를 빼도 되는지 아무도 모르게 됩니다.

기준은 단순합니다. **eager loading은 모델의 기본값이 아니라 호출부(화면·API 엔드포인트) 단위로 선언합니다.** 화면마다 필요한 관계가 다르기 때문에, 로딩 선언도 화면 쪽에 있어야 유지보수가 됩니다.

## 자주 묻는 질문

**Q. eager loading을 분명히 했는데도 쿼리가 여전히 많아요.**
십중팔구 로딩한 관계와 실제로 접근하는 관계가 어긋나 있습니다. `with('comments')`를 해놓고 코드에서는 `$comment->user`에 접근하면, 바로 그 지점에서 N+1이 다시 시작됩니다. 중첩 관계는 `comments.user`처럼 끝까지 지정해야 새는 곳이 없습니다.

## 정리하면

- N+1은 지연 로딩 + 반복문 조합에서 나오는 구조적 패턴입니다. ORM 종류와 무관합니다.
- 발견은 쿼리 로그와 요청당 쿼리 수 확인이 가장 확실하고, 개발 환경에서는 지연 로딩 금지 옵션을 켜두는 것이 좋습니다.
- 해결은 화면이 실제로 쓰는 관계만 eager loading하는 것이고, 개수만 필요하면 count 계열을 씁니다.
- 모든 관계를 기본 로딩하는 과적재는 반대 방향의 성능 문제를 만듭니다. 로딩 선언은 호출부에 둡니다.

## 출처

- [Laravel Documentation - Eloquent: Relationships, Eager Loading](https://laravel.com/docs/12.x/eloquent-relationships#eager-loading)
- [Laravel Documentation - Configuring Eloquent Strictness](https://laravel.com/docs/12.x/eloquent#configuring-eloquent-strictness)
