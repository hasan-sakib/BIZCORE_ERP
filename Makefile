# =============================================================================
# BizCore ERP — Makefile
# Developer shortcuts for local Docker-based development
# =============================================================================

# ----------------------------------------------------------------
# Configuration
# ----------------------------------------------------------------
DOCKER_COMPOSE  := docker-compose
APP_CONTAINER   := app
MYSQL_CONTAINER := mysql

# Default compose file; override with: make up COMPOSE_FILE=docker-compose.prod.yml
COMPOSE_FILE ?= docker-compose.yml

# Colour helpers
GREEN  := \033[0;32m
YELLOW := \033[1;33m
CYAN   := \033[0;36m
RESET  := \033[0m

.DEFAULT_GOAL := help

# ----------------------------------------------------------------
# Help
# ----------------------------------------------------------------
.PHONY: help
help: ## Show this help message
	@echo ""
	@echo "$(CYAN)BizCore ERP — Developer Shortcuts$(RESET)"
	@echo "======================================"
	@grep -E '^[a-zA-Z_-]+:.*?## .*$$' $(MAKEFILE_LIST) \
		| sort \
		| awk 'BEGIN {FS = ":.*?## "}; {printf "  $(GREEN)%-18s$(RESET) %s\n", $$1, $$2}'
	@echo ""

# ----------------------------------------------------------------
# Docker lifecycle
# ----------------------------------------------------------------
.PHONY: up
up: ## Start all containers in detached mode
	@echo "$(YELLOW)Starting containers...$(RESET)"
	$(DOCKER_COMPOSE) -f $(COMPOSE_FILE) up -d
	@echo "$(GREEN)Containers started.$(RESET)"

.PHONY: down
down: ## Stop and remove containers (preserves volumes)
	@echo "$(YELLOW)Stopping containers...$(RESET)"
	$(DOCKER_COMPOSE) -f $(COMPOSE_FILE) down
	@echo "$(GREEN)Containers stopped.$(RESET)"

.PHONY: build
build: ## Build / rebuild Docker images
	@echo "$(YELLOW)Building images...$(RESET)"
	$(DOCKER_COMPOSE) -f $(COMPOSE_FILE) build --no-cache
	@echo "$(GREEN)Build complete.$(RESET)"

.PHONY: restart
restart: down up ## Restart all containers

.PHONY: logs
logs: ## Tail logs from all containers (Ctrl+C to exit)
	$(DOCKER_COMPOSE) -f $(COMPOSE_FILE) logs -f

.PHONY: ps
ps: ## List running containers
	$(DOCKER_COMPOSE) -f $(COMPOSE_FILE) ps

# ----------------------------------------------------------------
# Shell access
# ----------------------------------------------------------------
.PHONY: shell
shell: ## Open an interactive bash shell in the app container
	docker exec -it $(APP_CONTAINER) bash

.PHONY: shell-mysql
shell-mysql: ## Open a MySQL shell as root
	docker exec -it $(MYSQL_CONTAINER) mysql -u root -proot bizcore_erp

# ----------------------------------------------------------------
# Application
# ----------------------------------------------------------------
.PHONY: key
key: ## Generate a new APP_KEY and write it to .env
	@echo "$(YELLOW)Generating application key...$(RESET)"
	docker exec $(APP_CONTAINER) php -r "\
		require 'vendor/autoload.php'; \
		\$$key = 'base64:' . base64_encode(random_bytes(32)); \
		\$$env = file_get_contents('/var/www/html/.env'); \
		\$$env = preg_replace('/^APP_KEY=.*/m', 'APP_KEY=' . \$$key, \$$env); \
		file_put_contents('/var/www/html/.env', \$$env); \
		echo 'APP_KEY set to: ' . \$$key . PHP_EOL;"
	@echo "$(GREEN)Application key generated.$(RESET)"

.PHONY: migrate
migrate: ## Run database migrations
	@echo "$(YELLOW)Running migrations...$(RESET)"
	docker exec $(APP_CONTAINER) php database/migrate.php
	@echo "$(GREEN)Migrations complete.$(RESET)"

.PHONY: seed
seed: ## Run database seeders
	@echo "$(YELLOW)Running seeders...$(RESET)"
	docker exec $(APP_CONTAINER) php database/seed.php
	@echo "$(GREEN)Seeding complete.$(RESET)"

.PHONY: migrate-seed
migrate-seed: migrate seed ## Run migrations then seeders

# ----------------------------------------------------------------
# Code quality
# ----------------------------------------------------------------
.PHONY: test
test: ## Run PHPUnit test suite
	@echo "$(YELLOW)Running tests...$(RESET)"
	docker exec $(APP_CONTAINER) ./vendor/bin/phpunit --configuration phpunit.xml --testdox

.PHONY: test-coverage
test-coverage: ## Run PHPUnit with HTML coverage report
	@echo "$(YELLOW)Running tests with coverage...$(RESET)"
	docker exec $(APP_CONTAINER) ./vendor/bin/phpunit \
		--configuration phpunit.xml \
		--coverage-html storage/coverage/html \
		--coverage-clover storage/coverage/clover.xml
	@echo "$(GREEN)Coverage report: storage/coverage/html/index.html$(RESET)"

.PHONY: lint
lint: ## Run PHP_CodeSniffer (PSR-12)
	@echo "$(YELLOW)Running PHP_CodeSniffer...$(RESET)"
	docker exec $(APP_CONTAINER) ./vendor/bin/phpcs --standard=phpcs.xml

.PHONY: lint-fix
lint-fix: ## Automatically fix CodeSniffer violations
	@echo "$(YELLOW)Auto-fixing code style...$(RESET)"
	docker exec $(APP_CONTAINER) ./vendor/bin/phpcbf --standard=phpcs.xml
	@echo "$(GREEN)Auto-fix complete.$(RESET)"

.PHONY: analyse
analyse: ## Run PHPStan static analysis (level 8)
	@echo "$(YELLOW)Running PHPStan...$(RESET)"
	docker exec $(APP_CONTAINER) ./vendor/bin/phpstan analyse --configuration=phpstan.neon

.PHONY: check
check: lint analyse test ## Run all quality checks (lint + analyse + test)

# ----------------------------------------------------------------
# Composer
# ----------------------------------------------------------------
.PHONY: install
install: ## Install Composer dependencies
	@echo "$(YELLOW)Installing dependencies...$(RESET)"
	docker exec $(APP_CONTAINER) composer install --no-interaction --prefer-dist
	@echo "$(GREEN)Dependencies installed.$(RESET)"

.PHONY: update
update: ## Update Composer dependencies
	@echo "$(YELLOW)Updating dependencies...$(RESET)"
	docker exec $(APP_CONTAINER) composer update
	@echo "$(GREEN)Dependencies updated.$(RESET)"

# ----------------------------------------------------------------
# Database
# ----------------------------------------------------------------
.PHONY: db-backup
db-backup: ## Run a manual database backup
	@echo "$(YELLOW)Backing up database...$(RESET)"
	docker exec $(MYSQL_CONTAINER) /scripts/backup.sh
	@echo "$(GREEN)Backup complete.$(RESET)"

# ----------------------------------------------------------------
# Nuclear reset
# ----------------------------------------------------------------
.PHONY: fresh
fresh: ## Full reset: stop → remove volumes → build → start → migrate → seed
	@echo "$(YELLOW)Performing full environment reset...$(RESET)"
	$(DOCKER_COMPOSE) -f $(COMPOSE_FILE) down -v --remove-orphans
	$(DOCKER_COMPOSE) -f $(COMPOSE_FILE) build --no-cache
	$(DOCKER_COMPOSE) -f $(COMPOSE_FILE) up -d
	@echo "$(YELLOW)Waiting for services to be ready...$(RESET)"
	@sleep 10
	$(MAKE) migrate
	$(MAKE) seed
	@echo "$(GREEN)Environment is fresh and ready.$(RESET)"

# ----------------------------------------------------------------
# Environment setup
# ----------------------------------------------------------------
.PHONY: env
env: ## Copy .env.example to .env if .env does not exist
	@if [ ! -f .env ]; then \
		cp .env.example .env; \
		echo "$(GREEN).env created from .env.example$(RESET)"; \
	else \
		echo "$(YELLOW).env already exists — skipping.$(RESET)"; \
	fi
