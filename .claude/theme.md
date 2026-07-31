# 🇬🇧 Custom theme (`web/app/themes/custom/tailwind`)

WordPress theme built with [Timber v2](https://timber.github.io/docs/v2/) (Twig templates) and [Tailwind CSS 4](https://tailwindcss.com/) compiled by [Vite](https://vitejs.dev/), with an HMR dev server.

## Stack

| Piece | Role |
|---|---|
| [Timber v2](https://timber.github.io/docs/v2/) | Separates WordPress logic (PHP) from rendering (Twig templates in `views/`) |
| [Twig](https://twig.symfony.com/) | Template engine used by Timber |
| [Tailwind CSS 4](https://tailwindcss.com/) | Utility classes, CSS-first config (no `tailwind.config.js`) |
| [Vite](https://vitejs.dev/) | Asset bundler (`assets/scripts/app.js` + `assets/styles/app.css`), production build and dev server with HMR |

The theme is installed locally (not via Composer/WP Packages like `twentytwentyfive`): it's a folder versioned directly in the repo.

## Directory tree

```
web/app/themes/custom/tailwind/
├── style.css                  # Required WordPress header (metadata only, no styles)
├── functions.php              # Bootstrap: Timber::init(), instantiates Site, loads inc/vite.php and inc/acf-fields.php
├── index.php                  # Generic template (archives, blog)
├── front-page.php             # Static homepage
├── page.php                   # Pages
├── single.php                 # Posts
├── 404.php                    # Not found page
├── src/
│   └── Site.php               # App\Theme\Site extends Timber\Site: theme supports, menus, Twig context
├── inc/
│   ├── vite.php                # Dev (HMR) / prod (manifest) asset switch
│   └── acf-fields.php          # "sections" Flexible Content field (ACF/SCF page builder), see §Components
├── views/
│   ├── layouts/base.twig      # Base HTML layout (head, header, footer, wp_head/wp_footer)
│   ├── partials/              # head.twig, menu.twig (recursive), footer.twig
│   ├── components/            # Reusable Twig components (atoms/molecules/organisms), see §Components
│   └── templates/             # index.twig, front-page.twig, page.twig, single.twig, 404.twig
├── assets/
│   ├── styles/app.css         # `@import "tailwindcss"` + `@theme` (tokens) + `@source` + `@plugin` typography
│   ├── scripts/app.js         # JS entry point (vanilla), imports app.css
│   ├── images/sprite.svg      # SVG sprite consumed by the `icon` component
│   └── fonts/                 # Empty (`.gitkeep`), use as needed
├── package.json
└── vite.config.js
```

`dist/` (production build) and `node_modules/` are generated, not versioned (see the root `.gitignore`).

## Twig components (`views/components/`)

Reusable component library, organized into `01-atoms/`,
`02-molecules/`, `03-organisms/` (one folder per component: `*.twig` +
`README.md` with a real usage example):

| Component | Type | Role |
|---|---|---|
| `button` | atom | `<a>`/`<button>`, primary/secondary/ghost variants, sm/md/lg sizes |
| `heading` | atom | semantic level (`<h1>`-`<h6>`) decoupled from visual size |
| `badge` | atom | static indicator (neutral/success/warning/danger/info variants) |
| `tag` | atom | optionally removable chip (vanilla JS, `assets/scripts/app.js`) |
| `icon` | atom | SVG sprite (`assets/images/sprite.svg`), isolated (`sprite_url` as an explicit prop) |
| `card` | molecule | image/title/content/footer slots, vertical/horizontal variant (+ `reverse`) |
| `accordion-item` | molecule | native `<details>/<summary>`, no JS |
| `hero` | organism | banner (title/subtitle/media/CTA), composes `heading` + `button` |
| `cards-grid` | organism | 2/3/4-column grid of vertical `card` |
| `accordion` | organism | `divide-y` wrapper around `accordion-item` |
| `embed` | organism | raw third-party content (iframe, social embed) |

Convention (documented in each component, see also
[`prompts/WORDPRESS.md`](prompts/WORDPRESS.md) §3):
- props documented in a Twig comment at the top of the file (no
  automatically validated schema, Twig has no equivalent to Drupal's
  Single Directory Components)
- component without slots → `{% include '...' with {...} only %}`
  (isolated, never an implicit dependency on `post`/`site`)
- component with slots → `{% embed '...' %}` **without** `only` on the
  `embed` itself (the content of `{% block %}` must keep access to the
  calling page's scope); each `{% include %}` *inside* a slot keeps its
  own `only`

## Page builder (ACF Flexible Content)

`inc/acf-fields.php` registers in PHP (`acf_add_local_field_group()`, not
via the UI + Local JSON export — faster and more reliable to evolve) a
`sections` Flexible Content field on the `page` post type:
- top level: `hero` (max 1) and `section` layouts only
- `section` contains `columns` (1/2/3) and a nested flexible content
  `content` with `text_media`, `cards_grid`, `cta_banner`, `accordion`,
  `embed`

`views/templates/page.twig` does the pure ACF layout → Twig component
mapping (zero structural HTML outside the `section` grid); see
[`prompts/WORDPRESS.md`](prompts/WORDPRESS.md) §7 for the full detail
and [`prompts/WORDPRESS-PROCESS.md`](prompts/WORDPRESS-PROCESS.md) for
the real bugs hit while building it.

## Multilingual (Polylang)

FR (default) + EN, symmetric `/fr/` and `/en/` prefixes on both
languages, URL-only detection. See
[`prompts/WORDPRESS.md`](prompts/WORDPRESS.md) §8 for the config and the
translation workflow (copying the `sections` structure when a translation
is created is Polylang's native free behavior, no need for Polylang Pro).

## Timber / Twig

### Bootstrap (`functions.php`)

```php
namespace App\Theme;

use Timber\Timber;

Timber::init();

new Site();          // src/Site.php — enables theme supports, menus, context
require __DIR__ . '/inc/vite.php';
require __DIR__ . '/inc/acf-fields.php'; // "sections" Flexible Content field (ACF/SCF)
```

Timber looks for `.twig` files in the `views/` folder by default (`Timber::$dirname`), so paths passed to `Timber::render()` are relative to `views/` (e.g. `templates/page.twig`).

### `App\Theme\Site` (`src/Site.php`)

Extends `Timber\Site`:
- `theme_supports()` (`after_setup_theme` hook): `title-tag`, `post-thumbnails`, `automatic-feed-links`, `editor-styles`, `html5`, and registers the `primary` menu.
- `add_to_context()` (`timber/context` filter): adds `site` (the `Site` instance, giving access to `site.name`, `site.url`, `site.charset`, `site.language_attributes`…) and `menu` (`Timber::get_menu('primary')`) to the context available in every Twig template.

To add global data to all templates (e.g. an ACF setting, an option), add it in `add_to_context()`.

To add a custom post type / taxonomy, fill in `register_post_types()` / `register_taxonomies()` (stubs present in the class, hooked on `init`).

### Template hierarchy

Each PHP file at the theme root follows the standard [WordPress template hierarchy](https://developer.wordpress.org/themes/basics/template-hierarchy/) and delegates rendering to a `.twig` file in `views/templates/`:

| PHP file | Context | Twig template rendered |
|---|---|---|
| `index.php` | Archives, blog | `templates/index.twig` (+ `templates/front-page.twig` with priority if `is_front_page()`) |
| `front-page.php` | Static homepage (*Settings > Reading* setting) | `templates/front-page.twig` |
| `page.php` | Page (`page` post_type) | `templates/page-{slug}.twig` if present, otherwise `templates/page.twig` |
| `single.php` | Post (or any post type) | `templates/single-{post_type}.twig` if present, otherwise `templates/single.twig` |
| `404.php` | Not found | `templates/404.twig` |

**Adding a dedicated template**: for example for a "Contact" page (slug `contact`), create `views/templates/page-contact.twig` (which can do `{% extends 'layouts/base.twig' %}`) — `page.php` detects it automatically, no PHP change needed. Same logic for an `event` post type → `views/templates/single-event.twig`.

### Layout and partials

- `views/layouts/base.twig`: full HTML structure (`<html>`, `<head>` via `partials/head.twig`, `<header>` with logo + `partials/menu.twig`, `<main>` with the `{% block content %}`, `partials/footer.twig`, `wp_footer()`). Each template in `views/templates/` does `{% extends 'layouts/base.twig' %}` and fills `{% block content %}`.
- `partials/head.twig`: `<meta charset>`, viewport, `wp_head()`.
- `partials/menu.twig`: recursive rendering of a Timber menu (`item.children`), used for the `primary` menu.
- `partials/footer.twig`: copyright.

### Context available in Twig templates

In addition to what `Timber::context()` adds by default (`post`, `posts`, `body_class`, etc. depending on the PHP file), every template has access to:
- `site` — `Site` object (`site.name`, `site.url`, `site.charset`, `site.language_attributes`, `site.theme`…)
- `menu` — items of the `primary` navigation menu (assigned in *Appearance > Menus*)

## Assets (Tailwind CSS 4 + Vite)

### CSS (`assets/styles/app.css`)

```css
@import "tailwindcss";
@plugin "@tailwindcss/typography";

@source "../../views/**/*.twig";
@source "../../**/*.php";

@theme {
    --color-primary: oklch(0.55 0.22 265);
    --color-primary-hover: oklch(0.47 0.22 265);
    /* ... surface/text/border/success/warning/danger/info, radius, spacing */
}
```

Tailwind CSS 4 uses a **CSS-first** configuration: no `tailwind.config.js`. The `@source` directives explicitly tell it where to scan for used classes (by default Tailwind only looks from the Vite project root while respecting `.gitignore`, which would probably be enough, but this makes it explicit) — `views/**/*.twig` also covers `views/components/`. The `@theme` block defines semantic tokens (colors, radius, spacing): the **only customization point** between projects based on this theme, no arbitrary `[...]` values in components unless justified in a comment. The `@tailwindcss/typography` plugin provides the `prose`/`prose-neutral` classes used for WYSIWYG content (post content, ACF wysiwyg).

IDE diagnostics like *"Unknown at rule @plugin/@source/@theme"* are normal: the CSS language server doesn't know these Tailwind v4 at-rules yet, it's not a functional error.

### JS (`assets/scripts/app.js`)

Single entry point, imports the CSS and handles vanilla interactions
(no framework):

```js
import '../styles/app.css';

document.addEventListener('click', (event) => {
    const removeButton = event.target.closest('[data-tag-remove]');
    if (!removeButton) return;
    removeButton.closest('[data-tag]')?.remove();
});
```

The delegated listener above handles the remove button of the `tag`
component (`views/components/01-atoms/tag/`) — a single global listener
rather than a per-component JS dependency. To add JS, write it in this
file (or import it from another module) — Vite's entry point
(`vite.config.js` → `build.rollupOptions.input`) is a single file
(`assets/scripts/app.js`).

### Production build (`make vite-build`)

```bash
make vite-build
# → npm --prefix web/app/themes/custom/tailwind run build
```

Generates `dist/assets/*.{js,css}` (hashed files) and `dist/.vite/manifest.json`. This is the manifest that `inc/vite.php` reads to enqueue the right files in production.

### Dev server / HMR (`make vite-dev`)

```bash
make vite-dev
# → npm --prefix web/app/themes/custom/tailwind run dev
```

Starts Vite on `https://tailwind-wordpress.localhost:3009/` (port exposed directly by the `node` service, outside Traefik):
- Reuses Traefik's mkcert certificates (`docker/traefik/certs/tailwind-wordpress.localhost{,-key}.pem`) to serve over HTTPS with an already-trusted certificate — no browser warning.
- `hmr: { host, protocol: 'wss' }`: the HMR client connects over a secure WebSocket on this same host/port.

### Dev / prod switch (`inc/vite.php`)

On every page load (`wp_enqueue_scripts`), the theme detects whether the Vite server is running:

```php
$connection = @fsockopen(VITE_INTERNAL_HOST, VITE_DEV_PORT, ...); // 'node', 3009
```

The test uses the internal Docker service name `node:3009` (only resolvable from the `php` container, via the Compose network), not `tailwind-wordpress.localhost` (which only resolves on the host/browser side). This is an important distinction if you modify this file:
- **Dev server detection** (server-side, PHP → Node): `node:3009`.
- **Script URLs served to the browser**: `https://tailwind-wordpress.localhost:3009` (`VITE_DEV_HOST`).

If the dev server responds (and `WP_ENV=development`):
- enqueues `https://tailwind-wordpress.localhost:3009/@vite/client` (HMR client)
- enqueues `https://tailwind-wordpress.localhost:3009/assets/scripts/app.js` (uncompiled source, transformed on the fly)

Otherwise, it reads `dist/.vite/manifest.json` and enqueues the built files (`dist/assets/app-XXXX.js`, `dist/assets/app-XXXX.css`).

In both cases, a `script_loader_tag` filter adds `type="module"` (WordPress doesn't natively enqueue ES modules without it).

### `vite.config.js` — details

```js
export default defineConfig(({ command }) => ({
    base: command === 'build' ? '/app/themes/custom/tailwind/dist/' : '/',
    plugins: [tailwindcss(), phpTwigReload()],
    ...
}));
```

- **Conditional `base`**: in `build`, internal URLs generated by Vite (e.g. `url()` in CSS) must be prefixed with the theme's real public path (`/app/themes/custom/tailwind/dist/` — the `/app` comes from `CONTENT_DIR` defined by Bedrock in `config/application.php`). In `serve` (dev), `base: '/'` is kept: the dev server serves files from the theme root, so `/assets/scripts/app.js` correctly matches `<theme>/assets/scripts/app.js`. Mixing the two breaks either the dev URLs or the prod URLs.
- **`server.https`**: reads the mkcert certificates if they exist, otherwise lets Vite generate a self-signed certificate (`https: true`).
- **`server.watch.usePolling: true`**: see the HMR section below — needed under Colima/virtiofs.
- **`phpTwigReload()` plugin**: see the HMR section below.

## HMR: why CSS/Twig weren't reloading (and the fix)

Two distinct problems, both fixed in `vite.config.js`:

### 1. `.twig`/`.php` files don't trigger any reload by default

Vite only does HMR for files that are part of its **module graph** (imported JS/CSS). A `.twig` file rendered server-side is never part of that: editing a template has, by default, *no effect at all* on the browser side, even with the dev server running.

**Fix**: a minimal Vite plugin (`phpTwigReload()`, in `vite.config.js`) listens for changes on `**/*.twig` and `**/*.php` via `server.watcher` and triggers a full page reload:

```js
server.watcher.on('change', (file) => {
    if (/\.(twig|php)$/.test(file)) {
        server.ws.send({ type: 'full-reload' });
    }
});
```

### 2. The file watcher saw no changes coming from macOS

More fundamentally: under [Colima](https://github.com/abiosoft/colima) with the `virtiofs` mount type (see `docker/README.md`), `inotify` events triggered by a file change made from macOS (editor, IDE) don't reliably propagate into the Linux container. As a result, neither Vite's watcher nor the `@tailwindcss/vite` plugin's watcher (which scans `@source` files to regenerate CSS) detected anything — so the CSS also wasn't regenerated when a Tailwind class was added/removed in a `.twig` file.

**Fix**: force polling instead of relying on native filesystem events:

```js
server: {
    watch: {
        usePolling: true,
        interval: 300, // ms
    },
},
```

The Tailwind plugin shares the same `server.watcher` as Vite, so this setting fixes both symptoms (page not reloading, CSS not regenerating) at once. Trade-off: polling uses a bit more CPU than a native watcher — acceptable for local development.

### Verifying HMR works

1. `make vite-dev`
2. Open https://tailwind-wordpress.localhost/ (the `<script type="module" src="https://tailwind-wordpress.localhost:3009/...">` should appear in the page — otherwise the dev server isn't detected, see the dev/prod switch section above)
3. Change a Tailwind class in a `.twig` file → the page reloads automatically and the style is up to date.

## Commands

| Command | Effect |
|---|---|
| `make vite-install` | `npm install` in the theme (once, or after changing `package.json`) |
| `make vite-dev` | Starts the Vite dev server (HMR) at `https://tailwind-wordpress.localhost:3009/` |
| `make vite-build` | Production build (`dist/`) |
| `make npm ARGS="..."` | Arbitrary npm command in the theme folder, e.g. `make npm ARGS="run build"` |
| `make wp ARGS="theme activate custom/tailwind"` | Activates the theme |

The dev server runs in the `node` Docker service (`command: tail -f /dev/null`, so `make vite-dev` runs it via `docker compose exec`) — it doesn't start on its own with `make start` and must be started manually every dev session.

## PHP autoload

The `App\Theme\Site` class is loaded via the PSR-4 mapping in the **root** `composer.json` of the repo (not a separate `composer.json` in the theme):

```json
"autoload": {
    "psr-4": {
        "App\\Theme\\": "web/app/themes/custom/tailwind/src/"
    }
}
```

After adding a new class in `src/`, regenerate the autoloader if needed:

```bash
make shell
composer dump-autoload
```

## See also

- [`prompts/WORDPRESS.md`](prompts/WORDPRESS.md) — full mission (components, page builder, multilingual)
- [`prompts/WORDPRESS-PROCESS.md`](prompts/WORDPRESS-PROCESS.md) — real execution journal (bugs found by testing, not by re-reading the code)
- [`deploy.md`](deploy.md) — o2switch deployment (manual and CI GitHub Actions)

---

# 🇫🇷 Thème custom (`web/app/themes/custom/tailwind`)

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
├── functions.php              # Bootstrap : Timber::init(), instancie Site, charge inc/vite.php et inc/acf-fields.php
├── index.php                  # Template générique (archives, blog)
├── front-page.php             # Page d'accueil statique
├── page.php                   # Pages
├── single.php                 # Articles
├── 404.php                    # Page introuvable
├── src/
│   └── Site.php               # App\Theme\Site extends Timber\Site : theme supports, menus, contexte Twig
├── inc/
│   ├── vite.php                # Bascule dev (HMR) / prod (manifest) pour les assets
│   └── acf-fields.php          # Champ Flexible Content "sections" (page builder ACF/SCF), voir §Composants
├── views/
│   ├── layouts/base.twig      # Layout HTML de base (head, header, footer, wp_head/wp_footer)
│   ├── partials/              # head.twig, menu.twig (récursif), footer.twig
│   ├── components/            # Composants Twig réutilisables (atoms/molecules/organisms), voir §Composants
│   └── templates/             # index.twig, front-page.twig, page.twig, single.twig, 404.twig
├── assets/
│   ├── styles/app.css         # `@import "tailwindcss"` + `@theme` (tokens) + `@source` + `@plugin` typography
│   ├── scripts/app.js         # Point d'entrée JS (vanilla), importe app.css
│   ├── images/sprite.svg      # Sprite SVG consommé par le composant `icon`
│   └── fonts/                 # Vide (`.gitkeep`), à utiliser au besoin
├── package.json
└── vite.config.js
```

`dist/` (build de prod) et `node_modules/` sont générés, non versionnés (voir `.gitignore` racine).

## Composants Twig (`views/components/`)

Bibliothèque de composants réutilisables, organisée en `01-atoms/`,
`02-molecules/`, `03-organisms/` (un dossier par composant : `*.twig` +
`README.md` avec exemple d'usage réel) :

| Composant | Type | Rôle |
|---|---|---|
| `button` | atom | `<a>`/`<button>`, variants primary/secondary/ghost, sizes sm/md/lg |
| `heading` | atom | niveau sémantique (`<h1>`-`<h6>`) découplé de la taille visuelle |
| `badge` | atom | indicateur statique (variants neutral/success/warning/danger/info) |
| `tag` | atom | chip optionnellement supprimable (vanilla JS, `assets/scripts/app.js`) |
| `icon` | atom | sprite SVG (`assets/images/sprite.svg`), isolé (`sprite_url` en prop explicite) |
| `card` | molecule | slots image/title/content/footer, variant vertical/horizontal (+ `reverse`) |
| `accordion-item` | molecule | `<details>/<summary>` natif, sans JS |
| `hero` | organism | bannière (titre/sous-titre/média/CTA), compose `heading` + `button` |
| `cards-grid` | organism | grille 2/3/4 colonnes de `card` vertical |
| `accordion` | organism | wrapper `divide-y` d'`accordion-item` |
| `embed` | organism | contenu tiers brut (iframe, embed réseau social) |

Convention (documentée dans chaque composant, voir aussi
[`prompts/WORDPRESS.md`](prompts/WORDPRESS.md) §3) :
- props documentées en commentaire Twig en tête de fichier (pas de schéma
  validé automatiquement, Twig n'a pas d'équivalent aux Single Directory
  Components de Drupal)
- composant sans slot → `{% include '...' with {...} only %}` (isolé,
  jamais de dépendance implicite à `post`/`site`)
- composant à slots → `{% embed '...' %}` **sans** `only` sur l'`embed`
  lui-même (le contenu des `{% block %}` doit garder accès à la portée de
  la page appelante) ; chaque `{% include %}` *à l'intérieur* d'un slot
  garde en revanche son propre `only`

## Page builder (ACF Flexible Content)

`inc/acf-fields.php` enregistre en PHP (`acf_add_local_field_group()`, pas
via l'UI + export Local JSON — plus rapide et fiable à faire évoluer) un
champ Flexible Content `sections` sur le post type `page` :
- premier niveau : layouts `hero` (max 1) et `section` uniquement
- `section` contient `columns` (1/2/3) et un flexible content imbriqué
  `content` avec `text_media`, `cards_grid`, `cta_banner`, `accordion`,
  `embed`

`views/templates/page.twig` fait le mapping pur layout ACF → composant Twig
(zéro HTML de structure en dehors de la grille de `section`) ; voir
[`prompts/WORDPRESS.md`](prompts/WORDPRESS.md) §7 pour le détail complet
et [`prompts/WORDPRESS-PROCESS.md`](prompts/WORDPRESS-PROCESS.md) pour
les bugs réels rencontrés en le construisant.

## Multilingue (Polylang)

FR (défaut) + EN, préfixes `/fr/` et `/en/` symétriques sur les deux
langues, détection URL uniquement. Voir
[`prompts/WORDPRESS.md`](prompts/WORDPRESS.md) §8 pour la config et le
workflow de traduction (la copie de structure `sections` à la création
d'une traduction est un comportement Polylang gratuit natif, pas besoin de
Polylang Pro).

## Timber / Twig

### Bootstrap (`functions.php`)

```php
namespace App\Theme;

use Timber\Timber;

Timber::init();

new Site();          // src/Site.php — active les theme supports, menus, contexte
require __DIR__ . '/inc/vite.php';
require __DIR__ . '/inc/acf-fields.php'; // Champ Flexible Content "sections" (ACF/SCF)
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

@theme {
    --color-primary: oklch(0.55 0.22 265);
    --color-primary-hover: oklch(0.47 0.22 265);
    /* ... surface/text/border/success/warning/danger/info, radius, spacing */
}
```

Tailwind CSS 4 utilise une configuration **CSS-first** : pas de `tailwind.config.js`. Les directives `@source` indiquent explicitement où scanner les classes utilisées (par défaut Tailwind ne regarde que depuis la racine du projet Vite en respectant `.gitignore`, ce qui suffirait probablement mais on le rend explicite) — `views/**/*.twig` couvre aussi `views/components/`. Le bloc `@theme` définit les tokens sémantiques (couleurs, radius, spacing) : **seul point de personnalisation** entre projets basés sur ce thème, aucune valeur arbitraire `[...]` dans les composants sauf justification en commentaire. Le plugin `@tailwindcss/typography` fournit les classes `prose`/`prose-neutral` utilisées pour le contenu WYSIWYG (post content, wysiwyg ACF).

Les diagnostics IDE du type *"Unknown at rule @plugin/@source/@theme"* sont normaux : le serveur de langage CSS ne connaît pas encore ces at-rules Tailwind v4, ce n'est pas une erreur fonctionnelle.

### JS (`assets/scripts/app.js`)

Point d'entrée unique, importe le CSS et gère les interactions vanilla
(pas de framework) :

```js
import '../styles/app.css';

document.addEventListener('click', (event) => {
    const removeButton = event.target.closest('[data-tag-remove]');
    if (!removeButton) return;
    removeButton.closest('[data-tag]')?.remove();
});
```

Le listener délégué ci-dessus gère le bouton de suppression du composant
`tag` (`views/components/01-atoms/tag/`) — un seul listener global plutôt
qu'une dépendance JS par composant. Pour ajouter du JS, l'écrire dans ce
fichier (ou l'importer depuis un autre module) — le point d'entrée Vite
(`vite.config.js` → `build.rollupOptions.input`) est unique
(`assets/scripts/app.js`).

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

## Voir aussi

- [`prompts/WORDPRESS.md`](prompts/WORDPRESS.md) — mission complète (composants, page builder, multilingue)
- [`prompts/WORDPRESS-PROCESS.md`](prompts/WORDPRESS-PROCESS.md) — journal réel d'exécution (bugs trouvés en testant, pas en relisant le code)
- [`deploy.md`](deploy.md) — déploiement o2switch (manuel et CI GitHub Actions)
