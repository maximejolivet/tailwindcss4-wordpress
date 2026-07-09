COMPOSE = docker compose -f docker/docker-compose.yml --project-directory .

.PHONY: start stop restart status logs shell install wp dockhand-register npm vite-install vite-dev vite-build

start: ## Start all services in the background
	colima start && $(COMPOSE) up -d

stop: ## Stop all services without removing containers
	colima stop && $(COMPOSE) stop

restart: ## Restart all services
	colima start && $(COMPOSE) restart

status: ## Show container status
	$(COMPOSE) ps

logs: ## Follow logs for all services
	$(COMPOSE) logs -f

shell: ## Open a shell in the php container
	$(COMPOSE) exec php bash

install: ## Install Bedrock's Composer dependencies (WordPress core, plugins, themes)
	$(COMPOSE) exec php composer install

wp: ## Run a WP-CLI command, e.g. `make wp ARGS="core install --url=... "`
	$(COMPOSE) exec php wp --allow-root $(ARGS)

dockhand-register: ## Register the stack in Dockhand
	COMPOSE_FILE=docker/docker-compose.yml ./docker/dockhand-register.sh

THEME_DIR = web/app/themes/custom/tailwind

npm: ## Run an npm command in the tailwind theme, e.g. `make npm ARGS="install"`
	$(COMPOSE) exec node npm --prefix $(THEME_DIR) $(ARGS)

vite-install: ## Install the tailwind theme's npm dependencies
	$(COMPOSE) exec node npm --prefix $(THEME_DIR) install

vite-dev: ## Start the Vite dev server (HMR) at https://tailwind-wordpress.localhost:3009/
	$(COMPOSE) exec node npm --prefix $(THEME_DIR) run dev

vite-build: ## Build the tailwind theme's production assets
	$(COMPOSE) exec node npm --prefix $(THEME_DIR) run build
