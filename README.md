# TailwindCSS4 WordPress

A Bedrock, Vite &amp; TailwindCSS 4 WordPress project

![Node.js version](https://img.shields.io/badge/Node-24-407E37)
![PHP version](https://img.shields.io/badge/PHP-8.4-4E5B93)
![MariaDB](https://img.shields.io/badge/MariaDB-11-yellow)
![WordPress version](https://img.shields.io/badge/WordPress-7-21759B)
![Bedrock](https://img.shields.io/badge/Bedrock-roots-D0021B)

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

```bash
# Flush the WordPress cache
make wp ARGS="cache flush"
```

### Plugins &amp; themes via Composer

Bedrock manages plugins and themes through Composer (wpackagist) rather than the WordPress admin:

```bash
docker compose -f docker/docker-compose.yml --project-directory . exec php \
  composer require wpackagist-plugin/<slug>
```

## Roadmap

- [ ] Custom WordPress theme with Vite + Tailwind CSS 4 (`web/app/themes/custom/tailwind`), HMR dev server on `https://localhost:3009/`

## Documentation

- [`docs/README.md`](docs/README.md) — commandes Docker au quotidien (start/stop/logs, accès aux conteneurs, base de données)
- [`docker/README.md`](docker/README.md) — détails de l'environnement (Colima, Traefik, certificats, Bedrock/Composer/WP-CLI)
