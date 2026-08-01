# TailwindCSS4 WordPress

![Node.js version](https://img.shields.io/badge/Node-24-407E37)
![PHP version](https://img.shields.io/badge/PHP-8.4-4E5B93)
![MariaDB](https://img.shields.io/badge/MariaDB-11-yellow)
![WordPress version](https://img.shields.io/badge/WordPress-7-21759B)
![Bedrock](https://img.shields.io/badge/Bedrock-1.31.1-D0021B)
![WP Packages](https://img.shields.io/badge/WP_Packages-repo.wp--packages.org-2271B1)
![Claude Code](https://img.shields.io/badge/Claude_Code-Sonnet%20%7C%20Opus%20%7C%20Fable-D97757)

# 🇬🇧 English

A Bedrock, Vite &amp; TailwindCSS 4 WordPress project

## Table of contents

- [Installation](#installation)
- [Configuration Development mode](#configuration-development-mode)
- [Custom theme (`web/app/themes/custom/tailwind`)](#custom-theme-webappthemescustomtailwind)
- [Commands](#commands)
- [Roadmap](#roadmap)

## Installation

This project runs in a Docker Compose environment (Traefik, PHP/Apache, MariaDB, Node) to serve a [Bedrock](https://roots.io/bedrock/) WordPress site.

```bash
# Copy the environment file, then fill in DB_*, WP_HOME, WP_SITEURL and the
# auth keys/salts (generate them at https://roots.io/salts.html)
cp .env.example .env

# Build the php image and start the containers for tailwind-wordpress.localhost
make start

# Install WordPress core + Composer dependencies (web/wp, vendor/)
make install

# Install WordPress itself (creates the admin user)
make wp ARGS="core install --url=https://tailwind-wordpress.localhost --title=... --admin_user=... --admin_email=... --admin_password=..."
```

Local certificates (`docker/traefik/certs/`) are generated with [mkcert](https://github.com/FiloSottile/mkcert) — see [`docker/README.md`](docker/README.md) to regenerate them for a different hostname.

## Configuration Development mode

### WP_DEBUG

`WP_DEBUG`, `WP_DEBUG_DISPLAY` and `SCRIPT_DEBUG` are enabled automatically when `WP_ENV='development'` in `.env` (see `config/environments/development.php`) — no manual toggle needed.

### Plugins &amp; themes via Composer

Bedrock manages plugins and themes through Composer rather than the WordPress admin. Since Bedrock 1.30 the [official package source is WP Packages](https://roots.io/wp-composer-is-now-wp-packages/) (`repo.wp-packages.org`, declared in `composer.json`), which replaced WPackagist — package names are `wp-plugin/<slug>` and `wp-theme/<slug>`, **not** the older `wpackagist-plugin/<slug>`/`wpackagist-theme/<slug>`:

```bash
docker compose -f docker/docker-compose.yml --project-directory . exec php \
  composer require wp-plugin/<slug>
```

Currently installed: `wp-plugin/secure-custom-fields` (ACF-compatible custom fields, powers the page builder — see [`.claude/THEME.md`](.claude/THEME.md)) and `wp-plugin/polylang` (FR/EN multilingual).

## Custom theme (`web/app/themes/custom/tailwind`)

A custom theme built with [Timber v2](https://timber.github.io/docs/v2/) (Twig templates, `views/`) and Tailwind CSS 4 via Vite, with a reusable Twig component library (`views/components/`, atoms/molecules/organisms) and an ACF Flexible Content page builder (`inc/acf-fields.php`).

See [`.claude/THEME.md`](.claude/THEME.md) for the theme's architecture, Timber/Twig conventions and HMR setup, and the **Commands** section below for `vite-*` targets.

## Commands

All commands run via `make` from the repo root (see `Makefile`; run `make help` for the same list with descriptions).

### Docker lifecycle

| Command | Effect |
|---|---|
| `make start` | Start Colima (if needed) and all services in the background |
| `make stop` | Stop this project's containers (leaves Colima running for other projects) |
| `make restart` | Restart all services |
| `make colima-stop` | Stop the Colima VM entirely (stops **all** projects' containers) |
| `make status` | Show container status |
| `make logs` | Follow logs for all services |
| `make shell` | Open a shell in the `php` container |
| `make ports` | Show this project's ports and which container (if any) already holds each one |
| `make urls` | Show this project's service URLs |

### WordPress / Composer (Bedrock)

| Command | Effect |
|---|---|
| `make install` | Install Composer dependencies (WordPress core, plugins, themes) |
| `make update ARGS="..."` | Update Composer dependencies within `composer.json` constraints, e.g. `make update ARGS="roots/wordpress"` |
| `make wp ARGS="..."` | Run a WP-CLI command, e.g. `make wp ARGS="cache flush"` |
| `make wp-login ARGS="admin"` | Generate a one-time magic login link for a user |
| `make check-updates` | Check available WordPress core/plugin/theme updates and outdated Composer packages |

### Frontend (Tailwind theme, Vite)

| Command | Effect |
|---|---|
| `make vite-install` | Install the theme's npm dependencies (once, or after `package.json` changes) |
| `make vite-dev` | Start the Vite dev server (HMR) at `https://tailwind-wordpress.localhost:3009/` |
| `make vite-build` | Build production assets (`web/app/themes/custom/tailwind/dist/`) |
| `make npm ARGS="..."` | Run an arbitrary npm command in the theme, e.g. `make npm ARGS="run build"` |

### Deployment (o2switch — see [`.claude/DEPLOY.md`](.claude/DEPLOY.md))

| Command | Effect |
|---|---|
| `make deploy-dry-run` | Preview what `make deploy` would sync/delete on the server, without changing anything |
| `make deploy` | Build the theme, rsync to o2switch, run `composer install --no-dev` on the server |
| `make deploy-env` | One-time: generate and push a production `.env` to the server (interactive confirmation) |
| `make deploy-permalinks` | One-time after first deploy: flush permalinks so WordPress (re)writes `web/.htaccess` |

### Dockhand

| Command | Effect |
|---|---|
| `make dockhand-register` | Register this stack in [Dockhand](https://dockhand.pro/) (local Docker admin UI) |

## Roadmap

- [x] Custom WordPress theme with Vite + Tailwind CSS 4 (`web/app/themes/custom/tailwind`), HMR dev server on `https://tailwind-wordpress.localhost:3009/`
- [x] Reusable Twig component library (atoms/molecules/organisms, `views/components/`)
- [x] ACF Flexible Content page builder (`hero`, `section` with `text_media`/`cards_grid`/`cta_banner`/`accordion`/`embed`)
- [x] Multilingual FR/EN (Polylang), symmetric translation workflow
- [x] Production deployment to o2switch (manual `make deploy` + GitHub Actions CI/CD)
- [ ] CI: automated tests (Pest) and linting in GitHub Actions

---

# 🇫🇷 Français

Un projet WordPress Bedrock, Vite &amp; TailwindCSS 4

## Sommaire

- [Installation](#installation-1)
- [Configuration du mode développement](#configuration-du-mode-développement)
- [Thème custom (`web/app/themes/custom/tailwind`)](#thème-custom-webappthemescustomtailwind)
- [Commandes](#commandes)
- [Feuille de route](#feuille-de-route)

## Installation

Ce projet tourne dans un environnement Docker Compose (Traefik, PHP/Apache, MariaDB, Node) pour servir un site WordPress [Bedrock](https://roots.io/bedrock/).

```bash
# Copier le fichier d'environnement, puis renseigner DB_*, WP_HOME, WP_SITEURL et les
# clés/sels d'authentification (générés sur https://roots.io/salts.html)
cp .env.example .env

# Construire l'image php et démarrer les conteneurs pour tailwind-wordpress.localhost
make start

# Installer le cœur WordPress + les dépendances Composer (web/wp, vendor/)
make install

# Installer WordPress lui-même (crée l'utilisateur admin)
make wp ARGS="core install --url=https://tailwind-wordpress.localhost --title=... --admin_user=... --admin_email=... --admin_password=..."
```

Les certificats locaux (`docker/traefik/certs/`) sont générés avec [mkcert](https://github.com/FiloSottile/mkcert) — voir [`docker/README.md`](docker/README.md) pour les régénérer pour un autre nom d'hôte.

## Configuration du mode développement

### WP_DEBUG

`WP_DEBUG`, `WP_DEBUG_DISPLAY` et `SCRIPT_DEBUG` sont activés automatiquement quand `WP_ENV='development'` dans `.env` (voir `config/environments/development.php`) — aucun bascule manuel nécessaire.

### Plugins &amp; thèmes via Composer

Bedrock gère les plugins et thèmes via Composer plutôt que depuis l'admin WordPress. Depuis Bedrock 1.30, [la source de paquets officielle est WP Packages](https://roots.io/wp-composer-is-now-wp-packages/) (`repo.wp-packages.org`, déclaré dans `composer.json`), qui a remplacé WPackagist — les paquets se nomment `wp-plugin/<slug>` et `wp-theme/<slug>`, **pas** l'ancienne convention `wpackagist-plugin/<slug>`/`wpackagist-theme/<slug>` :

```bash
docker compose -f docker/docker-compose.yml --project-directory . exec php \
  composer require wp-plugin/<slug>
```

Actuellement installés : `wp-plugin/secure-custom-fields` (champs personnalisés compatibles ACF, alimente le page builder — voir [`.claude/THEME.md`](.claude/THEME.md)) et `wp-plugin/polylang` (multilingue FR/EN).

## Thème custom (`web/app/themes/custom/tailwind`)

Un thème custom construit avec [Timber v2](https://timber.github.io/docs/v2/) (templates Twig, `views/`) et Tailwind CSS 4 via Vite, avec une librairie de composants Twig réutilisables (`views/components/`, atoms/molecules/organisms) et un page builder ACF Flexible Content (`inc/acf-fields.php`).

Voir [`.claude/THEME.md`](.claude/THEME.md) pour l'architecture du thème, les conventions Timber/Twig et la configuration HMR, et la section **Commandes** ci-dessous pour les cibles `vite-*`.

## Commandes

Toutes les commandes s'exécutent via `make` depuis la racine du repo (voir `Makefile` ; `make help` affiche la même liste avec des descriptions).

### Cycle de vie Docker

| Commande | Effet |
|---|---|
| `make start` | Démarre Colima (si nécessaire) et tous les services en arrière-plan |
| `make stop` | Arrête les conteneurs de ce projet (laisse Colima tourner pour les autres projets) |
| `make restart` | Redémarre tous les services |
| `make colima-stop` | Arrête entièrement la VM Colima (arrête les conteneurs de **tous** les projets) |
| `make status` | Affiche l'état des conteneurs |
| `make logs` | Suit les logs de tous les services |
| `make shell` | Ouvre un shell dans le conteneur `php` |
| `make ports` | Affiche les ports de ce projet et quel conteneur (le cas échéant) occupe déjà chacun |
| `make urls` | Affiche les URLs des services de ce projet |

### WordPress / Composer (Bedrock)

| Commande | Effet |
|---|---|
| `make install` | Installe les dépendances Composer (cœur WordPress, plugins, thèmes) |
| `make update ARGS="..."` | Met à jour les dépendances Composer dans les contraintes de `composer.json`, ex. `make update ARGS="roots/wordpress"` |
| `make wp ARGS="..."` | Exécute une commande WP-CLI, ex. `make wp ARGS="cache flush"` |
| `make wp-login ARGS="admin"` | Génère un lien de connexion à usage unique pour un utilisateur |
| `make check-updates` | Vérifie les mises à jour WordPress core/plugins/thèmes disponibles et les paquets Composer obsolètes |

### Frontend (thème Tailwind, Vite)

| Commande | Effet |
|---|---|
| `make vite-install` | Installe les dépendances npm du thème (une fois, ou après modification de `package.json`) |
| `make vite-dev` | Démarre le serveur de dev Vite (HMR) sur `https://tailwind-wordpress.localhost:3009/` |
| `make vite-build` | Construit les assets de production (`web/app/themes/custom/tailwind/dist/`) |
| `make npm ARGS="..."` | Exécute une commande npm arbitraire dans le thème, ex. `make npm ARGS="run build"` |

### Déploiement (o2switch — voir [`.claude/DEPLOY.md`](.claude/DEPLOY.md))

| Commande | Effet |
|---|---|
| `make deploy-dry-run` | Prévisualise ce que `make deploy` synchroniserait/supprimerait sur le serveur, sans rien changer |
| `make deploy` | Construit le thème, rsync vers o2switch, exécute `composer install --no-dev` sur le serveur |
| `make deploy-env` | Une fois : génère et envoie un `.env` de production sur le serveur (confirmation interactive) |
| `make deploy-permalinks` | Une fois après le premier déploiement : vide les permaliens pour que WordPress (ré)écrive `web/.htaccess` |

### Dockhand

| Commande | Effet |
|---|---|
| `make dockhand-register` | Enregistre ce stack dans [Dockhand](https://dockhand.pro/) (interface d'admin Docker locale) |

## Feuille de route

- [x] Thème WordPress custom avec Vite + Tailwind CSS 4 (`web/app/themes/custom/tailwind`), serveur de dev HMR sur `https://tailwind-wordpress.localhost:3009/`
- [x] Librairie de composants Twig réutilisables (atoms/molecules/organisms, `views/components/`)
- [x] Page builder ACF Flexible Content (`hero`, `section` avec `text_media`/`cards_grid`/`cta_banner`/`accordion`/`embed`)
- [x] Multilingue FR/EN (Polylang), workflow de traduction symétrique
- [x] Déploiement en production sur o2switch (`make deploy` manuel + CI/CD GitHub Actions)
- [ ] CI : tests automatisés (Pest) et lint dans GitHub Actions
