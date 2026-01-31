# P-Commerce Makefile
# Convenience commands for development and deployment

.PHONY: help build up down restart logs shell migrate fresh seed test lint dev prod

# Default target
help:
	@echo "P-Commerce Docker Commands"
	@echo ""
	@echo "Development:"
	@echo "  make dev          - Start development environment"
	@echo "  make up           - Start all containers"
	@echo "  make down         - Stop all containers"
	@echo "  make restart      - Restart all containers"
	@echo "  make logs         - View container logs"
	@echo "  make shell        - Open shell in app container"
	@echo ""
	@echo "Database:"
	@echo "  make migrate      - Run database migrations"
	@echo "  make fresh        - Fresh migration with seeders"
	@echo "  make seed         - Run database seeders"
	@echo ""
	@echo "Build:"
	@echo "  make build        - Build Docker images"
	@echo "  make build-prod   - Build production images"
	@echo ""
	@echo "Testing:"
	@echo "  make test         - Run PHPUnit tests"
	@echo "  make lint         - Run linting"
	@echo ""
	@echo "Keycloak:"
	@echo "  make realm-import - Import Keycloak realm"
	@echo "  make realm-export - Export Keycloak realm"

# ============================================================================
# Development
# ============================================================================

dev:
	docker-compose -f docker-compose.yml -f docker-compose.dev.yml up -d
	@echo ""
	@echo "Development environment started!"
	@echo "  App:        http://localhost:8000"
	@echo "  Keycloak:   http://localhost:8080"
	@echo "  phpMyAdmin: http://localhost:8081"
	@echo "  Redis UI:   http://localhost:8082"
	@echo "  Mailpit:    http://localhost:8025"

up:
	docker-compose up -d

down:
	docker-compose down

restart:
	docker-compose restart

logs:
	docker-compose logs -f

logs-app:
	docker-compose logs -f app

logs-keycloak:
	docker-compose logs -f keycloak

shell:
	docker-compose exec app sh

shell-root:
	docker-compose exec -u root app sh

# ============================================================================
# Database
# ============================================================================

migrate:
	docker-compose exec app php artisan migrate

fresh:
	docker-compose exec app php artisan migrate:fresh --seed

seed:
	docker-compose exec app php artisan db:seed

# ============================================================================
# Build
# ============================================================================

build:
	docker-compose build

build-no-cache:
	docker-compose build --no-cache

build-prod:
	docker-compose build app
	@echo "Production image built successfully"

# ============================================================================
# Testing
# ============================================================================

test:
	docker-compose exec app php artisan test

test-coverage:
	docker-compose exec app php artisan test --coverage

lint:
	docker-compose exec app ./vendor/bin/pint --test

lint-fix:
	docker-compose exec app ./vendor/bin/pint

# ============================================================================
# Artisan Commands
# ============================================================================

artisan:
	docker-compose exec app php artisan $(filter-out $@,$(MAKECMDGOALS))

tinker:
	docker-compose exec app php artisan tinker

cache-clear:
	docker-compose exec app php artisan cache:clear
	docker-compose exec app php artisan config:clear
	docker-compose exec app php artisan route:clear
	docker-compose exec app php artisan view:clear

optimize:
	docker-compose exec app php artisan optimize

# ============================================================================
# Keycloak
# ============================================================================

realm-import:
	docker-compose exec keycloak /opt/keycloak/bin/kcadm.sh config credentials \
		--server http://localhost:8080 \
		--realm master \
		--user admin \
		--password admin
	docker-compose exec keycloak /opt/keycloak/bin/kcadm.sh create realms \
		-f /opt/keycloak/data/import/pcommerce.json

realm-export:
	docker-compose exec keycloak /opt/keycloak/bin/kcadm.sh config credentials \
		--server http://localhost:8080 \
		--realm master \
		--user admin \
		--password admin
	docker-compose exec keycloak /opt/keycloak/bin/kcadm.sh get realms/pcommerce \
		> realms/pcommerce-export.json

# ============================================================================
# Production
# ============================================================================

prod:
	docker-compose up -d app worker scheduler nginx mysql redis keycloak
	@echo ""
	@echo "Production environment started!"
	@echo "  App: http://localhost"

prod-down:
	docker-compose down

# ============================================================================
# Utility
# ============================================================================

ps:
	docker-compose ps

clean:
	docker-compose down -v --remove-orphans
	docker system prune -f

# Catch-all for artisan commands
%:
	@:
