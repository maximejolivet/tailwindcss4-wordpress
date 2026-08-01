COMPOSE = docker compose -f docker/docker-compose.yml --project-directory .
THEME_DIR = web/app/themes/custom/tailwind

-include .env.deploy
export

.PHONY: help \
	start stop colima-stop restart status logs shell ports urls \
	install update wp wp-login check-updates \
	lint lint-fix phpstan audit \
	dockhand-register \
	npm vite-install vite-dev vite-build \
	deploy-dry-run deploy deploy-env deploy-permalinks

.DEFAULT_GOAL := help

help: ## Show this help
	@grep -hE '^[a-zA-Z_-]+:.*## .*$$' $(MAKEFILE_LIST) | sort | awk 'BEGIN {FS = ":.*## "}; {printf "\033[36m%-20s\033[0m %s\n", $$1, $$2}'

# --------------------------------------------------------------------------
# Docker lifecycle
# --------------------------------------------------------------------------

start: ## Start all services in the background
	colima start && $(COMPOSE) up -d

stop: ## Stop this project's containers without removing them (leaves Colima running for other projects)
	$(COMPOSE) stop

colima-stop: ## Stop the Colima VM entirely (stops ALL projects' containers, not just this one)
	colima stop

restart: ## Restart all services
	colima start && $(COMPOSE) restart

status: ## Show container status
	$(COMPOSE) ps

logs: ## Follow logs for all services
	$(COMPOSE) logs -f

shell: ## Open a shell in the php container
	$(COMPOSE) exec php bash

ports: ## Show this project's ports and which container (if any) already holds each one
	@for p in $$($(COMPOSE) config --format json | jq -r '.services[].ports[]?.published' | sort -un); do \
		used_by=$$(docker ps --filter "publish=$$p" --format '{{.Names}}'); \
		if [ -n "$$used_by" ]; then \
			printf '%-6s used by %s\n' "$$p" "$$used_by"; \
		else \
			printf '%-6s free\n' "$$p"; \
		fi; \
	done

urls: ## Show this project's service URLs
	@grep -ohE 'Host\(`[^`]+`\)' docker/traefik/dynamic/routes.yml | sed -E 's/Host\(`([^`]+)`\)/https:\/\/\1/' | sort
	@echo "https://tailwind-wordpress.localhost:3009  (Vite dev server, when \`make vite-dev\` is running)"
	@echo "http://localhost:3000  (Dockhand, shared across projects, see \`make ports\` if the port is taken by another project)"

# --------------------------------------------------------------------------
# WordPress / Composer (Bedrock)
# --------------------------------------------------------------------------

install: ## Install Bedrock's Composer dependencies (WordPress core, plugins, themes)
	$(COMPOSE) exec php composer install

update: ## Update Composer dependencies within their composer.json constraints, e.g. `make update ARGS="roots/wordpress"` for one package
	$(COMPOSE) exec php composer update $(ARGS)

wp: ## Run a WP-CLI command, e.g. `make wp ARGS="core install --url=... "`
	$(COMPOSE) exec php wp --allow-root $(ARGS)

wp-login: ## Generate a one-time magic login link for a user, e.g. `make wp-login ARGS="admin"`
	$(COMPOSE) exec php wp --allow-root login create $(ARGS)

check-updates: ## Check for available WordPress core/plugin/theme updates and outdated Composer packages
	@echo "--- WordPress core ---"
	@$(COMPOSE) exec php wp --allow-root core check-update
	@echo "--- Plugins ---"
	@$(COMPOSE) exec php wp --allow-root plugin list --update=available
	@echo "--- Themes ---"
	@$(COMPOSE) exec php wp --allow-root theme list --update=available
	@echo "--- Composer packages (source of truth for this Bedrock project) ---"
	@$(COMPOSE) exec php composer outdated --direct

# --------------------------------------------------------------------------
# Quality (composer.json scripts — config/, the tailwind theme, web/index.php, web/wp-config.php)
# --------------------------------------------------------------------------

lint: ## Check code style with Pint (preset "per", pint.json)
	$(COMPOSE) exec php composer lint

lint-fix: ## Fix code style with Pint
	$(COMPOSE) exec php composer lint:fix

phpstan: ## Run PHPStan static analysis (level 5, WordPress/ACF-aware — phpstan.neon.dist)
	$(COMPOSE) exec php composer phpstan

