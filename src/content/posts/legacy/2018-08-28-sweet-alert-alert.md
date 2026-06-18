---
title: 'SweetAlert로 멋진 알림창 만들기'
slug: 'sweet-alert-alert'
author: 'Lovizu'
date: '2018-08-28'
summary: 'Laravel에서 기본 JavaScript alert를 SweetAlert로 대체해 더 아름답고 사용자 친화적인 알림창을 구현하는 완벽한 가이드. Composer 설치, 설정, 커스터마이징, 실무 활용까지 모든 것을 다룹니다.'
oneLineSummary: 'Laravel SweetAlert로 맞춤형 알림창 구현하기'
tags: ['legacy', 'legacy-migration', 'Laravel', 'JavaScript', 'UI']
status: "draft"
updatedDate: '2026-04-23'
---

## SweetAlert란?

기본 JavaScript의 `alert()` 함수는 단순하고 투박합니다. SweetAlert는 이를 대체하는 아름다운 알림창 라이브러리로, 사용자 경험을 크게 향상시킵니다.

## 설치

### 1. Composer를 통한 설치

```bash
composer require uxweb/sweet-alert
```

## 설정

### config/app.php 수정

Providers 배열에 SweetAlertServiceProvider 추가:

```php
'providers' => [
    // ... 다른 provider들
    UxWeb\SweetAlert\SweetAlertServiceProvider::class,
];
```

Aliases 배열에 Alert facade 등록:

```php
'aliases' => [
    // ... 다른 alias들
    'Alert' => UxWeb\SweetAlert\SweetAlert::class,
];
```

## NPM 패키지 설치

```bash
yarn add sweetalert --dev
```

또는 npm 사용:

```bash
npm install sweetalert --save-dev
```

## 커스터마이징

설정 파일 발행:

```bash
php artisan vendor:publish --provider "UxWeb\SweetAlert\SweetAlertServiceProvider"
```

## 사용 방법

### 컨트롤러에서 알림 설정

```php
// 성공 메시지
Alert::success('성공!', '작업이 완료되었습니다.');

// 오류 메시지
Alert::error('오류!', '작업 중 문제가 발생했습니다.');

// 정보 메시지
Alert::info('정보', '참고사항입니다.');

// 경고 메시지
Alert::warning('경고', '주의하세요.');
```

### 뷰 템플릿에서 JavaScript

```javascript
<script>
swal({
    text: "{!! Session::get('sweet_alert.text') !!}",
    title: "{!! Session::get('sweet_alert.title') !!}",
    timer: "{!! Session::get('sweet_alert.timer') !!}",
    type: "{!! Session::get('sweet_alert.type') !!}",
    icon: "{!! Session::get('sweet_alert.type') !!}",
    showConfirmButton: "{!! Session::get('sweet_alert.showConfirmButton') !!}",
    confirmButtonText: "{!! Session::get('sweet_alert.confirmButtonText') !!}",
    confirmButtonColor: "#AEDEF4"
    // 추가 옵션들...
});
</script>
```

## 주요 옵션

| 옵션 | 설명 |
|------|------|
| `title` | 알림창 제목 |
| `text` | 알림창 본문 |
| `type` | success, error, warning, info, question |
| `timer` | 자동 종료 시간 (밀리초) |
| `showConfirmButton` | 확인 버튼 표시 여부 |
| `confirmButtonText` | 확인 버튼 텍스트 |
| `confirmButtonColor` | 확인 버튼 색상 |
| `cancelButtonText` | 취소 버튼 텍스트 |
| `allowOutsideClick` | 배경 클릭으로 닫기 허용 |

## 실무 예시

### 폼 제출 후 성공 알림

```php
// Controller
public function store(Request $request)
{
    // ... 데이터 저장 로직
    
    Alert::success('성공!', '게시글이 작성되었습니다.');
    return redirect()->route('posts.index');
}
```

### 삭제 확인 알림

```javascript
swal({
    title: '삭제하시겠습니까?',
    text: '이 작업은 되돌릴 수 없습니다.',
    type: 'warning',
    showCancelButton: true,
    confirmButtonColor: '#dd3333',
    confirmButtonText: '삭제',
    cancelButtonText: '취소'
}).then(function(result) {
    if (result.value) {
        // 삭제 로직
    }
});
```

## 기본 제공 스타일

SweetAlert는 다양한 미리 정의된 색상과 아이콘을 제공합니다:
- Success (초록색)
- Error (빨간색)
- Warning (노란색)
- Info (파란색)
- Question (회색)

## 장점

1. **아름다운 UI**: 현대적이고 세련된 디자인
2. **사용자 친화적**: 직관적인 인터페이스
3. **유연한 커스터마이징**: 색상, 텍스트, 동작 등 자유로운 설정
4. **접근성**: 키보드 네비게이션 지원
5. **반응형**: 모바일 환경에서도 완벽하게 작동

## 마이그레이션 팁

기존 alert() 함수를 SweetAlert로 대체할 때는 사용자 기대값을 고려하여 단계적으로 진행하는 것이 좋습니다.

SweetAlert를 사용하면 일반적인 웹 애플리케이션을 프로페셔널한 수준으로 한 단계 업그레이드할 수 있습니다.
