# Coding Style

Enforced tooling does the formatting/type-safety work — see root `CLAUDE.md` § Linting. This file covers what the tools *don't* catch.

## Style & static analysis are non-negotiable, not a suggestion

- `make lint-fix` (Pint, `per` preset) before considering PHP work done — don't hand-format to match Pint's style, just run it.
- `make phpstan` (level 5, WordPress/ACF-aware via stubs) must stay clean on anything touching `config/`, the custom theme, `web/index.php`, `web/wp-config.php`.
- Both are also wired as a `PostToolUse` hook that auto-runs Pint on every PHP file you edit — if it reformats something, that's expected, not an error.

## File organization

- Bedrock's top-level layout (`config/`, `web/wp` for core, `web/app` for themes/plugins/uploads) is fixed — never restructure it.
- `web/wp/` is WordPress core and `vendor/` is Composer-managed — never hand-edited.
- Project PHP logic lives in `web/app/themes/custom/tailwind/inc/`; Twig templates/components in `views/`.
- Twig components: one concern per component, atoms/molecules/organisms under `views/components/`, always paired with a `README.md` (props table + usage example) — see the `new-component` skill for the exact convention.

## Twig/Tailwind specifics

- Variant/size logic through a Twig `{% set %}` map (`{variant: 'class-a', ...}`) — never a string-concatenated class (`bg-{{ color }}`), because Tailwind's content scanner can't see dynamically built class names.
- Leaf `{% include %}` calls use `with {...} only`; components needing markup slots use `{% block %}`/`{% embed %}`, and the `{% embed %}` tag itself must never carry `only` (see `.claude/WORDPRESS.md` for why).

## Error handling

- Wrap external calls (`wp_remote_get()`/`_post()`, filesystem access, `$wpdb`) with a real check on the result — WordPress functions typically return `WP_Error` or `false` rather than throwing; check for that, don't assume success.
- No `@`-suppression of errors.

## Before marking work complete

- [ ] `make lint` clean
- [ ] `make phpstan` clean
- [ ] No hardcoded secrets or dynamic Tailwind class names
- [ ] User-facing strings are translatable (Polylang) where relevant
- [ ] Manually checked in the browser (`make vite-dev` for HMR) — there is no automated test suite in this repo, so this step isn't optional
