# 🇬🇧 Context
You're working on this WordPress project managed with [Bedrock](https://roots.io/bedrock/)
(repo root = Bedrock root: `composer.json`, `config/`, `web/wp` for
WordPress core, `web/app` for themes/plugins/uploads). The custom theme
`web/app/themes/custom/tailwind` already exists: Timber v2 (Twig templates in
`views/`), Tailwind CSS v4 compiled by Vite with HMR already working (see
[`theme.md`](../theme.md) for the pipeline detail). Goal:
industrialize the front end with reusable Twig components, consumed by
an editorial ACF Flexible Content page builder, on a multilingual FR/EN site
via Polylang. Agency purpose: reduce Twig duplication, make multi-project
Bedrock+Timber delivery more reliable, with Tailwind tokens (`@theme`) being
the only customization point between projects built on this same foundation.

# Mission

## 1. Build pipeline (already in place, to extend)
The Vite + Tailwind v4 pipeline already exists and is documented in
`theme.md`: single entry point `assets/styles/app.css`
(`@import "tailwindcss"`), explicit `@source`, dev/prod switch in
`inc/vite.php`, HMR (`phpTwigReload` plugin + polling for Colima/virtiofs).
Only to extend:
- if the components (mission §3) live outside `views/`, add the matching
  `@source` line in `assets/styles/app.css` (otherwise the existing
  `@source "../../views/**/*.twig"` is already enough)
- `npm run lint:components` script (validates the props headers, see
  §3) and `npm run prettier:check` (Prettier + `prettier-plugin-tailwindcss`
  for class ordering), ESLint on `assets/scripts/**/*.js`

