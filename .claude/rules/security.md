# Security Guidelines

Project-specific complement to [`security-reviewer`](../agents/security-reviewer.md) — read that agent's file for the full checklist and detection commands.

## Mandatory checks before any commit

- [ ] No hardcoded secrets (DB credentials, salts, o2switch SSH key, GitHub Action secrets) — always via `.env` / Bedrock's `config/application.php`
- [ ] Every `$wpdb` query goes through `->prepare()` — no string-concatenated SQL
- [ ] Every dynamic PHP output is escaped (`esc_html()`, `esc_attr()`, `esc_url()`); no Twig `|raw` or `{% autoescape false %}` on user/DB-derived content
- [ ] Forms, admin-ajax handlers, and REST routes have a nonce check and a `current_user_can()` / `permission_callback` check
- [ ] `make audit` is clean (Composer's scan; `roave/security-advisories` already blocks installing a package version with a known CVE)
- [ ] No secret ever gets echoed into a CI log (`.github/workflows/deploy.yml`)

## Secret management

```php
// NEVER: hardcoded
$dbPassword = 'hunter2';

// ALWAYS: environment, via Bedrock's config/application.php (vlucas/phpdotenv)
$dbPassword = env('DB_PASSWORD');
```

## Security response protocol

If you find a real security issue:
1. Stop what you were doing.
2. Use the **security-reviewer** agent for a full pass, not just the one spot you noticed.
3. Fix CRITICAL issues before continuing other work.
4. If a real secret was exposed (committed, logged, or pasted somewhere), flag it to the user for rotation — don't rotate production secrets unilaterally.
5. Grep for the same pattern elsewhere in the repo; one instance of a mistake is often not the only one.
