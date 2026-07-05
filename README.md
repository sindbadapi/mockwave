# 🌊 Mockwave

> Adaptive Mock Service — система для имитации и проксирования запросов к микросервисам.

Mockwave позволяет зарегистрировать сервисы и их эндпоинты, возвращать настроенные mock-ответы или прозрачно
проксировать запросы в реальные upstream-сервисы. Управление выполняется через административную панель или MCP.

## Возможности

| Функция | Описание |
|---|---|
| **Mock Mode** | Настраиваемые body, headers, HTTP status и задержка ответа |
| **Proxy Mode** | Прозрачная передача запроса в реальный upstream |
| **Webhook Scheduler** | Отправка вебхуков по cron-расписанию |
| **Request Logs** | Логирование запросов и ответов после отправки ответа клиенту |
| **Admin Panel** | SPA для управления сервисами, эндпоинтами, моками и логами |
| **Roles** | Чтение доступно пользователям, изменения — только администраторам |
| **MCP Server** | Управление Mockwave из AI-клиентов через MCP |
| **Auth switches** | Независимое включение регистрации и восстановления пароля |

## Стек

- PHP 8.5, Laravel 13
- PostgreSQL 16
- Redis 7
- Inertia.js 3, React 19, TypeScript
- Vite 8, Tailwind CSS 4
- Laravel MCP
- Docker Compose
- Nginx для локальной разработки
- Caddy с автоматическим HTTPS для production

## Как обрабатывается запрос

```text
Client
  │
  ▼
/gateway/{service_slug}/{path}
  │
  ▼
MockGatewayController
  │
  ├── mock  ──► настроенный MockResponse
  │
  └── proxy ──► реальный upstream
  │
  ▼
defer() ──► RequestLog в PostgreSQL
```

Сервис задаёт основной режим `mock` или `proxy`. Значение `mode_override` конкретного эндпоинта имеет приоритет над
режимом сервиса.

## Локальный запуск

### Требования

- Docker 24+
- Docker Compose v2+
- Make — опционально

### Установка

```bash
git clone https://github.com/sindbadapi/mockwave.git
cd mockwave

cp .env.example .env
docker compose up -d
make install
```

Без Make:

```bash
docker compose exec app composer install
docker compose exec app php artisan key:generate
docker compose exec app php artisan migrate --seed
docker compose exec app npm install
docker compose exec app npm run build
```

Приложение будет доступно по адресу [http://localhost:8080](http://localhost:8080).

Начальный администратор создаётся seeder:

```text
Email:    admin@mockwave.local
Password: password
```

Перед публичным развёртыванием обязательно задайте собственные `ADMIN_EMAIL` и `ADMIN_PASSWORD`.

## Конфигурация

Полный список находится в [.env.example](.env.example). Основные переменные:

```dotenv
APP_NAME=Mockwave
APP_ENV=local
APP_KEY=
APP_DEBUG=true
APP_URL=http://localhost:8080

DB_CONNECTION=pgsql
DB_HOST=postgres
DB_PORT=5432
DB_DATABASE=mockwave
DB_USERNAME=mockwave
DB_PASSWORD=secret

REDIS_HOST=redis
REDIS_PORT=6379
QUEUE_CONNECTION=redis

GATEWAY_TIMEOUT_SECONDS=30
GATEWAY_LOG_REQUEST_BODY=true
GATEWAY_LOG_RESPONSE_BODY=true
GATEWAY_MAX_LOG_BODY_SIZE=65536

AUTH_REGISTRATION_ENABLED=false
AUTH_PASSWORD_RESET_ENABLED=false

ADMIN_EMAIL=admin@mockwave.local
ADMIN_PASSWORD=password
```

### Регистрация и восстановление пароля

Публичные auth-возможности управляются независимо:

```dotenv
# Разрешить создание новых аккаунтов через /register
AUTH_REGISTRATION_ENABLED=true

# Разрешить /forgot-password и /reset-password/*
AUTH_PASSWORD_RESET_ENABLED=true
```

По умолчанию обе настройки равны `false`: доступен только вход существующих пользователей. Отключённые страницы и
обработчики форм возвращают `404`, а ссылка восстановления не отображается на странице входа.

После изменения production-конфигурации пересоздайте контейнер приложения и обновите кеш:

```bash
docker compose --env-file .env.production -f compose.production.yml \
  up -d --no-build --force-recreate app

docker compose --env-file .env.production -f compose.production.yml \
  exec -T app php artisan optimize
```

Смена пароля авторизованного пользователя остаётся доступной независимо от этих переключателей:
`Profile → Update Password`.

## API Gateway

Единая публичная точка входа:

```text
{METHOD} /gateway/{service_slug}/{path?}
```

Примеры:

```bash
curl http://localhost:8080/gateway/bank-api/v1/accounts

curl -H "Authorization: Bearer test-token" \
  http://localhost:8080/gateway/payment-service/charge
```

Поддерживаются методы `GET`, `POST`, `PUT`, `PATCH`, `DELETE`, `HEAD`, `OPTIONS` и универсальный метод `ANY`.

## Административная панель

| Раздел | Назначение |
|---|---|
| Dashboard | Сводная информация |
| Services | Сервисы, upstream URL и основной режим |
| Endpoints | HTTP-методы, пути и переопределение режима |
| Mock Responses | Body, headers, status code и задержка |
| Scheduler | Вебхуки и cron-расписание |
| Request Logs | Фильтрация и просмотр запросов/ответов |
| Profile | Данные пользователя и смена пароля |

Все страницы требуют аутентификацию. Пользователь с ролью `user` имеет доступ на чтение, а мутации разрешены только
роли `admin`.

JSON API административной панели доступен под префиксом `/api/admin`.

## MCP

MCP endpoint поддерживает транспортные запросы `GET`, `POST` и `DELETE`:

```text
/mcp
```

Маршрут защищён middleware `auth:sanctum`.

### Доступ для агента

Создайте для агента отдельного пользователя с ролью `admin` и Sanctum-токеном:

```bash
php artisan mockwave:mcp-agent agent@example.com
```

В Docker production:

```bash
docker compose --env-file .env.production -f compose.production.yml \
  exec -T app php artisan mockwave:mcp-agent agent@example.com
```

Команда один раз выведет Bearer-токен. Он хранится в базе только в виде SHA-256 hash, поэтому скопируйте его сразу и
передавайте MCP-клиенту через заголовок:

```http
Authorization: Bearer 1|YOUR_TOKEN
```

Пример конфигурации MCP-клиента:

```json
{
  "mcpServers": {
    "mockwave": {
      "url": "https://mockwave.example.com/mcp",
      "headers": {
        "Authorization": "Bearer 1|YOUR_TOKEN"
      }
    }
  }
}
```

### Подключение Codex

Сначала примените миграции и создайте агента на production-сервере:

```bash
cd ~/mockwave

docker compose --env-file .env.production -f compose.production.yml \
  exec -T app php artisan migrate --force

docker compose --env-file .env.production -f compose.production.yml \
  exec -T app php artisan mockwave:mcp-agent agent@example.com
```

Команда покажет токен вида `1|xxxxxxxx`. На компьютере, где запускается Codex, добавьте сервер в
`~/.codex/config.toml`:

```toml
[mcp_servers.mockwave]
url = "https://mockwave.example.com/mcp"
bearer_token_env_var = "MOCKWAVE_MCP_TOKEN"
required = true
default_tools_approval_mode = "prompt"
```

Перед запуском Codex передайте токен через переменную окружения:

```bash
export MOCKWAVE_MCP_TOKEN='1|xxxxxxxx'
codex
```

Не записывайте сам токен в `config.toml`, проектный `.codex/config.toml` или Git. CLI и IDE-расширение Codex используют
общую конфигурацию MCP. Для проверки подключения выполните в Codex:

```text
/mcp
```

Сервер `mockwave` должен показать инструменты:

```text
list-services-tool
get-endpoints-tool
get-mock-response-tool
get-request-logs-tool
create-service-tool
create-endpoint-tool
upsert-mock-response-tool
switch-mode-tool
```

После подключения агенту можно дать задачу:

```text
Создай сервис catalog-api, добавь GET /v1/products
и настрой mock-ответ 200 с телом {"products": []}.
```

Подробнее о Streamable HTTP MCP и Bearer-аутентификации:
[документация Codex MCP](https://developers.openai.com/codex/mcp).

### Подключение Claude Code

Создайте `.mcp.json` в корне проекта:

```json
{
  "mcpServers": {
    "mockwave": {
      "type": "http",
      "url": "https://mockwave.example.com/mcp",
      "headers": {
        "Authorization": "Bearer ${MOCKWAVE_MCP_TOKEN}"
      }
    }
  }
}
```

Claude Code подставляет `${MOCKWAVE_MCP_TOKEN}` из окружения во время запуска. Перед запуском:

```bash
export MOCKWAVE_MCP_TOKEN='1|xxxxxxxx'
claude
```

Внутри Claude Code проверьте подключение:

```text
/mcp
```

Либо проверьте конфигурацию из терминала:

```bash
claude mcp list
claude mcp get mockwave
```

При первом открытии проекта Claude Code попросит подтвердить доверие к `.mcp.json`. Сам токен в этот файл не
записывается и не должен попадать в Git. Подробнее:
[документация Claude Code MCP](https://code.claude.com/docs/en/mcp).

Токен получает abilities `mcp:read` и `mcp:write`. Операции записи требуют одновременно ability `mcp:write` и роль
`admin`. Для ротации токена с отзывом всех ранее выданных токенов:

```bash
php artisan mockwave:mcp-agent agent@example.com --revoke-existing
```

Существующий обычный пользователь не повышается автоматически. Для осознанного повышения используйте `--promote`.
Токен является секретом: не добавляйте его в Git и не передавайте в логи.

### Tools

| Tool | Назначение |
|---|---|
| `ListServices` | Список сервисов |
| `GetEndpoints` | Эндпоинты выбранного сервиса |
| `SwitchMode` | Переключение режима mock/proxy |
| `GetRequestLogs` | Последние логи эндпоинта |
| `GetMockResponse` | Настроенный mock-ответ |
| `CreateService` | Создание сервиса, только для администратора |
| `CreateEndpoint` | Создание эндпоинта, только для администратора |
| `UpsertMockResponse` | Создание или обновление mock-ответа, только для администратора |

### Resources

| URI | Содержимое |
|---|---|
| `mockwave://services/{slug}` | Конфигурация сервиса и эндпоинтов |
| `mockwave://logs/{endpointId}` | Последние логи запросов |

## Планировщик вебхуков

Расписание задаётся cron-выражением:

```text
┌──────── минута (0–59)
│ ┌────── час (0–23)
│ │ ┌──── день месяца (1–31)
│ │ │ ┌── месяц (1–12)
│ │ │ │ ┌ день недели (0–6)
│ │ │ │ │
* * * * *
```

Например, `*/15 * * * *` — запуск каждые 15 минут.

Локально scheduler запускается обычным `docker compose`. В production worker и scheduler вынесены в профиль
`background`:

```bash
docker compose --env-file .env.production -f compose.production.yml \
  --profile background up -d
```

Без этого профиля основная панель, gateway, PostgreSQL и Redis продолжают работать, но фоновые очереди и расписание
не выполняются.

## Production

Production-стек описан в [compose.production.yml](compose.production.yml):

| Сервис | Назначение | Лимит памяти |
|---|---|---:|
| `app` | Laravel PHP-FPM | 256 MB |
| `web` | Caddy, статика, HTTPS | 64 MB |
| `postgres` | PostgreSQL 16 | 192 MB |
| `redis` | Redis 7 | 96 MB |
| `queue` | Laravel queue worker, профиль `background` | 160 MB |
| `scheduler` | Laravel scheduler, профиль `background` | 160 MB |

Caddy автоматически получает TLS-сертификат, перенаправляет HTTP на HTTPS и добавляет security headers.

### Первый деплой на VPS

Требуются Docker Compose, DNS A-запись домена на IP сервера и свободные порты `80/443`.

```bash
git clone https://github.com/sindbadapi/mockwave.git
cd mockwave

cp .env.example .env.production
chmod 600 .env.production
```

Минимальные production-значения:

```dotenv
APP_DOMAIN=mockwave.example.com
APP_ENV=production
APP_DEBUG=false
APP_URL=https://mockwave.example.com
APP_KEY=base64:PASTE_GENERATED_KEY_HERE

DB_CONNECTION=pgsql
DB_HOST=postgres
DB_PORT=5432
DB_DATABASE=mockwave
DB_USERNAME=mockwave
DB_PASSWORD=CHANGE_ME

REDIS_HOST=redis
REDIS_PORT=6379
CACHE_STORE=redis
SESSION_DRIVER=redis
QUEUE_CONNECTION=redis

AUTH_REGISTRATION_ENABLED=false
AUTH_PASSWORD_RESET_ENABLED=false

ADMIN_EMAIL=admin@mockwave.example.com
ADMIN_PASSWORD=CHANGE_ME
```

Сгенерировать `APP_KEY` можно командой `php artisan key:generate --show` в установленном проекте. Не добавляйте
`.env.production` в Git.

Сборка и запуск:

```bash
docker compose --env-file .env.production -f compose.production.yml build
docker compose --env-file .env.production -f compose.production.yml up -d

docker compose --env-file .env.production -f compose.production.yml \
  exec -T app php artisan migrate --force

docker compose --env-file .env.production -f compose.production.yml \
  exec -T app php artisan db:seed --force

docker compose --env-file .env.production -f compose.production.yml \
  exec -T app php artisan optimize
```

Проверка:

```bash
docker compose --env-file .env.production -f compose.production.yml ps
curl -I https://mockwave.example.com/login
curl -I https://mockwave.example.com/up
```

### Обновление

```bash
git pull --ff-only

docker compose --env-file .env.production -f compose.production.yml build app web
docker compose --env-file .env.production -f compose.production.yml up -d

docker compose --env-file .env.production -f compose.production.yml \
  exec -T app php artisan migrate --force

docker compose --env-file .env.production -f compose.production.yml \
  exec -T app php artisan optimize
```

На маломощном VPS production-образы удобнее собирать на локальной машине под платформу сервера, передавать через
`docker save`/`docker load`, а на сервере выполнять `up -d --no-build`.

Health endpoint Laravel: `/up`.

## Разработка

### Make

```bash
make up
make down
make install
make migrate
make fresh
make test
make test-filter FILTER=GatewayTest
make npm-dev
make npm-build
make lint
make phpstan
make cache-clear
make shell
make logs
```

### Проверки без Make

```bash
php artisan test
./vendor/bin/pint --test
npm run type-check
npm run lint
npm run build
```

### Локальные контейнеры

| Сервис | Назначение | Порт хоста |
|---|---|---|
| `app` | PHP-FPM, Node.js, Vite | `5173` |
| `nginx` | HTTP | `APP_PORT`, по умолчанию `8080` |
| `postgres` | PostgreSQL | `DB_PORT_FORWARD`, по умолчанию `5432` |
| `redis` | Redis | `REDIS_PORT_FORWARD`, по умолчанию `6379` |
| `queue` | Queue worker | — |
| `scheduler` | Laravel scheduler | — |

## Структура

```text
app/
├── Console/Commands/             # команды scheduler
├── Http/
│   ├── Controllers/              # web, auth и gateway
│   ├── Controllers/Admin/        # JSON API
│   ├── Middleware/               # admin и auth-feature проверки
│   └── Requests/Admin/           # валидация мутаций
├── Mcp/
│   ├── Servers/
│   ├── Tools/
│   └── Resources/
├── Models/
└── Services/                     # MockHandler и ProxyHandler

config/
├── gateway.php
└── mockwave.php                  # admin credentials и auth switches

docker/
├── caddy/Caddyfile
├── nginx/default.conf
├── php/
└── scheduler/

resources/js/
├── Components/
├── layouts/
├── pages/
└── types/

routes/
├── web.php
├── api.php
└── ai.php

compose.production.yml
docker-compose.yml
Dockerfile
```

## Лицензия

MIT © Mockwave Contributors
