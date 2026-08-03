# Git Workflow

## Commit message format

Conventional Commits, mandatory scope, emoji — enforced by a `commitlint`/Husky `commit-msg` hook. Full detail (types, emojis, breaking changes) in the `semantic-commit-messages` skill and root `README.md` § Convention de commit. Don't freelance a different format.

## Production deploy is one push away

Push to `main` triggers `.github/workflows/deploy.yml` automatically (build theme, rsync to o2switch, write production `.env`) — there is no separate staging step. The same is true of a manual `workflow_dispatch` trigger or adding/rotating a deployment GitHub secret. **Confirm with the user before any of these**, even mid-task — this is already stated in root `CLAUDE.md`, restated here so it surfaces in agent context too.

## Pull request workflow

1. Look at the *full* commit history for the branch, not just the latest commit: `git log` and `git diff [base-branch]...HEAD`.
2. Draft a PR summary that explains why, not just what.
3. Include a manual verification checklist (lint/phpstan/audit status, what was checked in the browser) — there's no test suite to point to instead.
4. Push with `-u` on a new branch.

## Feature workflow

1. **Plan first** for anything non-trivial — the **planner** agent restates requirements, breaks work into phases, and flags deploy/DB risks before any code is written.
2. **Implement**, keeping Pint/PHPStan clean as you go (see `coding-style.md`).
3. **Review** — the **code-reviewer** agent right after writing code; the **security-reviewer** agent specifically if the change touches forms, `$wpdb`, auth, or REST/AJAX.
4. **Verify manually** in the browser — no automated tests exist here.
5. **Commit** with a properly scoped, typed message.

## No test suite

This repo has no PHPCS/WPCS, PHPMD, or JS/CSS lint configured, and no test runner. Don't propose a TDD workflow or ask for coverage numbers — the quality gate is Pint + PHPStan + `composer audit` + manual verification.