audit: ## Check Composer dependencies for known security vulnerabilities
	$(COMPOSE) exec php composer audit

# --------------------------------------------------------------------------
# Dockhand
# --------------------------------------------------------------------------

dockhand-register: ## Register the stack in Dockhand
	COMPOSE_FILE=docker/docker-compose.yml ./docker/dockhand-register.sh

# --------------------------------------------------------------------------
# Frontend (Tailwind theme, Vite)
# --------------------------------------------------------------------------

npm: ## Run an npm command in the tailwind theme, e.g. `make npm ARGS="install"`
	$(COMPOSE) exec node npm --prefix $(THEME_DIR) $(ARGS)

vite-install: ## Install the tailwind theme's npm dependencies
	$(COMPOSE) exec node npm --prefix $(THEME_DIR) install

vite-dev: ## Start the Vite dev server (HMR) at https://tailwind-wordpress.localhost:3009/
	$(COMPOSE) exec node npm --prefix $(THEME_DIR) run dev

vite-build: ## Build the tailwind theme's production assets
	$(COMPOSE) exec node npm --prefix $(THEME_DIR) run build

# --------------------------------------------------------------------------
# Déploiement (o2switch) — voir docs/deploy.md pour la configuration
# one-shot côté cPanel (document root, base de données, .env serveur).
# Variables lues depuis .env.deploy (non versionné, cf. .env.deploy.example).
# --------------------------------------------------------------------------

DEPLOY_SSH = ssh -p $(DEPLOY_SSH_PORT) $(DEPLOY_SSH_USER)@$(DEPLOY_SSH_HOST)
DEPLOY_RSYNC_EXCLUDES = --exclude-from=.gitignore --exclude '.git' --exclude 'docker' --exclude '.env.deploy*'

deploy-dry-run: vite-build ## Preview what `make deploy` would sync/delete on the server, without changing anything
	rsync -avzn --delete $(DEPLOY_RSYNC_EXCLUDES) -e "ssh -p $(DEPLOY_SSH_PORT)" \
		./ $(DEPLOY_SSH_USER)@$(DEPLOY_SSH_HOST):$(DEPLOY_PROJECT_PATH)/

deploy: vite-build ## Deploy to o2switch: rsync tracked/built files, then composer install --no-dev on the server
	rsync -avz --delete $(DEPLOY_RSYNC_EXCLUDES) -e "ssh -p $(DEPLOY_SSH_PORT)" \
		./ $(DEPLOY_SSH_USER)@$(DEPLOY_SSH_HOST):$(DEPLOY_PROJECT_PATH)/
	$(DEPLOY_SSH) "cd $(DEPLOY_PROJECT_PATH) && $(DEPLOY_PHP_BIN) \$$(command -v composer) install --no-dev --optimize-autoloader --no-interaction"

deploy-env: ## One-time: push a generated production .env to the server (never runs automatically as part of `deploy`)
	@test -n "$(DEPLOY_DB_PASSWORD)" || (echo "DEPLOY_DB_PASSWORD missing in .env.deploy" && exit 1)
	$(COMPOSE) exec -T php php bin/generate-production-env.php '$(DEPLOY_DB_NAME)' '$(DEPLOY_DB_USER)' '$(DEPLOY_DB_PASSWORD)' '$(DEPLOY_DOMAIN)' > /tmp/deploy.env
	@echo "Generated a fresh production .env (new random salts) — review /tmp/deploy.env before this pushes it:"
	@cat /tmp/deploy.env
	@echo ""
	@read -p "Copy this to $(DEPLOY_PROJECT_PATH)/.env on the server via scp? [y/N] " ans && [ "$$ans" = "y" ]
	scp -P $(DEPLOY_SSH_PORT) /tmp/deploy.env $(DEPLOY_SSH_USER)@$(DEPLOY_SSH_HOST):$(DEPLOY_PROJECT_PATH)/.env
	@rm /tmp/deploy.env

deploy-permalinks: ## One-time after first deploy: flush permalinks so WordPress (re)writes web/.htaccess on the server
	@echo "wp-cli isn't a project dependency (not in composer.json) — checking for a global 'wp' on the server instead."
	$(DEPLOY_SSH) "cd $(DEPLOY_PROJECT_PATH)/web && command -v wp >/dev/null && wp rewrite flush --hard --path=. || echo 'No wp-cli on the server — flush permalinks manually once via wp-admin > Réglages > Permaliens (just open the page and click Enregistrer)'"
