---
name: new-component
description: Scaffold a new Twig component (atom/molecule/organism) in web/app/themes/custom/tailwind/views/components/, following this repo's established convention. Use when the user asks to create/add a new reusable Twig component, atom, molecule, or organism for the theme.
---

Scaffold a new component in `web/app/themes/custom/tailwind/views/components/`.

Arguments: `$ARGUMENTS` — the component name (kebab-case, e.g. `pricing-table`) and optionally its type (`atom`, `molecule`, or `organism`). If the type isn't given, infer it from what the user describes (atom = single element like a button/badge; molecule = a small composition like a card; organism = a page-level section like a hero) and confirm your choice in your reply.

## Steps

1. Pick the target folder: `views/components/01-atoms/<name>/`, `02-molecules/<name>/`, or `03-organisms/<name>/`.
2. Look at 2-3 existing components of the same type (e.g. `01-atoms/button/`, `02-molecules/card/`, `03-organisms/hero/`) to match the established style before writing the new one — variant/size mapping objects, prop comment format, etc.
3. Create `<name>.twig`:
   - Start with a `{# Props: ... #}` comment block documenting every prop, its type, allowed values, and default — Twig has no schema validation, this comment is the only documentation.
   - No slots → plain Twig, meant to be consumed via `{% include '...' with {...} only %}`. Never reference `post`, `site`, or other ambient globals directly.
   - Slots needed → use `{% block name %}{% endblock %}` (never `{{ name }}` interpolation, which can't carry markup or nested components), meant to be consumed via `{% embed %}`. Document in the README that callers must **never** add `only` to the `{% embed %}` tag itself (it silently cuts off `post`/`site` access inside the slot blocks — see @.claude/rules/wordpress.md) — leaf `{% include %}` calls nested inside a slot keep their own `only`.
   - Variant/size logic: map via a Twig `{% set %}` object (`{variant: 'class-a', ...}`), never concatenate a class name dynamically (`bg-{{ color }}`) — Tailwind's scanner can't see it.
4. Create `README.md` next to it: one-line description, a props table, and a **real** usage example (`{% include %}` or `{% embed %}` snippet) — copy the format from an existing component's README.
5. If the component composes other components internally (e.g. an organism using `heading` + `button`), include them the same way a template would.
6. Report back which file(s) you created and remind the user to actually wire it into a template (or ACF layout mapping in `page.twig`) and check the render — this skill only scaffolds, it doesn't hook the component into any page.
