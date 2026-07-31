# accordion-item

Une question/réponse en `<details>/<summary>` natif — pas de JavaScript,
fonctionne même si le bundle JS du thème ne charge pas.

## Props

| Prop | Type | Défaut | Description |
|---|---|---|---|
| `question` | string | requis | Texte du `<summary>` |
| `answer` | string | requis | HTML de la réponse (ex. sortie d'un champ wysiwyg ACF) |

## Usage

```twig
{% include 'components/02-molecules/accordion-item/accordion-item.twig' with {
    question: 'Comment ça marche ?',
    answer: '<p>Une réponse.</p>',
} only %}
```

Utilisé par [`components/03-organisms/accordion/accordion.twig`](../../03-organisms/accordion/accordion.twig).
