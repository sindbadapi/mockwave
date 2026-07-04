# Mockwave — инструкция для агента

Ты — senior full-stack разработчик, работающий в репозитории **Mockwave**: системы имитации и проксирования запросов к
микросервисам. Используй этот документ как единственный источник правды о проекте перед тем, как писать любой код.

---

## 0. Скиллы — читай перед написанием кода

Проект содержит скиллы с правилами и паттернами. Читай нужный скилл **до** того, как писать код:

| Скилл                      | Путь                                               | Когда читать                                                                      |
|----------------------------|----------------------------------------------------|-----------------------------------------------------------------------------------|
| `laravel-best-practices`   | `.claude/skills/laravel-best-practices/SKILL.md`   | Любой PHP-код: контроллеры, модели, миграции, сервисы, хендлеры, шедулер, очереди |
| `mcp-development`          | `.claude/skills/mcp-development/SKILL.md`          | Любой код в `app/Mcp/` — инструменты, ресурсы, серверный класс, `routes/ai.php`   |
| `inertia-react-development`| `.claude/skills/inertia-react-development/SKILL.md`| React-страницы, формы, навигация, Inertia v3 features (deferred props, polling)   |
| `tailwindcss-development`  | `.claude/skills/tailwindcss-development/SKILL.md`  | Tailwind-классы, компоненты UI, responsive-вёрстка, badge/card/layout паттерны    |

Каждый скилл содержит `rules/` с детальными правилами по подтемам. Скилл указывает, в каком файле искать нужный раздел.

---

## 1. Суть проекта

Mockwave — прокси-слой между клиентами и реальными микросервисами. Разработчик регистрирует сервис, описывает его
эндпоинты, задаёт моки — и может отправлять запросы на gateway-URL вместо реального адреса. Ничего менять в клиентском
коде не нужно.

**Два режима на уровне каждого эндпоинта:**

- `mock` — вернуть заранее настроенный ответ (body, headers, status, delay)
- `proxy` — прозрачно переслать запрос на реальный upstream-сервис

Режим эндпоинта (`mode_override`) имеет приоритет над режимом сервиса (`mode`).

---

## 2. Стек

| Слой                 | Технология                                          |
|----------------------|-----------------------------------------------------|
| Backend              | Laravel 13, PHP 8.5                                 |
| База данных          | PostgreSQL 16 (JSONB для body/headers/payload)      |
| Очереди / кеш        | Redis 7                                             |
| HTTP-клиент          | Laravel Http (обёртка Guzzle) — **не сырой Guzzle** |
| Frontend             | Node 24 + Inertia.js v3 + React 19 + TypeScript     |
| Сборка               | Vite 8 + Tailwind CSS 4                             |
| MCP                  | Laravel 13 MCP module (`routes/ai.php`)             |
| Локальная разработка | Docker Compose                                      |
| Production-образ     | Multi-stage Dockerfile (node → php)                 |
| Деплой               | Dokploy/VPS (вынесен в отдельную задачу)            |

> **Http vs Guzzle:** `ProxyHandler` использует `Http::` фасад (Laravel Http Client), а не `new GuzzleHttp\Client()`.
> Это обязательно — иначе `Http::fake()` в тестах не перехватит вызовы, и тест будет ходить в реальный сервис.

---

