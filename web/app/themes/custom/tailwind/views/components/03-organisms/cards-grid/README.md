# cards-grid

Grille de [`card`](../../02-molecules/card/README.md) en variant vertical,
2/3/4 colonnes (classes Tailwind fixes via un objet de mapping — jamais de
concaténation dynamique `grid-cols-{{ columns }}`, invisible au scanner
Tailwind).

## Props

| Prop      | Type                                                | Défaut            | Description                                           |
| --------- | --------------------------------------------------- | ----------------- | ----------------------------------------------------- |
| `columns` | int                                                 | `3` (`2`,`3`,`4`) | Nombre de colonnes en desktop                         |
| `cards`   | array de `{title, content, image: {src, alt}, url}` | requis            | Une entrée par carte, tous les sous-champs optionnels |

## Usage

```twig
{% include 'components/03-organisms/cards-grid/cards-grid.twig' with {
    columns: 3,
    cards: [
        { title: 'Carte 1', content: 'Texte de la carte.', url: '/carte-1' },
        { title: 'Carte 2', content: 'Texte de la carte.', url: '/carte-2' },
    ],
} only %}
```

Mappage ACF réel : le layout `cards_grid` (`columns` select + répéteur
`cards`) — voir `views/templates/page.twig`.
