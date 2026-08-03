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
  - [3. First deployment — checklist](#3-first-deployment--checklist)
  - [4. Real issues hit and fixed (don't redo these)](#4-real-issues-hit-and-fixed-dont-redo-these)
  - [Related files](#related-files)
- [🇫🇷 Déploiement (o2switch)](#-déploiement-o2switch)
  - [Sommaire](#sommaire)
  - [1. Config one-shot côté cPanel (à faire une seule fois)](#1-config-one-shot-côté-cpanel-à-faire-une-seule-fois)
  - [2. GitHub Actions (`deploy.yml`, automatique sur push `main`)](#2-github-actions-deployyml-automatique-sur-push-main)
    - [Secrets GitHub requis](#secrets-github-requis)
  - [3. Premier déploiement — checklist](#3-premier-déploiement--checklist)
  - [4. Ennuis réels rencontrés et corrigés (à ne pas refaire)](#4-ennuis-réels-rencontrés-et-corrigés-à-ne-pas-refaire)
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

## 3. Premier déploiement — checklist

- [x] Document root du sous-domaine pointé sur `.../web` (§1)
- [x] `.env` de production généré et poussé sur le serveur (manuellement le
  2026-07-31, automatisé via `DEPLOY_ENV_FILE` depuis le 2026-08-03)
- [x] Premier déploiement réussi (push sur `main`, workflow `Deploy` vert)
- [x] Permaliens vidés une fois après le premier déploiement (plus de cible
  `make` dédiée depuis le 2026-08-03 — si jamais nécessaire à nouveau :
  `wp rewrite flush --hard --path=.` via SSH, ou wp-admin > Réglages >
  Permaliens)
- [x] `https://wordpress.jolivetmaxime.fr/` vérifié en HTTPS — WordPress
  fonctionne

Pipeline vérifié de bout en bout le 2026-07-31 ; passage au tout-CI (`.env`
inclus, plus de `make deploy*`) le 2026-08-03.

## 4. Ennuis réels rencontrés et corrigés (à ne pas refaire)

1. **Clé SSH invalide côté runner** (`error in libcrypto` au chargement) —
   la clé initialement utilisée pour `DEPLOY_SSH_KEY` était en fait une
   ancienne clé DSA (`id_dsa`), algorithme désactivé par défaut sur les
   OpenSSH récents, avec en plus des permissions trop ouvertes
   (`0644`) qui la faisaient rejeter même en local. Corrigé en générant une
   clé **ed25519** dédiée (`ssh-keygen -t ed25519 -f deploy_key -N ""`) et
   en remplaçant l'écriture manuelle du fichier de clé (`printf ... > ~/.ssh/deploy_key`)
   par `webfactory/ssh-agent`, plus robuste pour charger un secret
   multi-lignes.
2. **`ssh-keyscan` qui timeout sans aucun message** — en fait la connexion
   SSH était bloquée par la liste blanche IP d'o2switch : l'appel API de
   whitelisting retournait `HTTP 200` mais avec
   `{"success":false,"message":"Vous avez atteint la limite d'exceptions
   autorisées."}` — le nombre d'IP whitelistées a un plafond, et comme
   chaque run GitHub Actions a une IP différente sans jamais nettoyer les
   anciennes, le quota finissait par être atteint. Le script de
   suppression automatique des anciennes entrées (repris du gist) n'a pas
   fonctionné du premier coup (format HTML de la page de whitelist
   différent de celui du gist d'origine, daté de 2024) — nettoyage fait
   manuellement une fois via cPanel en attendant d'ajuster le pattern
   d'extraction.
3. **Ce même bug de whitelist a refait surface** au premier run du
   pipeline fusionné (2026-08-03) — le job `Deploy` ne faisait qu'un
   `download-artifact`, jamais de `checkout` : `.github/scripts/
   o2switch-whitelist.sh` (extrait du YAML ce jour-là) n'existe que dans
   le repo source, pas dans l'artefact déployable (`.github` en est
   exclu). Corrigé en ajoutant `actions/checkout@v7` en premier step du
   job `Deploy`.
4. **`make deploy-env` bloqué par `Permission denied (publickey...)` en
   local** (2026-08-03) — la machine du développeur n'était pas (ou plus)
   whitelistée/autorisée côté o2switch pour du SSH manuel, contrairement
   au runner GitHub Actions qui gère sa propre whitelist à chaque run.
   Plutôt que déboguer l'accès SSH local, décision de supprimer `make
   deploy*` entièrement et de faire porter l'écriture du `.env` par le CI
   lui-même (`DEPLOY_ENV_FILE`, cf. §2) — un seul chemin de déploiement,
   plus de divergence possible entre "ce que fait `make deploy`" et "ce
   que fait le CI".
5. **Permaliens cassés en prod après (quasiment) chaque déploiement CI,
   sans que le smoke test ne le détecte** (découvert le 2026-08-03) —
   `web/.htaccess` est gitignore (convention Bedrock : WordPress le génère
   lui-même au flush des permaliens), donc jamais présent dans le repo ni
   dans l'artefact déployable. Le rsync du job `Deploy` tourne avec
   `--delete` sans l'exclure : à chaque déploiement, `--delete` supprimait
   donc `web/.htaccess` du serveur. Le smoke test ne l'a jamais vu passer
   au rouge parce qu'il ne teste que la page d'accueil, qui répond 200
   même sans `.htaccess` — seules les URLs à permaliens "jolis" (articles,
   pages, flux RSS...) 404 sans lui. Corrigé en ajoutant
   `--exclude 'web/.htaccess'` au rsync, au même titre que `.env`/
   `uploads`/`cache`. Après ce correctif, les permaliens doivent être
   reflushés une fois (`wp rewrite flush --hard` en SSH, ou wp-admin >
   Réglages > Permaliens > Enregistrer) pour régénérer le `.htaccess`
   manquant sur le serveur — les déploiements suivants le préserveront.

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
  - [3. First deployment — checklist](#3-first-deployment--checklist)
  - [4. Real issues hit and fixed (don't redo these)](#4-real-issues-hit-and-fixed-dont-redo-these)
  - [Related files](#related-files)
- [🇫🇷 Déploiement (o2switch)](#-déploiement-o2switch)
  - [Sommaire](#sommaire)
  - [1. Config one-shot côté cPanel (à faire une seule fois)](#1-config-one-shot-côté-cpanel-à-faire-une-seule-fois)
  - [2. GitHub Actions (`deploy.yml`, automatique sur push `main`)](#2-github-actions-deployyml-automatique-sur-push-main)
    - [Secrets GitHub requis](#secrets-github-requis)
  - [3. Premier déploiement — checklist](#3-premier-déploiement--checklist)
  - [4. Ennuis réels rencontrés et corrigés (à ne pas refaire)](#4-ennuis-réels-rencontrés-et-corrigés-à-ne-pas-refaire)
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

## 3. First deployment — checklist

- [x] Subdomain document root pointed at `.../web` (§1)
- [x] Production `.env` generated and copied to the server (manually on
  2026-07-31, automated via `DEPLOY_ENV_FILE` since 2026-08-03)
- [x] First deployment succeeded (push to `main`, `Deploy` workflow green)
- [x] Permalinks flushed once after the first deployment (no dedicated
  `make` target since 2026-08-03 — if ever needed again:
  `wp rewrite flush --hard --path=.` over SSH, or wp-admin > Settings >
  Permalinks)
- [x] `https://wordpress.jolivetmaxime.fr/` checked over HTTPS — WordPress
  works

Pipeline verified end to end on 2026-07-31; moved to all-CI (`.env`
included, no more `make deploy*`) on 2026-08-03.

## 4. Real issues hit and fixed (don't redo these)

1. **Invalid SSH key on the runner** (`error in libcrypto` when loading) —
   the key initially used for `DEPLOY_SSH_KEY` was actually an old DSA key
   (`id_dsa`), an algorithm disabled by default on recent OpenSSH, plus
   overly open permissions (`0644`) that got it rejected even locally.
   Fixed by generating a dedicated **ed25519** key
   (`ssh-keygen -t ed25519 -f deploy_key -N ""`) and replacing the manual
   key-file write (`printf ... > ~/.ssh/deploy_key`) with
   `webfactory/ssh-agent`, which is more robust at loading a multi-line
   secret.
2. **`ssh-keyscan` timing out with no message at all** — the SSH connection
   was actually being blocked by o2switch's IP whitelist: the whitelisting
   API call returned `HTTP 200` but with
   `{"success":false,"message":"Vous avez atteint la limite d'exceptions
   autorisées."}` — the number of whitelisted IPs has a cap, and since
   every GitHub Actions run has a different IP and nothing ever cleaned up
   old ones, the quota eventually got hit. The script that auto-removes old
   entries (taken from the gist) didn't work on the first try (the
   whitelist page's HTML format differs from the original 2024 gist) —
   cleaned up manually once via cPanel while waiting to adjust the
   extraction pattern.
3. **The same whitelist script resurfaced this bug** on the first run of
   the merged pipeline (2026-08-03) — the `Deploy` job only ever did a
   `download-artifact`, never a `checkout`: `.github/scripts/
   o2switch-whitelist.sh` (extracted out of the YAML that same day) only
   exists in the source repo, not in the deployable artifact (`.github`
   is excluded from it). Fixed by adding `actions/checkout@v7` as the
   first step of the `Deploy` job.
4. **`make deploy-env` blocked by `Permission denied (publickey...)`
   locally** (2026-08-03) — the developer's machine wasn't (or no longer)
   whitelisted/authorized on o2switch for manual SSH, unlike the GitHub
   Actions runner, which manages its own whitelist entry on every run.
   Rather than debug local SSH access, the decision was to remove `make
   deploy*` entirely and have CI itself own writing `.env`
   (`DEPLOY_ENV_FILE`, see §2) — a single deployment path, no more
   possible drift between "what `make deploy` does" and "what CI does".
5. **Permalinks broken in production after (almost) every CI deploy, with
   the smoke test never catching it** (discovered 2026-08-03) —
   `web/.htaccess` is gitignored (Bedrock convention: WordPress generates
   it itself on permalink flush), so it's never in the repo or the
   deployable artifact. The `Deploy` job's rsync runs with `--delete`
   without excluding it: every deploy, `--delete` removed `web/.htaccess`
   from the server. The smoke test never went red because it only checks
   the homepage, which still returns 200 without `.htaccess` — only
   "pretty" permalink URLs (posts, pages, RSS feeds...) 404 without it.
   Fixed by adding `--exclude 'web/.htaccess'` to the rsync, alongside
   `.env`/`uploads`/`cache`. After this fix, permalinks need flushing once
   (`wp rewrite flush --hard` over SSH, or wp-admin > Settings >
   Permalinks > Save) to regenerate the missing `.htaccess` on the
   server — every deploy after that will preserve it.

## Related files

- `.github/scripts/o2switch-whitelist.sh` — IP whitelisting logic (used
  by the `Deploy` job)
- `.github/actions/setup-php-composer/` — composite action (PHP setup +
  Composer cache + `composer install`), used by `Build & Quality`
- `bin/generate-production-env.php` — generates the production `.env`
  content to store (once, or on a deliberate rotation) in the
  `DEPLOY_ENV_FILE` GitHub secret
- `.github/workflows/deploy.yml` — CI/CD, the only deployment path
