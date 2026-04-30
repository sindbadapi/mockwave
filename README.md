# 🌊 Mockwave

> Adaptive Mock Service — система для имитации и проксирования запросов к микросервисам.

Mockwave позволяет заменять реальные сервисы гибким мок-слоем с административной панелью, поддержкой расписаний и детальным логированием — без изменения кода клиентских приложений.

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
- [Планировщик вебхуков](#планировщик-вебхуков)
- [Административная панель](#административная-панель)
- [Разработка](#разработка)
- [Production](#production)
- [Роадмап](#роадмап)

---

## Возможности

| Функция | Описание |
|---|---|
| **Mock Mode** | Возврат заранее настроенных ответов (body, headers, status, delay) |
| **Proxy Mode** | Прозрачное проксирование запросов к реальному сервису |
| **Webhook Scheduler** | Имитация входящих вебхуков по cron-расписанию |
| **Request Logs** | Полное логирование всех входящих запросов и ответов |
| **Admin Panel** | SPA на Inertia + React для управления всей конфигурацией |
| **Multi-service** | Поддержка неограниченного числа сервисов и эндпоинтов |

---

## Стек технологий

**Backend**
- [Laravel 11](https://laravel.com/) — PHP-фреймворк
- [PostgreSQL 15](https://www.postgresql.org/) — основная база данных
- [Redis](https://redis.io/) — очереди и кеш
- [Guzzle HTTP](https://docs.guzzlephp.org/) — проксирование запросов

**Frontend**
- [Inertia.js](https://inertiajs.com/) — связующий слой между Laravel и React
- [React 18](https://react.dev/) — UI-компоненты
- [TypeScript](https://www.typescriptlang.org/) — типизация
- [Vite](https://vitejs.dev/) — сборка ассетов

**Инфраструктура**
- [Docker + Docker Compose](https://www.docker.com/) — локальная разработка
- [Dokploy](https://dokploy.com/) — деплой на VPS *(в роадмапе)*

---

## Архитектура

```
Client Request
      │
      ▼
┌─────────────────────┐
│  MockGatewayController │  ← единая точка входа
│  /{service}/{path}  │
└──────────┬──────────┘
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
    ▼
RequestLog (PostgreSQL)
```

Роутинг происходит по `service_slug` + `path`. Каждый сервис и каждый эндпоинт могут иметь собственный режим (`mock` / `proxy`), причём режим эндпоинта имеет приоритет над режимом сервиса.

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
```

---

## Структура проекта

```
mockwave/
├── app/
│   ├── Http/
│   │   ├── Controllers/
│   │   │   ├── Admin/              # CRUD для панели управления
│   │   │   └── MockGatewayController.php
│   │   ├── Requests/               # FormRequest-валидация
│   │   └── Resources/              # API Resources
│   ├── Services/
│   │   ├── Contracts/
│   │   │   ├── RequestHandlerInterface.php
│   │   ├── MockHandler.php
│   │   └── ProxyHandler.php
│   ├── Models/
│   │   ├── Service.php
│   │   ├── Endpoint.php
│   │   ├── MockResponse.php
│   │   ├── ScheduledWebhook.php
│   │   └── RequestLog.php
│   └── Console/
│       └── Commands/
│           └── DispatchWebhooksCommand.php
├── database/
│   └── migrations/
├── resources/
│   └── js/
│       ├── Pages/                  # Inertia-страницы (React)
│       └── Components/
├── routes/
│   ├── web.php                     # Admin SPA + gateway catch-all
│   └── api.php
├── docker/
│   ├── nginx/
│   ├── php/
│   └── scheduler/
├── docker-compose.yml
├── Dockerfile
└── .env.example
```

---

## Схема базы данных

```sql
services
  id, name, slug, base_url, description,
  mode (mock|proxy), is_active, timestamps

endpoints
  id, service_id, method, path, mode_override,
  proxy_url, mock_response_id, is_active, timestamps

mock_responses
  id, endpoint_id, status_code,
  body (jsonb), headers (jsonb), delay_ms, timestamps

scheduled_webhooks
  id, name, target_url, method,
  payload (jsonb), cron_expression, is_active, timestamps

request_logs
  id, endpoint_id, method, path,
  request_data (jsonb), response_data (jsonb),
  mode_used, duration_ms, created_at
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

Режим обработки (mock/proxy) определяется автоматически на основе конфигурации эндпоинта.

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

### Доступные команды

```bash
# Запуск контейнеров
docker compose up -d

# Логи приложения
docker compose logs -f app

# Artisan
docker compose exec app php artisan <command>

# Тесты
docker compose exec app php artisan test

# Сборка фронтенда (dev с hot-reload)
docker compose exec app npm run dev

# Линтинг
docker compose exec app npm run lint
docker compose exec app ./vendor/bin/pint
```

### Контейнеры

| Сервис | Описание | Порт |
|---|---|---|
| `app` | Laravel (PHP-FPM) | — |
| `nginx` | Веб-сервер | 8080 |
| `postgres` | База данных | 5432 |
| `redis` | Кеш и очереди | 6379 |
| `scheduler` | `schedule:run` каждую минуту | — |
| `queue` | `queue:work` | — |

---

## Production

```bash
# Сборка production-образа (multi-stage)
docker build -t mockwave:latest .

# Health-check эндпоинт (без авторизации)
curl http://your-domain.com/health
# → {"status": "ok", "timestamp": "..."}
```

Деплой на VPS через Dokploy — отдельная задача. Инфраструктурные файлы (`Dockerfile`, `.env.example`) подготовлены для этого шага.

---

## Роадмап

- [x] Базовая архитектура gateway (mock + proxy)
- [x] Административная панель (CRUD)
- [x] Webhook scheduler
- [x] Request logs
- [ ] Импорт/экспорт конфигурации (JSON/YAML)
- [ ] OpenAPI-импорт эндпоинтов из Swagger-схемы
- [ ] Статистика и графики по логам
- [ ] Webhooks с retry-механизмом
- [ ] Мультиюзерность + разграничение доступа (Teams)
- [ ] Деплой на VPS через Dokploy

---

## Лицензия

MIT © 2024 Mockwave Contributors
