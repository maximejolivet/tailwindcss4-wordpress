# tag

Chip, optionnellement supprimable (le retrait du DOM est géré par un
listener délégué en vanilla JS dans `assets/scripts/app.js`, pas de
dépendance JS par composant).

## Props

| Prop        | Type   | Défaut  | Description                          |
| ----------- | ------ | ------- | ------------------------------------ |
| `label`     | string | requis  | Texte du tag                         |
| `removable` | bool   | `false` | Affiche un bouton de suppression `×` |

## Usage

```twig
{% include 'components/01-atoms/tag/tag.twig' with {
    label: 'Actualités',
    removable: true,
} only %}
```

## Note

Le libellé `aria-label="Retirer {{ label }}"` est codé en dur en français
pour l'instant (aucune infrastructure de traduction n'est encore en place —
voir §8 de `WORDPRESS.md` et le backlog de `WORDPRESS-PROCESS.md`). Une fois
Polylang branché, remplacer par la fonction de traduction exposée au Twig.
