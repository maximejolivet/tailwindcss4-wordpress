# heading

Titre dont le niveau sémantique (`<h1>`-`<h6>`, hiérarchie de la page) est
découplé de sa taille visuelle (design), pour ne jamais sacrifier
l'accessibilité à la mise en page (ex. un `<h2>` visuellement petit dans une
sidebar, ou un `<h1>` de taille `md` sur une page secondaire).

## Props

| Prop | Type | Valeurs | Défaut | Description |
|---|---|---|---|---|
| `text` | string | — | requis | Contenu du titre |
| `level` | int | `1`-`6` | `2` | Niveau HTML sémantique |
| `size` | string | `xs`,`sm`,`md`,`lg`,`xl`,`2xl` | dérivé de `level` | Taille visuelle |

## Usage

```twig
{% include 'components/01-atoms/heading/heading.twig' with {
    text: post.title,
    level: 1,
    size: '2xl',
} only %}
```

Un `<h1>` de taille `md` (ex. dans une carte) :

```twig
{% include 'components/01-atoms/heading/heading.twig' with {
    text: 'Titre de carte',
    level: 3,
    size: 'md',
} only %}
```
