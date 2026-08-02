---
name: semantic-commit-messages
description: "Write git commit messages in the semantic/conventional format (`<type>(<scope>): <description>`) instead of a free-form sentence. Use whenever the user asks for a commit, or asks to write/review a commit message, and wants it typed (feat/fix/refactor/perf/style/test/docs/build/ops/chore) rather than descriptive prose."
---

Prefix every commit subject with a **type**, and optionally a **scope**, before the actual description — a small style change that makes `git log` scannable (what kind of change, at a glance) and lets tooling (changelogs, semantic-release) parse history mechanically instead of guessing from prose. This repo follows the [qoomon/git-conventional-commits](https://github.com/qoomon/git-conventional-commits) nomenclature, a stricter, opinionated superset of [conventionalcommits.org](https://www.conventionalcommits.org/) — not the loose "use whatever type feels right" version.

## Format

```
<type>(<optional scope>): <description>

<optional body>

<optional footer>
```

- `<scope>` — optional, the area of the codebase touched (`deploy`, `theme`, `docker`...). Never an issue identifier.
- `<description>` — mandatory, imperative present tense ("add", not "added"/"adds" — think "This commit will ..."), no capitalized first letter, no trailing period.
- `<body>` — optional, the motivation for the change contrasted with previous behavior. Same imperative present tense. Blank line separates it from the subject.
- `<footer>` — optional except when the commit is breaking (then mandatory) — issue references (`Closes #123`, `Fixes JIRA-456`) and `BREAKING CHANGE:` descriptions. Blank line separates it from the body.

```
feat: add hat wobble
^--^  ^--------------^
|     |
|     +-> Description: imperative, present tense, no cap, no period.
|
+-------> Type: see table below.
```

## Types

| Type | When to use it |
|---|---|
| `feat` | Adds, adjusts, or removes a feature to/of/from the API or UI |
| `fix` | Fixes an API or UI bug of a preceding `feat` |
| `refactor` | Rewrites or restructures code with **no** API/UI behavior change |
| `perf` | A `refactor` specialized for performance (still no behavior change) |
| `style` | Code style only (whitespace, formatting, missing semicolons) — no behavior change |
| `test` | Adds missing tests or fixes existing ones |
| `docs` | Documentation only |
| `build` | Build tooling, dependencies, project version |
| `ops` | Infra (IaC), deploy scripts, CI/CD pipelines, backups, monitoring, recovery — see `.github/workflows/`, `Makefile` deploy targets |
| `chore` | Everything else: initial commit, `.gitignore`, repo housekeeping |

`feat`/`fix` are the only two that describe a change to what the software *does* for its users — that split is what semantic-release-style tooling keys off for version bumps (see below). Everything else describes how it's built, tested, deployed, or documented.

## Breaking changes

- `!` right after the type/scope: `feat(api)!: remove status endpoint`
- Described in the footer when the description alone isn't enough:
  ```
  feat(api)!: remove status endpoint

  BREAKING CHANGE: the /status endpoint no longer exists, use /health instead.
  ```
- Single-line `BREAKING CHANGE:` → one space after the colon. Multi-line → two newlines after the colon.

Reach for `!`/`BREAKING CHANGE:` deliberately — it's the signal that bumps a **major** version, not something to add on every `fix`/`refactor`.

## Versioning (if this repo ever cuts tagged releases)

- Any commit with a **breaking change** → bump **major**.
- Else, any `feat` or `fix` (API/UI-relevant) → bump **minor**.
- Else → bump **patch**.

Not currently applicable here: this repo deploys straight to production on push to `main` (see `.claude/DEPLOY.md`), no tags cut — but the type list is chosen to stay compatible if that changes.

## Special commit formats

- **Initial commit**: `chore: init`
- **Merge commit**: leave git's default `Merge branch '<name>'` message as-is, don't rewrite it to a type-prefixed form.
- **Revert commit**: leave git's default `Revert "<reverted subject>"` message as-is, same reason.

## Tooling — enforced in this repo

`commitlint` + a Husky `commit-msg` hook are installed at the repo root and **reject a non-compliant commit message locally**, before it lands:

- `package.json` (root) — devDependencies `@commitlint/cli`, `@commitlint/config-conventional`, `husky`; `"prepare": "husky"` script wires the hook back up on every `npm install`.
- `commitlint.config.js` (root) — extends `@commitlint/config-conventional` but **overrides `type-enum`** to the qoomon list above (`feat fix refactor perf style test docs build ops chore`) — config-conventional's own defaults additionally allow `ci`/`revert`, which this repo doesn't use (`ops` covers CI/CD, revert commits use git's own format, see above).
- `.husky/commit-msg` — runs `npx --no -- commitlint --edit "$1"` on every commit.

This is root-level Node tooling, unrelated to the Vite theme's own `package.json`/`node_modules` (`web/app/themes/custom/tailwind/`, managed via `make npm`/`make vite-*`) — a fresh clone needs one `npm install` at the repo root (not `make npm ARGS="install"`, which targets the theme) to activate the hook locally. Verify it's active: `git config core.hooksPath` should print `.husky/_`.

A commit template (`.gitmessage`, opt in with `git config commit.template .gitmessage`) pre-fills `git commit` (without `-m`) with a change-type + verification checklist — see the "Convention de commit"/"Commit convention" section in the root `README.md`.

Not installed (would need a deliberate follow-up decision, not assumed): **Commitizen** (`git cz`, an interactive prompt for building a compliant message) and **semantic-release**/**standard-version** (auto-bump the version and generate a changelog from commit types since the last tag) — see the Versioning section above for why there's nothing to key off yet.

## Applying this in a repo that doesn't already use it

Check `git log --oneline -10` before assuming this format is already the convention — if recent history is plain descriptive sentences (no `type:` prefix), introducing semantic types is a deliberate style change, not a restatement of the existing convention: mention that explicitly rather than silently switching styles mid-history. If the repo's existing commits are written in a language other than English, keep that language for `<description>` and just add the (English) `<type>` prefix — the type keywords stay in English either way, since tooling that parses them expects the standard set.

## Writing one

1. Look at what's actually staged/changed (`git diff --staged`, `git status`) — the type must match the *effect* of the diff, not the intent behind the request. A refactor that happens to fix a latent bug is `fix`, not `refactor`.
2. Pick exactly one type, one concern. If a commit seems to need two types (e.g. a fix *and* a docs update), that's usually a sign it should be two commits — but don't split an already-requested single commit without checking with the user first.
3. Keep `<description>` short (~50 chars), imperative present tense, no cap, no trailing period; put any further explanation in the commit body.
4. Use the scope when it genuinely narrows things down (`fix(deploy): ...`, `feat(theme): ...`) — skip it rather than force a vague one on a change that touches several areas at once. Never an issue identifier.
5. Still follow this repo's own commit rules (see root `CLAUDE.md` / the system's git instructions) for everything else — only create a commit when asked, never `--no-verify`, prefer new commits over amending, etc. This skill only changes the *subject line format*, not when or how a commit gets made.

## Examples

```
feat: add email notifications on new direct messages
```
```
feat(shopping-cart): add the amazing button
```
```
fix(shopping-cart): prevent ordering an empty shopping cart
```
```
fix: add missing parameter to service call

The error occurred due to a stale cache read on the pricing service.
```
```
perf: decrease memory footprint for unique-visitor counting using HyperLogLog
```
```
refactor: implement fibonacci number calculation as recursion
```
```
style: remove empty line
```
```
build: update dependencies
```
```
ops(deploy): whitelist the runner IP before rsync
```
```
feat(api)!: remove ticket list endpoint

refers to JIRA-1337

BREAKING CHANGE: ticket endpoints no longer support listing all entities.
```

## References

- https://www.conventionalcommits.org/
- https://github.com/qoomon/git-conventional-commits
