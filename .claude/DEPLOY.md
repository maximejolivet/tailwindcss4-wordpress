# 🇫🇷 Déploiement (o2switch)

Hébergement o2switch (cPanel), sous-domaine `wordpress.jolivetmaxime.fr`. Deux
chemins de déploiement disponibles :
- `make deploy` — manuel, depuis ta machine
- `.github/workflows/deploy.yml` — automatique, sur push vers `main`

Les deux font la même chose au fond : synchroniser le code vers le serveur
puis reconstruire les dépendances/assets. Ils diffèrent sur **où** tourne
`composer install` (cf. §3) à cause d'une contrainte locale : ne pas casser
le `vendor/` de dev (Pint, PHPStan) sur la machine du développeur.

## Sommaire

- [🇬🇧 Deployment (o2switch)](#-deployment-o2switch)
  - [Table of contents](#table-of-contents)
  - [1. One-shot cPanel setup (do once)](#1-one-shot-cpanel-setup-do-once)
  - [2. `make deploy` (manual, from your machine)](#2-make-deploy-manual-from-your-machine)
  - [3. GitHub Actions (`deploy.yml`, automatic on push to `main`)](#3-github-actions-deployyml-automatic-on-push-to-main)
    - [Required GitHub secrets](#required-github-secrets)
  - [4. First deployment — checklist](#4-first-deployment--checklist)
  - [5. Real issues hit and fixed (don't redo these)](#5-real-issues-hit-and-fixed-dont-redo-these)
  - [Related files](#related-files)
- [🇫🇷 Déploiement (o2switch)](#-déploiement-o2switch)
  - [Sommaire](#sommaire)
  - [1. Config one-shot côté cPanel (à faire une seule fois)](#1-config-one-shot-côté-cpanel-à-faire-une-seule-fois)
  - [2. `make deploy` (manuel, depuis ta machine)](#2-make-deploy-manuel-depuis-ta-machine)
  - [3. GitHub Actions (`deploy.yml`, automatique sur push `main`)](#3-github-actions-deployyml-automatique-sur-push-main)
    - [Secrets GitHub requis](#secrets-github-requis)
  - [4. Premier déploiement — checklist](#4-premier-déploiement--checklist)
  - [5. Ennuis réels rencontrés et corrigés (à ne pas refaire)](#5-ennuis-réels-rencontrés-et-corrigés-à-ne-pas-refaire)
  - [Fichiers concernés](#fichiers-concernés)

## 1. Config one-shot côté cPanel (à faire une seule fois)

1. **Document root du sous-domaine** : cPanel > Domaines/Sous-domaines >
   `wordpress.jolivetmaxime.fr` > modifier le document root vers
   `/home/{{user}}/repositories/tailwindcss4-wordpress/web` (le `web/` de
   Bedrock — **pas** `web/wp`, qui n'est que le cœur WordPress installé par
   Composer, sans le bootstrap Bedrock ni le `.htaccess`).
2. **Base de données MySQL** : déjà créée (`{{user}}_wordpress` /
   `{{user}}_maxime`, cf. `.env.deploy`).
3. **`.env` de production** : n'existe que sur le serveur, jamais versionné,
   jamais généré par le CI. Le générer une fois avec `make deploy-env`
   (utilise les valeurs de `.env.deploy`, génère des salts WordPress
   fraîches, affiche un aperçu avant de le copier via `scp`).
4. **Accès SSH par IP** : o2switch restreint SSH par liste blanche d'IP
   (cPanel > Sécurité > Accès SSH). Le poste du développeur doit y être
   pour que `make deploy` fonctionne ; GitHub Actions gère ça tout seul à
   chaque run (l'IP du runner change à chaque fois, cf. §4).

## 2. `make deploy` (manuel, depuis ta machine)

```bash
make deploy-dry-run   # aperçu (rsync --dry-run), ne modifie rien sur le serveur
make deploy           # build du thème + rsync + composer install --no-dev à distance
```

Variables lues depuis `.env.deploy` (non versionné, cf.
`.env.deploy.example`). Le `rsync` réutilise `.gitignore` comme liste
d'exclusion (`--exclude-from=.gitignore`) : `vendor/`, `web/wp/`,
`web/app/plugins/*` etc. ne sont donc **pas** envoyés — `composer install
--no-dev` tourne ensuite directement sur le serveur pour les régénérer,
avec le bon binaire PHP (`DEPLOY_PHP_BIN`, ALT-PHP 8.4).

`web/app/uploads/` n'est jamais touché (exclu du rsync via `.gitignore`),
donc jamais écrasé ni vidé par un déploiement.

## 3. GitHub Actions (`deploy.yml`, automatique sur push `main`)

Contrairement à `make deploy`, le workflow CI **construit `vendor/` sur le
runner** (`composer install --no-dev`, PHP 8.4) et l'envoie tel quel par
rsync — il ne fait donc pas tourner composer sur le serveur. Cette
asymétrie avec `make deploy` est volontaire : sur la machine du
développeur, faire tourner `composer install --no-dev` en local viderait
les dépendances de dev (Pint, PHPStan) nécessaires au quotidien ; sur un
runner CI (checkout neuf à chaque fois), ce risque n'existe pas.

Deux jobs :
- **`Build & Quality`** : `composer install` (avec dev deps) →
  `composer validate`/`lint` (Pint)/`phpstan` (niveau 5)/`audit` — bloque
  la suite si l'un échoue — puis `composer install --no-dev` (élague pour
  la prod), build du thème (Vite), staging de l'arborescence déployable,
  upload en artefact. Le setup PHP + cache Composer est factorisé dans
  une action composite locale (`.github/actions/setup-php-composer/`).
- **`Deploy`** : checkout (pour `.github/scripts/o2switch-whitelist.sh`,
  absent de l'artefact), téléchargement de l'artefact, whitelist IP,
  attente que le port SSH réponde, rsync, puis un smoke test qui vérifie
  que le CSS/JS du thème sont bien enqueued sur le site en prod.
  Déclare un environnement GitHub `production` (historique de
  déploiement visible dans l'onglet *Environments*).

Le runner GitHub Actions a une IP différente à chaque exécution : le
workflow l'ajoute dynamiquement à la liste blanche SSH d'o2switch via
l'API cPanel (port 2083) avant de tenter la connexion SSH — logique dans
`.github/scripts/o2switch-whitelist.sh` (pattern d'origine repris de
[ce gist](https://gist.github.com/webaxones/54a9aee13bd9152e900ef30a0fcef3ed),
spécifique aux hébergements o2switch/cPanel avec restriction SSH par IP).
Le script échoue explicitement si l'appel d'ajout à la whitelist ne
renvoie pas un succès (au lieu de continuer silencieusement puis
d'échouer plus tard sur un SSH "Permission denied" incompréhensible).

`workflow_dispatch` accepte un input `dry_run` (booléen) — équivalent CI
de `make deploy-dry-run` : build/whitelist/SSH tournent normalement, mais
`rsync` reçoit `--dry-run` et le smoke test est sauté.

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

La clé publique correspondant à `DEPLOY_SSH_KEY` doit être ajoutée aux
clés autorisées côté o2switch (cPanel > Accès SSH > Gérer les clés SSH >
Importer une clé) — génération suggérée :
`ssh-keygen -t ed25519 -f deploy_key -N ""`.

## 4. Premier déploiement — checklist

- [x] Document root du sous-domaine pointé sur `.../web` (§1)
- [x] `.env` généré et copié sur le serveur (`make deploy-env`)
- [x] Premier déploiement réussi (push sur `main`, workflow `Deploy` vert)
- [x] `make deploy-permalinks` exécuté (pas d'erreur)
- [x] `https://wordpress.jolivetmaxime.fr/` vérifié en HTTPS — WordPress
  fonctionne

Pipeline vérifié de bout en bout le 2026-07-31.

## 5. Ennuis réels rencontrés et corrigés (à ne pas refaire)

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
3. **`Permission denied (publickey...)` au rsync** — la clé publique avait
   été **importée** dans cPanel (Accès SSH > Gérer les clés SSH) mais pas
   **autorisée** (case à part, facile à manquer) : importer une clé SSH ne
   suffit pas à cPanel, il faut explicitement l'autoriser avant qu'elle
   fonctionne pour l'authentification.
4. **CSS/JS absents en prod après un déploiement, sans erreur nulle part**
   (2026-08-02) — `actions/upload-artifact` exclut les fichiers/dossiers
   cachés par défaut depuis la v4.4 (anti-fuite de secrets) ; le manifest
   Vite (`dist/.vite/manifest.json`) vit dans un dossier caché, donc
   l'artefact le perdait silencieusement, `inc/vite.php` ne trouvait pas
   de manifest et n'enqueue rien (pas d'erreur PHP). Corrigé avec
   `include-hidden-files: true` sur `actions/upload-artifact`.
5. **Ce même bug de whitelist a refait surface** au premier run du
   pipeline fusionné (2026-08-03) — le job `Deploy` ne faisait qu'un
   `download-artifact`, jamais de `checkout` : `.github/scripts/
   o2switch-whitelist.sh` (extrait du YAML ce jour-là) n'existe que dans
   le repo source, pas dans l'artefact déployable (`.github` en est
   exclu). Corrigé en ajoutant `actions/checkout@v7` en premier step du
   job `Deploy`.

## Fichiers concernés

- `.env.deploy` (non versionné) / `.env.deploy.example` (versionné) —
  config de déploiement
- `Makefile` — cibles `deploy`, `deploy-dry-run`, `deploy-env`,
  `deploy-permalinks`
- `.github/scripts/o2switch-whitelist.sh` — logique de whitelisting IP
  (utilisée par le job `Deploy`)
- `.github/actions/setup-php-composer/` — action composite (setup PHP +
  cache Composer + `composer install`), utilisée par `Build & Quality`
- `bin/generate-production-env.php` — génère un `.env` de prod (salts
  aléatoires) à partir de `.env.deploy`
- `.github/workflows/deploy.yml` — CI/CD

---

# 🇬🇧 Deployment (o2switch)

o2switch (cPanel) hosting, subdomain `wordpress.jolivetmaxime.fr`. Two
deployment paths available:
- `make deploy` — manual, from your machine
- `.github/workflows/deploy.yml` — automatic, on push to `main`

Both do fundamentally the same thing: sync the code to the server, then
rebuild dependencies/assets. They differ on **where** `composer install`
runs (see §3) because of a local constraint: not breaking the dev `vendor/`
(Pint, PHPStan) on the developer's machine.

## Table of contents

- [🇬🇧 Deployment (o2switch)](#-deployment-o2switch)
  - [Table of contents](#table-of-contents)
  - [1. One-shot cPanel setup (do once)](#1-one-shot-cpanel-setup-do-once)
  - [2. `make deploy` (manual, from your machine)](#2-make-deploy-manual-from-your-machine)
  - [3. GitHub Actions (`deploy.yml`, automatic on push to `main`)](#3-github-actions-deployyml-automatic-on-push-to-main)
    - [Required GitHub secrets](#required-github-secrets)
  - [4. First deployment — checklist](#4-first-deployment--checklist)
  - [5. Real issues hit and fixed (don't redo these)](#5-real-issues-hit-and-fixed-dont-redo-these)
  - [Related files](#related-files)
- [🇫🇷 Déploiement (o2switch)](#-déploiement-o2switch)
  - [Sommaire](#sommaire)
  - [1. Config one-shot côté cPanel (à faire une seule fois)](#1-config-one-shot-côté-cpanel-à-faire-une-seule-fois)
  - [2. `make deploy` (manuel, depuis ta machine)](#2-make-deploy-manuel-depuis-ta-machine)
  - [3. GitHub Actions (`deploy.yml`, automatique sur push `main`)](#3-github-actions-deployyml-automatique-sur-push-main)
    - [Secrets GitHub requis](#secrets-github-requis)
  - [4. Premier déploiement — checklist](#4-premier-déploiement--checklist)
  - [5. Ennuis réels rencontrés et corrigés (à ne pas refaire)](#5-ennuis-réels-rencontrés-et-corrigés-à-ne-pas-refaire)
  - [Fichiers concernés](#fichiers-concernés)

## 1. One-shot cPanel setup (do once)

1. **Subdomain document root**: cPanel > Domains/Subdomains >
   `wordpress.jolivetmaxime.fr` > change the document root to
   `/home/{{user}}/repositories/tailwindcss4-wordpress/web` (Bedrock's
   `web/` — **not** `web/wp`, which is only the Composer-installed WordPress
   core, without Bedrock's bootstrap or `.htaccess`).
2. **MySQL database**: already created (`{{user}}_wordpress` /
   `{{user}}_maxime`, see `.env.deploy`).
3. **Production `.env`**: only exists on the server, never versioned, never
   generated by CI. Generate it once with `make deploy-env` (uses the
   values from `.env.deploy`, generates fresh WordPress salts, shows a
   preview before copying it via `scp`).
4. **IP-restricted SSH access**: o2switch restricts SSH with an IP
   whitelist (cPanel > Security > SSH Access). The developer's machine
   needs to be on it for `make deploy` to work; GitHub Actions handles this
   on its own on every run (the runner's IP changes each time, see §4).

## 2. `make deploy` (manual, from your machine)

```bash
make deploy-dry-run   # preview (rsync --dry-run), changes nothing on the server
make deploy           # build the theme + rsync + composer install --no-dev remotely
```

Variables are read from `.env.deploy` (not versioned, see
`.env.deploy.example`). `rsync` reuses `.gitignore` as its exclude list
(`--exclude-from=.gitignore`): `vendor/`, `web/wp/`, `web/app/plugins/*`
etc. are therefore **not** sent — `composer install --no-dev` then runs
directly on the server to regenerate them, with the right PHP binary
(`DEPLOY_PHP_BIN`, ALT-PHP 8.4).

`web/app/uploads/` is never touched (excluded from rsync via
`.gitignore`), so it's never overwritten or wiped by a deploy.

## 3. GitHub Actions (`deploy.yml`, automatic on push to `main`)

Unlike `make deploy`, the CI workflow **builds `vendor/` on the runner**
(`composer install --no-dev`, PHP 8.4) and ships it as-is via rsync — it
doesn't run composer on the server at all. This asymmetry with `make
deploy` is intentional: on the developer's machine, running `composer
install --no-dev` locally would wipe the dev dependencies (Pint, PHPStan)
needed day to day; on a CI runner (fresh checkout every time), that risk
doesn't exist.

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
  port to respond, rsyncs, then runs a smoke test that checks the
  theme's CSS/JS are actually enqueued on the live site. Declares a
  `production` GitHub environment (deployment history visible under the
  *Environments* tab).

The GitHub Actions runner gets a different IP on every run: the workflow
dynamically adds it to o2switch's SSH whitelist via the cPanel API (port
2083) before attempting the SSH connection — logic lives in
`.github/scripts/o2switch-whitelist.sh` (original pattern taken from
[this gist](https://gist.github.com/webaxones/54a9aee13bd9152e900ef30a0fcef3ed),
specific to o2switch/cPanel hosting with IP-restricted SSH). The script
fails explicitly if the whitelist-add call doesn't report success
(instead of silently continuing and failing later with an opaque SSH
"Permission denied").

`workflow_dispatch` takes a `dry_run` boolean input — the CI equivalent
of `make deploy-dry-run`: build/whitelist/SSH run normally, but `rsync`
gets `--dry-run` and the smoke test is skipped.

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

The public key matching `DEPLOY_SSH_KEY` must be added to the authorized
keys on o2switch (cPanel > SSH Access > Manage SSH Keys > Import Key) —
suggested generation: `ssh-keygen -t ed25519 -f deploy_key -N ""`.

## 4. First deployment — checklist

- [x] Subdomain document root pointed at `.../web` (§1)
- [x] Production `.env` generated and copied to the server (`make deploy-env`)
- [x] First deployment succeeded (push to `main`, `Deploy` workflow green)
- [x] `make deploy-permalinks` run (no error)
- [x] `https://wordpress.jolivetmaxime.fr/` checked over HTTPS — WordPress
  works

Pipeline verified end to end on 2026-07-31.

## 5. Real issues hit and fixed (don't redo these)

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
3. **`Permission denied (publickey...)` on rsync** — the public key had
   been **imported** into cPanel (SSH Access > Manage SSH Keys) but not
   **authorized** (a separate checkbox, easy to miss): importing an SSH key
   isn't enough in cPanel, it must be explicitly authorized before it works
   for authentication.
4. **CSS/JS missing in production after a deploy, no error anywhere**
   (2026-08-02) — `actions/upload-artifact` excludes hidden files/dirs by
   default since v4.4 (to avoid leaking secrets by accident); Vite's
   manifest (`dist/.vite/manifest.json`) lives in a hidden folder, so the
   artifact silently dropped it, `inc/vite.php` found no manifest and
   enqueued nothing (no PHP error). Fixed with `include-hidden-files:
   true` on `actions/upload-artifact`.
5. **The same whitelist script resurfaced this bug** on the first run of
   the merged pipeline (2026-08-03) — the `Deploy` job only ever did a
   `download-artifact`, never a `checkout`: `.github/scripts/
   o2switch-whitelist.sh` (extracted out of the YAML that same day) only
   exists in the source repo, not in the deployable artifact (`.github`
   is excluded from it). Fixed by adding `actions/checkout@v7` as the
   first step of the `Deploy` job.

## Related files

- `.env.deploy` (not versioned) / `.env.deploy.example` (versioned) —
  deployment config
- `Makefile` — `deploy`, `deploy-dry-run`, `deploy-env`,
  `deploy-permalinks` targets
- `.github/scripts/o2switch-whitelist.sh` — IP whitelisting logic (used
  by the `Deploy` job)
- `.github/actions/setup-php-composer/` — composite action (PHP setup +
  Composer cache + `composer install`), used by `Build & Quality`
- `bin/generate-production-env.php` — generates a production `.env`
  (random salts) from `.env.deploy`
- `.github/workflows/deploy.yml` — CI/CD
