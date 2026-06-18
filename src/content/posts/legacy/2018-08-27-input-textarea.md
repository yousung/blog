---
title: 'Laravel에서 유효성 검사 오류 입력창 시각적 표시'
slug: 'input-textarea'
author: 'Lovizu'
date: '2018-08-27'
summary: 'Laravel 폼 유효성 검사 실패 시 입력 필드의 테두리 색상을 동적으로 변경하여 사용자에게 오류를 시각적으로 표시하는 방법입니다. 부트스트랩 스타일의 커스텀 헬퍼 함수를 활용한 구현 예시를 제시합니다.'
oneLineSummary: 'Laravel 폼 검증 실패 시 입력창 색상 변경으로 오류 표시'
tags: ['legacy', 'legacy-migration', 'Laravel', 'PHP', '폼 검증', 'CSS']
status: 'published'
updatedDate: '2026-04-23'
---

## Laravel 폼 검증 오류의 시각적 피드백

사용자가 폼을 입력할 때 유효성 검사에 실패하면 단순한 텍스트 오류 메시지보다 입력 필드 자체에 시각적 피드백을 주는 것이 더 직관적입니다. 부트스트랩 스타일을 활용하여 오류가 있는 입력 필드의 테두리 색상을 빨간색으로 변경하는 방법을 소개합니다.

## Laravel 커스텀 헬퍼 함수 작성

먼저 `app/Helpers` 또는 프로젝트 구조에 맞는 위치에 헬퍼 함수를 정의합니다:

```php
if (!function_exists('hasError')) {
    function hasError($errName, $isMsg = false)
    {
        if ($errors = session('errors', new \Illuminate\Support\MessageBag())) {
            if ($isError = $errors->has($errName)) {
                return $isMsg
                    ? $errors->first($errName, ':message')
                    : 'has-error';
            }
        }
    }
}
```

이 헬퍼 함수는 두 가지 역할을 합니다:
- `isMsg`가 `false`일 때: CSS 클래스명 'has-error' 반환
- `isMsg`가 `true`일 때: 실제 오류 메시지 반환

## CSS 스타일 정의

오류 상태인 입력 필드에 적용할 CSS를 작성합니다:

```css
input.has-error {
    box-shadow: 0 0 5px rgba(255, 90, 90, 1);
    padding: 3px 0px 3px 3px;
    margin: 5px 1px 3px 0px;
    border: 1px solid rgba(255, 90, 90, 1);
}
```

이 스타일은:
- 빨간색 테두리(border)를 적용
- 부드러운 그림자 효과로 시각적 강조
- 적절한 패딩과 마진으로 여백 조정

## HTML 폼 작성

뷰 파일에서 헬퍼 함수를 활용하여 동적으로 CSS 클래스를 적용합니다:

```html
<input class="{{ hasError('phone') }}" 
    type="text" 
    name="phone" 
    id="phone" 
    value="{{ old('phone') }}">
```

- `hasError('phone')`: phone 필드에 오류가 있으면 'has-error' 클래스 추가
- `old('phone')`: 폼 제출 실패 시 사용자가 입력한 값을 유지

## 오류 메시지 함께 표시

오류 메시지도 함께 표시하려면:

```html
<div>
    <input class="{{ hasError('phone') }}" 
        type="text" 
        name="phone" 
        id="phone" 
        value="{{ old('phone') }}">
    @if ($errors->has('phone'))
        <span class="error-message">{{ hasError('phone', true) }}</span>
    @endif
</div>
```

## 정리

이 방법은 Bootstrap 스타일에 부합하면서도 간단하게 구현할 수 있는 폼 검증 피드백 시스템입니다. 사용자 경험을 향상시키고 시각적 명확성을 제공합니다.
