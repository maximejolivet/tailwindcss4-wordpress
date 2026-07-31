# icon

Icône en sprite SVG (`assets/images/sprite.svg`, servi tel quel par
WordPress — pas de passage par Vite, ce n'est pas du JS/CSS à bundler).
Symboles disponibles actuellement : `arrow-right`, `close`, `check`.

## Props

| Prop         | Type   | Défaut                | Description                                          |
| ------------ | ------ | --------------------- | ---------------------------------------------------- |
| `name`       | string | requis                | id du `<symbol>` dans `sprite.svg`                   |
| `sprite_url` | string | requis                | URL absolue vers `sprite.svg` (voir note)            |
| `size`       | string | `md` (`sm`,`md`,`lg`) | Taille (`size-4/5/6`)                                |
| `class`      | string | —                     | Classes supplémentaires (ex. couleur `text-primary`) |

## Usage

```twig
{% include 'components/01-atoms/icon/icon.twig' with {
    name: 'arrow-right',
    sprite_url: site.theme.link ~ '/assets/images/sprite.svg',
    size: 'sm',
} only %}
```

## Note

Le composant ne lit jamais `site` lui-même (règle du §3 de `WORDPRESS.md` :
rendable isolément, sans dépendance implicite au contexte de page) — c'est
au template appelant de résoudre `sprite_url` depuis `site.theme.link`
(propriété exposée par `Timber\Theme`) et de le passer explicitement.
