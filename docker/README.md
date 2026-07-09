# Docker environment

The development stack runs entirely through Docker Compose
(`docker/docker-compose.yml`, driven by the `Makefile` at the repo root:
`make start`, `make stop`, `make restart`, `make status`, `make logs`).

## Colima

[Colima](https://github.com/abiosoft/colima) provides the Docker daemon on
macOS (a lightweight alternative to Docker Desktop, no license and no GUI).

### Start / stop

```bash
colima start   # start the VM and the Docker daemon
colima stop    # stop the VM
colima status  # current state (running/stopped)
colima list    # profile, arch, allocated CPU/memory/disk
```

`make start` (i.e.
`docker compose -f docker/docker-compose.yml --project-directory . up -d`)
fails with `no configuration file provided` or a Docker socket connection
error if Colima isn't running: run `colima start` before any
`make`/`docker compose` command.

`--project-directory .` is required: the compose file lives in `docker/`,
but its volumes (`.:/var/www/html`, `./docker/traefik/...`) and `.env` file
are resolved relative to the repo root, not the compose file's directory.

### Allocated resources

By default Colima starts with 2 CPU / 2 GiB RAM / 100 GiB disk. To adjust:

```bash
colima start --cpu 4 --memory 4 --disk 60
```

(requires `colima stop` then a fresh `colima start` if the VM already
exists with different values).

### Architecture (Apple Silicon)

On Apple Silicon Macs, Colima runs natively in `aarch64`. All images used in
`docker-compose.yml` (mariadb, node, phpmyadmin, mailhog, traefik, the `php`
image built from `docker/apache/Dockerfile`) are multi-arch and require no
emulation.

## Traefik

[Traefik](https://doc.traefik.io/traefik/) acts as a reverse proxy in front
of the stack's services: it terminates TLS (HTTPS) and routes requests to
the right container based on hostname.

### Configuration

- **Static config**: passed as `command:` arguments on the `traefik` service
  in `docker/docker-compose.yml` (entrypoints `web` (:80, redirected to
  HTTPS) and `websecure` (:443), file provider for dynamic config).
- **Dynamic config**: `docker/traefik/dynamic/routes.yml` (routers and
  services) and `docker/traefik/dynamic/tls.yml` (certificates). These files
  are watched (`--providers.file.watch=true`): any change is picked up
  without restarting the container.

### Current routes

| Host                                 | Service      | Internal target         |
|--------------------------------------|--------------|--------------------------|
| `tailwind-wordpress.localhost`       | WordPress    | `http://php:80`         |
| `pma.tailwind-wordpress.localhost`   | phpMyAdmin   | `http://phpmyadmin:80`  |
| `mail.tailwind-wordpress.localhost`  | Mailhog      | `http://mailhog:8025`   |

To add a route, add a router + service in `docker/traefik/dynamic/routes.yml`
following the same pattern.

### Local certificates (mkcert)

The certificates served by Traefik (`docker/traefik/certs/`) are generated
with [mkcert](https://github.com/FiloSottile/mkcert) and cover
`tailwind-wordpress.localhost` and `*.tailwind-wordpress.localhost`.

Install the local CA (once per machine):

```bash
mkcert -install
```

Regenerate the certificates if needed:

```bash
mkcert -cert-file docker/traefik/certs/tailwind-wordpress.localhost.pem \
       -key-file docker/traefik/certs/tailwind-wordpress.localhost-key.pem \
       tailwind-wordpress.localhost "*.tailwind-wordpress.localhost"
```

Without `mkcert -install`, the browser will show an untrusted certificate
warning even if the certificate itself is correctly generated.

## Node on macOS (Apple Silicon)

The `node` service in `docker-compose.yml` uses the official `node:22` image
(multi-arch, no special configuration needed on Apple Silicon).

## Bedrock (WordPress via Composer)

The `php` container bundles both `composer` and `wp` (WP-CLI). The
repo root is the Bedrock project root: `composer.json`, `config/`, `web/`
(with `web/wp` for WordPress core and `web/app` for
themes/plugins/mu-plugins/uploads).

First-time setup, once the stack is up (`make start`):

```bash
make install                     # composer install: pulls WordPress core + deps into web/wp and vendor/
make wp ARGS="core install --url=https://tailwind-wordpress.localhost --title=Site --admin_user=admin --admin_email=you@example.com --admin_password=..."
```

`.env` (repo root, gitignored, copy from `.env.example`) is read three times
with the same values: by Docker Compose (variable interpolation in
`docker-compose.yml`), by the `php`/`node` containers (`env_file`), and by
Bedrock's own `config/application.php` (via `vlucas/phpdotenv`). Keep
`DB_HOST=database` (the MariaDB service name) and `WP_HOME`/`WP_SITEURL`
matching the Traefik host above.

Plugins/themes are managed through Composer (wpackagist), not the WP admin:

```bash
docker compose -f docker/docker-compose.yml --project-directory . exec php \
  composer require wpackagist-plugin/<slug>
```
