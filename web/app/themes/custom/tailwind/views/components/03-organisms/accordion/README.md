# accordion

Wrapper `divide-y` d'un ensemble de [`accordion-item`](../../02-molecules/accordion-item/README.md).

## Props

| Prop    | Type                          | Défaut | Description                  |
| ------- | ----------------------------- | ------ | ---------------------------- |
| `items` | array de `{question, answer}` | requis | Un item par question/réponse |

## Usage

```twig
{% include 'components/03-organisms/accordion/accordion.twig' with {
    items: [
        { question: 'Comment ça marche ?', answer: '<p>Une réponse.</p>' },
        { question: 'Autre question ?', answer: '<p>Une autre réponse.</p>' },
    ],
} only %}
```

Mappage ACF réel : le layout `accordion` (champ répéteur `items`, sous-champs
`question`/`answer`) — voir `views/templates/page.twig`.