## 3. Структура репозитория

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
│   │   ├── Middleware/
│   │   │   └── HandleInertiaRequests.php
│   │   ├── Requests/Admin/               # FormRequest-классы (Store*/Update*)
│   │   └── Resources/                    # API Resources (JSON-ответы)
│   ├── Mcp/
│   │   ├── Servers/
│   │   │   └── MockwaveServer.php        # регистрирует tools + resources
│   │   ├── Tools/
│   │   │   ├── ListServicesTool.php
│   │   │   ├── GetEndpointsTool.php
│   │   │   ├── SwitchModeTool.php        # переключает mock/proxy, сбрасывает кеш
│   │   │   ├── GetRequestLogsTool.php
│   │   │   └── GetMockResponseTool.php
│   │   └── Resources/
│   │       ├── ServiceConfigResource.php # URI: mockwave://services/{slug}
│   │       └── RequestLogResource.php    # URI: mockwave://logs/{endpointId}
│   ├── Models/
│   │   ├── Service.php
│   │   ├── Endpoint.php          # метод resolvedMode() — приоритет override над service.mode
│   │   ├── MockResponse.php
│   │   ├── ScheduledWebhook.php
│   │   └── RequestLog.php        # append-only, $timestamps = false
│   ├── Providers/
│   │   └── AppServiceProvider.php  # bind RequestHandlerInterface, Http macros
│   └── Services/
│       ├── Contracts/RequestHandlerInterface.php
│       ├── MockHandler.php         # usleep(delay_ms * 1000), json_encode body
│       └── ProxyHandler.php        # Http::, hop-by-hop headers фильтруются
├── bootstrap/app.php               # scheduler: everyMinute() → DispatchWebhooksCommand
├── config/gateway.php              # timeout_seconds, log_request_body, max_log_body_size
├── database/
│   ├── migrations/                 # 6 файлов, префикс 2024_01_01_00000{0-5}_
│   └── seeders/DatabaseSeeder.php  # admin user + demo Bank API + demo Webhook
├── docker/
│   ├── nginx/default.conf
│   ├── php/php.ini + opcache.ini
│   └── scheduler/scheduler.sh      # loop: schedule:run каждые 60 сек
├── docker-compose.yml              # сервисы: app, nginx, postgres, redis, queue, scheduler
├── Dockerfile                      # stages: node_builder, php_base, dev, production
├── resources/
│   ├── css/app.css                 # Tailwind + badge-* классы + .json-viewer
│   ├── js/
│   │   ├── app.tsx                 # Inertia entry point
│   │   ├── bootstrap.ts            # axios defaults
│   │   ├── types/index.ts          # все TS-типы: Service, Endpoint, MockResponse, ...
│   │   ├── Components/layout/
│   │   │   └── AppLayout.tsx       # сайдбар + flash-сообщения
│   │   └── Pages/
│   │       ├── Dashboard.tsx       # stats карточки + quick start
│   │       ├── Services/Index.tsx  # полный CRUD с модальным окном
│   │       └── Logs/Index.tsx      # таблица с expand-строками
│   └── views/app.blade.php         # Blade-шаблон для Inertia
├── routes/
│   ├── web.php                     # /health, admin SPA, auth, gateway catch-all
│   ├── api.php                     # /api/admin/* (auth middleware)
│   └── ai.php                      # MCP: Mcp::web('/mcp', MockwaveServer::class)
├── .env.example
├── composer.json
├── package.json
├── tailwind.config.js
├── tsconfig.json
└── vite.config.ts
```

---

## 4. База данных

### Схема (PostgreSQL)

```sql
-- Пользователи (admin panel auth)
users: id, name, email, password, remember_token, timestamps

-- Зарегистрированные сервисы
services: id, name, slug (unique), base_url, description,
          mode enum('mock','proxy') default 'mock',
          is_active bool, timestamps

-- Эндпоинты сервиса
endpoints: id, service_id FK→services,
           method ('GET'|'POST'|'PUT'|'PATCH'|'DELETE'|'HEAD'|'OPTIONS'|'ANY'),
           path (starts with /),
           mode_override enum('mock','proxy') NULLABLE,
           proxy_url NULLABLE,
           is_active bool, timestamps
           UNIQUE(service_id, method, path)

-- Мок-ответы (1:1 с endpoint)
mock_responses: id, endpoint_id FK→endpoints,
                status_code smallint default 200,
                body jsonb NULLABLE,
                headers jsonb NULLABLE,
                delay_ms int default 0, timestamps

-- Задачи планировщика
scheduled_webhooks: id, name, target_url, method,
                    payload jsonb NULLABLE, headers jsonb NULLABLE,
                    cron_expression, is_active bool,
                    last_run_at timestamp NULLABLE, timestamps

-- Лог запросов (append-only, нет updated_at)
request_logs: id, endpoint_id FK NULLABLE→endpoints,
              method, path,
              request_data jsonb  -- {headers, query, body}
              response_data jsonb -- {status, headers, body}
              mode_used enum('mock','proxy','not_found'),
              duration_ms int, created_at (index)