## 2. Design tokens
`@theme` block in `assets/styles/app.css` (to create — currently absent,
the theme uses Tailwind's default colors): semantic colors
(`--color-primary/-hover`, `--color-surface/-alt`, `--color-text/-muted`,
`--color-border`, etc.), spacing, fonts, radius. No arbitrary
`[...]` value in components unless justified in a comment. The tokens
are the only customization point between the agency's projects built on this
theme.

## 3. Twig component structure
Under `views/components/` (to create, distinct from the already-existing
`views/templates/` and `views/partials/`): `01-atoms/`, `02-molecules/`,
`03-organisms/`. Each component: one `*.twig`, one `README.md` with a real
usage example. Timber/Twig has no native equivalent to Drupal's Single
Directory Components props schema (no automatic type validation):
document the expected props in a Twig comment at the top of the file, e.g.

```twig
{#
  Props: variant (primary|secondary|ghost, default: primary), size (sm|md|lg, default: md),
         url (optional — renders <a> instead of <button>), disabled (bool)
#}
```

and in the component's `README.md` — this is a team convention, not an
automated constraint (see §9 for an in-house linter that at least checks the
presence of this block). Every component WITHOUT slots must be renderable
in isolation via `{% include 'components/01-atoms/button/button.twig' with
{...} only %}` — always with `only`, so it never implicitly depends
on the page's context (`post`, `site`, `menu`…) or WordPress entities.

**Exception confirmed in practice**: for a component with slots (`{% block
%}`, consumed via `{% embed %}` — see §4 `card`), NEVER put `only`
on the `{% embed %}` tag itself. The content of the blocks defined there
runs in the calling page's scope — that's what lets it
use `post.title`, `site.theme.link`, etc. to compose the actual
content. `only` on the embed cuts off that access for the whole block, including
nested `{% include ... only %}` calls inside the slots, which then
receive empty variables with no Twig error (silent, hence dangerous — only
caught by actually rendering the page). Each `{% include %}` nested
inside a slot keeps its own `only`: it's the one that isolates the
leaf component, not the enclosing embed.

## 4. Starting components (typed props in a comment + slots via `{% block %}`)
- `button`: primary/secondary/ghost variants, sm/md/lg sizes, `url` prop
  (renders `<a>` or `<button>`), `disabled` prop
- `card`: image/title/content/footer slots, horizontal variant
- `heading`: `level` prop (1-6, semantic) decoupled from `size` prop (visual)
- `badge`, `tag`, `icon` (SVG sprite in `assets/images/sprite.svg`)
- `hero` (organism): title, subtitle, media, cta

## 5. Variant pattern in Twig
Mapping via a Twig object, never dynamic class concatenation (Tailwind's
detector wouldn't see `bg-{{ color }}`):

```twig
{% set variants = {
  primary: 'bg-primary text-white hover:bg-primary-hover',
  secondary: 'bg-transparent border border-primary text-primary',
} %}
<button class="inline-flex items-center rounded-md {{ variants[variant|default('primary')] }} {{ sizes[size|default('md')] }}">
```

## 6. Timber / WordPress integration
- consumption via `{% include 'components/01-atoms/button.twig' with {...} only %}`
- the existing template hierarchy (`theme.md`) stays the same:
  `views/templates/single-{post_type}.twig`, `page-{slug}.twig`, etc.
- WYSIWYG content (`post.content` or ACF `wysiwyg` field): `prose` class
  (`@tailwindcss/typography`, already in place in `assets/styles/app.css`) —
  the only place where `@apply` is tolerated if an adjustment is needed (in
  `assets/styles/app.css`, never in a component)
- Preflight: Tailwind v4 enables it by default via `@import "tailwindcss"`;
  document in the architecture README (§ Deliverables) the restyling
  strategy for tags injected by WordPress plugins (shortcodes,
  third-party widgets) that don't have a dedicated component

## 7. Page builder with ACF Flexible Content (multilingual)
Plugins via Composer: `advanced-custom-fields-pro` (or `secure-custom-fields`
if budget-constrained — flexible content is available there since it went
free; it's also the only option installable via Composer without a
license key, since ACF Pro is never distributed on the wordpress.org SVN),
`polylang` (`polylang-pro` if advanced field synchronization is needed).
Real package name: **`wp-plugin/<slug>`**, not `wpackagist-plugin/<slug>` —
since Bedrock 1.30, [WP Packages](https://roots.io/wp-composer-is-now-wp-packages/)
(`repo.wp-packages.org`, see `composer.json`) is the **official**
package source replacing WPackagist, not a convention specific to this
repo (confirmed by `composer show wp-plugin/polylang --all`).

### a. Translation architecture — SYMMETRIC, non-negotiable
- each Polylang translation of a post is a distinct WordPress post (linked
  via `pll_get_post_translations`): the ACF Flexible Content field
  (`field_sections`) is therefore never "shared" between languages the way
  `field_sections` was in Drupal — symmetry must be **enforced by the
  editorial process**, not by storage
- workflow — **verified for real, see `WORDPRESS-PROCESS.md` §7**: clicking
  the `+` next to the target language's flag in the editor's *Languages*
  panel creates the translation with the `sections` field
  **already filled in identically** (Polylang's free default behavior,
  no per-field sync setting required, no "copy the content" option to
  check); only translate the text/media sub-fields of each layout
  afterward — never rebuild the layout list by hand on the EN side,
  nothing technically prevents it, this is an editorial discipline
- this copy only happens **at creation time**: beyond that, the two
  languages are independent posts (separate postmeta) — changing the
  `sections` structure on FR afterward has no effect on EN, and vice
  versa; if a real need for permanent per-field synchronization arises
  (not just an initial copy), that's a Polylang Pro feature
- document why: asymmetrically, each language derives its own layout
  list → duplicated work, orphaned layouts on one side,
  editorial desync. We don't do that.
- test: duplicating a post FR→EN must offer translation of every
  existing layout, never an empty structure to rebuild

### b. ACF Flexible Content layouts (each mapped to a Twig component)
- `hero` → hero organism
- `text_media` (body, media, left/right position) → horizontal card
- `cards_grid` (repeater sub-field or nested flexible content of cards)
- `cta_banner`, `accordion`, `embed`
- a `variant` sub-field (select, choices = same values as the
  props comment of the matching Twig component, §3) on layouts
  that have variants; the source of truth remains the Twig component —
  mention in the README the trick to keep both in sync (generate
  the ACF select's `choices` from a shared JSON file if the number of
  variants drifts)

### c. Twig templates = pure mapping
The consuming template (e.g. `page.twig`) loops over `get_field('sections')`
and does ONLY the layout → component mapping via `{% include %}`:

```twig
{% for section in fields.sections %}
  {% if section.acf_fc_layout == 'hero' %}
    {% include 'components/03-organisms/hero.twig' with section only %}
  {% elseif section.acf_fc_layout == 'cta_banner' %}
    {% include 'components/03-organisms/cta-banner.twig' with section only %}
  {% endif %}
{% endfor %}
```

Zero structural HTML tag in this mapping. If HTML shows up there, it means
the Twig component is incomplete — fix the component.

### d. Section layout / grid
An ACF `section` layout with a `columns` sub-field (1/2/3, select) and a
nested flexible content for its content; grids as Tailwind
classes (`grid grid-cols-{{ columns }}`, via the mapping object from §5, no
dynamic concatenation). Other layouts are only insertable INSIDE
a `section` layout (`parent_layout` field in the ACF Flexible
Content config, or by limiting available layouts at the nested field
group level).

### e. Editorial guardrails
- min/max per layout: ACF Flexible Content natively lets you set a
  min/max **per layout** (e.g. `hero`: min 0, max 1) — no need for a
  custom validator like on the Drupal side
- field labels and instructions in French
- layout icons/previews configured (`Field Group > Flexible Content >
  Layout > icon`)

## 8. Multilingual — global config (Polylang)
- languages FR (default) + EN, "directory" mode (`/fr/`, `/en/`) rather
  than subdomains
- detection: URL only — disable *"Detect browser
  language"* in Polylang's settings
- Polylang enabled on `post`, `page`, `attachment` (media — so alt
  text is translatable) and on any custom post types
- hardcoded strings in components' Twig (e.g. "Read
  more"): `__('Lire la suite', 'tailwind')` exposed to Twig via a
  `Timber::$twig_functions` or a custom Twig function, or registered
  via *Polylang > String translation* for strings outside PHP/Twig
  (theme settings, global ACF options)
- permalinks: pretty permalinks required (Polylang prerequisite), aliases
  and SEO metadata per language if an SEO plugin (Yoast, Rank Math) is
  present

## 9. Quality / CI
- Prettier + `prettier-plugin-tailwindcss` (class ordering)
- ESLint on `assets/scripts/**/*.js`
- `composer lint` (Pint) and `composer test` (Pest) already scripted in
  `composer.json` — wire them into CI rather than duplicating tooling
- GitHub Actions job: `composer install`, `npm ci` (in the theme),
  `composer lint`, `composer test`, a script validating the components'
  props headers (§3) and the presence of their `README.md`,
  `npm run build`, fail if the generated CSS exceeds 50 KB gzipped
- regression test: a post with every layout, translated FR→EN via
  the §7a workflow, renders the same structural DOM in both languages

# Constraints
- Bedrock, WordPress via `roots/wordpress`, PHP ≥ 8.4 (see `composer.json`),
  Tailwind v4 (CSS-first config, no legacy `tailwind.config.js`), no
  jQuery in the custom theme
- `@apply` forbidden outside the `prose` block of `assets/styles/app.css`
- Accessibility (RGAA): systematic focus-visible, contrasts checked on
  the tokens, ARIA justified
- No rendering logic in `functions.php`/`src/Site.php` (mapping in
  Twig; PHP only for actual data transformation — e.g. in
  `add_to_context()`)
- ACF field groups versioned in git — **in PHP**
  (`acf_add_local_field_group()` on the `acf/init` hook, e.g.
  `inc/acf-fields.php`), not via the UI + Local JSON export (`acf-json/`):
  building nested layouts by mouse (a full page builder = dozens of
  fields) is slow and fragile to redo on every change, whereas PHP
  stays just as versioned, faster to write and easier to review in a
  diff. Avoid the `url`/`page_link` field type for an internal CTA with a
  free-form URL: `url` rejects any relative path (strict validation),
  `page_link` forces picking an existing page — prefer
  `text` with an explicit instruction. Polylang settings (languages,
  per-field sync settings) documented in the architecture README for
  lack of a reliable native export

# Deliverables
Proceed step by step: pipeline (check what exists, extend if needed),
tokens, components one by one with a real usage example, then ACF layouts
and multilingual config. End with an architecture README covering:
- when to create a Twig component vs. a template in `views/templates/`
- the cross-project token strategy
- Polylang vs. WPML: stay on Polylang as long as the free field
  synchronization is enough; switch criterion to WPML/Polylang Pro =
  a real need for assisted machine translation or a multi-role
  validation workflow

---

# 🇫🇷 Contexte
Tu interviens sur ce projet WordPress géré avec [Bedrock](https://roots.io/bedrock/)
(racine du repo = racine Bedrock : `composer.json`, `config/`, `web/wp` pour le
cœur WordPress, `web/app` pour thèmes/plugins/uploads). Le thème custom
`web/app/themes/custom/tailwind` existe déjà : Timber v2 (templates Twig dans
`views/`), Tailwind CSS v4 compilé par Vite avec HMR déjà fonctionnel (voir
[`theme.md`](../theme.md) pour le détail du pipeline). Objectif :
industrialiser le front avec des composants Twig réutilisables, consommés par
un page builder éditorial ACF Flexible Content, sur un site multilingue FR/EN
via Polylang. Finalité agence : réduire la duplication Twig, fiabiliser les
livraisons multi-projets Bedrock+Timber, les tokens Tailwind (`@theme`) étant
le seul point de personnalisation entre projets basés sur ce même socle.

# Mission

## 1. Pipeline de build (déjà en place, à étendre)
Le pipeline Vite + Tailwind v4 existe déjà et est documenté dans
`theme.md` : entrée unique `assets/styles/app.css`
(`@import "tailwindcss"`), `@source` explicites, bascule dev/prod dans
`inc/vite.php`, HMR (plugin `phpTwigReload` + polling pour Colima/virtiofs).
À étendre seulement :
- si les composants (mission §3) vivent hors de `views/`, ajouter la ligne
  `@source` correspondante dans `assets/styles/app.css` (sinon le
  `@source "../../views/**/*.twig"` existant suffit déjà)
- scripts `npm run lint:components` (validation des en-têtes de props, voir
  §3) et `npm run prettier:check` (Prettier + `prettier-plugin-tailwindcss`
  pour l'ordre des classes), ESLint sur `assets/scripts/**/*.js`

## 2. Design tokens
Bloc `@theme` dans `assets/styles/app.css` (à créer — actuellement absent,
le thème utilise les couleurs par défaut de Tailwind) : couleurs sémantiques
(`--color-primary/-hover`, `--color-surface/-alt`, `--color-text/-muted`,
`--color-border`, etc.), spacing, fonts, radius. Aucune valeur arbitraire
`[...]` dans les composants sauf justification en commentaire. Les tokens
sont le seul point de personnalisation entre projets de l'agence basés sur ce
thème.

## 3. Structure des composants Twig
Sous `views/components/` (à créer, distinct de `views/templates/` et
`views/partials/` déjà existants) : `01-atoms/`, `02-molecules/`,
`03-organisms/`. Chaque composant : un `*.twig`, un `README.md` avec exemple
d'usage réel. Timber/Twig n'a pas d'équivalent natif au schéma de props des
Single Directory Components Drupal (pas de validation automatique de type) :
documenter les props attendues en commentaire Twig en tête de fichier, ex.

```twig
{#
  Props: variant (primary|secondary|ghost, default: primary), size (sm|md|lg, default: md),
         url (optionnel — rend <a> au lieu de <button>), disabled (bool)
#}
```

et dans le `README.md` du composant — c'est une convention d'équipe, pas une
contrainte automatisée (voir §9 pour un linter maison qui vérifie au moins la
présence de ce bloc). Chaque composant SANS slot doit être rendable
isolément via `{% include 'components/01-atoms/button/button.twig' with
{...} only %}` — toujours avec `only`, pour ne jamais dépendre implicitement
du contexte de la page (`post`, `site`, `menu`…) ni d'entités WordPress.

**Exception confirmée à l'usage** : pour un composant à slots (`{% block
%}`, consommé via `{% embed %}` — cf. §4 `card`), ne JAMAIS mettre `only`
sur le tag `{% embed %}` lui-même. Le contenu des blocs qu'on y définit
s'exécute dans la portée de la page appelante — c'est ce qui permet d'y
utiliser `post.title`, `site.theme.link`, etc. pour composer le vrai
contenu. `only` sur l'embed coupe cet accès pour tout le bloc, y compris les
`{% include ... only %}` imbriqués à l'intérieur des slots, qui reçoivent
alors des variables vides sans erreur Twig (silencieux, donc dangereux — se
vérifie uniquement en rendant réellement la page). Chaque `{% include %}`
imbriqué dans un slot garde son propre `only` : c'est lui qui isole le
composant terminal, pas l'embed englobant.

## 4. Composants de départ (props typées en commentaire + slots via `{% block %}`)
- `button` : variants primary/secondary/ghost, sizes sm/md/lg, prop `url`
  (rend `<a>` ou `<button>`), prop `disabled`
- `card` : slots image/title/content/footer, variant horizontal
- `heading` : prop `level` (1-6, sémantique) découplée de prop `size` (visuel)
- `badge`, `tag`, `icon` (sprite SVG dans `assets/images/sprite.svg`)
- `hero` (organism) : title, subtitle, media, cta

## 5. Pattern de variants dans le Twig
Mapping via un objet Twig, jamais de concaténation dynamique de classes (la
détection Tailwind ne verrait pas `bg-{{ color }}`) :

```twig
{% set variants = {
  primary: 'bg-primary text-white hover:bg-primary-hover',
  secondary: 'bg-transparent border border-primary text-primary',
} %}
<button class="inline-flex items-center rounded-md {{ variants[variant|default('primary')] }} {{ sizes[size|default('md')] }}">
```

## 6. Intégration Timber / WordPress
- consommation via `{% include 'components/01-atoms/button.twig' with {...} only %}`
- la hiérarchie de templates existante (`theme.md`) reste la même :
  `views/templates/single-{post_type}.twig`, `page-{slug}.twig`, etc.
- contenu WYSIWYG (`post.content` ou champ ACF `wysiwyg`) : classe `prose`
  (`@tailwindcss/typography`, déjà en place dans `assets/styles/app.css`) —
  seul endroit où `@apply` est toléré si besoin d'un ajustement (dans
  `assets/styles/app.css`, jamais dans un composant)
- Preflight : Tailwind v4 l'active par défaut via `@import "tailwindcss"` ;
  documente dans le README d'architecture (§ Livrables) la stratégie de
  restylage des balises injectées par les plugins WordPress (shortcodes,
  widgets tiers) qui n'ont pas de composant dédié

## 7. Page builder avec ACF Flexible Content (multilingue)
Plugins via Composer : `advanced-custom-fields-pro` (ou `secure-custom-fields`
si budget contraint — flexible content y est disponible depuis son passage
en gratuit ; c'est d'ailleurs la seule option installable via Composer sans
clé de licence, ACF Pro n'étant jamais distribué sur le SVN wordpress.org),
`polylang` (`polylang-pro` si synchronisation de champs avancée nécessaire).
Nom de paquet réel : **`wp-plugin/<slug>`**, pas `wpackagist-plugin/<slug>` —
depuis Bedrock 1.30, [WP Packages](https://roots.io/wp-composer-is-now-wp-packages/)
(`repo.wp-packages.org`, cf. `composer.json`) est la source de paquets
**officielle** qui remplace WPackagist, pas une convention propre à ce
dépôt (confirmé par `composer show wp-plugin/polylang --all`).

### a. Architecture de traduction — SYMÉTRIQUE, non négociable
- chaque traduction Polylang d'un post est un post WordPress distinct (lié
  via `pll_get_post_translations`) : le champ ACF Flexible Content
  (`field_sections`) n'est donc jamais "partagé" entre langues comme
  `field_sections` l'était dans Drupal — la symétrie doit être **imposée par
  le process éditorial**, pas par le storage
- workflow — **vérifié réellement, cf. `WORDPRESS-PROCESS.md` §7** : cliquer
  sur le `+` en face du drapeau de la langue cible dans le panneau
  *Languages* de l'éditeur crée la traduction avec le champ `sections`
  **déjà rempli à l'identique** (comportement Polylang gratuit par défaut,
  aucun réglage de synchronisation par champ requis, aucune option "copier
  le contenu" à cocher) ; ne traduire ensuite QUE les sous-champs
  texte/média de chaque layout — jamais recomposer la liste de layouts à la
  main côté EN, rien ne l'empêche techniquement, c'est une discipline
  éditoriale
- cette copie n'a lieu qu'**à la création** de la traduction : au-delà, les
  deux langues sont des posts indépendants (postmeta séparés) — modifier la
  structure `sections` du FR après coup ne répercute rien côté EN, et
  inversement ; si un vrai besoin de synchronisation permanente par champ
  apparaît (pas juste une copie de départ), c'est une fonctionnalité
  Polylang Pro
- documente pourquoi : en asymétrique, chaque langue dérive sa propre liste
  de layouts → duplication de travail, layouts orphelins d'un côté,
  désynchronisation éditoriale. On ne le fait pas.
- test : dupliquer un post FR→EN doit proposer la traduction de chaque
  layout existant, jamais une structure vide à reconstruire

### b. Layouts ACF Flexible Content (chacun mappé sur un composant Twig)
- `hero` → organism hero
- `text_media` (body, media, position gauche/droite) → card horizontal
- `cards_grid` (sous-champ répéteur ou flexible content imbriqué de cards)
- `cta_banner`, `accordion`, `embed`
- un sous-champ `variant` (select, choices = mêmes valeurs que le
  commentaire de props du composant Twig correspondant, §3) sur les layouts
  qui ont des variants ; la source de vérité reste le composant Twig —
  mentionne dans le README l'astuce pour garder les deux en phase (générer
  les `choices` du select ACF depuis un fichier JSON partagé si le nombre de
  variants dérive)

### c. Templates Twig = mapping pur
Le template consommateur (ex. `page.twig`) boucle sur `get_field('sections')`
et fait UNIQUEMENT le mapping layout → composant via `{% include %}` :

```twig
{% for section in fields.sections %}
  {% if section.acf_fc_layout == 'hero' %}
    {% include 'components/03-organisms/hero.twig' with section only %}
  {% elseif section.acf_fc_layout == 'cta_banner' %}
    {% include 'components/03-organisms/cta-banner.twig' with section only %}
  {% endif %}
{% endfor %}
```

Zéro balise HTML de structure dans ce mapping. Si du HTML apparaît là, c'est
que le composant Twig est incomplet — corrige le composant.

### d. Layout / grille de section
Un layout ACF `section` avec un sous-champ `columns` (1/2/3, select) et un
flexible content imbriqué pour son contenu ; les grilles en classes
Tailwind (`grid grid-cols-{{ columns }}`, via l'objet de mapping du §5, pas
de concaténation dynamique). Les autres layouts ne sont insérables QUE dans
un layout `section` (champ `parent_layout` dans la config ACF Flexible
Content, ou en limitant les layouts disponibles au niveau du groupe de
champs imbriqué).

### e. Garde-fous éditoriaux
- min/max par layout : ACF Flexible Content permet nativement de fixer un
  min/max **par layout** (ex. `hero` : min 0, max 1) — pas besoin de
  validateur custom comme côté Drupal
- libellés et instructions des champs en français
- icônes/aperçus des layouts configurés (`Field Group > Flexible Content >
  Layout > icône`)

## 8. Multilingue — config globale (Polylang)
- langues FR (défaut) + EN, mode "dossier" (`/fr/`, `/en/`) plutôt que
  sous-domaines
- détection : URL uniquement — désactiver *"Détecter la langue du
  navigateur"* dans les réglages Polylang
- Polylang activé sur `post`, `page`, `attachment` (médias — pour que l'alt
  text soit traduisible) et sur les post types custom éventuels
- chaînes codées en dur dans les Twig des composants (ex. « Lire la
  suite ») : `__('Lire la suite', 'tailwind')` exposé au Twig via un
  `Timber::$twig_functions` ou une fonction Twig custom, ou enregistrées
  via *Polylang > Traduction des chaînes* pour les chaînes hors PHP/Twig
  (réglages de thème, options ACF globales)
- permaliens : pretty permalinks obligatoires (prérequis Polylang), alias
  et métadonnées SEO par langue si un plugin SEO (Yoast, Rank Math) est
  présent

## 9. Qualité / CI
- Prettier + `prettier-plugin-tailwindcss` (ordre des classes)
- ESLint sur `assets/scripts/**/*.js`
- `composer lint` (Pint) et `composer test` (Pest) déjà scriptés dans
  `composer.json` — les intégrer à la CI plutôt que dupliquer l'outillage
- job GitHub Actions : `composer install`, `npm ci` (dans le thème),
  `composer lint`, `composer test`, script de validation des en-têtes de
  props des composants (§3) et de la présence de leur `README.md`,
  `npm run build`, échec si le CSS généré dépasse 50 Ko gzippé
- test de non-régression : un post avec tous les layouts, traduit FR→EN via
  le workflow du §7a, rend le même DOM structurel dans les deux langues

# Contraintes
- Bedrock, WordPress via `roots/wordpress`, PHP ≥ 8.4 (cf. `composer.json`),
  Tailwind v4 (config CSS-first, pas de `tailwind.config.js` legacy), pas de
  jQuery dans le thème custom
- `@apply` interdit hors du bloc `prose` de `assets/styles/app.css`
- Accessibilité RGAA : focus-visible systématique, contrastes vérifiés sur
  les tokens, ARIA justifié
- Aucune logique de rendu dans `functions.php`/`src/Site.php` (mapping en
  Twig ; PHP uniquement pour transformation de données réelle — ex. dans
  `add_to_context()`)
- Groupes de champs ACF versionnés dans git — **en PHP**
  (`acf_add_local_field_group()` sur le hook `acf/init`, ex.
  `inc/acf-fields.php`), pas via l'UI + export Local JSON (`acf-json/`) :
  construire des layouts imbriqués à la souris (page builder complet =
  dizaines de champs) est lent et fragile à refaire à chaque évolution,
  alors que le PHP reste aussi versionné, plus rapide à écrire et plus
  facile à revoir en diff. Champ `url`/`page_link` à éviter pour un CTA
  interne à URL libre : `url` rejette tout chemin relatif (validation
  stricte), `page_link` impose de choisir une page existante — préférer
  `text` avec une instruction explicite. Réglages Polylang (langues,
  réglages de synchronisation par champ) documentés dans le README
  d'architecture à défaut d'un export natif fiable

# Livrables
Procède étape par étape : pipeline (vérifier l'existant, étendre si besoin),
tokens, composants un par un avec exemple d'usage réel, puis layouts ACF et
config multilingue. Termine par un README d'architecture couvrant :
- quand créer un composant Twig vs un template dans `views/templates/`
- la stratégie de tokens inter-projets
- Polylang vs WPML : on reste sur Polylang tant que la synchronisation de
  champs gratuite suffit ; critère de bascule vers WPML/Polylang Pro =
  besoin réel de traduction automatique assistée ou de workflow de
  validation multi-rôles
