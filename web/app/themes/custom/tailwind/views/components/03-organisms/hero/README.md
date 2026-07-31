# hero

Bannière de page : titre, sous-titre, média optionnel, CTA optionnel.
Compose en interne `heading` et `button` plutôt que de dupliquer leur
balisage — si un jour `heading`/`button` change de rendu, `hero` en
bénéficie automatiquement.

Sans `media`, le contenu est centré pleine largeur (usage type page
d'accueil). Avec `media`, bascule en grille 2 colonnes (texte + image).

## Props

| Prop | Type | Défaut | Description |
|---|---|---|---|
| `title` | string | requis | Titre principal (`<h1>`) |
| `subtitle` | string | — | Texte secondaire |
| `media` | objet `{src, alt}` | — | Image affichée à droite ; change la mise en page |
| `cta` | objet `{label, url}` | — | Bouton d'action principal |

## Usage

Sans média (centré) :

```twig
{% include 'components/03-organisms/hero/hero.twig' with {
    title: 'Bienvenue',
    subtitle: 'Une phrase d\'accroche.',
    cta: { label: 'En savoir plus', url: '/decouvrir' },
} only %}
```

Avec média (2 colonnes) :

```twig
{% include 'components/03-organisms/hero/hero.twig' with {
    title: post.title,
    subtitle: post.preview.length(30),
    media: { src: post.thumbnail.src, alt: post.thumbnail.alt },
    cta: { label: 'Lire l\'article', url: post.link },
} only %}
```
