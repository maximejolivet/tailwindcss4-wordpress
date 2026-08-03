---
description: Find and safely remove dead code (orphaned Twig components, unreferenced ACF layouts, unused dependencies) with grep-verified references and lint/phpstan/build checks in place of a test suite.
---

# Refactor Clean

Invokes the **refactor-cleaner** agent (`.claude/agents/refactor-cleaner.md`). There is no automated test suite in this repo, so safety here comes from exhaustive grep-based reference checking plus `make lint`/`make phpstan`/`make vite-build`, not from a test run.

## Steps

1. Detect candidates:
   - Twig components under `views/components/` with zero `{% include %}`/`{% embed %}` references anywhere in the theme
   - ACF Flexible Content layouts (`inc/acf-fields.php`) not matched by any `layout ==` branch in the Twig templates
   - Composer plugins listed in `composer.json` but inactive (`make wp ARGS="plugin list"`)
   - npm dependencies in the theme's `package.json` with no matching import
2. Categorize:
   - **SAFE** — confirmed zero references across the whole theme
   - **CAUTION** — an ACF layout or component that might still be used by *published page content* in the database, not just in code; check `make wp ARGS="post list --post_type=page"` before touching, and ask if unsure
   - **DANGER** — anything under `web/wp/`, `vendor/`, `config/`, or the theme bootstrap — never touched by this command
3. Propose SAFE deletions only, listed with the grep evidence for each.
4. For each approved deletion: apply it, then `make lint` && `make phpstan` && `make vite-build` — all three must stay clean. Roll back (`git checkout -- <file>`) on any failure rather than patching forward.
5. If anything ACF/page-builder related was touched, ask the user to spot-check the affected page(s) in the browser — this command can't verify rendered output itself.
6. Report a summary: removed / flagged CAUTION (left alone) / lint-phpstan-build status.

Never delete without running the checks first, and never delete a CAUTION item without explicit confirmation.
