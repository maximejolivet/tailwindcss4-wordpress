---
description: WordPress/Bedrock/Twig/ACF/Polylang conventions for this repo
---

# 🇬🇧 WordPress conventions

## Table of contents

- [Bedrock structure](#bedrock-structure)
- [Twig components (`web/app/themes/custom/tailwind/views/components/`)](#twig-components-webappthemescustomtailwindviewscomponents)
- [Page builder (ACF / Secure Custom Fields)](#page-builder-acf-secure-custom-fields)
- [Multilingual (Polylang)](#multilingual-polylang)
- [Deployment (o2switch)](#deployment-o2switch)

## Bedrock structure

Repo root = Bedrock root (`composer.json`, `config/`). `web/wp` = WordPress core (Composer-installed, gitignored). `web/app` = themes/plugins/uploads. Plugins/themes are managed via Composer, **never** from the WordPress admin (`DISALLOW_FILE_MODS` is set in `config/application.php`).

## Twig components (`web/app/themes/custom/tailwind/views/components/`)

One folder per component (`01-atoms/`, `02-molecules/`, `03-organisms/`), each with `*.twig` + `README.md` (real usage example) + a props comment block at the top of the `.twig` file (Twig has no schema validation, unlike ACF fields).

- **Component without slots** → `{% include '...' with {...} only %}`. Always `only`: never let it depend implicitly on ambient `post`/`site`.
- **Component with slots** (`{% block %}`) → `{% embed '...' %}`, but **never put `only` on the `{% embed %}` tag itself**. The content of `{% block %}` overrides runs in the calling page's scope — that's what lets it use `post`/`site`; `only` on the embed silently breaks that access for the whole block (confirmed real bug, not theoretical: an `icon` nested in a card's `image` slot lost `site.theme.link`, no Twig error, just an empty value). Each `{% include %}` *nested inside* a slot keeps its own `only` — that's what isolates the leaf component, not the outer embed.

## Page builder (ACF / Secure Custom Fields)

The `sections` Flexible Content field (page builder) is registered in PHP (`inc/acf-fields.php`, `acf_add_local_field_group()` on `acf/init`) — not built through the wp-admin UI with a Local JSON export. Keep new field groups in PHP for the same reason: faster and more reliable to review/evolve in git than re-clicking through nested layouts in the UI.

Structure: top-level layouts are `hero` (max 1) and `section` only; `section` contains a nested Flexible Content (`text_media`, `cards_grid`, `cta_banner`, `accordion`, `embed`) — other layouts are never insertable directly at the top level.

`views/templates/page.twig` maps `post.meta('sections')` (Timber exposes the nested ACF structure as-is, with `acf_fc_layout` at every level) to Twig components — keep that mapping template free of structural HTML; if HTML creeps in there, a component is missing something.

## Multilingual (Polylang)

FR (default) + EN, symmetric `/fr/`/`/en/` prefixes, URL-only detection (no browser detection). Creating a translation via the `+` in the *Languages* panel copies the entire `sections` structure as-is at creation time — this is Polylang's free default behavior, not something that needed configuring, and it only happens once (FR and EN become independent postmeta afterward, no ongoing per-field sync).

## Deployment (o2switch)

See @.claude/DEPLOY.md for the full pipeline. The one gotcha worth repeating here: the subdomain's document root must point at `<repo>/web`, **never** `web/wp` (that's only the Composer-installed WP core, without Bedrock's `index.php`/`wp-config.php`/`.htaccess` bootstrap).

---

# 🇫🇷 Conventions WordPress

## Sommaire

- [Structure Bedrock](#structure-bedrock)
- [Composants Twig (`web/app/themes/custom/tailwind/views/components/`)](#composants-twig-webappthemescustomtailwindviewscomponents)
- [Page builder (ACF / Secure Custom Fields)](#page-builder-acf-secure-custom-fields-1)
- [Multilingue (Polylang)](#multilingue-polylang)
- [Déploiement (o2switch)](#déploiement-o2switch)

## Structure Bedrock

Racine du repo = racine Bedrock (`composer.json`, `config/`). `web/wp` = cœur WordPress (installé par Composer, gitignored). `web/app` = thèmes/plugins/uploads. Les plugins/thèmes se gèrent via Composer, **jamais** depuis l'admin WordPress (`DISALLOW_FILE_MODS` est activé dans `config/application.php`).

## Composants Twig (`web/app/themes/custom/tailwind/views/components/`)

Un dossier par composant (`01-atoms/`, `02-molecules/`, `03-organisms/`), chacun avec un `*.twig` + `README.md` (exemple d'usage réel) + un commentaire de props en tête du fichier `.twig` (Twig n'a pas de validation de schéma, contrairement aux champs ACF).

- **Composant sans slot** → `{% include '...' with {...} only %}`. Toujours `only` : ne jamais le laisser dépendre implicitement de `post`/`site` ambiants.
- **Composant avec slots** (`{% block %}`) → `{% embed '...' %}`, mais **ne jamais mettre `only` sur le tag `{% embed %}` lui-même**. Le contenu des overrides `{% block %}` s'exécute dans la portée de la page appelante — c'est ce qui permet d'y utiliser `post`/`site` ; `only` sur l'embed coupe silencieusement cet accès pour tout le bloc (bug réel confirmé, pas théorique : un `icon` imbriqué dans le slot `image` d'une carte a perdu `site.theme.link`, sans erreur Twig, juste une valeur vide). Chaque `{% include %}` *imbriqué dans* un slot garde son propre `only` — c'est lui qui isole le composant terminal, pas l'embed englobant.

## Page builder (ACF / Secure Custom Fields)

Le champ Flexible Content `sections` (page builder) est enregistré en PHP (`inc/acf-fields.php`, `acf_add_local_field_group()` sur `acf/init`) — pas construit via l'UI wp-admin avec un export Local JSON. Garder les nouveaux groupes de champs en PHP pour la même raison : plus rapide et plus fiable à revoir/faire évoluer en git qu'en re-cliquant à travers des layouts imbriqués dans l'UI.

Structure : les layouts de premier niveau sont uniquement `hero` (max 1) et `section` ; `section` contient un Flexible Content imbriqué (`text_media`, `cards_grid`, `cta_banner`, `accordion`, `embed`) — les autres layouts ne sont jamais insérables directement au premier niveau.

`views/templates/page.twig` mappe `post.meta('sections')` (Timber expose la structure ACF imbriquée telle quelle, avec `acf_fc_layout` à chaque niveau) vers des composants Twig — garder ce template de mapping libre de tout HTML de structure ; si du HTML s'y glisse, c'est qu'un composant manque quelque chose.

## Multilingue (Polylang)

FR (défaut) + EN, préfixes `/fr/`/`/en/` symétriques, détection URL uniquement (pas de détection navigateur). Créer une traduction via le `+` du panneau *Languages* copie toute la structure `sections` telle quelle au moment de la création — c'est le comportement gratuit par défaut de Polylang, rien à configurer, et ça n'arrive qu'une fois (FR et EN deviennent ensuite des postmeta indépendants, pas de synchronisation par champ continue).

## Déploiement (o2switch)

Voir @.claude/DEPLOY.md pour le pipeline complet. Le seul piège qui mérite d'être répété ici : le document root du sous-domaine doit pointer sur `<repo>/web`, **jamais** `web/wp` (qui n'est que le cœur WP installé par Composer, sans le bootstrap `index.php`/`wp-config.php`/`.htaccess` de Bedrock).
