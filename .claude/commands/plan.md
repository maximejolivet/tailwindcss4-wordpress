---
description: Restate requirements, check existing conventions, and produce a phased implementation plan for this WordPress/Bedrock theme. Waits for confirmation before touching any code.
---

# Plan

Invokes the **planner** agent (`.claude/agents/planner.md`) to produce an implementation plan before writing code.

## When to use

- A new feature, ACF page-builder layout, or Twig component spanning several files
- Anything that would touch the deploy pipeline (`.github/workflows/deploy.yml`) or GitHub secrets
- Requirements that are still fuzzy and worth pinning down before implementation starts

Skip it for a genuinely small, single-file change — the ceremony isn't worth it there.

## What happens

1. Requirements are restated in concrete terms (including which languages/Polylang locales are affected, if relevant).
2. Existing patterns are checked first (`.claude/THEME.md`, `.claude/WORDPRESS.md`, similar existing components/layouts) rather than inventing a new one.
3. Work is broken into phases (data/ACF → PHP → Twig → wiring → i18n → verification).
4. Risks specific to this repo are called out explicitly — Composer package naming, `web/wp/`/`vendor/` being off-limits, no automated test suite, and anything that would trigger a production deploy.
5. The plan is presented and **execution waits for your explicit confirmation** ("yes"/"proceed", or "modify: ...").

## After the plan is confirmed

- Implement following `coding-style.md`.
- Run the **code-reviewer** agent once code is written, and **security-reviewer** if it touches forms/`$wpdb`/auth/REST.
- Verify manually (`make lint`, `make phpstan`, browser check via `make vite-dev`) — there's no test suite to run instead.
