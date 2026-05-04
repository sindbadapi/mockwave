# 🌊 Mockwave

> Adaptive Mock Service — система для имитации и проксирования запросов к микросервисам.

Mockwave позволяет заменять реальные сервисы гибким мок-слоем с административной панелью, поддержкой расписаний и
детальным логированием — без изменения кода клиентских приложений.

---

## Содержание

- [Возможности](#возможности)
- [Стек технологий](#стек-технологий)
- [Архитектура](#архитектура)
- [Быстрый старт](#быстрый-старт)
- [Переменные окружения](#переменные-окружения)
- [Структура проекта](#структура-проекта)
- [Схема базы данных](#схема-базы-данных)
- [API Gateway](#api-gateway)
- [MCP сервер](#mcp-сервер)
- [Agentic Development](#Agentic-Development)
- [Планировщик вебхуков](#планировщик-вебхуков)
- [Административная панель](#административная-панель)
- [Разработка](#разработка)
- [Production](#production)

---

## Возможности

| Функция               | Описание                                                           |
|-----------------------|--------------------------------------------------------------------|
| **Mock Mode**         | Возврат заранее настроенных ответов (body, headers, status, delay) |
| **Proxy Mode**        | Прозрачное проксирование запросов к реальному сервису              |
| **Webhook Scheduler** | Имитация входящих вебхуков по cron-расписанию                      |
| **Request Logs**      | Полное логирование всех входящих запросов и ответов                |
| **Admin Panel**       | SPA на Inertia + React для управления всей конфигурацией           |
| **MCP Server**        | AI-клиенты (Claude, Cursor) управляют сервисами через MCP          |
| **Multi-service**     | Поддержка неограниченного числа сервисов и эндпоинтов              |

---

## Стек технологий

**Backend**

- [Laravel 13](https://laravel.com/) — PHP 8.5, PHP-фреймворк
- [PostgreSQL 16](https://www.postgresql.org/) — основная база данных (JSONB для body/headers/payload)
- [Redis 7](https://redis.io/) — очереди и кеш
- [Laravel Http Client](https://laravel.com/docs/13.x/http-client) — проксирование запросов (обёртка над Guzzle)

**Frontend**

- [Inertia.js 3](https://inertiajs.com/) — связующий слой между Laravel и React
- [React 19](https://react.dev/) — UI-компоненты
- [TypeScript](https://www.typescriptlang.org/) — типизация
- [Vite 8](https://vitejs.dev/) — сборка ассетов
- [Tailwind CSS 4](https://tailwindcss.com/) — стили

**AI / MCP**

- [Laravel MCP](https://laravel.com/docs/13.x/mcp) — встроенный MCP-модуль Laravel 13

**Инфраструктура**

- [Docker + Docker Compose](https://www.docker.com/) — локальная разработка (Node 24, PHP 8.5)
- [Dokploy](https://dokploy.com/) — деплой на VPS *(в роадмапе)*

---

## Архитектура

```
Client Request
      │
      ▼
┌────────────────────────┐
│  MockGatewayController │  ← единая точка входа
│  /gateway/{svc}/{path} │
└──────────┬─────────────┘
           │
    ┌──────┴──────┐
    │             │
    ▼             ▼
┌────────┐  ┌─────────┐
│  Mock  │  │  Proxy  │
│Handler │  │ Handler │
└───┬────┘  └────┬────┘
    │             │
    ▼             ▼
Configured    Real
Response    Microservice
    │
    ▼ defer()
RequestLog (PostgreSQL)

AI Client (Claude / Cursor)
      │
      ▼ MCP  /mcp
┌─────────────────┐
│ MockwaveServer  │  ← tools: SwitchMode, GetLogs, ...
└─────────────────┘
```

Роутинг происходит по `service_slug` + `path`. Каждый сервис и каждый эндпоинт могут иметь собственный режим (`mock` /
`proxy`), причём режим эндпоинта (`mode_override`) имеет приоритет над режимом сервиса.

---

## Быстрый старт

### Требования

- Docker 24+
- Docker Compose v2+
- Make *(опционально, для удобных команд)*

### Установка

```bash
# 1. Клонировать репозиторий
git clone https://github.com/your-username/mockwave.git
cd mockwave

# 2. Скопировать конфиг окружения
cp .env.example .env

# 3. Поднять контейнеры
docker compose up -d

# 4. Установить зависимости и настроить приложение
make install
```

Или вручную, без Make:

```bash
docker compose exec app composer install
docker compose exec app php artisan key:generate
docker compose exec app php artisan migrate --seed
docker compose exec app npm install
docker compose exec app npm run build
```

Административная панель доступна по адресу: **http://localhost:8080**

Учётные данные по умолчанию (seeder):

- Email: `admin@mockwave.local`
- Password: `password`

---

## Переменные окружения

Полный список переменных в `.env.example`. Ключевые:

```dotenv
# Приложение
APP_NAME=Mockwave
APP_ENV=local
APP_URL=http://localhost:8080

# База данных
DB_CONNECTION=pgsql
DB_HOST=postgres
DB_PORT=5432
DB_DATABASE=mockwave
DB_USERNAME=mockwave
DB_PASSWORD=secret

# Redis
REDIS_HOST=redis
REDIS_PORT=6379

# Очереди
QUEUE_CONNECTION=redis

# Gateway
GATEWAY_TIMEOUT_SECONDS=30        # таймаут проксирования
GATEWAY_LOG_REQUEST_BODY=true      # логировать тело запроса
GATEWAY_LOG_RESPONSE_BODY=true     # логировать тело ответа
GATEWAY_MAX_LOG_BODY_SIZE=65536    # максимальный размер тела в логе (байт)

# Seeder
ADMIN_EMAIL=admin@mockwave.local
ADMIN_PASSWORD=password
```

---

## Структура проекта

```
mockwave/
├── app/
│   ├── Console/Commands/
│   │   └── DispatchWebhooksCommand.php   # artisan mockwave:dispatch-webhooks
│   ├── Http/
│   │   ├── Controllers/
│   │   │   ├── Admin/                    # CRUD: Service, Endpoint, MockResponse,
│   │   │   │                             #       ScheduledWebhook, RequestLog, Dashboard
│   │   │   └── MockGatewayController.php # единая точка входа gateway
│   │   ├── Requests/Admin/               # FormRequest-классы (Store*/Update*)
│   │   └── Resources/                    # API Resources
│   ├── Mcp/
│   │   ├── Servers/MockwaveServer.php    # регистрирует tools + resources
│   │   ├── Tools/                        # ListServices, GetEndpoints, SwitchMode, ...
│   │   └── Resources/                    # ServiceConfig, RequestLog (URI-шаблоны)
│   ├── Models/
│   │   ├── Service.php
│   │   ├── Endpoint.php
│   │   ├── MockResponse.php
│   │   ├── ScheduledWebhook.php
│   │   └── RequestLog.php        # append-only
│   ├── Providers/
│   │   └── AppServiceProvider.php
│   └── Services/
│       ├── Contracts/RequestHandlerInterface.php
│       ├── MockHandler.php
│       └── ProxyHandler.php      # использует Laravel Http Client
├── bootstrap/app.php
├── config/gateway.php            # timeout_seconds, log_request_body, max_log_body_size
├── database/
│   ├── migrations/
│   └── seeders/DatabaseSeeder.php
├── docker/
│   ├── nginx/default.conf
│   ├── php/php.ini + opcache.ini
│   └── scheduler/scheduler.sh
├── docker-compose.yml
├── Dockerfile                    # stages: node_builder → php_base → dev → production
├── resources/
│   ├── css/app.css
│   └── js/
│       ├── Pages/                # Inertia-страницы (React + TypeScript)
│       ├── Components/
│       └── types/index.ts        # все TypeScript-типы проекта
├── routes/
│   ├── web.php                   # /health, admin SPA, auth, gateway catch-all
│   ├── api.php                   # /api/admin/* (auth middleware)
│   └── ai.php                    # MCP: Mcp::web('/mcp', MockwaveServer::class)
├── .env.example
├── composer.json
├── package.json
└── vite.config.ts
```

---

## Схема базы данных

```sql
services
  id, name, slug (unique), base_url, description,
  mode enum('mock','proxy') default 'mock',
  is_active bool, timestamps

endpoints
  id, service_id FK→services,
  method ('GET'|'POST'|'PUT'|'PATCH'|'DELETE'|'HEAD'|'OPTIONS'|'ANY'),
  path (starts with /),
  mode_override enum('mock','proxy') NULLABLE,
  proxy_url NULLABLE,
  is_active bool, timestamps
  UNIQUE(service_id, method, path)

mock_responses
  id, endpoint_id FK→endpoints,
  status_code smallint default 200,
  body jsonb NULLABLE, headers jsonb NULLABLE,
  delay_ms int default 0, timestamps

scheduled_webhooks
  id, name, target_url, method,
  payload jsonb NULLABLE, headers jsonb NULLABLE,
  cron_expression, is_active bool,
  last_run_at timestamp NULLABLE, timestamps

request_logs
  id, endpoint_id FK NULLABLE→endpoints,
  method, path,
  request_data jsonb,   -- {headers, query, body}
  response_data jsonb,  -- {status, headers, body}
  mode_used enum('mock','proxy','not_found'),
  duration_ms int, created_at (indexed)
```

---

## API Gateway

Все входящие запросы к мок-сервису проходят через единый роут:

```
{METHOD} /gateway/{service_slug}/{path?}
```

Примеры:

```bash
# Запрос к мок-сервису банка
curl http://localhost:8080/gateway/bank-api/v1/accounts

# Запрос с заголовками
curl -H "Authorization: Bearer test-token" \
     http://localhost:8080/gateway/payment-service/charge
```

Режим обработки (mock/proxy) определяется автоматически: `Endpoint.mode_override` имеет приоритет над `Service.mode`.

---

## MCP сервер

Mockwave предоставляет MCP-сервер для AI-клиентов (Claude, Cursor и других). Это позволяет управлять сервисами
прямо из чата или IDE — без открытия административной панели.

**Точка подключения:** `http://localhost:8080/mcp` (требует аутентификацию)

**Доступные инструменты:**

| Tool              | Что делает                                |
|-------------------|-------------------------------------------|
| `ListServices`    | Список всех сервисов с режимом и статусом |
| `GetEndpoints`    | Эндпоинты конкретного сервиса             |
| `SwitchMode`      | Переключить сервис между mock и proxy     |
| `GetRequestLogs`  | Последние логи запросов для эндпоинта     |
| `GetMockResponse` | Прочитать настроенный мок-ответ           |

**Ресурсы (Resources):**

| URI                            | Содержимое                                   |
|--------------------------------|----------------------------------------------|
| `mockwave://services/{slug}`   | Полная конфигурация сервиса и его эндпоинтов |
| `mockwave://logs/{endpointId}` | Последние логи запросов                      |

---

## Agentic Development

Laravel's predictable structure and conventions make it ideal for AI coding agents like Claude Code, Cursor, and GitHub Copilot. Install [Laravel Boost](https://laravel.com/docs/ai) to supercharge your AI workflow:

```bash
composer require laravel/boost --dev

php artisan boost:install
```

Boost provides your agent 15+ tools and skills that help agents build Laravel applications while following best practices.

---

## Планировщик вебхуков

Mockwave умеет имитировать входящие вебхуки от внешних систем по расписанию.

Расписание настраивается через административную панель в формате cron-выражений:

```
┌─────────── минута (0-59)
│ ┌────────── час (0-23)
│ │ ┌───────── день месяца (1-31)
│ │ │ ┌──────── месяц (1-12)
│ │ │ │ ┌─────── день недели (0-6)
│ │ │ │ │
* * * * *
```

Пример: `*/15 * * * *` — каждые 15 минут.

---

## Административная панель

SPA построена на Inertia.js + React и включает следующие разделы:

- **Services** — добавление и управление сервисами
- **Endpoints** — настройка эндпоинтов, выбор режима mock/proxy
- **Mock Responses** — редактор тела ответа, заголовков, статуса и задержки
- **Scheduler** — управление вебхук-задачами и расписанием
- **Request Logs** — просмотр и фильтрация логов с full diff запрос/ответ

---

## Разработка

### Доступные команды (Make)

```bash
make up           # docker compose up -d
make install      # первый запуск: composer + key + migrate + seed + npm build
make migrate      # php artisan migrate
make fresh        # migrate:fresh --seed (сбрасывает данные)
make test         # php artisan test
make test-filter FILTER=GatewayTest
make npm-dev      # vite dev с HMR
make npm-build    # production build
make lint         # pint + eslint
make phpstan      # PHPStan статический анализ (level 6)
make cache-clear  # сброс всех кешей Laravel
make shell        # bash в app-контейнере
make logs         # tail всех контейнеров
```

### Контейнеры

| Сервис      | Образ              | Роль                | Порт (хост)                         |
|-------------|--------------------|---------------------|-------------------------------------|
| `app`       | Dockerfile `dev`   | PHP-FPM (Laravel)   | —                                   |
| `nginx`     | nginx:1.25-alpine  | Веб-сервер          | `APP_PORT` (default 8080)           |
| `postgres`  | postgres:16-alpine | База данных         | `DB_PORT_FORWARD` (default 5432)    |
| `redis`     | redis:7-alpine     | Кеш и очереди       | `REDIS_PORT_FORWARD` (default 6379) |
| `queue`     | Dockerfile `dev`   | `queue:work redis`  | —                                   |
| `scheduler` | Dockerfile `dev`   | loop `schedule:run` | —                                   |

---

## Production

```bash
# Сборка production-образа (multi-stage)
docker build -t mockwave:latest .

# Health-check эндпоинт (без авторизации)
curl http://your-domain.com/health
# → {"status": "ok", "timestamp": "..."}
```

Деплой на VPS через Dokploy — отдельная задача. Инфраструктурные файлы (`Dockerfile`, `.env.example`) подготовлены для
этого шага.

---

## Лицензия

MIT © 2024 Mockwave Contributors
