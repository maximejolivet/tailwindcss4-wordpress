# button

Bouton ou lien stylé de façon identique, atome de base pour toute action
(CTA, formulaire, navigation secondaire).

## Props

| Prop | Type | Valeurs | Défaut | Description |
|---|---|---|---|---|
| `label` | string | — | requis | Texte du bouton |
| `variant` | string | `primary`, `secondary`, `ghost` | `primary` | Style visuel |
| `size` | string | `sm`, `md`, `lg` | `md` | Taille |
| `url` | string | — | — | Si renseigné, rend un `<a>` au lieu d'un `<button>` |
| `disabled` | bool | — | `false` | Ignoré si `url` est renseigné |

## Usage

```twig
{% include 'components/01-atoms/button/button.twig' with {
    label: 'Découvrir'|trans,
    variant: 'primary',
    size: 'lg',
    url: '/decouvrir',
} only %}
```

Utilisé en exemple réel dans
[`views/templates/front-page.twig`](../../../templates/front-page.twig).
