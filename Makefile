# ─────────────────────────────────────────────────────────────────────────────
# Mockwave — Makefile
# Convenience wrappers around docker compose commands.
# ─────────────────────────────────────────────────────────────────────────────

DC      = docker compose
ARTISAN = $(DC) exec app php artisan

.PHONY: help up down restart build logs shell \
        install key migrate seed fresh test lint phpstan \
        npm-dev npm-build queue-work

help: ## Show this help
	@grep -E '^[a-zA-Z_-]+:.*?## .*$$' $(MAKEFILE_LIST) | \
		awk 'BEGIN {FS = ":.*?## "}; {printf "  \033[36m%-20s\033[0m %s\n", $$1, $$2}'

# ── Docker ────────────────────────────────────────────────────────────────────
up: ## Start all containers in background
	$(DC) up -d

down: ## Stop and remove containers
	$(DC) down

restart: ## Restart all containers
	$(DC) restart

build: ## Rebuild images (no cache)
	$(DC) build --no-cache

logs: ## Tail logs from all containers
	$(DC) logs -f

logs-app: ## Tail app container logs only
	$(DC) logs -f app

shell: ## Open bash shell in app container
	$(DC) exec app bash

# ── Laravel bootstrap ─────────────────────────────────────────────────────────
install: ## Full first-time setup (composer + key + migrate + seed + npm)
	$(DC) exec app composer install
	$(DC) exec app php artisan key:generate
	$(ARTISAN) migrate --seed
	$(DC) exec app npm install
	$(DC) exec app npm run build
	@echo "\n✅  Mockwave is ready at http://localhost:$${APP_PORT:-8080}"
	@echo "    Login: admin@mockwave.local / password"

key: ## Generate application key
	$(ARTISAN) key:generate

migrate: ## Run database migrations
	$(ARTISAN) migrate

seed: ## Run database seeders
	$(ARTISAN) db:seed

fresh: ## Drop all tables and re-migrate + seed
	$(ARTISAN) migrate:fresh --seed

# ── Testing ───────────────────────────────────────────────────────────────────
test: ## Run PHPUnit test suite
	$(DC) exec -e APP_ENV=testing app php artisan test

test-filter: ## Run tests matching FILTER=<name>
	$(DC) exec -e APP_ENV=testing app php artisan test --filter=$(FILTER)

# ── Code quality ──────────────────────────────────────────────────────────────
lint: ## Run Laravel Pint (PHP) + ESLint (JS/TS)
	$(DC) exec app ./vendor/bin/pint
	$(DC) exec app npm run lint

phpstan: ## Run PHPStan static analysis (level 6)
	$(DC) exec app ./vendor/bin/phpstan analyse --memory-limit=256M

# ── Frontend ──────────────────────────────────────────────────────────────────
npm-dev: ## Start Vite dev server with HMR
	$(DC) exec app npm run dev

npm-build: ## Build production assets
	$(DC) exec app npm run build

# ── Utilities ─────────────────────────────────────────────────────────────────
cache-clear: ## Clear all Laravel caches
	$(ARTISAN) cache:clear
	$(ARTISAN) config:clear
	$(ARTISAN) route:clear
	$(ARTISAN) view:clear

tinker: ## Open Laravel Tinker REPL
	$(ARTISAN) tinker
