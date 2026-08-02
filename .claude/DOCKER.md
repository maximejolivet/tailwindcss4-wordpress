# 🇫🇷 Commandes Docker

> Pour le thème custom (Timber v2 + Tailwind CSS 4 + Vite, composants, page builder ACF, multilingue Polylang), voir [`THEME.md`](THEME.md). Pour le déploiement en production (o2switch), voir [`DEPLOY.md`](DEPLOY.md).

Environnement de développement local basé sur `docker/docker-compose.yml` (services : `traefik`, `php`, `database`, `node`, `phpmyadmin`, `mailhog`, `dockhand`), pour un projet WordPress [Bedrock](https://roots.io/bedrock/) (racine du repo = racine Bedrock : `composer.json`, `config/`, `web/wp` pour le cœur WordPress, `web/app` pour thèmes/plugins/uploads).

Le fichier compose vit dans `docker/` mais ses volumes (`.:/var/www/html`, `./docker/traefik/...`) et le fichier `.env` sont résolus par rapport à la racine du repo : toutes les commandes brutes ci-dessous passent donc `--project-directory .`. Utiliser de préférence les raccourcis `make` qui l'encapsulent déjà.

## Sommaire

- [Suivi](#suivi)
- [Build](#build-1)
- [Accès aux conteneurs](#accès-aux-conteneurs)
- [Configuration (.env)](#configuration-env-1)
- [URLs locales (via Traefik)](#urls-locales-via-traefik)
- [Services](#services-1)
- [Enregistrer le stack dans Dockhand](#enregistrer-le-stack-dans-dockhand)

```bash
COMPOSE="docker compose -f docker/docker-compose.yml --project-directory ."

# Démarrer tous les services en arrière-plan
$COMPOSE up -d

# Arrêter et supprimer les conteneurs (les volumes, ex. db-data, sont conservés)
$COMPOSE down

# Arrêter les conteneurs sans les supprimer
$COMPOSE stop

# Redémarrer tous les services
$COMPOSE restart

# Redémarrer un seul service
$COMPOSE restart php
```

Raccourcis équivalents via `Makefile` (racine du repo) :

```bash
make start     # docker compose ... up -d
make stop      # docker compose ... stop
make restart   # docker compose ... restart
make status    # docker compose ... ps
make logs      # docker compose ... logs -f
make shell     # shell dans le conteneur php
make install   # composer install (cœur WordPress + dépendances)
make wp ARGS="..." # commande WP-CLI, ex. make wp ARGS="core install --url=... --title=... --admin_user=... --admin_email=..."
```

## Suivi

```bash
# État des conteneurs
$COMPOSE ps

# Logs de tous les services
$COMPOSE logs -f

# Logs d'un service précis
$COMPOSE logs -f php
```

## Build

```bash
# Reconstruire l'image PHP après modification de docker/apache/Dockerfile
$COMPOSE build php

# Rebuild puis redémarrage si nécessaire
$COMPOSE up -d --build
```

## Accès aux conteneurs

```bash
# Shell dans le conteneur PHP
$COMPOSE exec php bash

# Installer les dépendances Bedrock (cœur WordPress dans web/wp, vendor/)
$COMPOSE exec php composer install

# Commande WP-CLI
$COMPOSE exec php wp cache flush

# Accès direct à la base de données
$COMPOSE exec database mariadb -u"$DB_USER" -p"$DB_PASSWORD" "$DB_NAME"
```

## Configuration (.env)

Le fichier `.env` (racine du repo, non commité, à copier depuis `.env.example`) est lu trois fois avec les mêmes valeurs : par Docker Compose (interpolation dans `docker-compose.yml`), par les conteneurs `php`/`node` (`env_file`), et par Bedrock lui-même (`config/application.php`, via `vlucas/phpdotenv`).

Points d'attention :
- `DB_HOST` doit valoir `database` (nom du service MariaDB), pas `localhost`.
- `WP_HOME`/`WP_SITEURL` doivent correspondre à l'URL Traefik (`https://tailwind-wordpress.localhost`).
- Les plugins/thèmes se gèrent via Composer, pas depuis l'admin WordPress : `$COMPOSE exec php composer require wp-plugin/<slug>`. Depuis Bedrock 1.30, [WP Packages](https://roots.io/wp-composer-is-now-wp-packages/) (`repo.wp-packages.org`, déclaré dans `composer.json`) est la source de paquets **officielle**, en remplacement de WPackagist — les paquets se nomment `wp-plugin/<slug>` et `wp-theme/<slug>`, pas `wpackagist-plugin/<slug>`/`wpackagist-theme/<slug>` (ancienne convention) ; vérifié avec `composer show wp-plugin/polylang --all`.

## URLs locales (via Traefik)

| URL | Service |
|---|---|
| https://tailwind-wordpress.localhost | Site Wordpress |
| https://pma.tailwind-wordpress.localhost | phpMyAdmin |
| https://mail.tailwind-wordpress.localhost | Mailhog |
| https://localhost:3009 | Serveur de dev Vite (HMR) |
| http://localhost:3000 | Dockhand (admin Docker) |

## Services

| Service | Image | Rôle |
|---|---|---|
| traefik | traefik:v3.5 | Reverse proxy HTTPS local |
| php | build `docker/apache` | Apache + mod_php + Composer + WP-CLI pour WordPress (Bedrock) |
| database | mariadb:11 | Base de données |
| node | node:24 | Serveur de dev Vite |
| phpmyadmin | phpmyadmin:5 | Interface d'admin MySQL |
| mailhog | mailhog/mailhog:v1.0.1 | Capture des emails sortants |
| dockhand | fnsys/dockhand:latest | Interface web de gestion Docker ([dockhand.pro](https://dockhand.pro/)) |

⚠️ Le service `dockhand` monte `/var/run/docker.sock` : il a un accès complet au démon Docker de la machine hôte (tous les conteneurs, pas seulement ceux de ce projet).

## Enregistrer le stack dans Dockhand

```bash
make dockhand-register
```

Exécute `docker/dockhand-register.sh`, qui appelle l'API Dockhand pour :
1. créer un environnement Docker local (`socket:/var/run/docker.sock`) s'il n'en existe pas encore ;
2. enregistrer `docker-compose.yml` comme stack `tailwindcss4-wordpress` (sans redémarrer les conteneurs déjà lancés).

Si l'authentification Dockhand est activée, définir `DOCKHAND_USER` et `DOCKHAND_PASSWORD` (ex. dans `.env`, non commité) avant de lancer la commande.

---

# 🇬🇧 Docker commands

> For the custom theme (Timber v2 + Tailwind CSS 4 + Vite, components, ACF page builder, Polylang multilingual), see [`THEME.md`](THEME.md). For production deployment (o2switch), see [`DEPLOY.md`](DEPLOY.md).

Local development environment based on `docker/docker-compose.yml` (services: `traefik`, `php`, `database`, `node`, `phpmyadmin`, `mailhog`, `dockhand`), for a WordPress [Bedrock](https://roots.io/bedrock/) project (repo root = Bedrock root: `composer.json`, `config/`, `web/wp` for WordPress core, `web/app` for themes/plugins/uploads).

The compose file lives in `docker/` but its volumes (`.:/var/www/html`, `./docker/traefik/...`) and the `.env` file are resolved relative to the repo root: every raw command below therefore passes `--project-directory .`. Prefer the `make` shortcuts, which already wrap this.

## Table of contents

- [Monitoring](#monitoring)
- [Build](#build)
- [Container access](#container-access)
- [Configuration (.env)](#configuration-env)
- [Local URLs (via Traefik)](#local-urls-via-traefik)
- [Services](#services)
- [Registering the stack in Dockhand](#registering-the-stack-in-dockhand)

```bash
COMPOSE="docker compose -f docker/docker-compose.yml --project-directory ."

# Start all services in the background
$COMPOSE up -d

# Stop and remove containers (volumes, e.g. db-data, are kept)
$COMPOSE down

# Stop containers without removing them
$COMPOSE stop

# Restart all services
$COMPOSE restart

# Restart a single service
$COMPOSE restart php
```

Equivalent shortcuts via `Makefile` (repo root):

```bash
make start     # docker compose ... up -d
make stop      # docker compose ... stop
make restart   # docker compose ... restart
make status    # docker compose ... ps
make logs      # docker compose ... logs -f
make shell     # shell in the php container
make install   # composer install (WordPress core + dependencies)
make wp ARGS="..." # WP-CLI command, e.g. make wp ARGS="core install --url=... --title=... --admin_user=... --admin_email=..."
```

## Monitoring

```bash
# Container status
$COMPOSE ps

# Logs for all services
$COMPOSE logs -f

# Logs for one service
$COMPOSE logs -f php
```

## Build

```bash
# Rebuild the PHP image after changing docker/apache/Dockerfile
$COMPOSE build php

# Rebuild then restart if needed
$COMPOSE up -d --build
```

## Container access

```bash
# Shell in the PHP container
$COMPOSE exec php bash

# Install Bedrock dependencies (WordPress core in web/wp, vendor/)
$COMPOSE exec php composer install

# WP-CLI command
$COMPOSE exec php wp cache flush

# Direct database access
$COMPOSE exec database mariadb -u"$DB_USER" -p"$DB_PASSWORD" "$DB_NAME"
```

## Configuration (.env)

The `.env` file (repo root, not committed, copied from `.env.example`) is read three times with the same values: by Docker Compose (interpolation in `docker-compose.yml`), by the `php`/`node` containers (`env_file`), and by Bedrock itself (`config/application.php`, via `vlucas/phpdotenv`).

Things to watch:
- `DB_HOST` must be `database` (the MariaDB service name), not `localhost`.
- `WP_HOME`/`WP_SITEURL` must match the Traefik URL (`https://tailwind-wordpress.localhost`).
- Plugins/themes are managed via Composer, not from the WordPress admin: `$COMPOSE exec php composer require wp-plugin/<slug>`. Since Bedrock 1.30, [WP Packages](https://roots.io/wp-composer-is-now-wp-packages/) (`repo.wp-packages.org`, declared in `composer.json`) is the **official** package source, replacing WPackagist — packages are named `wp-plugin/<slug>` and `wp-theme/<slug>`, not `wpackagist-plugin/<slug>`/`wpackagist-theme/<slug>` (older convention); verified with `composer show wp-plugin/polylang --all`.

## Local URLs (via Traefik)

| URL | Service |
|---|---|
| https://tailwind-wordpress.localhost | WordPress site |
| https://pma.tailwind-wordpress.localhost | phpMyAdmin |
| https://mail.tailwind-wordpress.localhost | Mailhog |
| https://localhost:3009 | Vite dev server (HMR) |
| http://localhost:3000 | Dockhand (Docker admin) |

## Services

| Service | Image | Role |
|---|---|---|
| traefik | traefik:v3.5 | Local HTTPS reverse proxy |
| php | build `docker/apache` | Apache + mod_php + Composer + WP-CLI for WordPress (Bedrock) |
| database | mariadb:11 | Database |
| node | node:24 | Vite dev server |
| phpmyadmin | phpmyadmin:5 | MySQL admin interface |
| mailhog | mailhog/mailhog:v1.0.1 | Outgoing email capture |
| dockhand | fnsys/dockhand:latest | Docker web admin UI ([dockhand.pro](https://dockhand.pro/)) |

⚠️ The `dockhand` service mounts `/var/run/docker.sock`: it has full access to the host machine's Docker daemon (all containers, not just this project's).

## Registering the stack in Dockhand

```bash
make dockhand-register
```

Runs `docker/dockhand-register.sh`, which calls the Dockhand API to:
1. create a local Docker environment (`socket:/var/run/docker.sock`) if none exists yet;
2. register `docker-compose.yml` as the `tailwindcss4-wordpress` stack (without restarting already-running containers).

If Dockhand authentication is enabled, set `DOCKHAND_USER` and `DOCKHAND_PASSWORD` (e.g. in `.env`, not committed) before running the command.
