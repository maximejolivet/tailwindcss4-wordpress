# 🇫🇷 Déploiement (o2switch)

Hébergement o2switch (cPanel), sous-domaine `wordpress.jolivetmaxime.fr`. Un
seul chemin de déploiement : `.github/workflows/deploy.yml`, automatique sur
push vers `main`. Le `make deploy` manuel a été retiré le 2026-08-03 : deux
chemins redondants n'apportaient rien, et le `.env` de production est
désormais lui aussi géré automatiquement par le CI (cf. §2) — plus besoin
d'un chemin manuel séparé pour ça non plus.

## Sommaire

- [🇬🇧 Deployment (o2switch)](#-deployment-o2switch)
  - [Table of contents](#table-of-contents)
  - [1. One-shot cPanel setup (do once)](#1-one-shot-cpanel-setup-do-once)
  - [2. GitHub Actions (`deploy.yml`, automatic on push to `main`)](#2-github-actions-deployyml-automatic-on-push-to-main)
    - [Required GitHub secrets](#required-github-secrets)
  - [Related files](#related-files)
- [🇫🇷 Déploiement (o2switch)](#-déploiement-o2switch)
  - [Sommaire](#sommaire)
  - [1. Config one-shot côté cPanel (à faire une seule fois)](#1-config-one-shot-côté-cpanel-à-faire-une-seule-fois)
  - [2. GitHub Actions (`deploy.yml`, automatique sur push `main`)](#2-github-actions-deployyml-automatique-sur-push-main)
    - [Secrets GitHub requis](#secrets-github-requis)
  - [Fichiers concernés](#fichiers-concernés)

## 1. Config one-shot côté cPanel (à faire une seule fois)

1. **Document root du sous-domaine** : cPanel > Domaines/Sous-domaines >
   `wordpress.jolivetmaxime.fr` > modifier le document root vers
   `/home/{{user}}/repositories/tailwindcss4-wordpress/web` (le `web/` de
   Bedrock — **pas** `web/wp`, qui n'est que le cœur WordPress installé par
   Composer, sans le bootstrap Bedrock ni le `.htaccess`).
2. **Base de données MySQL** : déjà créée (`{{user}}_wordpress` /
   `{{user}}_maxime`).
3. **`.env` de production** : n'existe que sur le serveur, jamais versionné.
   Écrit automatiquement à chaque déploiement CI depuis le secret GitHub
   `DEPLOY_ENV_FILE` (cf. §2) — son contenu doit rester **fixe** d'un run à
   l'autre (mêmes salts WordPress, cf. `web/app/mu-plugins/sentry.php` pour
   `SENTRY_DSN`). Généré une fois avec `bin/generate-production-env.php`
   puis stocké via `gh secret set DEPLOY_ENV_FILE` — à ne régénérer que pour
   une rotation volontaire d'un credential (nouveau mot de passe DB, nouveau
   `SENTRY_DSN`...), jamais en routine.
4. **Accès SSH par IP** : o2switch restreint SSH par liste blanche d'IP
   (cPanel > Sécurité > Accès SSH). GitHub Actions gère ça tout seul à
   chaque run (l'IP du runner change à chaque fois, cf. §2) — ta machine
   locale n'a plus besoin d'y être pour déployer (plus de `make deploy`),
   sauf si tu veux te connecter en SSH toi-même pour du debug.

## 2. GitHub Actions (`deploy.yml`, automatique sur push `main`)

Seule voie de déploiement du projet. Le workflow **construit `vendor/` sur
le runner** (`composer install --no-dev`, PHP 8.4) et l'envoie tel quel par
rsync — il ne fait donc jamais tourner composer sur le serveur.

Deux jobs :
- **`Build & Quality`** : `composer install` (avec dev deps) →
  `composer validate`/`lint` (Pint)/`phpstan` (niveau 5)/`audit` — bloque
  la suite si l'un échoue — puis `composer install --no-dev` (élague pour
  la prod), build du thème (Vite), staging de l'arborescence déployable,
  upload en artefact. Le setup PHP + cache Composer est factorisé dans
  une action composite locale (`.github/actions/setup-php-composer/`).
- **`Deploy`** : checkout (pour `.github/scripts/o2switch-whitelist.sh`,
  absent de l'artefact), téléchargement de l'artefact, whitelist IP,
  attente que le port SSH réponde, rsync, **écriture du `.env` de
  production** (cf. ci-dessous), puis un smoke test qui vérifie que le
  CSS/JS du thème sont bien enqueued sur le site en prod. Déclare un
  environnement GitHub `production` (historique de déploiement visible
  dans l'onglet *Environments*).

Le runner GitHub Actions a une IP différente à chaque exécution : le
workflow l'ajoute dynamiquement à la liste blanche SSH d'o2switch via
l'API cPanel (port 2083) avant de tenter la connexion SSH — logique dans
`.github/scripts/o2switch-whitelist.sh` (pattern d'origine repris de
[ce gist](https://gist.github.com/webaxones/54a9aee13bd9152e900ef30a0fcef3ed),
spécifique aux hébergements o2switch/cPanel avec restriction SSH par IP).
Le script échoue explicitement si l'appel d'ajout à la whitelist ne
renvoie pas un succès (au lieu de continuer silencieusement puis
d'échouer plus tard sur un SSH "Permission denied" incompréhensible).

**`.env` de production automatique** : l'étape "Deploy production .env"
écrit le contenu du secret `DEPLOY_ENV_FILE` sur le serveur via SSH, à
chaque déploiement. Ce contenu doit rester **fixe** entre les runs : les
salts WordPress (`AUTH_KEY` etc.) invalident toutes les sessions/cookies/
nonces à chaque changement — les régénérer à chaque push déconnecterait
tous les visiteurs à chaque déploiement. Pour créer ou faire tourner ce
secret :

```bash
docker compose -f docker/docker-compose.yml --project-directory . \
  exec -T php php bin/generate-production-env.php DB_NAME DB_USER DB_PASSWORD DOMAIN SENTRY_DSN \
  | gh secret set DEPLOY_ENV_FILE
```

`workflow_dispatch` accepte un input `dry_run` (booléen) : build/whitelist/
SSH tournent normalement, mais `rsync` reçoit `--dry-run`, le `.env` n'est
pas écrit, et le smoke test est sauté — aperçu sûr de ce qu'un déploiement
changerait/supprimerait sur le serveur, sans rien modifier.

### Secrets GitHub requis

À ajouter soi-même (`gh secret set ...` ou Settings > Secrets and
variables > Actions) — jamais en clair dans le chat :

| Secret | Valeur |
|---|---|
| `DEPLOY_SSH_KEY` | Clé privée SSH **dédiée** au déploiement (pas la clé perso) |
| `DEPLOY_SSH_HOST` | `{{user}}.o2switch.net` |
| `DEPLOY_SSH_USER` | `{{user}}` |
| `DEPLOY_PROJECT_PATH` | `/home/{{user}}/repositories/tailwindcss4-wordpress` |
| `DEPLOY_CPANEL_PASSWORD` | Mot de passe cPanel — **uniquement** pour l'API de whitelist SSH (port 2083), sans rapport avec la clé SSH |
| `DEPLOY_ENV_FILE` | Contenu complet du `.env` de production (DB, salts WordPress, `SENTRY_DSN`...) — voir ci-dessus, contenu **fixe** entre les runs |

La clé publique correspondant à `DEPLOY_SSH_KEY` doit être ajoutée aux
clés autorisées côté o2switch (cPanel > Accès SSH > Gérer les clés SSH >
Importer une clé) — génération suggérée :
`ssh-keygen -t ed25519 -f deploy_key -N ""`.

## Fichiers concernés

- `.github/scripts/o2switch-whitelist.sh` — logique de whitelisting IP
  (utilisée par le job `Deploy`)
- `.github/actions/setup-php-composer/` — action composite (setup PHP +
  cache Composer + `composer install`), utilisée par `Build & Quality`
- `bin/generate-production-env.php` — génère le contenu du `.env` de prod
  à stocker (une fois, ou en rotation volontaire) dans le secret GitHub
  `DEPLOY_ENV_FILE`
- `.github/workflows/deploy.yml` — CI/CD, seule voie de déploiement

---

# 🇬🇧 Deployment (o2switch)

o2switch (cPanel) hosting, subdomain `wordpress.jolivetmaxime.fr`. A single
deployment path: `.github/workflows/deploy.yml`, automatic on push to
`main`. The manual `make deploy` was removed on 2026-08-03: two redundant
paths added nothing, and production `.env` is now also managed
automatically by CI (see §2) — no need for a separate manual path for that
either.

## Table of contents

- [🇬🇧 Deployment (o2switch)](#-deployment-o2switch)
  - [Table of contents](#table-of-contents)
  - [1. One-shot cPanel setup (do once)](#1-one-shot-cpanel-setup-do-once)
  - [2. GitHub Actions (`deploy.yml`, automatic on push to `main`)](#2-github-actions-deployyml-automatic-on-push-to-main)
    - [Required GitHub secrets](#required-github-secrets)
  - [Related files](#related-files)
- [🇫🇷 Déploiement (o2switch)](#-déploiement-o2switch)
  - [Sommaire](#sommaire)
  - [1. Config one-shot côté cPanel (à faire une seule fois)](#1-config-one-shot-côté-cpanel-à-faire-une-seule-fois)
  - [2. GitHub Actions (`deploy.yml`, automatique sur push `main`)](#2-github-actions-deployyml-automatique-sur-push-main)
    - [Secrets GitHub requis](#secrets-github-requis)
  - [Fichiers concernés](#fichiers-concernés)

## 1. One-shot cPanel setup (do once)

1. **Subdomain document root**: cPanel > Domains/Subdomains >
   `wordpress.jolivetmaxime.fr` > change the document root to
   `/home/{{user}}/repositories/tailwindcss4-wordpress/web` (Bedrock's
   `web/` — **not** `web/wp`, which is only the Composer-installed WordPress
   core, without Bedrock's bootstrap or `.htaccess`).
2. **MySQL database**: already created (`{{user}}_wordpress` /
   `{{user}}_maxime`).
3. **Production `.env`**: only exists on the server, never versioned.
   Written automatically on every CI deploy from the `DEPLOY_ENV_FILE`
   GitHub secret (see §2) — its content must stay **fixed** across runs
   (same WordPress salts every time; see `web/app/mu-plugins/sentry.php`
   for `SENTRY_DSN`). Generated once with `bin/generate-production-env.php`
   then stored via `gh secret set DEPLOY_ENV_FILE` — only regenerate it for
   a deliberate credential rotation (new DB password, new `SENTRY_DSN`...),
   never routinely.
4. **IP-restricted SSH access**: o2switch restricts SSH with an IP
   whitelist (cPanel > Security > SSH Access). GitHub Actions handles this
   on its own on every run (the runner's IP changes each time, see §2) —
   your local machine no longer needs to be on it to deploy (no more
   `make deploy`), unless you want to SSH in yourself for debugging.

## 2. GitHub Actions (`deploy.yml`, automatic on push to `main`)

The project's only deployment path. The workflow **builds `vendor/` on the
runner** (`composer install --no-dev`, PHP 8.4) and ships it as-is via
rsync — it never runs composer on the server.

Two jobs:
- **`Build & Quality`**: `composer install` (with dev deps) →
  `composer validate`/`lint` (Pint)/`phpstan` (level 5)/`audit` — stops
  the run if any of them fail — then `composer install --no-dev` (prunes
  for production), builds the theme (Vite), stages the deployable tree,
  uploads it as an artifact. PHP setup + Composer cache is factored into
  a local composite action (`.github/actions/setup-php-composer/`).
- **`Deploy`**: checkout (needed for
  `.github/scripts/o2switch-whitelist.sh`, which the artifact doesn't
  include), downloads the artifact, whitelists the IP, waits for the SSH
  port to respond, rsyncs, **writes production `.env`** (see below), then
  runs a smoke test that checks the theme's CSS/JS are actually enqueued
  on the live site. Declares a `production` GitHub environment (deployment
  history visible under the *Environments* tab).

The GitHub Actions runner gets a different IP on every run: the workflow
dynamically adds it to o2switch's SSH whitelist via the cPanel API (port
2083) before attempting the SSH connection — logic lives in
`.github/scripts/o2switch-whitelist.sh` (original pattern taken from
[this gist](https://gist.github.com/webaxones/54a9aee13bd9152e900ef30a0fcef3ed),
specific to o2switch/cPanel hosting with IP-restricted SSH). The script
fails explicitly if the whitelist-add call doesn't report success
(instead of silently continuing and failing later with an opaque SSH
"Permission denied").

**Automatic production `.env`**: the "Deploy production .env" step writes
the `DEPLOY_ENV_FILE` secret's content to the server over SSH, on every
deploy. This content must stay **fixed** across runs: WordPress auth salts
(`AUTH_KEY` etc.) invalidate every session/cookie/nonce when they change —
regenerating them on every push would log out every visitor on every
deploy. To create or rotate this secret:

```bash
docker compose -f docker/docker-compose.yml --project-directory . \
  exec -T php php bin/generate-production-env.php DB_NAME DB_USER DB_PASSWORD DOMAIN SENTRY_DSN \
  | gh secret set DEPLOY_ENV_FILE
```

`workflow_dispatch` takes a `dry_run` boolean input: build/whitelist/SSH
run normally, but `rsync` gets `--dry-run`, `.env` is not written, and the
smoke test is skipped — a safe preview of what a deploy would sync/delete
on the server, without changing anything.

### Required GitHub secrets

Add these yourself (`gh secret set ...` or Settings > Secrets and
variables > Actions) — never paste them in chat:

| Secret | Value |
|---|---|
| `DEPLOY_SSH_KEY` | SSH private key **dedicated** to deployment (not a personal key) |
| `DEPLOY_SSH_HOST` | `{{user}}.o2switch.net` |
| `DEPLOY_SSH_USER` | `{{user}}` |
| `DEPLOY_PROJECT_PATH` | `/home/{{user}}/repositories/tailwindcss4-wordpress` |
| `DEPLOY_CPANEL_PASSWORD` | cPanel password — **only** for the SSH whitelist API (port 2083), unrelated to the SSH key |
| `DEPLOY_ENV_FILE` | Full contents of the production `.env` (DB, WordPress salts, `SENTRY_DSN`...) — see above, content must stay **fixed** across runs |

The public key matching `DEPLOY_SSH_KEY` must be added to the authorized
keys on o2switch (cPanel > SSH Access > Manage SSH Keys > Import Key) —
suggested generation: `ssh-keygen -t ed25519 -f deploy_key -N ""`.

## Related files

- `.github/scripts/o2switch-whitelist.sh` — IP whitelisting logic (used
  by the `Deploy` job)
- `.github/actions/setup-php-composer/` — composite action (PHP setup +
  Composer cache + `composer install`), used by `Build & Quality`
- `bin/generate-production-env.php` — generates the production `.env`
  content to store (once, or on a deliberate rotation) in the
  `DEPLOY_ENV_FILE` GitHub secret
- `.github/workflows/deploy.yml` — CI/CD, the only deployment path
