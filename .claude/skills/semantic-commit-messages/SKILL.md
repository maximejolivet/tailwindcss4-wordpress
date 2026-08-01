---
name: semantic-commit-messages
description: "Write git commit messages in the semantic/conventional format (`<type>(<scope>): <subject>`) instead of a free-form sentence. Use whenever the user asks for a commit, or asks to write/review a commit message, and wants it typed (feat/fix/docs/style/refactor/test/chore) rather than descriptive prose."
---

Prefix every commit subject with a **type**, and optionally a **scope**, before the actual summary — a small style change that makes `git log` scannable (what kind of change, at a glance) and lets tooling (changelogs, semantic-release) parse history mechanically instead of guessing from prose.

## Format

```
<type>(<scope>): <subject>

[optional body]

[optional footer]
```

- `<scope>` is optional — the area of the codebase touched (`deploy`, `theme`, `docker`...), omit it when the change is repo-wide or the scope is obvious from context.
- `<subject>` — a short summary in the **present tense** ("add", not "added"/"adds"), no trailing period.
- `<body>` — optional, for the *why* that doesn't fit on the subject line (motivation, contrast with previous behavior). Separate from the subject with a blank line.
- `<footer>` — optional, for metadata: breaking-change notices (see below), issue references (`Refs #123`), co-author trailers.

```
feat: add hat wobble
^--^  ^------------^
|     |
|     +-> Summary, present tense.
|
+-------> Type: chore, docs, feat, fix, refactor, style, or test.
```

## Breaking changes

Two equivalent ways to flag a change that breaks backward compatibility, both part of the spec:

- `!` right after the type/scope: `feat(api)!: drop support for the v1 auth header`
- A `BREAKING CHANGE:` footer, when more explanation is needed:
  ```
  feat(api): change response shape for /users

  BREAKING CHANGE: `id` is now a string (UUID) instead of an integer.
  ```

This is the signal semantic-versioning tooling looks for to bump a **major** version — reach for it deliberately, not on every `fix`/`refactor`.

## Types

| Type | When to use it |
|---|---|
| `feat` | A new capability for the *user* of the software — not a new feature for the build/tooling setup |
| `fix` | A bug fix that changes behavior for the *user* — not a fix to a build script |
| `docs` | Documentation only, no code change |
| `style` | Formatting, whitespace, missing semicolons — no production code change |
| `refactor` | Restructuring production code (e.g. renaming a variable) with no behavior change |
| `test` | Adding or refactoring tests, no production code change |
| `chore` | Tooling/build/task-runner updates, no production code change |

The `feat`/`fix` vs `docs`/`style`/`refactor`/`test`/`chore` split is the one that matters most: the first two describe something that changes what the software *does*; the rest describe changes to how it's built, tested, or documented.

## Tooling — enforced in this repo

`commitlint` + a Husky `commit-msg` hook are installed at the repo root and **reject a non-compliant commit message locally**, before it lands:

- `package.json` (root) — devDependencies `@commitlint/cli`, `@commitlint/config-conventional`, `husky`; `"prepare": "husky"` script wires the hook back up on every `npm install`.
- `commitlint.config.js` (root) — `module.exports = { extends: ['@commitlint/config-conventional'] }`.
- `.husky/commit-msg` — runs `npx --no -- commitlint --edit "$1"` on every commit.

This is root-level Node tooling, unrelated to the Vite theme's own `package.json`/`node_modules` (`web/app/themes/custom/tailwind/`, managed via `make npm`/`make vite-*`) — a fresh clone needs one `npm install` at the repo root (not `make npm ARGS="install"`, which targets the theme) to activate the hook locally. Verify it's active: `git config core.hooksPath` should print `.husky/_`.

Not installed (would need a deliberate follow-up decision, not assumed): **Commitizen** (`git cz`, an interactive prompt for building a compliant message) and **semantic-release**/**standard-version** (auto-bump the version and generate a changelog from commit types since the last tag) — this repo doesn't cut tagged releases (it deploys straight to production on push to `main`, see `.claude/DEPLOY.md`), so there's nothing for either to key off yet.

## Applying this in a repo that doesn't already use it

Check `git log --oneline -10` before assuming this format is already the convention — if recent history is plain descriptive sentences (no `type:` prefix), introducing semantic types is a deliberate style change, not a restatement of the existing convention: mention that explicitly rather than silently switching styles mid-history. If the repo's existing commits are written in a language other than English, keep that language for `<subject>` and just add the (English) `<type>` prefix — the type keywords stay in English either way, since tooling that parses them expects the standard set.

## Writing one

1. Look at what's actually staged/changed (`git diff --staged`, `git status`) — the type must match the *effect* of the diff, not the intent behind the request. A refactor that happens to fix a latent bug is `fix`, not `refactor`.
2. Pick exactly one type, one concern. If a commit seems to need two types (e.g. a fix *and* a docs update), that's usually a sign it should be two commits — but don't split an already-requested single commit without checking with the user first.
3. Keep `<subject>` short (~50 chars) and in the present tense; put any further explanation in the commit body, not by cramming detail into the subject line.
4. Use the scope when it genuinely narrows things down (`fix(deploy): ...`, `feat(theme): ...`) — skip it rather than force a vague one on a change that touches several areas at once.
5. Still follow this repo's own commit rules (see root `CLAUDE.md` / the system's git instructions) for everything else — only create a commit when asked, never `--no-verify`, prefer new commits over amending, etc. This skill only changes the *subject line format*, not when or how a commit gets made.

## References

- https://www.conventionalcommits.org/