```

### JSONB-поля в моделях

Следующие поля кастятся как `'array'` в Eloquent и хранятся как JSONB в PostgreSQL:

- `MockResponse`: `body`, `headers`
- `ScheduledWebhook`: `payload`, `headers`
- `RequestLog`: `request_data`, `response_data`

---

## 5. Gateway — как работает роутинг

```
{METHOD} /gateway/{service_slug}/{path?}
```

`MockGatewayController::handle()` делает:

1. Ищет `Service` по `slug` + `is_active = true`
2. Ищет `Endpoint` по `service_id` + `is_active = true` + `path = /{path}`  
   — сначала точный метод, потом `ANY`
3. Вызывает `Endpoint::resolvedMode()` → `mode_override ?? service->mode`
4. Делегирует в `MockHandler` или `ProxyHandler`
5. Записывает `RequestLog` через `defer()` (после отправки ответа — не блокирует)
6. Возвращает response

**Формат пути в БД:** всегда начинается с `/`. При запросе `/gateway/bank-api/v1/accounts` ищется path = `/v1/accounts`.

---

## 6. MCP сервер

MCP (`routes/ai.php`) открывает Mockwave для AI-клиентов (Claude, Cursor). Все маршруты закрыты `auth:sanctum`.

```
GET /mcp   ← точка подключения MCP-клиента
```

**Инструменты (Tools):** ListServices, GetEndpoints, SwitchMode, GetRequestLogs, GetMockResponse  
**Ресурсы (Resources):** `mockwave://services/{slug}`, `mockwave://logs/{endpointId}`

При работе с любым кодом в `app/Mcp/` — читай скилл `mcp-development` (раздел 0).

---

## 7. API эндпоинты (admin)

Все маршруты требуют `auth` middleware. Мутации (`POST`, `PUT`, `PATCH`, `DELETE`) дополнительно требуют роль `admin`.
Префикс: `/api/admin/`.

| Метод  | URL                                  | Описание                                         |
|--------|--------------------------------------|--------------------------------------------------|
| GET    | `/api/admin/services`                | Список сервисов (пагинация 25)                   |
| POST   | `/api/admin/services`                | Создать сервис                                   |
| GET    | `/api/admin/services/{id}`           | Получить сервис                                  |
| PUT    | `/api/admin/services/{id}`           | Обновить сервис                                  |
| DELETE | `/api/admin/services/{id}`           | Удалить сервис                                   |
| GET    | `/api/admin/endpoints?service_id=`   | Список эндпоинтов                                |
| POST   | `/api/admin/endpoints`               | Создать эндпоинт                                 |
| PUT    | `/api/admin/endpoints/{id}`          | Обновить эндпоинт                                |
| DELETE | `/api/admin/endpoints/{id}`          | Удалить эндпоинт                                 |
| POST   | `/api/admin/mock-responses`          | Upsert мок-ответа (по endpoint_id)               |
| PUT    | `/api/admin/mock-responses/{id}`     | Обновить мок-ответ                               |
| DELETE | `/api/admin/mock-responses/{id}`     | Удалить мок-ответ                                |
| GET    | `/api/admin/scheduled-webhooks`      | Список вебхуков                                  |
| POST   | `/api/admin/scheduled-webhooks`      | Создать вебхук                                   |
| PUT    | `/api/admin/scheduled-webhooks/{id}` | Обновить вебхук                                  |
| DELETE | `/api/admin/scheduled-webhooks/{id}` | Удалить вебхук                                   |
| GET    | `/api/admin/logs`                    | Список логов (фильтры: service_id, mode, method) |
| GET    | `/api/admin/logs/{id}`               | Детали лога                                      |
| DELETE | `/api/admin/logs`                    | Очистить все логи                                |

---

## 8. Inertia SPA — страницы

Страницы живут в `resources/js/Pages/`. Inertia резолвит их по имени из Laravel-контроллеров.

| URL               | Компонент                 | Статус          |
|-------------------|---------------------------|-----------------|
| `/`               | `Dashboard.tsx`           | ✅ готово        |
| `/services`       | `Services/Index.tsx`      | ✅ готово (CRUD) |
| `/endpoints`      | `Endpoints/Index.tsx`     | ✅ готово        |
| `/mock-responses` | `MockResponses/Index.tsx` | ✅ готово        |
| `/scheduler`      | `Scheduler/Index.tsx`     | ✅ готово        |
| `/logs`           | `Logs/Index.tsx`          | ✅ готово        |

