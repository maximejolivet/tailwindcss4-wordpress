---
description: Incrementally fix Vite/Tailwind build errors or PHP fatals/PHPStan errors, one at a time, re-verifying after each fix.
---

# Build Fix

This project has two build surfaces — fix whichever is failing, one error at a time, minimal diffs:

## Frontend (Vite/Tailwind theme)

1. Run `make vite-build` (or `make npm ARGS="run build"`).
2. Group errors by file; fix the first one.
3. Show 5 lines of context around the error, explain it, apply the smallest fix that resolves it.
4. Re-run the build; confirm that specific error is gone before moving to the next.

## Backend (PHP fatals / static analysis)

1. Run `make phpstan` for static errors; for a runtime fatal, check the page with `WP_DEBUG_DISPLAY` on (already enabled automatically in `WP_ENV='development'`, see root `README.md`) or `make logs`.
2. Fix one reported error at a time — respect existing WordPress/ACF stub types rather than widening to `mixed` to make PHPStan quiet.
3. Re-run `make phpstan` after each fix.

## Stop conditions

- A fix introduces a *new* error — back it out and reconsider.
- The same error persists after 3 attempts — stop and report what's been tried instead of guessing further.
- User asks to pause.

## Summary format

- Errors fixed (with the one-line cause of each)
- Errors remaining, if any
- Whether `make lint` was affected by the fixes (re-run it if PHP files changed)
