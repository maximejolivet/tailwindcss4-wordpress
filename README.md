# TailwindCSS4 WordPress

A Bedrock, Vite &amp; TailwindCSS 4 WordPress project

![Node.js version](https://img.shields.io/badge/Node-24-407E37)
![PHP version](https://img.shields.io/badge/PHP-8.4-4E5B93)
![MariaDB](https://img.shields.io/badge/MariaDB-11-yellow)
![WordPress version](https://img.shields.io/badge/WordPress-7-21759B)
![Bedrock](https://img.shields.io/badge/Bedrock-1.31.1-D0021B)
![WP Packages](https://img.shields.io/badge/WP_Packages-repo.wp--packages.org-2271B1)

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

Currently installed: `wp-plugin/secure-custom-fields` (ACF-compatible custom fields, powers the page builder — see [`docs/theme.md`](docs/theme.md)) and `wp-plugin/polylang` (FR/EN multilingual).

## Custom theme (`web/app/themes/custom/tailwind`)

A custom theme built with [Timber v2](https://timber.github.io/docs/v2/) (Twig templates, `views/`) and Tailwind CSS 4 via Vite, with a reusable Twig component library (`views/components/`, atoms/molecules/organisms) and an ACF Flexible Content page builder (`inc/acf-fields.php`).

See [`docs/theme.md`](docs/theme.md) for the theme's architecture, Timber/Twig conventions and HMR setup, and the **Commands** section below for `vite-*` targets.

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

### Deployment (o2switch — see [`docs/deploy.md`](docs/deploy.md))

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

## Documentation

- [`docs/README.md`](docs/README.md) — commandes Docker au quotidien (start/stop/logs, accès aux conteneurs, base de données)
- [`docker/README.md`](docker/README.md) — détails de l'environnement (Colima, Traefik, certificats, Bedrock/Composer/WP-CLI)
- [`docs/theme.md`](docs/theme.md) — thème custom Timber v2 + Tailwind CSS 4 + Vite (architecture, composants, page builder, HMR)
- [`docs/deploy.md`](docs/deploy.md) — déploiement en production (o2switch, manuel et CI GitHub Actions)
- [`docs/prompts/WORDPRESS.md`](docs/prompts/WORDPRESS.md) / [`WORDPRESS-PROCESS.md`](docs/prompts/WORDPRESS-PROCESS.md) — mission complète et journal d'exécution réel
