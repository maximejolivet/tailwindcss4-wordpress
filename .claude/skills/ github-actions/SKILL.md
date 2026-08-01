---
name: github-actions
description: Design or review a GitHub Actions CI/CD pipeline that deploys a PHP CMS (WordPress/Bedrock, Drupal, or similar) to a web server, and general GitHub Actions authoring hygiene (action version pinning, secrets, injection safety, least-privilege permissions). Use when the user asks to create, fix, harden, or extend any workflow (`.github/workflows/*.yml`) — deploy-specific or generic CI. Grounded in the working pipeline already proven in this repo (`.github/workflows/deploy.yml`, `.claude/DEPLOY.md`).
---

Act as the devops engineer responsible for this pipeline: prefer the boring, already-proven pattern over a novel one, and call out every secret, every destructive flag (`--delete`, `--no-dev`), and every assumption about the target host before writing YAML.

## 0. General GitHub Actions hygiene (applies to every workflow, not just deploy)

These apply whether the workflow deploys the CMS or just runs CI — check them on every `.yml` file touched, adapted from [DaleStudy/skills](https://github.com/DaleStudy/skills/blob/main/skills/github-actions/SKILL.md) (MIT).

**Anti-patterns to catch:**

1. **Stale action versions.** `uses: actions/checkout@v4` when `v6` is current is the single most common mistake. Check the real latest tag before pinning, don't guess:
   ```bash
   gh release view --repo actions/checkout --json tagName --jq '.tagName'
   ```
   For security-sensitive workflows or lower-trust third-party actions, consider [pinning to a commit SHA](https://docs.github.com/en/actions/security-guides/security-hardening-for-github-actions#using-third-party-actions) instead of a tag.
2. **Hardcoded secrets.** Never write an API key, password, or token as a literal in the YAML — always `${{ secrets.NAME }}`, sourced from repo/org secrets. See [Using secrets](https://docs.github.com/en/actions/security-guides/using-secrets-in-github-actions).
3. **Injection via untrusted event data.** Interpolating `github.event.*` (issue titles, PR titles/bodies, comment text) directly into a `run:` shell command is a script-injection vector — an attacker controls that text. Pass it through `env:` instead and reference the environment variable, never the `${{ }}` expression, inside the shell script. See [Script injections](https://docs.github.com/en/actions/security-guides/security-hardening-for-github-actions#understanding-the-risk-of-script-injections).
4. **`pull_request_target` misuse.** Unlike `pull_request`, this event runs with the base repo's secrets available even for PRs from forks. Checking out the fork's HEAD (`ref: ${{ github.event.pull_request.head.sha }}`) inside that privileged context lets a forked PR's code run with access to your secrets — avoid unless the workflow deliberately needs privileged access to fork PRs, and never combine it with checking out untrusted fork code.
5. **Redundant setup for pre-installed tooling.** GitHub-hosted runners already ship Node.js/npm/npx, Python/pip, Ruby/gem, Go, Docker, git, `gh`, curl, wget, jq, yq — a `setup-node` step just to run `npx` wastes time and adds a network dependency for nothing. Tools that are *not* preinstalled and do need an explicit setup step: Bun, Deno, Rust, Zig, pnpm, Poetry, Ruff. Exact versions per runner image: [Ubuntu](https://github.com/actions/runner-images/blob/main/images/ubuntu/Ubuntu2404-Readme.md), [macOS](https://github.com/actions/runner-images/blob/main/images/macos/macos-15-Readme.md), [Windows](https://github.com/actions/runner-images/blob/main/images/windows/Windows2022-Readme.md).

**Least privilege:** declare `permissions:` at the job level (not workflow-wide) with the narrowest scope the job needs — e.g. `contents: read` for a build/checkout-only job. See [Modifying permissions for the GITHUB_TOKEN](https://docs.github.com/en/actions/security-guides/automatic-token-authentication#modifying-the-permissions-for-the-github_token).

**Common triggers:**
```yaml
on:
  push: { branches: [main] }
  pull_request: { branches: [main] }
  workflow_dispatch:        # manual run button
  schedule:
    - cron: "0 0 * * 1"     # every Monday 00:00 UTC
  release: { types: [published] }
  workflow_call:            # reusable from another workflow
```

**Common permissions:**
```yaml
permissions:
  contents: read        # CI (build/test), checkout
  contents: write        # commit/push from the workflow
  pull-requests: write   # PR-commenting bots
  issues: write          # issue-commenting bots
  packages: write        # publishing packages (pair with contents: write)
  id-token: write        # OIDC cloud auth (pair with contents: read)
```

**Actions this repo's own workflows are likely to reach for** (always version-check with `gh release view` first): `actions/checkout`, `actions/cache`, `actions/upload-artifact` / `actions/download-artifact`, `shivammathur/setup-php`, `actions/setup-node` — see `.github/workflows/deploy.yml` for how the PHP/Node/Composer/npm combination is actually wired in this repo.

## 1. Before writing a CMS deploy pipeline — ask what you don't know

A CMS deploy pipeline is only as safe as its assumptions about the target. Do not guess these; ask the user (or read `.claude/DEPLOY.md` / existing `.env.deploy*` if this repo already has one):

- **Target host type** — shared hosting (cPanel, IP-restricted SSH), a VPS with open SSH, or a platform (Pantheon/Platform.sh/WP Engine — those have their own CLI/pipeline, don't reinvent rsync for them).
- **CMS** — WordPress (Bedrock or classic), Drupal, or other — changes which CLI runs post-deploy (`wp`, `drush`) and which paths must never be touched (`uploads/`, `sites/default/files/`).
- **Where dependencies build** — on the runner (fresh checkout, safe to run `--no-dev`) or on the server (risk of clobbering a dev `vendor/` if a human ever runs the same command locally — see this repo's `.claude/DEPLOY.md` §3 for why that asymmetry exists here).
- **What must survive `--delete`** — uploads/media, cache directories, any server-generated file (`.htaccess` rewritten by the CMS, `web/app/uploads`, `sites/default/files`). These get `--exclude`, never left to chance.

## 2. Pipeline skeleton (adapt, don't reinvent)

Base every new workflow on this shape — it's the one already validated end-to-end in `.github/workflows/deploy.yml`:

1. `actions/checkout`
2. Set up the language runtime(s): `shivammathur/setup-php` (pin PHP version to match the host — check with the hosting panel, not composer.json's `>=`) and `actions/setup-node` if there's a frontend build.
3. `actions/cache` for the *download* cache (`~/.composer/cache`, npm cache via `setup-node`'s built-in `cache:` input) — key on the lockfile hash (`hashFiles('composer.lock')`), not on the whole repo. Cache the download cache, not `vendor/`/`node_modules/` themselves, when those get rebuilt fresh every run anyway.
4. `composer install --prefer-dist --no-dev --optimize-autoloader --no-interaction` (or on the server, per §1's decision — never both, pick one and document why in a comment like the existing workflow does).
5. Build frontend assets (`npm ci && npm run build`) if the theme/module ships one.
6. **If the host has IP-restricted SSH** (common on shared/cPanel hosting): fetch the runner's public IP (`curl -sS https://api.ipify.org` — don't reach for an unmaintained third-party action for something one `curl` does) and whitelist it via the host's API before connecting. If the API has a quota on whitelisted IPs (o2switch does), the workflow must also **remove old entries it added**, or every run silently eats into the quota until SSH starts failing with no useful error. See `.github/workflows/deploy.yml`'s "Whitelist IP on hosting" step for a concrete example.
7. Load the SSH key via `webfactory/ssh-agent` (or equivalent), never by hand-writing the key to a file with `printf`/`echo` — multi-line PEM content mangled that way has produced `error in libcrypto` on an otherwise valid key (documented incident, `.claude/DEPLOY.md` §5.1). `ssh-keyscan` the host into `known_hosts` before connecting.
8. `rsync -avz --delete` from the runner to the server, with explicit `--exclude` for: `.git`, `.env`/secrets files, `node_modules`, editor cruft (`.DS_Store`, `.idea`, `.vscode`), and every server-owned directory identified in §1 (uploads, cache, `sites/default/files`). `--delete` without those exclusions **will wipe live media** the first time paths diverge between the checkout and the server — this is the single most dangerous line in the whole pipeline, review it every time the file layout changes.
9. Post-deploy CMS step over SSH: WordPress → `wp cache flush`, `wp core update-db` if needed; Drupal → `drush deploy` or `drush updb && drush cim && drush cr`. Don't skip this — a code-only rsync without a DB/config sync leaves Drupal sites broken on any config or schema change.

## 3. Concurrency and triggers

```yaml
on:
  push:
    branches: [main]
  workflow_dispatch:

concurrency:
  group: deploy-${{ github.ref }}
```

No `cancel-in-progress: true` for deploys — a run already mid-`rsync` must finish, not get killed halfway and leave the server in a half-synced state. `workflow_dispatch` gives a manual re-run button without needing a new commit.

## 4. Secrets — what goes in GitHub, what never does

| Goes in `Settings > Secrets and variables > Actions` | Never appears in the workflow file or chat |
|---|---|
| SSH private key **dedicated to CI** (not a developer's personal key) | The key's passphrase-less private material — only the secret reference (`${{ secrets.X }}`) |
| Host, user, remote path, any panel password used only for whitelist APIs | Production DB credentials beyond what deploy strictly needs |

Generate a dedicated key with `ssh-keygen -t ed25519 -f deploy_key -N ""` — ed25519, not the legacy RSA/DSA a host's default `ssh-keygen` might suggest; recent OpenSSH disables DSA by default and will reject it with an opaque error. On cPanel-style hosts, remember that **importing** a public key and **authorizing** it are two separate steps — imported-but-unauthorized keys fail `rsync`/`ssh` with `Permission denied (publickey)` and no other clue.

If the user asks to add a secret, tell them to run `gh secret set NAME` themselves (or use the GitHub UI) — never paste secret values into the conversation, and never write them into a committed file.

## 5. Writing or reviewing the workflow

- Read the existing `.github/workflows/*.yml` and `.claude/DEPLOY.md` (or equivalent deploy docs) in the target repo first — a working pipeline's quirks (why composer runs where it runs, why an exclude list looks the way it does) are usually there for a reason from a real incident, not accidental.
- Comment the *why*, not the *what*, directly in the YAML — this repo's `deploy.yml` does this well (e.g. the comment explaining why IP whitelist cleanup exists, or why the SSH key loads via `ssh-agent` instead of a file write). A future reader debugging a 2am failed deploy needs the reasoning, not a restatement of the step name.
- Never add `--no-verify`, disable host key checking (`StrictHostKeyChecking no`), or skip the whitelist/auth steps to "make CI green" — these are exactly the safety checks worth keeping when a deploy pipeline touches production.
- `make deploy*` (or equivalent local deploy targets) touch the live site — per this repo's root `CLAUDE.md`, confirm with the user before running them, and the same caution applies to manually triggering a `Deploy` GitHub Actions run (`workflow_dispatch`) or merging a change that will auto-deploy on push to `main`.

## 6. After writing — checklist to hand back to the user

- [ ] All required secrets set (`gh secret list` to confirm names, never values)
- [ ] Deploy SSH key generated (ed25519), added to the server, and **authorized** (not just imported, on cPanel hosts)
- [ ] `--delete` exclude list reviewed against what the server owns (uploads/cache/files)
- [ ] First run triggered manually (`workflow_dispatch`) before relying on push-to-`main` auto-deploy
- [ ] Post-deploy CMS step verified (permalinks/cache flush for WordPress; `drush cr`/config import for Drupal)
- [ ] Site checked live over HTTPS after the first successful run
