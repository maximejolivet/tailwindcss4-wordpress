# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project

Bedrock WordPress site (PHP ≥8.4) with a custom Timber v2 + Tailwind CSS v4 + Vite theme (`web/app/themes/custom/tailwind`). Runs in Docker Compose (Traefik, PHP, MariaDB, Node) — see @.claude/docker.md and @docker/README.md.

## Commands

Always use the `make` targets, not raw `docker compose`/`composer`/`npm` — see the Commands table in @README.md (`make help` lists everything). `make deploy*` targets touch the live production site (o2switch) — confirm with the user before running them.

## Composer package source

This repo's Composer mirror (`repo.wp-packages.org`, declared in `composer.json`) is **WP Packages** — Bedrock's official package source since 1.30, replacing WPackagist. Package names are `wp-plugin/<slug>` and `wp-theme/<slug>`, **not** `wpackagist-plugin/<slug>`/`wpackagist-theme/<slug>`.

## Custom theme

Detailed WordPress/Bedrock/Twig/ACF/Polylang conventions: @.claude/rules/wordpress.md. Full architecture: @.claude/theme.md.

## Linting

PHP: `composer lint` (Pint, `pint.json`, preset `per`) / `composer test` (Pest — no tests written yet). No JS/CSS lint configured yet in the theme.

## Documentation

- [`.claude/docker.md`](.claude/docker.md) — commandes Docker au quotidien (start/stop/logs, accès aux conteneurs, base de données)
- [`docker/README.md`](docker/README.md) — détails de l'environnement (Colima, Traefik, certificats, Bedrock/Composer/WP-CLI)
- [`.claude/theme.md`](.claude/theme.md) — thème custom Timber v2 + Tailwind CSS 4 + Vite (architecture, composants, page builder, HMR)
- [`.claude/deploy.md`](.claude/deploy.md) — déploiement en production (o2switch, manuel et CI GitHub Actions)
- [`.claude/prompts/WORDPRESS.md`](.claude/prompts/WORDPRESS.md) / [`WORDPRESS-PROCESS.md`](.claude/prompts/WORDPRESS-PROCESS.md) — mission complète et journal d'exécution réel