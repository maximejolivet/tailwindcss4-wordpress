# Performance

## Model selection

- **Haiku**-class: mechanical, high-volume tasks — doc sync (`doc-updater`), simple single-file edits.
- **Sonnet**-class: the default for actual development work — implementing features, `code-reviewer`, `refactor-cleaner`.
- **Opus**-class: decisions with real cost if wrong — `planner`, `architect`, `security-reviewer`.

This is reflected in the `model:` field already set on each agent in `.claude/agents/` — don't override it without a reason.

## WordPress/PHP performance, not React performance

The usual "avoid re-renders" advice doesn't apply here. What does:

- **N+1 queries** — `get_field()` or nested `WP_Query` calls inside a loop; batch instead, or restructure the query to fetch what's needed upfront.
- **WP object cache / transients** — for genuinely expensive repeated queries, not by default; don't cache-wrap everything speculatively.
- **Twig depth** — avoid deeply nested `{% embed %}` chains repeated per row in a large loop; flatten with `{% include %}` where slots aren't actually needed.
- **Images** — `wp_get_attachment_image()` (responsive `srcset`) rather than a raw `<img src>` pointing at the full-size file.
- **Vite** — iterate against `make vite-dev` (HMR) rather than rebuilding with `make vite-build` on every change; only build for production or to sanity-check the final bundle.

## Build troubleshooting

If `make vite-build`/`make vite-dev` fails or PHP throws a fatal, use `/build-fix` (or the equivalent manual loop: read the error, fix the smallest thing that addresses it, re-run, repeat) rather than large speculative changes.

## Context window

For large multi-file work (new ACF layout family + Twig components + wiring), start with `/plan` so the actual implementation isn't competing with plan-drafting for context. Simple single-file edits and doc updates don't need that ceremony.
