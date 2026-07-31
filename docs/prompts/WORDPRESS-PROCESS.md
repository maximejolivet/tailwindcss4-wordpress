# Journal d'exécution — Composants Twig / Tailwind v4 / ACF Flexible Content

Ce document retrace, en français, l'état réel du projet par rapport à la
mission décrite dans [`WORDPRESS.md`](./WORDPRESS.md) : composants Twig,
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

## 0. État de départ (vérifié dans le dépôt au 2026-07-31)

Ce qui existe déjà, documenté en détail dans [`docs/theme.md`](../theme.md)
et [`docs/README.md`](../README.md) :

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
  `usePolling` (problèmes résolus documentés dans `docs/theme.md`, pas à
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
  `WORDPRESS.md`)

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
   dans `WORDPRESS.md` §3 et `card/README.md`.
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
  `docs/README.md` à l'origine (`composer require
  wpackagist-plugin/secure-custom-fields` échoue avec « package introuvable » ;
  `composer show wp-plugin/polylang --all` le confirme). Pas une
  bizarrerie de ce dépôt : depuis Bedrock 1.30, [WP Packages](https://roots.io/wp-composer-is-now-wp-packages/)
  (`repo.wp-packages.org`, déclaré dans `composer.json`) est la source de
  paquets **officielle** qui remplace WPackagist. `docs/README.md` et le
  `README.md` racine corrigés depuis.
- [x] `wp-plugin/advanced-custom-fields-pro` **n'existe pas** sur WP Packages
  (ACF Pro est un plugin payant, jamais distribué via le SVN wordpress.org
  que cette source reflète — logique, mais à vérifier avant de supposer que
  la mission §7 de `WORDPRESS.md` s'installe telle quelle). Installé
  `wp-plugin/secure-custom-fields` à la place, l'option de repli déjà
  documentée dans `WORDPRESS.md` §7 pour ce cas — flexible content y est
  bien disponible (Secure Custom Fields = les fonctionnalités cœur d'ACF
  passées gratuites/natives, cf. `wp plugin get secure-custom-fields`)
- [x] `wp-plugin/polylang` installé et activé (`make wp ARGS="plugin
  activate secure-custom-fields polylang"`, confirmé par `wp plugin list`)
- [x] champ flexible content `sections` créé sur `page`, avec la structure
  imbriquée exacte du §7d de `WORDPRESS.md` : top-level = `hero` (max 1) +
  `section` seulement ; `section` contient un `columns` (select 1/2/3) et un
  flexible content imbriqué `content` avec les 5 layouts `text_media`,
  `cards_grid`, `cta_banner`, `accordion`, `embed`
- [x] **enregistré en PHP** (`inc/acf-fields.php`, `acf_add_local_field_group`
  sur le hook `acf/init`), **pas** via l'UI + export Local JSON
  (`acf-json/`) comme le prévoyait `WORDPRESS.md` §7 au départ — écart
  assumé : construire 7 layouts imbriqués à la souris via de l'automatisation
  navigateur est lent et fragile (des dizaines de clics), alors que le
  déclarer en PHP est aussi versionné dans git, plus rapide et plus fiable à
  auditer/faire évoluer. `WORDPRESS.md` §7 mis à jour en conséquence.
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
  **Correction d'une hypothèse de `WORDPRESS.md` §7a** : la copie de
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
  alors pas de préfixe `/fr/`) ; `WORDPRESS.md` §8 demande explicitement
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