Данные в Inertia-страницы передаются через `Inertia::render('PageName', [...props])` из Laravel-контроллера. Для
SPA-данных с фильтрами и пагинацией — через AJAX к `/api/admin/*` прямо из React.

---

## 9. Shared props (Inertia)

`HandleInertiaRequests::share()` передаёт на каждую страницу:

```ts
interface SharedProps {
    auth: {
        user: {
            id: number
            name: string
            email: string
            email_verified_at: string | null
            role: 'admin' | 'user'
        } | null
    }
    flash: {
        success: string | null
        error: string | null
    }
}
```

Используй `usePage<SharedProps>()` для доступа к этим данным.

---

## 10. TypeScript-типы

Все типы централизованы в `resources/js/types/index.ts`. Импортируй только оттуда:

```ts
import type {Service, Endpoint, MockResponse, ScheduledWebhook, RequestLog, PaginatedResponse} from '@/types'
```

Алиас `@/` настроен в `vite.config.ts` и `tsconfig.json` → `resources/js/`.

---

## 11. Tailwind — соглашения

Проект использует **Tailwind v4 через `@tailwindcss/vite`** (Vite-native плагин):
- `vite.config.ts` — плагин `tailwindcss()` из `@tailwindcss/vite`
- `resources/css/app.css` — `@import "tailwindcss"` + `@config "../../tailwind.config.js"`
- `tailwind.config.js` — кастомные темы (`wave-*` цвета, шрифты), плагины (`@tailwindcss/forms`)
- `postcss.config.js` — только `autoprefixer` (tailwindcss убран)

**Запланированные CSS-классы** (нужно добавить в `tailwind.config.js` → `theme.extend`):
- Цветовая палитра бренда: `wave-50` … `wave-950` (синяя)
- `.badge-mock` — синий бейдж для режима mock
- `.badge-proxy` — фиолетовый бейдж для режима proxy
- `.badge-active` / `.badge-inactive` — зелёный / серый
- `.json-viewer` — тёмный моноширинный блок для JSON/body

> ⬜ TODO: Кастомные классы нужно создать в `tailwind.config.js` и/или `app.css`.

---

## 12. Docker — контейнеры

| Сервис      | Образ              | Роль                | Порт (хост)                         |
|-------------|--------------------|---------------------|-------------------------------------|
| `app`       | Dockerfile `dev`   | PHP-FPM (Laravel)   | —                                   |
| `nginx`     | nginx:1.25-alpine  | Веб-сервер          | `APP_PORT` (default 8080)           |
| `postgres`  | postgres:16-alpine | БД                  | `DB_PORT_FORWARD` (default 5432)    |
| `redis`     | redis:7-alpine     | Кеш + очереди       | `REDIS_PORT_FORWARD` (default 6379) |
| `queue`     | Dockerfile `dev`   | `queue:work redis`  | —                                   |
| `scheduler` | Dockerfile `dev`   | loop `schedule:run` | —                                   |

**Важно:** `app`, `queue` и `scheduler` используют один и тот же образ (`target: dev`), монтируют весь репозиторий как
volume.

---

## 13. Ключевые переменные окружения

```dotenv
# Gateway
GATEWAY_TIMEOUT_SECONDS=30        # таймаут proxy-запросов
GATEWAY_LOG_REQUEST_BODY=true     # логировать тело запросов
GATEWAY_LOG_RESPONSE_BODY=true    # логировать тело ответов
GATEWAY_MAX_LOG_BODY_SIZE=65536   # макс. размер тела в логе (байт), 0 = без лимита

# Seeder
ADMIN_EMAIL=admin@mockwave.local
ADMIN_PASSWORD=password
```

Полный список в `.env.example`.

---

## 14. Команды разработки

```bash
# Запуск
make up           # docker compose up -d
make install      # первый запуск: composer + key + migrate + seed + npm build

# Laravel
make migrate      # php artisan migrate
make fresh        # migrate:fresh --seed (сбрасывает данные)
make tinker       # php artisan tinker

# Тесты
make test         # php artisan test
make test-filter FILTER=GatewayTest

# Frontend
make npm-dev      # vite dev с HMR
make npm-build    # production build

# Качество кода
make lint         # pint + eslint
make phpstan      # PHPStan статический анализ (level 6)
make cache-clear  # сброс всех кешей Laravel

# Shell в контейнере
make shell        # bash в app-контейнере
make logs         # tail всех контейнеров
```

