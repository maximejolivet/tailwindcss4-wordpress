# Thème custom (`web/app/themes/custom/tailwind`)

Thème WordPress construit avec [Timber v2](https://timber.github.io/docs/v2/) (templates Twig) et [Tailwind CSS 4](https://tailwindcss.com/) compilé par [Vite](https://vitejs.dev/), avec un serveur de développement HMR.

## Stack

| Brique | Rôle |
|---|---|
| [Timber v2](https://timber.github.io/docs/v2/) | Sépare la logique WordPress (PHP) du rendu (templates Twig dans `views/`) |
| [Twig](https://twig.symfony.com/) | Moteur de templates utilisé par Timber |
| [Tailwind CSS 4](https://tailwindcss.com/) | Classes utilitaires, config CSS-first (pas de `tailwind.config.js`) |
| [Vite](https://vitejs.dev/) | Bundler des assets (`assets/scripts/app.js` + `assets/styles/app.css`), build de prod et serveur dev avec HMR |

Le thème est installé en local (pas via Composer/wpackagist comme `twentytwentyfive`) : c'est un dossier versionné directement dans le dépôt.

## Arborescence

```
web/app/themes/custom/tailwind/
├── style.css                  # En-tête WordPress obligatoire (métadonnées uniquement, pas de styles)
├── functions.php              # Bootstrap : Timber::init(), instancie Site, charge inc/vite.php
├── index.php                  # Template générique (archives, blog)
├── front-page.php             # Page d'accueil statique
├── page.php                   # Pages
├── single.php                 # Articles
├── 404.php                    # Page introuvable
├── src/
│   └── Site.php               # App\Theme\Site extends Timber\Site : theme supports, menus, contexte Twig
├── inc/
│   └── vite.php                # Bascule dev (HMR) / prod (manifest) pour les assets
├── views/
│   ├── layouts/base.twig      # Layout HTML de base (head, header, footer, wp_head/wp_footer)
│   ├── partials/              # head.twig, menu.twig (récursif), footer.twig
│   └── templates/             # index.twig, front-page.twig, page.twig, single.twig, 404.twig
├── assets/
│   ├── styles/app.css         # `@import "tailwindcss"` + `@source` + `@plugin` typography
│   ├── scripts/app.js         # Point d'entrée JS, importe app.css
│   ├── images/, fonts/        # Vides (`.gitkeep`), à utiliser au besoin
├── package.json
└── vite.config.js
```

`dist/` (build de prod) et `node_modules/` sont générés, non versionnés (voir `.gitignore` racine).

## Timber / Twig

### Bootstrap (`functions.php`)

```php
namespace App\Theme;

use Timber\Timber;

Timber::init();

new Site();          // src/Site.php — active les theme supports, menus, contexte
require __DIR__ . '/inc/vite.php';
```

Timber cherche les `.twig` dans le dossier `views/` par défaut (`Timber::$dirname`), donc les chemins passés à `Timber::render()` sont relatifs à `views/` (ex. `templates/page.twig`).

### `App\Theme\Site` (`src/Site.php`)

Étend `Timber\Site` :
- `theme_supports()` (hook `after_setup_theme`) : `title-tag`, `post-thumbnails`, `automatic-feed-links`, `editor-styles`, `html5`, et enregistre le menu `primary`.
- `add_to_context()` (filtre `timber/context`) : ajoute `site` (l'instance `Site`, donne accès à `site.name`, `site.url`, `site.charset`, `site.language_attributes`…) et `menu` (`Timber::get_menu('primary')`) au contexte disponible dans tous les templates Twig.

Pour ajouter des données globales à tous les templates (ex. un réglage ACF, une option), c'est dans `add_to_context()` qu'il faut les ajouter.

Pour ajouter un custom post type / taxonomie, compléter `register_post_types()` / `register_taxonomies()` (stubs présents dans la classe, hookés sur `init`).

### Hiérarchie de templates

Chaque fichier PHP à la racine du thème suit la [hiérarchie de templates WordPress](https://developer.wordpress.org/themes/basics/template-hierarchy/) standard et délègue le rendu à un `.twig` dans `views/templates/` :

| Fichier PHP | Contexte | Template Twig rendu |
|---|---|---|
| `index.php` | Archives, blog | `templates/index.twig` (+ `templates/front-page.twig` en priorité si `is_front_page()`) |
| `front-page.php` | Page d'accueil statique (réglage *Réglages > Lecture*) | `templates/front-page.twig` |
| `page.php` | Page (post_type `page`) | `templates/page-{slug}.twig` si présent, sinon `templates/page.twig` |
| `single.php` | Article (ou tout post type) | `templates/single-{post_type}.twig` si présent, sinon `templates/single.twig` |
| `404.php` | Page introuvable | `templates/404.twig` |

**Ajouter un template dédié** : par exemple pour la page "Contact" (slug `contact`), créer `views/templates/page-contact.twig` (qui peut faire `{% extends 'layouts/base.twig' %}`) — `page.php` le détecte automatiquement, aucune modification PHP nécessaire. Même logique pour un post type `event` → `views/templates/single-event.twig`.

### Layout et partials

- `views/layouts/base.twig` : structure HTML complète (`<html>`, `<head>` via `partials/head.twig`, `<header>` avec logo + `partials/menu.twig`, `<main>` avec le `{% block content %}`, `partials/footer.twig`, `wp_footer()`). Chaque template de `views/templates/` fait `{% extends 'layouts/base.twig' %}` et remplit `{% block content %}`.
- `partials/head.twig` : `<meta charset>`, viewport, `wp_head()`.
- `partials/menu.twig` : rendu récursif d'un menu Timber (`item.children`), utilisé pour le menu `primary`.
- `partials/footer.twig` : copyright.

### Contexte disponible dans les Twig

En plus de ce qu'ajoute `Timber::context()` par défaut (`post`, `posts`, `body_class`, etc. selon le fichier PHP), tous les templates ont accès à :
- `site` — objet `Site` (`site.name`, `site.url`, `site.charset`, `site.language_attributes`, `site.theme`…)
- `menu` — items du menu de navigation `primary` (à assigner dans *Apparence > Menus*)

## Assets (Tailwind CSS 4 + Vite)

### CSS (`assets/styles/app.css`)

```css
@import "tailwindcss";
@plugin "@tailwindcss/typography";

@source "../../views/**/*.twig";
@source "../../**/*.php";
```

Tailwind CSS 4 utilise une configuration **CSS-first** : pas de `tailwind.config.js`. Les directives `@source` indiquent explicitement où scanner les classes utilisées (par défaut Tailwind ne regarde que depuis la racine du projet Vite en respectant `.gitignore`, ce qui suffirait probablement mais on le rend explicite). Le plugin `@tailwindcss/typography` fournit les classes `prose`/`prose-neutral` utilisées dans `templates/page.twig`, `single.twig`, `front-page.twig` pour le contenu WYSIWYG.

Les diagnostics IDE du type *"Unknown at rule @plugin/@source"* sont normaux : le serveur de langage CSS ne connaît pas encore ces at-rules Tailwind v4, ce n'est pas une erreur fonctionnelle.

### JS (`assets/scripts/app.js`)

Point d'entrée unique, importe le CSS :

```js
import '../styles/app.css';
```

Pour ajouter du JS, l'écrire dans ce fichier (ou l'importer depuis un autre module) — le point d'entrée Vite (`vite.config.js` → `build.rollupOptions.input`) est unique (`assets/scripts/app.js`).

### Build de production (`make vite-build`)

```bash
make vite-build
# → npm --prefix web/app/themes/custom/tailwind run build
```

Génère `dist/assets/*.{js,css}` (fichiers hashés) et `dist/.vite/manifest.json`. C'est ce manifest que `inc/vite.php` lit pour enqueue les bons fichiers en production.

### Serveur de développement / HMR (`make vite-dev`)

```bash
make vite-dev
# → npm --prefix web/app/themes/custom/tailwind run dev
```

Démarre Vite sur `https://tailwind-wordpress.localhost:3009/` (port exposé directement par le service `node`, en dehors de Traefik) :
- Réutilise les certificats mkcert de Traefik (`docker/traefik/certs/tailwind-wordpress.localhost{,-key}.pem`) pour servir en HTTPS avec un certificat déjà approuvé — pas d'avertissement navigateur.
- `hmr: { host, protocol: 'wss' }` : le client HMR se connecte en WebSocket sécurisé sur ce même host/port.

### Bascule dev / prod (`inc/vite.php`)

À chaque chargement de page (`wp_enqueue_scripts`), le thème détecte si le serveur Vite tourne :

```php
$connection = @fsockopen(VITE_INTERNAL_HOST, VITE_DEV_PORT, ...); // 'node', 3009
```

Le test utilise le nom de service Docker interne `node:3009` (résoluble uniquement depuis le conteneur `php`, via le réseau Compose), pas `tailwind-wordpress.localhost` (qui n'est résolu que côté hôte/navigateur). C'est une distinction importante si on modifie ce fichier :
- **Détection du serveur dev** (côté serveur, PHP → Node) : `node:3009`.
- **URLs des scripts servies au navigateur** : `https://tailwind-wordpress.localhost:3009` (`VITE_DEV_HOST`).

Si le serveur dev répond (et `WP_ENV=development`) :
- enqueue `https://tailwind-wordpress.localhost:3009/@vite/client` (client HMR)
- enqueue `https://tailwind-wordpress.localhost:3009/assets/scripts/app.js` (source non compilée, transformée à la volée)

Sinon, lit `dist/.vite/manifest.json` et enqueue les fichiers buildés (`dist/assets/app-XXXX.js`, `dist/assets/app-XXXX.css`).

Dans les deux cas, un filtre `script_loader_tag` ajoute `type="module"` (WordPress n'enqueue pas nativement de modules ES sans ça).

### `vite.config.js` — détails

```js
export default defineConfig(({ command }) => ({
    base: command === 'build' ? '/app/themes/custom/tailwind/dist/' : '/',
    plugins: [tailwindcss(), phpTwigReload()],
    ...
}));
```

- **`base` conditionnel** : en `build`, les URLs internes générées par Vite (ex. `url()` dans le CSS) doivent être préfixées par le chemin public réel du thème (`/app/themes/custom/tailwind/dist/` — le `/app` vient de `CONTENT_DIR` défini par Bedrock dans `config/application.php`). En `serve` (dev), on garde `base: '/'` : le dev server sert les fichiers depuis la racine du thème, donc `/assets/scripts/app.js` correspond bien à `<thème>/assets/scripts/app.js`. Mélanger les deux casse soit les URLs de dev, soit les URLs de prod.
- **`server.https`** : lit les certificats mkcert s'ils existent, sinon laisse Vite générer un certificat auto-signé (`https: true`).
- **`server.watch.usePolling: true`** : voir section HMR ci-dessous — nécessaire sous Colima/virtiofs.
- **Plugin `phpTwigReload()`** : voir section HMR ci-dessous.

## HMR : pourquoi le CSS/Twig ne se rechargeait pas (et le fix)

Deux problèmes distincts, tous deux résolus dans `vite.config.js` :

### 1. Les fichiers `.twig`/`.php` ne déclenchent aucun rechargement par défaut

Vite ne fait du HMR que pour les fichiers qui font partie de son **graphe de modules** (JS/CSS importés). Un fichier `.twig` rendu côté PHP n'en fait jamais partie : éditer un template n'a par défaut *aucun effet* côté navigateur, même si le serveur dev tourne.

**Fix** : un plugin Vite minimal (`phpTwigReload()`, dans `vite.config.js`) écoute les changements sur `**/*.twig` et `**/*.php` via `server.watcher` et déclenche un rechargement complet de la page :

```js
server.watcher.on('change', (file) => {
    if (/\.(twig|php)$/.test(file)) {
        server.ws.send({ type: 'full-reload' });
    }
});
```

### 2. Le watcher de fichiers ne voyait aucun changement venant de macOS

Plus fondamental : sous [Colima](https://github.com/abiosoft/colima) avec le mount type `virtiofs` (voir `docker/README.md`), les événements `inotify` déclenchés par une modification de fichier faite depuis macOS (éditeur, IDE) ne remontent pas fiablement dans le conteneur Linux. Résultat : ni le watcher de Vite, ni celui du plugin `@tailwindcss/vite` (qui scrute les fichiers `@source` pour régénérer le CSS) ne détectaient quoi que ce soit — le CSS ne se régénérait donc pas non plus quand une classe Tailwind était ajoutée/retirée dans un `.twig`.

**Fix** : forcer le polling plutôt que de compter sur les événements natifs du système de fichiers :

```js
server: {
    watch: {
        usePolling: true,
        interval: 300, // ms
    },
},
```

Le plugin Tailwind partage le même `server.watcher` que Vite, donc ce réglage corrige les deux symptômes (page qui ne recharge pas, CSS qui ne se régénère pas) en une fois. Contrepartie : le polling consomme un peu plus de CPU qu'un watcher natif — acceptable pour du développement local.

### Vérifier que le HMR fonctionne

1. `make vite-dev`
2. Ouvrir https://tailwind-wordpress.localhost/ (le `<script type="module" src="https://tailwind-wordpress.localhost:3009/...">` doit apparaître dans la page — sinon le serveur dev n'est pas détecté, voir section bascule dev/prod ci-dessus)
3. Modifier une classe Tailwind dans un `.twig` → la page se recharge automatiquement et le style est à jour.

## Commandes

| Commande | Effet |
|---|---|
| `make vite-install` | `npm install` dans le thème (une fois, ou après modif de `package.json`) |
| `make vite-dev` | Démarre le serveur dev Vite (HMR) sur `https://tailwind-wordpress.localhost:3009/` |
| `make vite-build` | Build de production (`dist/`) |
| `make npm ARGS="..."` | Commande npm arbitraire dans le dossier du thème, ex. `make npm ARGS="run build"` |
| `make wp ARGS="theme activate custom/tailwind"` | Active le thème |

Le serveur dev tourne dans le service Docker `node` (`command: tail -f /dev/null`, donc `make vite-dev` l'exécute via `docker compose exec`) — il ne démarre pas tout seul avec `make start` et doit être relancé manuellement à chaque session de dev.

## Autoload PHP

La classe `App\Theme\Site` est chargée via le mapping PSR-4 du `composer.json` **racine** du dépôt (pas un `composer.json` séparé dans le thème) :

```json
"autoload": {
    "psr-4": {
        "App\\Theme\\": "web/app/themes/custom/tailwind/src/"
    }
}
```

Après ajout d'une nouvelle classe dans `src/`, régénérer l'autoloader si besoin :

```bash
make shell
composer dump-autoload
```
