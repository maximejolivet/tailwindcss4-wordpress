# embed

Contenu tiers (iframe vidéo, embed réseau social...) fourni tel quel par un
éditeur — pas de sanitization ici, le contenu vient du champ ACF `embed.code`
saisi côté admin (confiance équivalente à `post.content`), jamais d'une
entrée utilisateur front.

## Props

| Prop | Type | Défaut | Description |
|---|---|---|---|
| `code` | string | requis | HTML brut (iframe, script d'embed...) |

## Usage

```twig
{% include 'components/03-organisms/embed/embed.twig' with {
    code: '<iframe src="https://www.youtube.com/embed/xxxx" allowfullscreen></iframe>',
} only %}
```

Mappage ACF réel : le layout `embed` (champ textarea `code`) — voir
`views/templates/page.twig`.
