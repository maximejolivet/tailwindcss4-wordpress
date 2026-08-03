# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

# 🇫🇷 Français

Ce fichier fournit des indications à Claude Code (claude.ai/code) pour travailler sur le code de ce dépôt.

## Sommaire

- [Projet](#projet)
- [Commandes](#commandes)
- [Source des paquets Composer](#source-des-paquets-composer)
- [Thème custom](#thème-custom)
- [Linting](#linting-1)
- [Documentation](#documentation-1)

## Projet

Site WordPress Bedrock (PHP ≥8.4) avec un thème custom Timber v2 + Tailwind CSS v4 + Vite (`web/app/themes/custom/tailwind`). Tourne dans Docker Compose (Traefik, PHP, MariaDB, Node) — voir @.claude/DOCKER.md et @docker/README.md.

## Commandes

Toujours utiliser les cibles `make`, pas `docker compose`/`composer`/`npm` en direct — voir le tableau Commands dans @README.md (`make help` liste tout). Le déploiement (o2switch) est entièrement automatique via `.github/workflows/deploy.yml` sur push vers `main` (pas de cible `make deploy*`) — un push sur `main`, un déclenchement manuel du workflow (`workflow_dispatch`) ou l'ajout/la rotation d'un secret GitHub de déploiement touchent le site de production réel : demander confirmation à l'utilisateur avant.

## Source des paquets Composer

Le miroir Composer de ce repo (`repo.wp-packages.org`, déclaré dans `composer.json`) est **WP Packages** — la source de paquets officielle de Bedrock depuis la 1.30, en remplacement de WPackagist. Les paquets se nomment `wp-plugin/<slug>` et `wp-theme/<slug>`, **pas** `wpackagist-plugin/<slug>`/`wpackagist-theme/<slug>`.

## Thème custom

Conventions détaillées WordPress/Bedrock/Twig/ACF/Polylang : @.claude/WORDPRESS.md. Architecture complète : @.claude/THEME.md.

## Linting

Outillage qualité PHP (scripts composer, aussi disponibles en cibles `make` — `lint`/`lint-fix`/`phpstan`/`audit`) :

- `composer lint` / `lint:fix` — Pint (`pint.json`, préréglage `per`) : formatage/style.
- `composer phpstan` — PHPStan niveau 5 (`phpstan.neon.dist`), conscient de WordPress/ACF via les stubs `szepeviktor/phpstan-wordpress` + `php-stubs/acf-pro-stubs`. Analyse `config/`, le thème custom, `web/index.php`, `web/wp-config.php`.
- `composer audit` — scan des vulnérabilités des dépendances intégré à Composer. `roave/security-advisories` (dev uniquement, aucun code réel) bloque en plus `composer install`/`update` dès qu'un paquet a une CVE connue.
- Aucune suite de tests, PHPCS/WPCS, PHPMD, ni lint JS/CSS configuré.

## Documentation

- [`.claude/DOCKER.md`](.claude/DOCKER.md) — commandes Docker au quotidien (start/stop/logs, accès aux conteneurs, base de données)
- [`docker/README.md`](docker/README.md) — détails de l'environnement (Colima, Traefik, certificats, Bedrock/Composer/WP-CLI)
- [`.claude/THEME.md`](.claude/THEME.md) — thème custom Timber v2 + Tailwind CSS 4 + Vite (architecture, composants, page builder, HMR)
- [`.claude/DEPLOY.md`](.claude/DEPLOY.md) — déploiement en production (o2switch, CI GitHub Actions — seule voie de déploiement)
- [`.claude/prompts/WORDPRESS-MISSION-BRIEF.md`](.claude/prompts/WORDPRESS-MISSION-BRIEF.md) / [`WORDPRESS-PROCESS.md`](.claude/prompts/WORDPRESS-PROCESS.md) — mission complète et journal d'exécution réel
- [`.claude/agents/`](.claude/agents/) / [`.claude/rules/`](.claude/rules/) / [`.claude/commands/`](.claude/commands/) — sous-agents (`planner`, `architect`, `code-reviewer`, `security-reviewer`, `refactor-cleaner`, `doc-updater`), règles toujours actives et commandes slash (`/plan`, `/refactor-clean`, `/update-docs`, `/build-fix`), adaptés à ce dépôt depuis [WorldFlowAI/everything-claude-code](https://github.com/WorldFlowAI/everything-claude-code)

---

# 🇬🇧 English

## Table of contents

- [Project](#project)
- [Commands](#commands)
- [Composer package source](#composer-package-source)
- [Custom theme](#custom-theme)
- [Linting](#linting)
- [Documentation](#documentation)

## Project

Bedrock WordPress site (PHP ≥8.4) with a custom Timber v2 + Tailwind CSS v4 + Vite theme (`web/app/themes/custom/tailwind`). Runs in Docker Compose (Traefik, PHP, MariaDB, Node) — see @.claude/DOCKER.md and @docker/README.md.

## Commands

Always use the `make` targets, not raw `docker compose`/`composer`/`npm` — see the Commands table in @README.md (`make help` lists everything). Deployment (o2switch) is fully automatic via `.github/workflows/deploy.yml` on push to `main` (no `make deploy*` target) — a push to `main`, a manual workflow trigger (`workflow_dispatch`), or adding/rotating a deployment GitHub secret all touch the live production site: confirm with the user first.

## Composer package source

This repo's Composer mirror (`repo.wp-packages.org`, declared in `composer.json`) is **WP Packages** — Bedrock's official package source since 1.30, replacing WPackagist. Package names are `wp-plugin/<slug>` and `wp-theme/<slug>`, **not** `wpackagist-plugin/<slug>`/`wpackagist-theme/<slug>`.

## Custom theme

Detailed WordPress/Bedrock/Twig/ACF/Polylang conventions: @.claude/WORDPRESS.md. Full architecture: @.claude/THEME.md.

## Linting

PHP quality tooling (composer scripts, also wired as `make` targets — `lint`/`lint-fix`/`phpstan`/`audit`):

- `composer lint` / `lint:fix` — Pint (`pint.json`, preset `per`): formatting/style.
- `composer phpstan` — PHPStan level 5 (`phpstan.neon.dist`), WordPress/ACF-aware via `szepeviktor/phpstan-wordpress` + `php-stubs/acf-pro-stubs` stubs. Scans `config/`, the custom theme, `web/index.php`, `web/wp-config.php`.
- `composer audit` — Composer's built-in dependency vulnerability scan. `roave/security-advisories` (dev-only, no real code) additionally blocks `composer install`/`update` from ever pulling in a package version with a known CVE.
- No test suite, PHPCS/WPCS, PHPMD, or JS/CSS lint configured.

## Documentation

- [`.claude/DOCKER.md`](.claude/DOCKER.md) — day-to-day Docker commands (start/stop/logs, container access, database)
- [`docker/README.md`](docker/README.md) — environment details (Colima, Traefik, certificates, Bedrock/Composer/WP-CLI)
- [`.claude/THEME.md`](.claude/THEME.md) — custom theme: Timber v2 + Tailwind CSS 4 + Vite (architecture, components, page builder, HMR)
- [`.claude/DEPLOY.md`](.claude/DEPLOY.md) — production deployment (o2switch, GitHub Actions CI — the only deployment path)
- [`.claude/prompts/WORDPRESS-MISSION-BRIEF.md`](.claude/prompts/WORDPRESS-MISSION-BRIEF.md) / [`WORDPRESS-PROCESS.md`](.claude/prompts/WORDPRESS-PROCESS.md) — full mission spec and real execution journal
- [`.claude/agents/`](.claude/agents/) / [`.claude/rules/`](.claude/rules/) / [`.claude/commands/`](.claude/commands/) — subagents (`planner`, `architect`, `code-reviewer`, `security-reviewer`, `refactor-cleaner`, `doc-updater`), always-on rules, and slash commands (`/plan`, `/refactor-clean`, `/update-docs`, `/build-fix`), adapted to this repo from [WorldFlowAI/everything-claude-code](https://github.com/WorldFlowAI/everything-claude-code)
