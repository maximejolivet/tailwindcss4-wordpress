# 🇫🇷 Journal d'exécution — Composants Twig / Tailwind v4 / ACF Flexible Content

Ce document retrace, en français, l'état réel du projet par rapport à la
mission décrite dans [`wordpress-mission-brief.md`](./wordpress-mission-brief.md) : composants Twig,
page builder ACF Flexible Content, multilingue Polylang. Contrairement à
`DRUPAL-PROCESS.md` (l'ancienne version de ce document, qui documentait un
projet Drupal où la mission avait été intégralement exécutée et vérifiée),
**seule la partie 0 ci-dessous reflète un état vérifié du dépôt actuel** — le
reste est un plan d'exécution (backlog), pas un journal de travaux déjà
faits. Ne pas présenter les sections 1+ comme terminées tant qu'elles n'ont
pas été réellement implémentées et vérifiées (`make start` + `make wp
ARGS="..."` + rendu réel). Ce dépôt n'a pas de `CLAUDE.md` qui l'impose —
c'est une discipline volontaire pour ce document précis, après plusieurs bugs
réels trouvés uniquement en rendant (cf. bugs n°1-2 de la section 3-6
ci-dessous), jamais en relisant le code.

## Sommaire

- [0. État de départ (vérifié dans le dépôt au 2026-07-31)](#0-état-de-départ-vérifié-dans-le-dépôt-au-2026-07-31)
- [1. Pipeline de build — à faire](#1-pipeline-de-build-à-faire)
- [2. Design tokens — fait (2026-07-31)](#2-design-tokens-fait-2026-07-31)
- [3-6. Composants Twig — 7/7 faits (2026-07-31)](#3-6-composants-twig-77-faits-2026-07-31)
- [7. Page builder ACF Flexible Content — fait et vérifié de bout en bout (2026-07-31)](#7-page-builder-acf-flexible-content-fait-et-vérifié-de-bout-en-bout-2026-07-31)
- [8. Multilingue Polylang — configuré et vérifié (2026-07-31)](#8-multilingue-polylang-configuré-et-vérifié-2026-07-31)
- [9. Qualité / CI — à faire](#9-qualité-ci-à-faire)
- [Points de vigilance — résolus ou encore ouverts](#points-de-vigilance-résolus-ou-encore-ouverts)

## 0. État de départ (vérifié dans le dépôt au 2026-07-31)

Ce qui existe déjà, documenté en détail dans [`THEME.md`](../THEME.md)
et [`DOCKER.md`](../DOCKER.md) :

- Scaffold Bedrock (`composer.json`, `config/`, `web/wp`, `web/app`), PHP
  ≥ 8.4, Docker Compose (Traefik, PHP/Apache, MariaDB, node, phpMyAdmin,
  Mailhog, Dockhand)
- Thème custom `web/app/themes/custom/tailwind`, autoloadé en PSR-4
  (`App\Theme\`) depuis le `composer.json` racine
- Timber v2 initialisé (`functions.php` → `Timber::init()` +
  `new Site()`), `src/Site.php` : `theme_supports()` (title-tag,
  post-thumbnails, menu `primary`, etc.) et `add_to_context()` (ajoute
  `site` et `menu` au contexte Twig)
- Templates existants : `views/layouts/base.twig`, `views/partials/`
  (head, menu récursif, footer), `views/templates/` (`index`,
  `front-page`, `page`, `single`, `404`) — **aucun ne consomme de
  composant** pour l'instant, le HTML est écrit directement avec des
  classes Tailwind inline
- Pipeline Vite + Tailwind v4 fonctionnel : `assets/styles/app.css`
  (`@import "tailwindcss"`, `@plugin "@tailwindcss/typography"`, `@source`
  sur `views/**/*.twig` et `**/*.php`), `assets/scripts/app.js`, bascule
  dev/prod dans `inc/vite.php`, HMR avec plugin `phpTwigReload` +
  `usePolling` (problèmes résolus documentés dans `THEME.md`, pas à
  refaire ici)
- `composer.json` : scripts `lint` (Pint) et `test` (Pest) déjà déclarés,
  mais **aucun test Pest écrit**, aucune CI GitHub Actions

> Mise à jour du 2026-07-31 : le bloc `@theme` et le premier composant
> (`button`) ont été livrés — voir §2 et §3-6 ci-dessous, qui remplacent les
> deux puces correspondantes de cette liste initiale.

Ce qui n'existe **pas encore** (contrairement à ce qu'un lecteur pourrait
déduire d'un journal rédigé au passé) :

- `views/components/` ne contient que `01-atoms/button/` — `02-molecules/`
  et `03-organisms/` sont vides, aucun autre composant livré
- aucun plugin ACF ni Polylang dans `composer.json`
- pas de `acf-json/`, pas de champ flexible content, pas de config
  multilingue
- pas de CI, pas d'ESLint/Prettier configurés dans le thème

## 1. Pipeline de build — à faire

- [ ] vérifier si `views/components/` doit être ajouté à `@source` dans
  `assets/styles/app.css` une fois le dossier créé (le pattern
  `views/**/*.twig` existant le couvre déjà si les composants vivent sous
  `views/`)
- [ ] ajouter Prettier + `prettier-plugin-tailwindcss`, ESLint, aux
  `devDependencies` du thème (`package.json`)
- [ ] script de validation des en-têtes de props des composants (§3 de
  `wordpress-mission-brief.md`)

## 2. Design tokens — fait (2026-07-31)

- [x] bloc `@theme` écrit dans `assets/styles/app.css` : `--color-primary(-hover)`,
  `--color-secondary(-hover)`, `--color-surface(-alt/-inverse)`,
  `--color-text(-muted/-inverse)`, `--color-border(-strong)`, `--radius-sm/md/lg`,
  `--spacing-section`
- [x] `views/layouts/base.twig` migré vers les tokens sémantiques (`bg-surface
  text-text`, `border-border`) à la place de `bg-white text-gray-900`/`border-gray-200`
- [ ] les couleurs `--color-secondary`/`-success`/`-warning`/`-danger`/`-info`
  restent à affiner selon la charte réelle du client — valeurs actuelles
  arbitraires (placeholders OKLCH), à remplacer avant mise en production

## 3-6. Composants Twig — 7/7 faits (2026-07-31)

- [x] `views/components/01-atoms/`, `02-molecules/`, `03-organisms/` créés
- [x] `button` (`01-atoms/button/`) : variants primary/secondary/ghost,
  sizes sm/md/lg, prop `url` (rend `<a>` ou `<button>`), prop `disabled`
- [x] `heading` (`01-atoms/heading/`) : prop `level` (1-6, sémantique
  `<hN>` via `<h{{ level }}>`, valide en Twig) découplée de `size` (visuel),
  taille dérivée de `level` si `size` absent
- [x] `badge` (`01-atoms/badge/`) : variants neutral/success/warning/danger/info
  (a nécessité d'ajouter les tokens `--color-success/-warning/-danger/-info(-surface)`
  au `@theme`, absents du premier passage tokens du §2)
- [x] `tag` (`01-atoms/tag/`) : chip optionnellement supprimable ; le retrait
  DOM est un listener délégué `[data-tag-remove]` ajouté dans
  `assets/scripts/app.js` (vanilla, pas de dépendance JS par composant)
- [x] `icon` (`01-atoms/icon/`) : sprite `assets/images/sprite.svg` créé
  (symboles `arrow-right`, `close`, `check`), prop `sprite_url` explicite
  (le composant ne lit jamais `site` lui-même, cf. bug n°1 ci-dessous)
- [x] `card` (`02-molecules/card/`) : slots `image`/`title`/`content`/`footer`
  via `{% block %}` + `{% embed %}`, variant vertical/horizontal
- [x] `hero` (`03-organisms/hero/`) : title/subtitle/media/cta, compose en
  interne `heading` et `button` (pas de duplication de balisage), variante
  centrée sans `media` ou grille 2 colonnes avec `media` — utilisé
  réellement en tête de `front-page.twig`, vérifié au navigateur
  (`--spacing-section` généré en `py-section`, confirmé dans le CSS compilé
  via `grep`)
- [x] `views/templates/front-page.twig` consomme réellement les 7
  composants (hero, heading — via hero et via card, badge, tag, button —
  via hero et via card, icon, card) — vérifié au navigateur, voir bugs
  ci-dessous
- [x] `page.twig`, `single.twig` migrés vers `heading` (leur `<h1>` en dur) ;
  `text-gray-500` remplacé par le token `--color-text-muted` dans
  `single.twig` ; vérifié au navigateur sur `sample-page` et `hello-world`

### Bugs réels trouvés en vérifiant au navigateur (pas en relecture de code)

1. **`only` sur un `{% embed %}` casse silencieusement les includes imbriqués
   dans ses slots.** Premier jet de `card` utilisé avec `{% embed ... with
   {variant: 'horizontal'} only %}` : l'icône placée dans le slot `image`
   (`{% include '.../icon.twig' with {sprite_url: site.theme.link ~ '...'}
   only %}`) se rendait avec un `<use href="/assets/images/sprite.svg#check">`
   tronqué (404), au lieu de l'URL absolue attendue. Cause : le contenu des
   `{% block %}` d'un `embed` s'exécute dans la portée de la page appelante
   (c'est ce qui permet d'y utiliser `post`/`site`) ; `only` sur l'embed
   coupe cette portée pour tout le bloc, donc `site.theme.link` valait une
   chaîne vide **sans erreur Twig**. Confirmé en inspectant le DOM rendu
   (`document.querySelector('svg use').getAttribute('href')`) et en testant
   le fetch de l'URL réelle (404 → 200 après correctif). Corrigé en retirant
   `only` du tag `{% embed %}` (le composant `card` lui-même ne reçoit que
   `variant` de toute façon ; ce sont les `{% include %}` *à l'intérieur*
   des slots qui gardent leur propre `only` et restent isolés). Répercuté
   dans `wordpress-mission-brief.md` §3 et `card/README.md`.
2. **`site.theme.link`** (propriété/méthode Twig de `Timber\Theme`) résout
   correctement une fois le point 1 corrigé — confirmé via `wp eval-file` :
   `$theme->link()` retourne bien l'URI absolue du thème
   (`.../app/themes/custom/tailwind`). Le schéma `http://` renvoyé (au lieu
   de `https://`) vient de la détection de protocole côté conteneur PHP
   derrière Traefik, sans impact observé sur le rendu (navigateur résout en
   relatif/https) — à surveiller si un jour un lien absolu est affiché tel
   quel côté utilisateur.

## 7. Page builder ACF Flexible Content — fait et vérifié de bout en bout (2026-07-31)

- [x] plugins installés et activés — **avec un correctif de composition
  découvert en essayant** : le nom de paquet Composer réel est
  `wp-plugin/<slug>`, PAS `wpackagist-plugin/<slug>` comme l'affirmait
  `DOCKER.md` à l'origine (`composer require
  wpackagist-plugin/secure-custom-fields` échoue avec « package introuvable » ;
  `composer show wp-plugin/polylang --all` le confirme). Pas une
  bizarrerie de ce dépôt : depuis Bedrock 1.30, [WP Packages](https://roots.io/wp-composer-is-now-wp-packages/)
  (`repo.wp-packages.org`, déclaré dans `composer.json`) est la source de
  paquets **officielle** qui remplace WPackagist. `DOCKER.md` et le
  `README.md` racine corrigés depuis.
- [x] `wp-plugin/advanced-custom-fields-pro` **n'existe pas** sur WP Packages
  (ACF Pro est un plugin payant, jamais distribué via le SVN wordpress.org
  que cette source reflète — logique, mais à vérifier avant de supposer que
  la mission §7 de `wordpress-mission-brief.md` s'installe telle quelle). Installé
  `wp-plugin/secure-custom-fields` à la place, l'option de repli déjà
  documentée dans `wordpress-mission-brief.md` §7 pour ce cas — flexible content y est
  bien disponible (Secure Custom Fields = les fonctionnalités cœur d'ACF
  passées gratuites/natives, cf. `wp plugin get secure-custom-fields`)
- [x] `wp-plugin/polylang` installé et activé (`make wp ARGS="plugin
  activate secure-custom-fields polylang"`, confirmé par `wp plugin list`)
- [x] champ flexible content `sections` créé sur `page`, avec la structure
  imbriquée exacte du §7d de `wordpress-mission-brief.md` : top-level = `hero` (max 1) +
  `section` seulement ; `section` contient un `columns` (select 1/2/3) et un
  flexible content imbriqué `content` avec les 5 layouts `text_media`,
  `cards_grid`, `cta_banner`, `accordion`, `embed`
- [x] **enregistré en PHP** (`inc/acf-fields.php`, `acf_add_local_field_group`
  sur le hook `acf/init`), **pas** via l'UI + export Local JSON
  (`acf-json/`) comme le prévoyait `wordpress-mission-brief.md` §7 au départ — écart
  assumé : construire 7 layouts imbriqués à la souris via de l'automatisation
  navigateur est lent et fragile (des dizaines de clics), alors que le
  déclarer en PHP est aussi versionné dans git, plus rapide et plus fiable à
  auditer/faire évoluer. `wordpress-mission-brief.md` §7 mis à jour en conséquence.
- [x] vérifié dans l'admin : seuls `Hero` et `Section (colonnes)` proposés
  au premier niveau (`section`/`text_media`/etc. absents, conforme à la
  règle éditoriale) ; `Hero` grisé après un premier ajout (garde-fou "max 1"
  natif à ACF/SCF, pas besoin de validateur custom contrairement à Drupal) ;
  le flexible content imbriqué `Contenu` propose bien les 5 layouts internes
- [x] **pipeline complet vérifié de bout en bout au navigateur** : layout
  `section` (1 colonne) contenant un `cta_banner` créé sur `Sample Page`,
  `views/templates/page.twig` mappe `post.meta('sections')` (Timber expose
  bien la structure ACF imbriquée telle quelle, avec `acf_fc_layout` à
  chaque niveau — confirmé aussi côté PHP pur via `get_field('sections', 2)`)
  vers `heading` + `button` (pas de composant `cta-banner` dédié, cf. note
  ci-dessous) — rendu réel sur `/fr/sample-page/` : titre, bouton avec le
  bon lien (`/sample-page`), fond `bg-surface-alt`, aucune erreur PHP/Twig
- [x] **bug réel trouvé et corrigé** : le sous-champ `cta_url` déclaré en
  type ACF `url` refuse tout chemin relatif (`/sample-page` → « Le champ
  doit contenir une URL valide », échec de sauvegarde) — ACF valide un
  format d'URL absolue stricte. Corrigé en passant `cta_url` (hero,
  cta_banner, card) en type `text` avec une instruction explicite ; plus
  adapté à des CTA internes que `url` ou que `page_link` (qui imposerait de
  choisir une page existante plutôt qu'un chemin libre).
- [x] **`cards-grid`, `accordion` + `accordion-item`, `embed` livrés**
  (2026-07-31), `text_media` mappé sans composant dédié (composition directe
  de `card` horizontal dans `page.twig`, comme prévu par la mission §7b) :
  - `views/components/03-organisms/cards-grid/` : grille 2/3/4 colonnes
    (objet de mapping Tailwind, jamais `grid-cols-{{ columns }}`), boucle de
    `card` vertical en `{% embed %}` (sans `only`, cf. règle du bug n°1)
  - `views/components/02-molecules/accordion-item/` (`<details>/<summary>`
    natif, pas de JS) + `views/components/03-organisms/accordion/` (wrapper
    `divide-y` qui boucle dessus)
  - `views/components/03-organisms/embed/` : `{{ code|raw }}`, contenu de
    confiance (champ ACF saisi par un éditeur, même niveau de confiance que
    `post.content`)
  - `card.twig` étendu avec une prop `reverse` (image à droite) pour couvrir
    le `position` gauche/droite de `text_media`
  - `page.twig` mappe désormais les 5 layouts imbriqués (`text_media`,
    `cards_grid`, `cta_banner`, `accordion`, `embed`) — la boucle `cards`
    utilise le filtre Twig `|map` avec fonction fléchée (Twig ≥ 3.4, requiert
    `twig/twig: v3.28.0` déjà installé) pour transformer chaque item du
    répéteur ACF (`image.url`/`image.alt`) vers la forme attendue par
    `cards-grid` (`image.src`/`image.alt`)
  - **vérifié réellement** sur `Sample Page` : accordéon ouvert au clic
    (natif, sans JS), `<iframe>` confirmé présent dans le DOM
    (`document.querySelectorAll('iframe').length === 1`), carte de
    `cards_grid` rendue avec titre/contenu/lien, `text_media` sans image
    rendu sans erreur (position `droite` par défaut de l'éditeur)
- [x] `cta_banner` reste mappé en composition directe dans `page.twig`
  (`heading` + `button`, pas de composant `cta-banner` dédié) — à créer si
  cette composition se répète ailleurs (règle latente du futur README
  d'architecture, §"quand créer un composant vs un template")
- [x] **workflow de traduction symétrique Polylang testé réellement**
  (2026-07-31) : depuis `Sample Page` (post `2`, FR), clic sur le `+` en
  face du drapeau anglais dans le panneau *Languages* → nouvelle page
  (`post-new.php?from_post=2&new_lang=en`) créée avec le champ `sections`
  **déjà rempli à l'identique** (colonnes, layout `cta_banner` imbriqué,
  tous les sous-champs y compris `cta_url`) — confirmé en scrollant le
  formulaire ACF de la nouvelle page avant toute sauvegarde. Publiée
  (`Sample Page (EN)`, post `15`) et vérifiée sur `/en/sample-page-en/` :
  rendu identique au FR (non traduit, c'est le contenu littéralement copié).
  **Correction d'une hypothèse de `wordpress-mission-brief.md` §7a** : la copie de
  structure au moment de la création de la traduction est un comportement
  **Polylang gratuit par défaut** (aucun réglage de synchronisation par
  champ n'a été configuré sur le groupe ACF, enregistré en PHP sans écran
  Polylang dédié) — pas besoin de Polylang Pro ni d'une étape manuelle
  "copier le contenu" pour obtenir la symétrie structurelle de départ ; elle
  est acquise à la création. Ce qui reste manuel : traduire ensuite chaque
  champ texte dans la page EN sans casser la structure (aucun garde-fou
  technique ne l'empêche, c'est une discipline éditoriale, comme prévu).

## 8. Multilingue Polylang — configuré et vérifié (2026-07-31)

- [x] FR (défaut) + EN ajoutées via l'assistant Polylang (`mlang_wizard`,
  seul chemin possible — pas de commande WP-CLI native pour Polylang)
- [x] mode dossier confirmé (`The language is set from the directory name
  in pretty permalinks`, déjà le réglage par défaut de Polylang)
- [x] **`Hide URL language information for default language` décoché** —
  décoché volontairement dans `Réglages > Langues > URL modifications`.
  Par défaut Polylang coche cette case (le FR, langue par défaut, n'a
  alors pas de préfixe `/fr/`) ; `wordpress-mission-brief.md` §8 demande explicitement
  des préfixes `/fr` et `/en` symétriques sur les deux langues (fidèle à
  l'exigence du Drupal d'origine), d'où le décochage
  — vérifié : `/` redirige vers `/fr/`, `/en/` répond 200
- [x] détection navigateur : déjà désactivée par défaut (`Detect browser
  language` affiche `Activate`, donc inactif) — rien à faire
- [x] média : `Allow Polylang to translate media` activé dans l'assistant
- [ ] `post`/`page` sont gérés automatiquement par Polylang dès activation
  (pas de toggle dédié, contrairement aux custom post types) — rien à
  configurer tant qu'aucun CPT n'existe
- [ ] exposer `__()`/traduction de chaînes aux composants Twig — toujours
  à faire (aucune fonction de traduction encore enregistrée côté Twig,
  cf. note dans `tag/README.md`)

### Vérifié en vrai : contenu manquant en EN ≠ bug

En visitant `/en/` (aucune traduction anglaise du post « Hello world! »
n'existe), `post` est `null` dans le contexte Timber du front-page — le
`<h1>` du composant `hero` se rend donc vide (`heading` avec `text: null`),
sans erreur PHP/Twig (Twig ne lève pas sur attribut nul). Comportement
attendu tant qu'aucune traduction n'est créée, pas un défaut du composant —
mentionné ici pour ne pas le confondre plus tard avec une régression.

## 9. Qualité / CI — à faire

- [ ] écrire les premiers tests Pest (aucun test n'existe actuellement
  malgré le script `composer test` déjà présent)
- [ ] job GitHub Actions : `composer install`, `npm ci`, `composer lint`,
  `composer test`, `npm run build`, budget CSS gzippé

## Points de vigilance — résolus ou encore ouverts

- **RÉSOLU** — ~~ACF Flexible Content + Polylang : synchronisation par
  champ~~ : en pratique, aucun réglage de synchronisation par champ n'a été
  nécessaire. Polylang copie toute la structure `sections` telle quelle **au
  moment de la création** de la traduction (comportement gratuit natif,
  cf. §7/§8) ; au-delà, FR et EN sont des postmeta indépendants — pas de
  synchronisation permanente par champ à gérer, donc pas de risque de
  collision entre layouts partageant un même nom de sous-champ (`variant`,
  etc.) dans ce modèle.
- **RÉSOLU** — ~~`get_field()` dans Twig via Timber~~ : confirmé,
  `post.meta('sections')` expose bien la structure ACF Flexible Content
  imbriquée telle quelle (`acf_fc_layout` à chaque niveau), cf. §7.
- **Encore ouvert** — `{% include ... only %}` et fonctions Twig custom :
  `only` restreint
  le contexte de *variables*, pas les fonctions/filtres enregistrés
  globalement (ex. une éventuelle fonction `__()` exposée au Twig pour
  Polylang, §8) — comportement documenté par Twig, pas encore vérifié
  concrètement dans ce projet faute de fonction custom enregistrée pour
  l'instant. Voir en revanche le bug n°1 de la section 3-6 : ce même `only`,
  posé sur un `{% embed %}` plutôt qu'un `{% include %}` simple, casse bien
  l'accès aux variables ambiantes (`site`, `post`) dans les blocs de slots —
  confirmé, pas qu'un risque théorique.

---

# 🇬🇧 Execution journal — Twig components / Tailwind v4 / ACF Flexible Content

This document tracks the real state of the project against the
mission described in [`wordpress-mission-brief.md`](./wordpress-mission-brief.md): Twig components,
ACF Flexible Content page builder, Polylang multilingual. Unlike
`DRUPAL-PROCESS.md` (the previous version of this document, which documented
a Drupal project where the mission had been fully executed and verified),
**only part 0 below reflects a verified state of the current repo** — the
rest is an execution plan (backlog), not a journal of work already
done. Do not present sections 1+ as finished until they have
actually been implemented and verified (`make start` + `make wp
ARGS="..."` + real rendering). This repo has no `CLAUDE.md` requiring
this — it's a deliberate discipline for this specific document, after several
real bugs found only by rendering (see bugs #1-2 in section 3-6
below), never by re-reading the code.

## Table of contents

- [0. Starting point (verified in the repo as of 2026-07-31)](#0-starting-point-verified-in-the-repo-as-of-2026-07-31)
- [1. Build pipeline — to do](#1-build-pipeline-to-do)
- [2. Design tokens — done (2026-07-31)](#2-design-tokens-done-2026-07-31)
- [3-6. Twig components — 7/7 done (2026-07-31)](#3-6-twig-components-77-done-2026-07-31)
- [7. ACF Flexible Content page builder — done and verified end to end (2026-07-31)](#7-acf-flexible-content-page-builder-done-and-verified-end-to-end-2026-07-31)
- [8. Polylang multilingual — configured and verified (2026-07-31)](#8-polylang-multilingual-configured-and-verified-2026-07-31)
- [9. Quality / CI — to do](#9-quality-ci-to-do)
- [Watch points — resolved or still open](#watch-points-resolved-or-still-open)

## 0. Starting point (verified in the repo as of 2026-07-31)

What already exists, documented in detail in [`THEME.md`](../THEME.md)
and [`DOCKER.md`](../DOCKER.md):

- Bedrock scaffold (`composer.json`, `config/`, `web/wp`, `web/app`), PHP
  ≥ 8.4, Docker Compose (Traefik, PHP/Apache, MariaDB, node, phpMyAdmin,
  Mailhog, Dockhand)
- Custom theme `web/app/themes/custom/tailwind`, PSR-4 autoloaded
  (`App\Theme\`) from the root `composer.json`
- Timber v2 initialized (`functions.php` → `Timber::init()` +
  `new Site()`), `src/Site.php`: `theme_supports()` (title-tag,
  post-thumbnails, `primary` menu, etc.) and `add_to_context()` (adds
  `site` and `menu` to the Twig context)
- Existing templates: `views/layouts/base.twig`, `views/partials/`
  (head, recursive menu, footer), `views/templates/` (`index`,
  `front-page`, `page`, `single`, `404`) — **none consume a
  component** yet, HTML is written directly with inline
  Tailwind classes
- Working Vite + Tailwind v4 pipeline: `assets/styles/app.css`
  (`@import "tailwindcss"`, `@plugin "@tailwindcss/typography"`, `@source`
  on `views/**/*.twig` and `**/*.php`), `assets/scripts/app.js`, dev/prod
  switch in `inc/vite.php`, HMR with the `phpTwigReload` plugin +
  `usePolling` (issues fixed, documented in `THEME.md`, not to
  redo here)
- `composer.json`: `lint` (Pint) and `test` (Pest) scripts already declared,
  but **no Pest test written**, no GitHub Actions CI

> Update on 2026-07-31: the `@theme` block and the first component
> (`button`) have been delivered — see §2 and §3-6 below, which replace the
> two corresponding bullets in this initial list.

What does **not** exist yet (contrary to what a reader might
infer from a journal written in the past tense):

- `views/components/` only contains `01-atoms/button/` — `02-molecules/`
  and `03-organisms/` are empty, no other component delivered
- no ACF or Polylang plugin in `composer.json`
- no `acf-json/`, no flexible content field, no multilingual
  config
- no CI, no ESLint/Prettier configured in the theme

## 1. Build pipeline — to do

- [ ] check whether `views/components/` needs to be added to `@source` in
  `assets/styles/app.css` once the folder is created (the existing
  `views/**/*.twig` pattern already covers it if components live under
  `views/`)
- [ ] add Prettier + `prettier-plugin-tailwindcss`, ESLint, to the theme's
  `devDependencies` (`package.json`)
- [ ] script validating the components' props headers (§3 of
  `wordpress-mission-brief.md`)

## 2. Design tokens — done (2026-07-31)

- [x] `@theme` block written in `assets/styles/app.css`: `--color-primary(-hover)`,
  `--color-secondary(-hover)`, `--color-surface(-alt/-inverse)`,
  `--color-text(-muted/-inverse)`, `--color-border(-strong)`, `--radius-sm/md/lg`,
  `--spacing-section`
- [x] `views/layouts/base.twig` migrated to the semantic tokens (`bg-surface
  text-text`, `border-border`) instead of `bg-white text-gray-900`/`border-gray-200`
- [ ] the `--color-secondary`/`-success`/`-warning`/`-danger`/`-info` colors
  still need refining against the client's actual brand guidelines — current
  values are arbitrary (OKLCH placeholders), to be replaced before going to
  production

## 3-6. Twig components — 7/7 done (2026-07-31)

- [x] `views/components/01-atoms/`, `02-molecules/`, `03-organisms/` created
- [x] `button` (`01-atoms/button/`): primary/secondary/ghost variants,
  sm/md/lg sizes, `url` prop (renders `<a>` or `<button>`), `disabled` prop
- [x] `heading` (`01-atoms/heading/`): `level` prop (1-6, semantic
  `<hN>` via `<h{{ level }}>`, valid in Twig) decoupled from `size` (visual),
  size derived from `level` if `size` is absent
- [x] `badge` (`01-atoms/badge/`): neutral/success/warning/danger/info variants
  (required adding the `--color-success/-warning/-danger/-info(-surface)` tokens
  to `@theme`, absent from the first tokens pass in §2)
- [x] `tag` (`01-atoms/tag/`): optionally removable chip; DOM removal
  is a delegated `[data-tag-remove]` listener added in
  `assets/scripts/app.js` (vanilla, no per-component JS dependency)
- [x] `icon` (`01-atoms/icon/`): `assets/images/sprite.svg` sprite created
  (`arrow-right`, `close`, `check` symbols), explicit `sprite_url` prop
  (the component never reads `site` itself, see bug #1 below)
- [x] `card` (`02-molecules/card/`): `image`/`title`/`content`/`footer` slots
  via `{% block %}` + `{% embed %}`, vertical/horizontal variant
- [x] `hero` (`03-organisms/hero/`): title/subtitle/media/cta, internally
  composes `heading` and `button` (no markup duplication), centered
  variant without `media` or 2-column grid with `media` — actually
  used at the top of `front-page.twig`, verified in the browser
  (`--spacing-section` generated as `py-section`, confirmed in the compiled
  CSS via `grep`)
- [x] `views/templates/front-page.twig` actually consumes all 7
  components (hero, heading — via hero and via card, badge, tag, button —
  via hero and via card, icon, card) — verified in the browser, see bugs
  below
- [x] `page.twig`, `single.twig` migrated to `heading` (their hardcoded `<h1>`);
  `text-gray-500` replaced with the `--color-text-muted` token in
  `single.twig`; verified in the browser on `sample-page` and `hello-world`

### Real bugs found by checking in the browser (not by re-reading code)

1. **`only` on an `{% embed %}` silently breaks nested includes
   inside its slots.** First pass of `card` used with `{% embed ... with
   {variant: 'horizontal'} only %}`: the icon placed in the `image` slot
   (`{% include '.../icon.twig' with {sprite_url: site.theme.link ~ '...'}
   only %}`) rendered with a truncated `<use href="/assets/images/sprite.svg#check">`
   (404), instead of the expected absolute URL. Cause: the content of an
   `embed`'s `{% block %}` runs in the calling page's scope
   (that's what lets it use `post`/`site`); `only` on the embed
   cuts off that scope for the whole block, so `site.theme.link` was an
   empty string **with no Twig error**. Confirmed by inspecting the rendered DOM
   (`document.querySelector('svg use').getAttribute('href')`) and by testing
   the fetch of the real URL (404 → 200 after the fix). Fixed by removing
   `only` from the `{% embed %}` tag (the `card` component itself only
   receives `variant` anyway; it's the `{% include %}` calls *inside*
   the slots that keep their own `only` and stay isolated). Carried over
   into `wordpress-mission-brief.md` §3 and `card/README.md`.
2. **`site.theme.link`** (Twig property/method of `Timber\Theme`) resolves
   correctly once point 1 is fixed — confirmed via `wp eval-file`:
   `$theme->link()` correctly returns the theme's absolute URI
   (`.../app/themes/custom/tailwind`). The `http://` scheme returned (instead
   of `https://`) comes from protocol detection on the PHP container side
   behind Traefik, with no observed impact on rendering (the browser resolves
   it relative/https) — worth watching if an absolute link is ever displayed
   as-is on the user side.

## 7. ACF Flexible Content page builder — done and verified end to end (2026-07-31)

- [x] plugins installed and activated — **with a naming fix
  discovered while trying**: the real Composer package name is
  `wp-plugin/<slug>`, NOT `wpackagist-plugin/<slug>` as
  `DOCKER.md` originally claimed (`composer require
  wpackagist-plugin/secure-custom-fields` fails with "package not found";
  `composer show wp-plugin/polylang --all` confirms it). Not a
  quirk of this repo: since Bedrock 1.30, [WP Packages](https://roots.io/wp-composer-is-now-wp-packages/)
  (`repo.wp-packages.org`, declared in `composer.json`) is the
  **official** package source replacing WPackagist. `DOCKER.md` and the
  root `README.md` have been corrected since.
- [x] `wp-plugin/advanced-custom-fields-pro` **doesn't exist** on WP Packages
  (ACF Pro is a paid plugin, never distributed via the wordpress.org SVN
  that this source mirrors — makes sense, but worth checking before assuming
  `wordpress-mission-brief.md`'s §7 mission installs as-is). Installed
  `wp-plugin/secure-custom-fields` instead, the fallback option already
  documented in `wordpress-mission-brief.md` §7 for this case — flexible content is
  indeed available there (Secure Custom Fields = ACF's core features
  turned free/native, see `wp plugin get secure-custom-fields`)
- [x] `wp-plugin/polylang` installed and activated (`make wp ARGS="plugin
  activate secure-custom-fields polylang"`, confirmed by `wp plugin list`)
- [x] `sections` flexible content field created on `page`, with the exact
  nested structure from `wordpress-mission-brief.md` §7d: top-level = `hero` (max 1) +
  `section` only; `section` contains a `columns` (select 1/2/3) and a
  nested flexible content `content` with the 5 layouts `text_media`,
  `cards_grid`, `cta_banner`, `accordion`, `embed`
- [x] **registered in PHP** (`inc/acf-fields.php`, `acf_add_local_field_group`
  on the `acf/init` hook), **not** via the UI + Local JSON export
  (`acf-json/`) as `wordpress-mission-brief.md` §7 originally planned — deliberate
  deviation: building 7 nested layouts by mouse via browser
  automation is slow and fragile (dozens of clicks), whereas
  declaring it in PHP is just as versioned in git, faster and more reliable to
  audit/evolve. `wordpress-mission-brief.md` §7 updated accordingly.
- [x] verified in the admin: only `Hero` and `Section (columns)` offered
  at the top level (`section`/`text_media`/etc. absent, consistent with
  the editorial rule); `Hero` grayed out after a first addition (native "max 1"
  guardrail in ACF/SCF, no need for a custom validator unlike on Drupal);
  the nested flexible content `Content` correctly offers the 5 internal layouts
- [x] **full pipeline verified end to end in the browser**: a `section`
  layout (1 column) containing a `cta_banner` created on `Sample Page`,
  `views/templates/page.twig` maps `post.meta('sections')` (Timber correctly
  exposes the nested ACF structure as-is, with `acf_fc_layout` at
  every level — also confirmed on the pure PHP side via `get_field('sections', 2)`)
  to `heading` + `button` (no dedicated `cta-banner` component, see note
  below) — real render on `/fr/sample-page/`: title, button with the
  right link (`/sample-page`), `bg-surface-alt` background, no PHP/Twig error
- [x] **real bug found and fixed**: the `cta_url` sub-field declared as ACF
  type `url` rejects any relative path (`/sample-page` → "This field
  must contain a valid URL", save failure) — ACF validates a strict absolute
  URL format. Fixed by switching `cta_url` (hero,
  cta_banner, card) to type `text` with an explicit instruction; better
  suited to internal CTAs than `url` or `page_link` (which would force
  picking an existing page rather than a free-form path).
- [x] **`cards-grid`, `accordion` + `accordion-item`, `embed` delivered**
  (2026-07-31), `text_media` mapped without a dedicated component (direct
  composition of horizontal `card` in `page.twig`, as planned in mission §7b):
  - `views/components/03-organisms/cards-grid/`: 2/3/4-column grid
    (Tailwind mapping object, never `grid-cols-{{ columns }}`), loop of
    vertical `card` in `{% embed %}` (without `only`, see the bug #1 rule)
  - `views/components/02-molecules/accordion-item/` (native
    `<details>/<summary>`, no JS) + `views/components/03-organisms/accordion/`
    (`divide-y` wrapper looping over it)
  - `views/components/03-organisms/embed/`: `{{ code|raw }}`, trusted
    content (ACF field entered by an editor, same trust level as
    `post.content`)
  - `card.twig` extended with a `reverse` prop (image on the right) to cover
    `text_media`'s left/right `position`
  - `page.twig` now maps the 5 nested layouts (`text_media`,
    `cards_grid`, `cta_banner`, `accordion`, `embed`) — the `cards` loop
    uses the Twig `|map` filter with an arrow function (Twig ≥ 3.4, requires
    `twig/twig: v3.28.0` already installed) to transform each ACF
    repeater item (`image.url`/`image.alt`) into the shape expected by
    `cards-grid` (`image.src`/`image.alt`)
  - **actually verified** on `Sample Page`: accordion opens on click
    (native, no JS), `<iframe>` confirmed present in the DOM
    (`document.querySelectorAll('iframe').length === 1`), `cards_grid`
    card rendered with title/content/link, `text_media` without an image
    rendered with no error (editor's default `right` position)
- [x] `cta_banner` remains mapped as a direct composition in `page.twig`
  (`heading` + `button`, no dedicated `cta-banner` component) — to create if
  this composition repeats elsewhere (latent rule for the future architecture
  README, §"when to create a component vs. a template")
- [x] **symmetric Polylang translation workflow actually tested**
  (2026-07-31): from `Sample Page` (post `2`, FR), clicking the `+` next
  to the English flag in the *Languages* panel → new page
  (`post-new.php?from_post=2&new_lang=en`) created with the `sections`
  field **already filled in identically** (columns, nested `cta_banner`
  layout, every sub-field including `cta_url`) — confirmed by scrolling
  the new page's ACF form before any save. Published
  (`Sample Page (EN)`, post `15`) and checked on `/en/sample-page-en/`:
  render identical to FR (untranslated, the content is literally copied).
  **Correction of a `wordpress-mission-brief.md` §7a assumption**: copying the
  structure when the translation is created is a **Polylang free
  default behavior** (no per-field sync setting was configured on
  the ACF group, registered in PHP with no dedicated Polylang screen) — no
  need for Polylang Pro or a manual "copy the content" step to get the
  initial structural symmetry; it's acquired at creation time. What remains
  manual: then translating each text field on the EN page without breaking
  the structure (no technical guardrail prevents it, it's an editorial
  discipline, as expected).

## 8. Polylang multilingual — configured and verified (2026-07-31)

- [x] FR (default) + EN added via the Polylang wizard (`mlang_wizard`,
  the only path available — no native WP-CLI command for Polylang)
- [x] directory mode confirmed (`The language is set from the directory name
  in pretty permalinks`, already Polylang's default setting)
- [x] **`Hide URL language information for default language` unchecked** —
  deliberately unchecked in *Settings > Languages > URL modifications*.
  By default Polylang checks this box (FR, the default language, then has
  no `/fr/` prefix); `wordpress-mission-brief.md` §8 explicitly asks for symmetric
  `/fr` and `/en` prefixes on both languages (faithful to the original
  Drupal requirement), hence unchecking it
  — verified: `/` redirects to `/fr/`, `/en/` responds 200
- [x] browser detection: already disabled by default (`Detect browser
  language` shows `Activate`, so it's inactive) — nothing to do
- [x] media: `Allow Polylang to translate media` enabled in the wizard
- [ ] `post`/`page` are automatically managed by Polylang once activated
  (no dedicated toggle, unlike custom post types) — nothing to
  configure as long as no CPT exists
- [ ] expose `__()`/string translation to Twig components — still
  to do (no translation function registered on the Twig side yet,
  see the note in `tag/README.md`)

### Actually verified: missing content on EN ≠ a bug

Visiting `/en/` (no English translation of the "Hello world!" post
exists), `post` is `null` in the front-page's Timber context — the
`hero` component's `<h1>` therefore renders empty (`heading` with `text: null`),
with no PHP/Twig error (Twig doesn't raise on a null attribute). Expected
behavior as long as no translation has been created, not a component defect —
mentioned here so it's not later mistaken for a regression.

## 9. Quality / CI — to do

- [ ] write the first Pest tests (no test currently exists
  despite the `composer test` script already being present)
- [ ] GitHub Actions job: `composer install`, `npm ci`, `composer lint`,
  `composer test`, `npm run build`, gzipped CSS budget

## Watch points — resolved or still open

- **RESOLVED** — ~~ACF Flexible Content + Polylang: per-field
  synchronization~~: in practice, no per-field sync setting was
  needed. Polylang copies the entire `sections` structure as-is **at
  translation creation time** (native free behavior, see §7/§8); beyond
  that, FR and EN are independent postmeta — no ongoing
  per-field synchronization to manage, so no risk of collision
  between layouts sharing the same sub-field name (`variant`,
  etc.) in this model.
- **RESOLVED** — ~~`get_field()` in Twig via Timber~~: confirmed,
  `post.meta('sections')` correctly exposes the nested ACF Flexible Content
  structure as-is (`acf_fc_layout` at every level), see §7.
- **Still open** — `{% include ... only %}` and custom Twig functions:
  `only` restricts
  the *variable* context, not globally registered functions/filters
  (e.g. a hypothetical `__()` function exposed to Twig for
  Polylang, §8) — behavior documented by Twig, not yet concretely
  verified in this project for lack of a custom function registered
  so far. See bug #1 in section 3-6 for the flip side, though: that same
  `only`, placed on an `{% embed %}` rather than a plain `{% include %}`,
  does break access to ambient variables (`site`, `post`) in slot blocks —
  confirmed, not just a theoretical risk.
