# badge

Indicateur statique (non interactif), pour un statut ou une étiquette de
classification.

## Props

| Prop      | Type   | Valeurs                                       | Défaut    | Description   |
| --------- | ------ | --------------------------------------------- | --------- | ------------- |
| `label`   | string | —                                             | requis    | Texte affiché |
| `variant` | string | `neutral`,`success`,`warning`,`danger`,`info` | `neutral` | Couleur       |

## Usage

```twig
{% include 'components/01-atoms/badge/badge.twig' with {
    label: 'Brouillon',
    variant: 'warning',
} only %}
```
