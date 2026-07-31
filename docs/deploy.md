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
   `/home/joma2966/repositories/tailwindcss4-wordpress/web` (le `web/` de
   Bedrock — **pas** `web/wp`, qui n'est que le cœur WordPress installé par
   Composer, sans le bootstrap Bedrock ni le `.htaccess`).
2. **Base de données MySQL** : déjà créée (`joma2966_wordpress` /
   `joma2966_maxime`, cf. `.env.deploy`).
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
| `DEPLOY_SSH_USER` | `joma2966` |
| `DEPLOY_PROJECT_PATH` | `/home/joma2966/repositories/tailwindcss4-wordpress` |
| `DEPLOY_CPANEL_PASSWORD` | Mot de passe cPanel — **uniquement** pour l'API de whitelist SSH (port 2083), sans rapport avec la clé SSH |

La clé publique correspondant à `DEPLOY_SSH_KEY` doit être ajoutée aux
clés autorisées côté o2switch (cPanel > Accès SSH > Gérer les clés SSH >
Importer une clé) — génération suggérée :
`ssh-keygen -t ed25519 -f deploy_key -N ""`.

## 4. Premier déploiement — checklist

- [ ] Document root du sous-domaine pointé sur `.../web` (§1)
- [ ] `.env` généré et copié sur le serveur (`make deploy-env`)
- [ ] Premier `make deploy` (ou push sur `main`) réussi
- [ ] `make deploy-permalinks` (ou visiter *Réglages > Permaliens* et
  cliquer Enregistrer) pour que WordPress écrive `web/.htaccess` — ce
  fichier n'est pas versionné (cf. `.gitignore`), WordPress doit le
  générer lui-même au moins une fois sur le serveur
- [ ] Vérifier `https://wordpress.jolivetmaxime.fr/` en HTTPS sans erreur

## Fichiers concernés

- `.env.deploy` (non versionné) / `.env.deploy.example` (versionné) —
  config de déploiement
- `Makefile` — cibles `deploy`, `deploy-dry-run`, `deploy-env`,
  `deploy-permalinks`
- `bin/generate-production-env.php` — génère un `.env` de prod (salts
  aléatoires) à partir de `.env.deploy`
- `.github/workflows/deploy.yml` — CI/CD
