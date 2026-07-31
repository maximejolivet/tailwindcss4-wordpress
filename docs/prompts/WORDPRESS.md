# Contexte
Tu interviens sur ce projet WordPress géré avec [Bedrock](https://roots.io/bedrock/)
(racine du repo = racine Bedrock : `composer.json`, `config/`, `web/wp` pour le
cœur WordPress, `web/app` pour thèmes/plugins/uploads). Le thème custom
`web/app/themes/custom/tailwind` existe déjà : Timber v2 (templates Twig dans
`views/`), Tailwind CSS v4 compilé par Vite avec HMR déjà fonctionnel (voir
[`docs/theme.md`](../theme.md) pour le détail du pipeline). Objectif :
industrialiser le front avec des composants Twig réutilisables, consommés par
un page builder éditorial ACF Flexible Content, sur un site multilingue FR/EN
via Polylang. Finalité agence : réduire la duplication Twig, fiabiliser les
livraisons multi-projets Bedrock+Timber, les tokens Tailwind (`@theme`) étant
le seul point de personnalisation entre projets basés sur ce même socle.

# Mission

## 1. Pipeline de build (déjà en place, à étendre)
Le pipeline Vite + Tailwind v4 existe déjà et est documenté dans
`docs/theme.md` : entrée unique `assets/styles/app.css`
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
- la hiérarchie de templates existante (`docs/theme.md`) reste la même :
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
Nom de paquet réel sur le mirroir Composer de ce dépôt
(`repo.wp-packages.org`, cf. `composer.json`) : **`wp-plugin/<slug>`**, pas
`wpackagist-plugin/<slug>` (confirmé par `composer show wp-plugin/polylang
--all` — `docs/README.md` mentionne `wpackagist-plugin/<slug>` par erreur).

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
