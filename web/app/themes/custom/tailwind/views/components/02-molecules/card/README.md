# card

Slots image/title/content/footer, variant vertical (défaut) ou horizontal.

Les slots utilisent le mécanisme natif de blocs Twig (`{% block %}`) — pas
d'interpolation de variable (`{{ nom_du_slot }}`), qui ne permettrait pas de
passer du HTML/d'autres composants dans un slot. Un composant à slots
s'utilise donc toujours avec `{% embed %}`, jamais `{% include %}` (Twig ne
permet les blocs qu'à travers `embed`).

**Important : ne pas mettre `only` sur le tag `{% embed %}` lui-même.**
Contrairement à un `{% include ... only %}` (qui isole un composant sans
slots, cf. `button`/`icon`/`heading`), le contenu des `{% block %}` d'un
embed s'exécute dans la portée de la page appelante — c'est précisément ce
qui permet d'y utiliser `post`, `site`, etc. `only` sur l'embed coupe cet
accès pour l'ensemble du bloc, y compris les `{% include %}` imbriqués à
l'intérieur des slots (confirmé en le cassant réellement : un `icon` inclus
dans le slot `image` ne recevait plus `site.theme.link`, silencieusement
vide, sans erreur Twig). Chaque `{% include %}` imbriqué dans un slot garde
en revanche son propre `only` — c'est lui, pas l'embed englobant, qui isole
le composant terminal.

## Props

| Prop      | Type   | Valeurs                 | Défaut     | Description                                              |
| --------- | ------ | ----------------------- | ---------- | -------------------------------------------------------- |
| `variant` | string | `vertical`,`horizontal` | `vertical` | Disposition                                              |
| `reverse` | bool   | —                       | `false`    | Horizontal uniquement : image à droite au lieu de gauche |

## Slots

`image`, `title`, `content`, `footer` — tous optionnels.

## Usage

```twig
{% embed 'components/02-molecules/card/card.twig' with { variant: 'horizontal' } %}
    {% block image %}
        <img src="{{ post.thumbnail.src }}" alt="{{ post.thumbnail.alt }}" class="h-full w-full object-cover">
    {% endblock %}
    {% block title %}
        {% include 'components/01-atoms/heading/heading.twig' with { text: post.title, level: 3, size: 'md' } only %}
    {% endblock %}
    {% block content %}
        <p class="text-text-muted">{{ post.preview.length(20) }}</p>
    {% endblock %}
    {% block footer %}
        {% include 'components/01-atoms/button/button.twig' with { label: 'Lire la suite', variant: 'ghost', size: 'sm', url: post.link } only %}
    {% endblock %}
{% endembed %}
```