---

## 15. Что ещё не реализовано (роадмап)

Не реализовано из роадмапа:

- Импорт/экспорт конфигурации (JSON/YAML)
- OpenAPI-импорт эндпоинтов из Swagger
- Статистика и графики по логам
- Retry-механизм для вебхуков
- Мультиюзерность (Teams)
- Деплой через Dokploy

---

## 16. Правила для агента

### Перед написанием кода

1. **Прочитай нужный скилл** (раздел 0) — не пиши PHP или MCP-код без него.
2. Сверяйся с этим документом — не пиши по памяти о структуре проекта.
3. Используй **Laravel Boost `search-docs`** для актуальной документации по установленным пакетам — это MCP-инструмент
   проекта, возвращает версионно-точные результаты. Fallback — Context7 (`/laravel/docs/__branch__13.x`).
4. Если задача касается нового пакета — сначала `composer require` / `npm install`, потом код.

### Соглашения кода

- **PHP:** FormRequest для валидации, Resource для JSON-ответов, интерфейсы для сервисов. Миграции — не правь схему
  вручную.
- **TypeScript:** функциональные компоненты, типы только из `@/types`, `@/` алиас вместо относительных путей.
- **Имена файлов:** PHP → PascalCase, TS-компоненты → PascalCase, страницы → `Pages/EntityName/Index.tsx`.
- **JSONB-поля** в моделях всегда кастятся как `'array'`.
- **Http client:** всегда `Http::` фасад в `ProxyHandler` и MCP Tools — никогда `new GuzzleHttp\Client()`.

### Структурные решения

- Не добавляй конфиги в файлы — всё в БД (таблицы `services`, `endpoints`, `mock_responses`).
- Не меняй `MockGatewayController::handle()` без понимания порядка операций (resolve → match → delegate → log).
- `RequestLog` — append-only. Никогда не добавляй `updated_at`. Запись через `defer()` после ответа.
- Новый хендлер (не Mock и не Proxy) — реализуй `RequestHandlerInterface`, зарегистрируй в `AppServiceProvider`.
- MCP Tools с деструктивными операциями (SwitchMode) — добавляй `shouldRegister()` с проверкой роли.

### Проверки перед завершением задачи

Выполнить все три без исключений — если есть ошибки, исправить до отчёта:

```bash
make lint       # pint (PHP) + eslint (TS) — форматирование и линтинг
make phpstan    # PHPStan level 6 — статический анализ
make test       # PHPUnit — тесты
```

- **`make lint`** — запускай после каждого изменения PHP или TS файлов
- **`make phpstan`** — запускай после изменений в моделях, сервисах, контроллерах
- **`make test`** — запускай после любых изменений логики; используй `make test-filter FILTER=ClassName` для конкретного теста

### При неопределённости

- Если задача затрагивает деплой/Dokploy — напомни, что это отдельная задача. Фокусируйся на подготовке (Dockerfile,
  env).
- Если задача крупная — предложи декомпозицию и начни с первого шага.
- Если в репозитории уже есть похожий код — не создавай дубликат, расширяй существующий.

===

<laravel-boost-guidelines>
=== foundation rules ===

# Laravel Boost Guidelines

The Laravel Boost guidelines are specifically curated by Laravel maintainers for this application. These guidelines should be followed closely to ensure the best experience when building Laravel applications.

## Foundational Context

This application is a Laravel application and its main Laravel ecosystems package & versions are below. You are an expert with them all. Ensure you abide by these specific packages & versions.

- php - 8.5
- inertiajs/inertia-laravel (INERTIA_LARAVEL) - v3
- laravel/framework (LARAVEL) - v13
- laravel/prompts (PROMPTS) - v0
- laravel/sanctum (SANCTUM) - v4
- tightenco/ziggy (ZIGGY) - v2
- laravel/boost (BOOST) - v2
- laravel/breeze (BREEZE) - v2
- laravel/mcp (MCP) - v0
- laravel/pint (PINT) - v1
- laravel/sail (SAIL) - v1
- phpunit/phpunit (PHPUNIT) - v13
- larastan/larastan (LARASTAN) - v3
- @inertiajs/react (INERTIA_REACT) - v3
- react (REACT) - v19
- eslint (ESLINT) - v9
- tailwindcss (TAILWINDCSS) - v4

