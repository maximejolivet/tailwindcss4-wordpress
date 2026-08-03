---
description: Sync README.md/CLAUDE.md/.claude/*.md with the actual Makefile, composer.json, and repo state — mirroring every change into both the French and English sections.
---

# Update Docs

Invokes the **doc-updater** agent (`.claude/agents/doc-updater.md`). Every top-level doc in this repo (`README.md`, `CLAUDE.md`, `.claude/DOCKER.md`) is bilingual — French section, then an English mirror of the same content. **Any edit here must land in both sections.**

## Steps

1. Read the `Makefile` — regenerate the Commands tables in `README.md` (both FR and EN) so every target has a matching row, and no row exists for a removed target. Cross-check against `make help` output.
2. Read `composer.json` `require` — confirm the "Plugins & thèmes via Composer" / "Plugins & themes via Composer" lists in `README.md` match, using `wp-plugin/<slug>`/`wp-theme/<slug>` naming only.
3. Read `.env.example` and `docker/docker-compose.yml` — confirm `.claude/DOCKER.md`'s Configuration section still matches.
4. Grep the theme (`web/app/themes/custom/tailwind/`) for drift against `.claude/THEME.md`/`.claude/WORDPRESS.md` claims (components, ACF conventions).
5. If a new doc, agent, rule, or command was added, add it to the **Documentation** list in `CLAUDE.md` (both language sections) so it's discoverable.
6. Show a diff summary and confirm explicitly that both language halves were updated together.

Don't invent new doc locations (e.g. a `docs/` folder) — this repo keeps documentation consolidated in the files already listed under `CLAUDE.md`'s Documentation section.
