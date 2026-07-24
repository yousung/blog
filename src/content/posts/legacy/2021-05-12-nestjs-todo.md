---
title: 'NestJS: TODO API 완벽 구현 가이드'
slug: 'nestjs-todo'
author: "감성개발자"
date: '2021-05-12'
status: "draft"
summary: 'NestJS로 CRUD 기능이 완전한 TODO REST API를 구현하는 단계별 가이드입니다. 프로젝트 생성, Entity/DTO 설계, 유효성 검사, 서비스와 컨트롤러 작성까지 실무 패턴을 적용한 예제를 다룹니다.'
oneLineSummary: 'NestJS로 TODO API 만들기 (CRUD, 유효성 검사, 상태 관리)'
tags: ['legacy', 'legacy-migration', 'NestJS', 'TypeScript', 'Backend']
updatedDate: '2026-04-23'
---

## NestJS로 TODO API 구현하기

NestJS의 강력한 구조와 모듈식 설계를 활용하여 프로덕션급 TODO 애플리케이션을 만드는 과정을 단계별로 설명합니다.

### 프로젝트 생성

NestJS 프로젝트를 초기화한다.

```bash
nest new todo
```

### TODO 리소스 생성

NestJS의 `resource` 명령은 entity, controller, module, DTO를 한 번에 생성한다.

```bash
nest g res todo
# 또는
nest generate resource todo
```

### 유효성 검사 모듈 설치

데이터 유효성 검사와 변환을 위해 필요한 패키지를 설치한다.

```bash
npm i --save class-validator class-transformer
```

### 유효성 검사 적용

`main.ts`에 전역 ValidationPipe를 등록한다.

```typescript
// main.ts
async function bootstrap() {
  const app = await NestFactory.create(AppModule);
  app.useGlobalPipes(
    new ValidationPipe({
      transform: true,
    }),
  );
  await app.listen(3000);
}
bootstrap();
```

### Todo Entity 정의

```typescript
// todo.entity.ts
export enum Status {
  READY = 'ready',
  DONE = 'done',
}

export class Todo {
  id?: number;
  content: string;
  status: Status;
}
```

### DTO (Data Transfer Object)

요청 데이터의 유효성을 검사하기 위한 DTO를 작성한다.

```typescript
// create-todo.dto.ts
import { Status } from '../entities/todo.entity';
import { IsNotEmpty, Length, IsEnum } from 'class-validator';

export class CreateTodoDto {
  @IsNotEmpty({ message: '내용은 필수 값입니다.' })
  @Length(2, 50)
  readonly content: string;

  @IsEnum(Status, { message: '잘못된 상태 값입니다.' })
  readonly status: Status = Status.READY;
}
```

### TodoService 구현

```typescript
// todo.service.ts
import { Todo } from './entities/todo.entity';
import { Injectable } from '@nestjs/common';
import { CreateTodoDto } from './dto/create-todo.dto';
import { UpdateTodoDto } from './dto/update-todo.dto';
import { NotFoundException } from '@nestjs/common';

@Injectable()
export class TodoService {
  private id = 0;
  private todos: Todo[] = [];

  create(createTodoDto: CreateTodoDto): Todo {
    this.id++;
    const todo = {
      id: this.id,
      ...createTodoDto,
    };
    this.todos.push(todo);
    return todo;
  }

  findAll(): Todo[] {
    return this.todos;
  }

  findOne(id: number): Todo {
    const findId = this.todos.findIndex((todo) => todo.id === id);
    if (findId === -1) {
      throw new NotFoundException();
    }
    return this.todos[findId];
  }

  update(id: number, updateTodoDto: UpdateTodoDto) {
    const findId = this.todos.findIndex((todo) => todo.id === id);
    if (findId === -1) {
      throw new NotFoundException();
    }
    this.todos[findId] = {
      ...this.todos[findId],
      ...updateTodoDto,
    };
    return this.todos[findId];
  }

  remove(id: number): void {
    this.todos = this.todos.filter((todo) => todo.id !== id);
  }
}
```

### TodoController 구현

```typescript
// todo.controller.ts
import { Todo } from './entities/todo.entity';
import {
  Controller,
  Get,
  Post,
  Body,
  Patch,
  Param,
  Delete,
  HttpCode,
} from '@nestjs/common';
import { TodoService } from './todo.service';
import { CreateTodoDto } from './dto/create-todo.dto';
import { UpdateTodoDto } from './dto/update-todo.dto';

@Controller('todo')
export class TodoController {
  constructor(private readonly todoService: TodoService) {}

  @Post()
  create(@Body() createTodoDto: CreateTodoDto): Todo {
    return this.todoService.create(createTodoDto);
  }

  @Get()
  findAll(): Todo[] {
    return this.todoService.findAll();
  }

  @Get(':id')
  findOne(@Param('id') id: number): Todo {
    return this.todoService.findOne(id);
  }

  @Patch(':id')
  update(@Param('id') id: number, @Body() updateTodoDto: UpdateTodoDto): Todo {
    return this.todoService.update(id, updateTodoDto);
  }

  @Delete(':id')
  @HttpCode(204)
  remove(@Param('id') id: number): void {
    this.todoService.remove(id);
  }
}
```

## 다음 단계

이 기본 구조를 바탕으로 다음과 같은 기능을 확장할 수 있습니다:

- **데이터베이스**: TypeORM 또는 Prisma를 활용한 영속성 계층 구현
- **인증**: JWT 기반 사용자 인증 및 권한 관리
- **페이지네이션**: 대량 데이터 조회 시 페이지 처리
- **로깅**: Winston 또는 Pino를 이용한 구조화된 로깅
- **테스트**: Jest를 활용한 단위 및 통합 테스트

## 마치며

NestJS는 TypeScript 기반의 강력한 백엔드 프레임워크로, 이 예제는 기초를 다루고 있습니다. 제공된 구조와 패턴을 이해하면 더 복잡한 엔터프라이즈 애플리케이션으로 확장하는 것이 수월합니다.

**소스 코드**: [github.com/yousung/nestjs-todo](https://github.com/yousung/nestjs-todo)
