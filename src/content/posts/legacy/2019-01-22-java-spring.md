---
title: 'Spring 프로젝트 한글 인코딩 설정'
slug: 'java-spring'
author: 'Lovizu'
date: '2019-01-22'
summary: 'Java Spring 프로젝트 초기 단계에서 필수인 한글 인코딩(UTF-8) 설정 방법입니다. web.xml에 CharacterEncodingFilter를 등록하여 모든 요청과 응답에 UTF-8을 강제 적용하는 방식을 설명합니다. 이 설정이 없으면 사용자로부터 받은 한글 데이터가 깨질 수 있으므로 프로젝트 초기에 반드시 적용해야 합니다.'
oneLineSummary: 'Spring 프로젝트 필수 설정: UTF-8 한글 인코딩'
tags: ['legacy', 'legacy-migration', 'Java', 'Spring', 'UTF-8', 'web.xml']
status: "draft"
updatedDate: '2026-04-23'
---

## 도입

Java Spring 프로젝트를 시작할 때 가장 먼저 해야 할 설정 중 하나가 **한글 인코딩**입니다. 이 설정을 놓치면 사용자가 입력한 한글 데이터가 깨져서 저장되거나, 데이터베이스에서 조회한 한글이 제대로 표시되지 않는 문제가 발생합니다. 특히 한국 사용자를 대상으로 하는 프로젝트라면 이 설정은 선택이 아닌 필수입니다.

## web.xml 필터 설정

`web.xml` 파일에 다음 코드를 추가하세요. 일반적으로 `<filter>` 섹션 뒤에 `<filter-mapping>` 섹션을 배치합니다:

```xml
<filter>
  <filter-name>encodingFilter</filter-name>
  <filter-class>org.springframework.web.filter.CharacterEncodingFilter</filter-class>
  <init-param>
    <param-name>encoding</param-name>
    <param-value>UTF-8</param-value>
  </init-param>
  <init-param>
    <param-name>forceEncoding</param-name>
    <param-value>true</param-value>
  </init-param>
</filter>

<filter-mapping>
  <filter-name>encodingFilter</filter-name>
  <url-pattern>/*</url-pattern>
</filter-mapping>
```

## 설정 항목 상세 설명

### Filter 정의 부분

| 항목 | 설명 |
|-----|------|
| `filter-name` | 필터의 고유 이름 (encodingFilter) |
| `filter-class` | Spring 제공 문자 인코딩 필터 클래스 |
| `encoding` | 사용할 인코딩 방식 (UTF-8) |
| `forceEncoding` | true: 모든 요청/응답에 강제 적용, false: 기존 설정 우선 |

### Filter Mapping 부분

```xml
<filter-mapping>
  <filter-name>encodingFilter</filter-name>
  <url-pattern>/*</url-pattern>
</filter-mapping>
```

- **filter-name**: 위에서 정의한 필터의 이름과 동일해야 함
- **url-pattern**: 필터 적용 범위 (`/*`는 모든 요청)

## 동작 원리

1. **요청 인코딩**: 브라우저에서 보낸 HTTP 요청 데이터를 UTF-8로 해석
2. **응답 인코딩**: 서버에서 클라이언트로 보내는 응답을 UTF-8로 인코딩
3. **강제 적용**: `forceEncoding=true`로 다른 곳에서 설정한 인코딩을 무시하고 UTF-8 강제 사용

## 주의사항

### 프로젝트 초기에 적용 필수

이 설정은 **반드시 프로젝트 초기에 적용**해야 합니다. 나중에 추가하면 그 이전에 입력된 한글 데이터는 이미 깨져 있어서 설정을 적용해도 회복되지 않습니다.

### forceEncoding은 반드시 true

```xml
<param-name>forceEncoding</param-name>
<param-value>true</param-value>
```

이 값을 `false`로 설정하면, 요청이 이미 인코딩을 명시한 경우 그 설정을 우선합니다. 안정적인 한글 처리를 위해서는 항상 `true`로 설정하세요.

## 추가 설정 팁

### JSP 파일 인코딩

JSP 파일 상단에도 다음을 추가하면 더욱 안정적입니다:

```jsp
<%@ page language="java" contentType="text/html; charset=UTF-8" pageEncoding="UTF-8" %>
```

### 데이터베이스 연결 인코딩

MySQL 연결 문자열에도 UTF-8 지정:

```java
String url = "jdbc:mysql://localhost:3306/dbname?useUnicode=true&characterEncoding=utf8mb4";
```

## 문제 해결

### 한글이 여전히 깨지는 경우

1. 브라우저 개발자 도구에서 네트워크 탭 확인 - Response Headers에 `Content-Type: charset=UTF-8` 있는지 확인
2. 데이터베이스 테이블의 문자 집합 확인: `SHOW CREATE TABLE table_name`
3. 톰캣의 `server.xml`에서 Connector에 `URIEncoding="UTF-8"` 추가

### 특정 요청만 인코딩이 안 되는 경우

`url-pattern`을 더 구체적으로 지정:

```xml
<url-pattern>/api/*</url-pattern>
```

## 마치며

이 간단한 설정으로 Spring 프로젝트에서 한글 처리 문제를 완벽하게 해결할 수 있습니다. 한국어를 많이 사용하는 프로젝트라면 이 설정은 필수이며, 프로젝트 초기부터 적용하는 것이 향후 많은 문제를 예방할 수 있습니다. Spring Boot를 사용한다면 자동 설정으로 이미 UTF-8이 기본값이지만, 전통적인 Spring MVC 프로젝트에서는 반드시 수동으로 설정해야 합니다.