## Skills Activation

This project has domain-specific skills available in `**/skills/**`. You MUST activate the relevant skill whenever you work in that domain—don't wait until you're stuck.

## Conventions

- You must follow all existing code conventions used in this application. When creating or editing a file, check sibling files for the correct structure, approach, and naming.
- Use descriptive names for variables and methods. For example, `isRegisteredForDiscounts`, not `discount()`.
- Check for existing components to reuse before writing a new one.

## Verification Scripts

- Do not create verification scripts or tinker when tests cover that functionality and prove they work. Unit and feature tests are more important.

## Application Structure & Architecture

- Stick to existing directory structure; don't create new base folders without approval.
- Do not change the application's dependencies without approval.

## Frontend Bundling

- If the user doesn't see a frontend change reflected in the UI, it could mean they need to run `npm run build`, `npm run dev`, or `composer run dev`. Ask them.

## Documentation Files

- You must only create documentation files if explicitly requested by the user.

## Replies

- Be concise in your explanations - focus on what's important rather than explaining obvious details.

=== boost rules ===

# Laravel Boost

## Tools

- Laravel Boost is an MCP server with tools designed specifically for this application. Prefer Boost tools over manual alternatives like shell commands or file reads.
- Use `database-query` to run read-only queries against the database instead of writing raw SQL in tinker.
- Use `database-schema` to inspect table structure before writing migrations or models.
- Use `get-absolute-url` to resolve the correct scheme, domain, and port for project URLs. Always use this before sharing a URL with the user.
- Use `browser-logs` to read browser logs, errors, and exceptions. Only recent logs are useful, ignore old entries.

## Searching Documentation (IMPORTANT)

- Always use `search-docs` before making code changes. Do not skip this step. It returns version-specific docs based on installed packages automatically.
- Pass a `packages` array to scope results when you know which packages are relevant.
- Use multiple broad, topic-based queries: `['rate limiting', 'routing rate limiting', 'routing']`. Expect the most relevant results first.
- Do not add package names to queries because package info is already shared. Use `test resource table`, not `filament 4 test resource table`.

### Search Syntax

1. Use words for auto-stemmed AND logic: `rate limit` matches both "rate" AND "limit".
2. Use `"quoted phrases"` for exact position matching: `"infinite scroll"` requires adjacent words in order.
3. Combine words and phrases for mixed queries: `middleware "rate limit"`.
4. Use multiple queries for OR logic: `queries=["authentication", "middleware"]`.

## Artisan

- Run Artisan commands directly via the command line (e.g., `php artisan route:list`). Use `php artisan list` to discover available commands and `php artisan [command] --help` to check parameters.
- Inspect routes with `php artisan route:list`. Filter with: `--method=GET`, `--name=users`, `--path=api`, `--except-vendor`, `--only-vendor`.
- Read configuration values using dot notation: `php artisan config:show app.name`, `php artisan config:show database.default`. Or read config files directly from the `config/` directory.
- To check environment variables, read the `.env` file directly.

## Tinker

- Execute PHP in app context for debugging and testing code. Do not create models without user approval, prefer tests with factories instead. Prefer existing Artisan commands over custom tinker code.
- Always use single quotes to prevent shell expansion: `php artisan tinker --execute 'Your::code();'`
  - Double quotes for PHP strings inside: `php artisan tinker --execute 'User::where("active", true)->count();'`

=== php rules ===

# PHP

- Always use curly braces for control structures, even for single-line bodies.
- Use PHP 8 constructor property promotion: `public function __construct(public GitHub $github) { }`. Do not leave empty zero-parameter `__construct()` methods unless the constructor is private.
- Use explicit return type declarations and type hints for all method parameters: `function isAccessible(User $user, ?string $path = null): bool`
- Use TitleCase for Enum keys: `FavoritePerson`, `BestLake`, `Monthly`.
- Prefer PHPDoc blocks over inline comments. Only add inline comments for exceptionally complex logic.
- Use array shape type definitions in PHPDoc blocks.

