# Commandes Docker

Environnement de développement local basé sur `docker/docker-compose.yml` (services : `traefik`, `php`, `database`, `node`, `phpmyadmin`, `mailhog`, `dockhand`), pour un projet WordPress [Bedrock](https://roots.io/bedrock/) (racine du repo = racine Bedrock : `composer.json`, `config/`, `web/wp` pour le cœur WordPress, `web/app` pour thèmes/plugins/uploads).

Le fichier compose vit dans `docker/` mais ses volumes (`.:/var/www/html`, `./docker/traefik/...`) et le fichier `.env` sont résolus par rapport à la racine du repo : toutes les commandes brutes ci-dessous passent donc `--project-directory .`. Utiliser de préférence les raccourcis `make` qui l'encapsulent déjà.

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
- Les plugins/thèmes se gèrent via Composer (wpackagist), pas depuis l'admin WordPress : `$COMPOSE exec php composer require wpackagist-plugin/<slug>`.

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
| node | node:22 | Serveur de dev Vite |
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
