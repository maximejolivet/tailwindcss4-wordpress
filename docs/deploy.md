# Déploiement (o2switch)

Hébergement o2switch (cPanel), sous-domaine `wordpress.jolivetmaxime.fr`. Deux
chemins de déploiement disponibles :
- `make deploy` — manuel, depuis ta machine
- `.github/workflows/deploy.yml` — automatique, sur push vers `main`

Les deux font la même chose au fond : synchroniser le code vers le serveur
puis reconstruire les dépendances/assets. Ils diffèrent sur **où** tourne
`composer install` (cf. §3) à cause d'une contrainte locale : ne pas casser
le `vendor/` de dev (Pint, Pest) sur la machine du développeur.

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
les dépendances de dev (Pint, Pest) nécessaires au quotidien ; sur un
runner CI (checkout neuf à chaque fois), ce risque n'existe pas.

Le runner GitHub Actions a une IP différente à chaque exécution : le
workflow l'ajoute dynamiquement à la liste blanche SSH d'o2switch via
l'API cPanel (port 2083) avant de tenter la connexion SSH — pattern repris
de [ce gist](https://gist.github.com/webaxones/54a9aee13bd9152e900ef30a0fcef3ed),
spécifique aux hébergements o2switch/cPanel avec restriction SSH par IP.

### Secrets GitHub requis

À ajouter soi-même (`gh secret set ...` ou Settings > Secrets and
variables > Actions) — jamais en clair dans le chat :

| Secret | Valeur |
|---|---|
| `DEPLOY_SSH_KEY` | Clé privée SSH **dédiée** au déploiement (pas la clé perso) |
| `DEPLOY_SSH_HOST` | `loris.o2switch.net` |
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

## Fichiers concernés

- `.env.deploy` (non versionné) / `.env.deploy.example` (versionné) —
  config de déploiement
- `Makefile` — cibles `deploy`, `deploy-dry-run`, `deploy-env`,
  `deploy-permalinks`
- `bin/generate-production-env.php` — génère un `.env` de prod (salts
  aléatoires) à partir de `.env.deploy`
- `.github/workflows/deploy.yml` — CI/CD