=== deployments rules ===

# Deployment

- Laravel can be deployed using [Laravel Cloud](https://cloud.laravel.com/), which is the fastest way to deploy and scale production Laravel applications.

=== tests rules ===

# Test Enforcement

- Every change must be programmatically tested. Write a new test or update an existing test, then run the affected tests to make sure they pass.
- Run the minimum number of tests needed to ensure code quality and speed. Use `php artisan test --compact` with a specific filename or filter.

=== inertia-laravel/core rules ===

# Inertia

- Inertia creates fully client-side rendered SPAs without modern SPA complexity, leveraging existing server-side patterns.
- Components live in `resources/js/Pages` (unless specified in `vite.config.js`). Use `Inertia::render()` for server-side routing instead of Blade views.
- ALWAYS use `search-docs` tool for version-specific Inertia documentation and updated code examples.
- IMPORTANT: Activate `inertia-react-development` when working with Inertia client-side patterns.

# Inertia v3

- Use all Inertia features from v1, v2, and v3. Check the documentation before making changes to ensure the correct approach.
- New features: deferred props, infinite scroll, merging props, polling, prefetching, once props, flash data.
- When using deferred props, add an empty state with a pulsing or animated skeleton.

=== laravel/core rules ===

# Do Things the Laravel Way

- Use `php artisan make:` commands to create new files (i.e. migrations, controllers, models, etc.). You can list available Artisan commands using `php artisan list` and check their parameters with `php artisan [command] --help`.
- If you're creating a generic PHP class, use `php artisan make:class`.
- Pass `--no-interaction` to all Artisan commands to ensure they work without user input. You should also pass the correct `--options` to ensure correct behavior.

### Model Creation

- When creating new models, create useful factories and seeders for them too. Ask the user if they need any other things, using `php artisan make:model --help` to check the available options.

## APIs & Eloquent Resources

- For APIs, default to using Eloquent API Resources and API versioning unless existing API routes do not, then you should follow existing application convention.

## URL Generation

- When generating links to other pages, prefer named routes and the `route()` function.

## Testing

- When creating models for tests, use the factories for the models. Check if the factory has custom states that can be used before manually setting up the model.
- Faker: Use methods such as `$this->faker->word()` or `fake()->randomDigit()`. Follow existing conventions whether to use `$this->faker` or `fake()`.
- When creating tests, make use of `php artisan make:test [options] {name}` to create a feature test, and pass `--unit` to create a unit test. Most tests should be feature tests.

## Vite Error

- If you receive an "Illuminate\Foundation\ViteException: Unable to locate file in Vite manifest" error, you can run `npm run build` or ask the user to run `npm run dev` or `composer run dev`.

=== pint/core rules ===

# Laravel Pint Code Formatter

- If you have modified any PHP files, you must run `vendor/bin/pint --dirty --format agent` before finalizing changes to ensure your code matches the project's expected style.
- Do not run `vendor/bin/pint --test --format agent`, simply run `vendor/bin/pint --format agent` to fix any formatting issues.

=== phpunit/core rules ===

# PHPUnit

- This application uses PHPUnit for testing. All tests must be written as PHPUnit classes. Use `php artisan make:test --phpunit {name}` to create a new test.
- If you see a test using "Pest", convert it to PHPUnit.
- Every time a test has been updated, run that singular test.
- When the tests relating to your feature are passing, ask the user if they would like to also run the entire test suite to make sure everything is still passing.
- Tests should cover all happy paths, failure paths, and edge cases.
- You must not remove any tests or test files from the tests directory without approval. These are not temporary or helper files; these are core to the application.

## Running Tests

- Run the minimal number of tests, using an appropriate filter, before finalizing.
- To run all tests: `php artisan test --compact`.
- To run all tests in a file: `php artisan test --compact tests/Feature/ExampleTest.php`.
- To filter on a particular test name: `php artisan test --compact --filter=testName` (recommended after making a change to a related file).

=== inertia-react/core rules ===

# Inertia + React

- IMPORTANT: Activate `inertia-react-development` when working with Inertia React client-side patterns.

</laravel-boost-guidelines>
