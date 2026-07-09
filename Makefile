COMPOSE = docker compose -f docker/docker-compose.yml --project-directory .

.PHONY: start stop restart status logs shell install wp dockhand-register

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
