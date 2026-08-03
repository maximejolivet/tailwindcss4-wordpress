# Agent Delegation

## Available agents

Located in `.claude/agents/`:

| Agent | Purpose | When to use |
|---|---|---|
| `planner` | Phased implementation plan, waits for confirmation | New features, new ACF layouts, anything touching several files |
| `architect` | Structural decisions | New ACF layout families, plugin-vs-theme-code calls, Vite/Tailwind build changes |
| `code-reviewer` | Quality/convention review | Right after writing or modifying code |
| `security-reviewer` | Vulnerability review | Before committing anything touching forms, `$wpdb`, auth, REST/AJAX, or the deploy workflow |
| `refactor-cleaner` | Dead code removal | Orphaned Twig components, unreferenced ACF layouts, unused dependencies |
| `doc-updater` | Doc sync | After adding/renaming `make` targets, Composer packages, or docs — remember the bilingual FR/EN mirror rule |

## When to reach for one without being asked

- Complex or multi-file feature request → **planner**
- Code was just written/modified → **code-reviewer**
- A structural choice needs justifying (new layout family, plugin vs. custom code) → **architect**
- About to commit something touching auth/forms/queries/REST → **security-reviewer**
- Asked to "clean up" or remove unused things → **refactor-cleaner**
- `make` targets, Composer packages, or conventions changed → **doc-updater**

## Parallel execution

Independent checks (e.g. a security pass on one file and a style review of another) can run as parallel Task calls. Don't parallelize when one agent's output should inform the next (e.g. planner before code-reviewer).

## Scale to the task

This is a single small WordPress site, not a multi-service product — most changes don't need a plan, an architecture decision, *and* three review passes. Reach for the agent that matches the actual risk/size of the change, not the whole roster every time.
